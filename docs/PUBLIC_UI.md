# Public UI

## Scopo

Questo documento descrive il comportamento della parte pubblica di QRHunt.

L'obiettivo è definire in modo univoco il flusso di gioco e la visualizzazione dei checkpoint, indipendentemente dall'implementazione tecnica.

---

# Principi progettuali

## Il checkpoint è il contenuto

La narrazione del gioco è affidata esclusivamente ai contenuti dei checkpoint.

Ogni checkpoint è rappresentato da un elemento del Custom Post Type `qrhunt_checkpoint`, modificabile tramite il normale editor di WordPress.

Il plugin non genera pagine alternative contenenti la storia del gioco.

---

## Il plugin gestisce il gioco, WordPress gestisce i contenuti

QRHunt aggiunge esclusivamente gli elementi dinamici necessari al gioco, quali:

- stato della scansione;
- messaggi informativi;
- avanzamento del percorso;
- navigazione del giocatore.

Il contenuto del checkpoint rimane interamente gestito da WordPress.

---

## Mobile First

La Public UI è progettata principalmente per dispositivi mobili.

Ogni elemento dovrà essere facilmente utilizzabile da smartphone.

---

## Compatibilità con WordPress

La modalità predefinita utilizza il tema del sito.

Il plugin dovrà integrarsi con il template del tema senza sostituirlo.

Una futura versione dovrà prevedere una modalità "immersiva" con un layout dedicato.

---

# Flusso generale

Il flusso standard è il seguente:

1. Il giocatore scansiona un QR Code.
2. Il plugin risolve il token.
3. Viene verificata l'autenticazione dell'utente.
4. Vengono eseguite le validazioni del percorso.
5. Se la scansione è valida, viene registrato il checkpoint.
6. Viene visualizzato il contenuto del checkpoint.

---

# Stati della scansione

## Primo checkpoint

La scansione del primo checkpoint rappresenta anche l'inizio del percorso.

Non è prevista una pagina di benvenuto separata.

Viene direttamente visualizzato il contenuto del primo checkpoint.

---

## Checkpoint valido

Il checkpoint viene registrato.

Successivamente viene visualizzato il contenuto del checkpoint.

---

## Checkpoint già visitato

Viene visualizzato normalmente il contenuto del checkpoint.

Il plugin mostra un banner informativo che comunica che il checkpoint era già stato registrato.

Il contenuto del checkpoint rimane completamente accessibile.

---

## Ultimo checkpoint

L'ultimo checkpoint del percorso rappresenta anche la conclusione della narrazione.

Non è prevista una pagina finale separata.

Il plugin visualizza il contenuto dell'ultimo checkpoint.

In aggiunta mostra un banner che informa il giocatore del completamento del percorso, se lo ha anche completato e non solo conluso.

---

## Percorso già completato

Se un giocatore visita nuovamente l'ultimo checkpoint di un percorso già completato, viene visualizzato il contenuto dell'ultimo checkpoint.

Il banner informa che il percorso risulta già completato.

---

## Dipendenze non soddisfatte

Non viene visualizzato il checkpoint.

Il plugin mostra una pagina dedicata che informa il giocatore che il checkpoint non è ancora accessibile.

---

## Login richiesto

Se l'utente non è autenticato, il plugin mostra una pagina dedicata.

La pagina contiene un pulsante che reindirizza al login di WordPress.

Dopo l'autenticazione l'utente ritorna automaticamente alla scansione originaria.

---

## Token non valido

Il plugin mostra una pagina dedicata che informa che il QR Code non è valido.

---

# Visualizzazione del checkpoint

La pagina del checkpoint è composta da:

1. Banner dinamico del plugin.
2. Barra di avanzamento del percorso.
3. Contenuto del checkpoint.
4. Navigazione del giocatore.

Il contenuto del checkpoint viene sempre visualizzato utilizzando il normale template del tema WordPress.

---

# Banner dinamici

I banner vengono gestiti esclusivamente dal plugin.

Esempi:

- Checkpoint registrato.
- Checkpoint già visitato.
- Percorso completato.
- Percorso già completato.

Il contenuto editoriale del checkpoint non deve contenere tali messaggi.

---

# Barra di avanzamento

La barra di avanzamento viene calcolata dinamicamente.

Deve mostrare almeno:

- checkpoint visitati;
- checkpoint totali.

Esempio:

6 / 10 checkpoint

oppure

60%

La rappresentazione grafica verrà definita in fase di implementazione.

---

# Navigazione

Ogni checkpoint dovrà consentire al giocatore di accedere rapidamente ai propri percorsi.

La modalità di navigazione verrà definita durante l'implementazione.

---

# Pagina "I miei percorsi"

Il plugin dovrà prevedere una pagina pubblica che mostri i percorsi dell'utente autenticato.

Per ogni percorso saranno mostrati almeno:

- nome;
- stato;
- avanzamento;
- pulsante per riprendere il percorso.

La struttura dettagliata della pagina verrà definita durante l'implementazione.

---

# Funzionalità future

Non fanno parte della versione 1.0:

- modalità immersiva;
- classifiche;
- badge;
- punteggi;
- geolocalizzazione;
- mappe;
- notifiche push;
- temi grafici personalizzati.

---

# Decisioni architetturali

Per evitare duplicazioni e garantire la massima flessibilità editoriale:

- il plugin non duplica il contenuto dei checkpoint;
- il plugin non genera pagine narrative alternative;
- il contenuto della storia è sempre quello del CPT `qrhunt_checkpoint`;
- il plugin aggiunge esclusivamente elementi dinamici relativi allo stato del gioco.

Qualsiasi futura estensione della Public UI dovrà rispettare questi principi.