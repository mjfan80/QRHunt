# Events Model

## Scopo

Questo documento descrive gli Event generati da QuestUno.

Gli Event rappresentano esclusivamente lo storico delle operazioni effettuate dal sistema.

Gli Event non rappresentano lo stato corrente della progressione del giocatore.

Lo stato corrente è invece rappresentato dalla tabella `wp_questuno_participation_checkpoints`.

---

## Principi

Ogni Event è immutabile.

Gli Event non vengono mai modificati.

Gli Event non vengono mai eliminati.

Gli Event costituiscono il log tecnico del sistema.

---

## Quando viene generato un Event

Un Event viene generato quando una scansione raggiunge la validazione applicativa con una Participation persistita a cui associarlo.

Non vengono invece registrati Event per richieste che non possono essere elaborate, ad esempio:

- token inesistente;
- utente non autenticato;
- Path non disponibile;
- Participation annullata, terminata o completata;
- validazione iniziale del Checkpoint iniziale fallita prima della creazione della Participation.

La prima scansione valida il Checkpoint iniziale prima di persistere la Participation. Se viene accettata, vengono registrati coerentemente la Participation, il Checkpoint validato e l'Event `accepted`.

---

## Event Type

### qr_scan

Rappresenta una richiesta di validazione di un QR Code.

Nella versione 1.0 è l'unico Event previsto.

Versioni future potranno introdurre ulteriori Event Type.

---

## Result

Il campo `result` descrive l'esito dell'operazione.

### accepted

La scansione è stata accettata.

Il Checkpoint è stato registrato nella Participation.

---

### duplicate

Il Checkpoint risulta già validato nella Participation.

Lo stato della Participation non viene modificato.

---

### before_failed

La scansione non rispetta almeno una dipendenza di tipo `BEFORE`.

La Participation non viene modificata.

---

### after_failed

La scansione non rispetta almeno una dipendenza di tipo `AFTER`.

La Participation non viene modificata.

---

### path_closed

Il valore rimane disponibile nel modello e nello schema per evoluzioni future, ma non viene prodotto dal Player Flow 1.0: un Path non disponibile interrompe il flusso prima della validazione.

---

### participation_cancelled

Il valore rimane disponibile nel modello e nello schema per evoluzioni future, ma non viene prodotto dal Player Flow 1.0: una Participation annullata interrompe il flusso prima della validazione.

---

## Evoluzione

L'elenco degli Event Type e dei Result potrà essere esteso nelle versioni future senza modificare il modello generale del sistema.
