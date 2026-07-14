# Events Model

## Scopo

Questo documento descrive gli Event generati da QRHunt.

Gli Event rappresentano esclusivamente lo storico delle operazioni effettuate dal sistema.

Gli Event non rappresentano lo stato corrente della progressione del giocatore.

Lo stato corrente è invece rappresentato dalla tabella `wp_qrhunt_participation_checkpoints`.

---

## Principi

Ogni Event è immutabile.

Gli Event non vengono mai modificati.

Gli Event non vengono mai eliminati.

Gli Event costituiscono il log tecnico del sistema.

---

## Quando viene generato un Event

Un Event viene generato ogni volta che una scansione raggiunge il dominio applicativo.

Non vengono invece registrati Event per richieste che non possono essere elaborate, ad esempio:

- token inesistente;
- utente non autenticato;
- richiesta non valida.

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

Il Path non è disponibile.

La Participation non viene modificata.

---

### participation_cancelled

La Participation risulta annullata.

La scansione viene rifiutata.

---

## Evoluzione

L'elenco degli Event Type e dei Result potrà essere esteso nelle versioni future senza modificare il modello generale del sistema.