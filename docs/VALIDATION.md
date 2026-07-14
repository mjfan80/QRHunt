# Validation Engine

## Scopo

Il Validation Engine è il componente responsabile della verifica della validità di una scansione.

Il suo compito è esclusivamente valutare le regole definite per il Path e stabilire se un determinato Checkpoint possa essere validato.

Il Validation Engine **non modifica lo stato dell'applicazione**, **non registra dati nel database** e **non aggiorna alcuna Participation**.

Riceve in ingresso una Participation e il Checkpoint da validare e restituisce un oggetto `ValidationResult`.


## ValidationResult

Il Validation Engine restituisce sempre un oggetto `ValidationResult`.

Il risultato contiene almeno le seguenti informazioni:

- `valid`
  - indica se la validazione è andata a buon fine.

- `failed_dependencies`
  - elenco delle dipendenze che non risultano soddisfatte.

- `blocked_message`
  - eventuale messaggio definito dall'organizzatore del Path e associato al Checkpoint.
  Questo messaggio è facoltativo e viene restituito solo se presente.
  Il Validation Engine restituisce il blocked_message senza interpretarlo.


  ## Messaggi

Il Validation Engine non genera direttamente messaggi destinati all'utente.

Le informazioni restituite in `failed_dependencies` dovranno essere trasformate dal livello di presentazione (Web App, App mobile, API, ecc.) in messaggi comprensibili.

Ad esempio:

- "Per poter validare questo checkpoint devi prima completare il checkpoint «Ingresso Castello»."

oppure

- "Per poter validare questo checkpoint devi prima completare il gruppo «Bosco»."

Qualora il Checkpoint definisca anche un `blocked_message`, tale messaggio potrà essere mostrato insieme al messaggio tecnico generato dal sistema.


## Tipi di dipendenza

Ogni Checkpoint può definire una o più dipendenze.

Una dipendenza è composta da:

- tipo (`AFTER` oppure `BEFORE`);
- tipo di destinazione (`Checkpoint` oppure `Group`);
- elemento di destinazione.

### AFTER

Una dipendenza di tipo `AFTER` indica che il Checkpoint corrente può essere validato solo dopo che l'elemento di destinazione è stato soddisfatto.

Esempi:

- AFTER → Checkpoint A
- AFTER → Group "Bosco"

### BEFORE

Una dipendenza di tipo `BEFORE` indica che il Checkpoint corrente deve essere validato prima dell'elemento di destinazione.

Questo tipo di dipendenza è previsto principalmente per gestire vincoli di ordinamento tra Checkpoint opzionali.

Ad esempio:

```
3 → 4? → 5? → 6? → 7?
```

dove i Checkpoint 4, 5, 6 e 7 sono tutti opzionali ma si desidera imporre che il Checkpoint 4, qualora venga eseguito, debba sempre precedere il Checkpoint 7.

In questo caso sarà sufficiente definire:

- Checkpoint 4 → BEFORE → Checkpoint 7

Il motore impedirà la validazione del Checkpoint 4 qualora il Checkpoint 7 sia già stato completato.

Nella maggior parte dei casi sarà sufficiente utilizzare esclusivamente dipendenze di tipo `AFTER`.

Le dipendenze `BEFORE` sono destinate principalmente alla gestione di casi particolari.

### Raccomandazione

Utilizzare `AFTER` per tutte le normali regole di progressione del percorso.

Utilizzare `BEFORE` esclusivamente per esprimere vincoli di ordinamento condizionali che non possono essere rappresentati con `AFTER` senza modificare il comportamento del percorso.

## Group

Un Group rappresenta un insieme logico di Checkpoint.

Lo scopo principale dei Group è consentire la definizione di dipendenze riferite ad un insieme di Checkpoint anziché ad un singolo elemento.

Ogni Group definisce una modalità di completamento.

### ALL

Il Group è considerato completato solo quando tutti i Checkpoint appartenenti al Group sono stati validati.

### ANY

Il Group è considerato completato quando almeno uno dei Checkpoint appartenenti al Group è stato validato.

## Ordine di validazione

Il Validation Engine esegue sempre le verifiche nel seguente ordine:

1. verifica che il Checkpoint appartenga al Path della Participation;
2. verifica che il Checkpoint non sia già stato validato;
3. verifica tutte le dipendenze di tipo `AFTER`;
4. verifica tutte le dipendenze di tipo `BEFORE`;
5. se tutte le verifiche sono soddisfatte, la validazione ha esito positivo.

## Principi di progettazione

Il Validation Engine segue i seguenti principi:

- Il Validation Engine determina se una validazione sia consentita consultando lo stato corrente della Participation, rappresentato dalla tabella `wp_qrhunt_participation_checkpoints`.
- è completamente stateless;
- non salva dati nel database;
- non modifica lo stato della Participation;
- non registra visite;
- valuta esclusivamente le regole del dominio;
- restituisce sempre un `ValidationResult`.

La registrazione della visita, l'aggiornamento della Participation e ogni modifica dello stato dell'applicazione sono responsabilità del `VisitService`.

## DependencyViolation

Ogni elemento presente nella collezione `failed_dependencies` è rappresentato da un oggetto `DependencyViolation`.

Una `DependencyViolation` contiene almeno:

- `type` (`AFTER` oppure `BEFORE`);
- `target_type` (`Checkpoint` oppure `Group`);
- `target_id`;
- `display_name`.

Il Validation Engine restituisce esclusivamente informazioni strutturate e non produce direttamente messaggi destinati all'utente.

Sarà responsabilità del livello di presentazione trasformare tali informazioni in messaggi comprensibili nella lingua desiderata.

Ad esempio:

- "Per poter validare questo checkpoint devi prima completare il checkpoint «Ingresso Castello»."

oppure

- "Per poter validare questo checkpoint devi prima completare il gruppo «Bosco»."

Qualora il Checkpoint definisca anche un `blocked_message`, tale messaggio potrà essere mostrato insieme al messaggio tecnico generato automaticamente dal sistema.