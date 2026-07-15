# Player Flow

## Scopo

Questo documento descrive il flusso funzionale vissuto dal giocatore durante una partita.

L'obiettivo è definire l'esperienza utente indipendentemente dai dettagli implementativi del sistema.

Le modalità con cui il flusso viene realizzato (WordPress, REST API, template, JavaScript o altre tecnologie) sono considerate dettagli implementativi e non fanno parte di questo documento.

---

## Inizio della partita

Per poter partecipare ad un Path il giocatore deve essere autenticato.

La Participation viene normalmente creata automaticamente durante la prima scansione del Checkpoint iniziale del Path, qualora non esista già una Participation attiva per quel giocatore.

Se una Participation valida è già presente, il sistema la riutilizza e il giocatore prosegue normalmente il percorso.

Versioni future del sistema potranno prevedere modalità alternative di creazione della Participation, senza modificare il flusso generale descritto in questo documento.

---

## Scansione di un QR Code

Ogni Checkpoint espone un QR Code univoco.

Il QR Code contiene un collegamento che consente al sistema di identificare il Checkpoint corrispondente.

Quando il giocatore inquadra il QR Code viene avviato automaticamente il flusso di scansione.

Il sistema utilizza il token contenuto nel QR Code per identificare il Checkpoint corrispondente.

Se il token non è valido o non corrisponde ad alcun Checkpoint il flusso viene interrotto e viene mostrato un messaggio appropriato al giocatore.

---

## Identificazione del giocatore

Prima di elaborare la scansione il sistema verifica se il giocatore è autenticato al sistema. Se non lo è propone la pagina di login.

Successivamente il sistema ricerca una Participation associata al giocatore.

Se la scansione riguarda il Checkpoint iniziale e non esiste ancora una Participation valida, questa viene creata automaticamente.

Negli altri casi il sistema riutilizza la Participation esistente.

Se non siamo al primo checkpoin e il giocatore non dispone di una Participation valida il flusso viene interrotto e viene mostrato un messaggio appropriato.

---

## Elaborazione della scansione

Una volta identificati il giocatore ed il Checkpoint, il sistema esegue il flusso di validazione.

Durante questa fase il sistema:

- verifica la validità della Participation;
- verifica lo stato del Path;
- verifica le Dependency del Checkpoint;

Se la validazione ha esito positivo il sistema:

- registra il Checkpoint come validato;
- registra l'Event corrispondente;
- aggiorna la progressione della Participation;
- aggiorna lo stato della Participation, se necessario.

Se la valiodazione ha esito negativo il sistema:

- registra l'Event corrispondente;


Il dettaglio della logica di validazione è descritto nel documento `VALIDATION.md`.

---

## Esito della scansione

Al termine della validazione il sistema determina uno specifico risultato.

Ogni risultato produce una risposta coerente per il giocatore. La risposta mostrata al giocatore è determinata esclusivamente dall'esito della scansione.

La presentazione grafica può variare nel tempo senza modificare il comportamento funzionale descritto in questo documento.

Il giocatore riceve sempre una risposta, indipendentemente dall'esito della validazione.

Il contenuto della risposta può comprendere:

- messaggi informativi;
- messaggi narrativi;
- premi;
- indicazioni sul proseguimento del percorso;
- eventuali motivazioni del blocco.

---

## Prosecuzione del percorso

Se la scansione è stata accettata il giocatore può proseguire normalmente il Path.

Il sistema può mostrare informazioni aggiuntive relative al Checkpoint appena raggiunto.

---

## Fine del percorso

Quando viene validato il Checkpoint finale il Path risulta terminato.

Se risultano validati tutti i Checkpoint previsti dal Path, la Participation assume anche lo stato di completata.

Terminato il percorso il sistema può mostrare un messaggio conclusivo ed eventuali ricompense previste dall'organizzatore.

---

## Errori

Qualora la scansione non possa essere elaborata il sistema mostra sempre una risposta comprensibile al giocatore.

L'errore tecnico non deve mai essere esposto direttamente all'utente finale.