# Scan Flow

## Scopo

Questo documento descrive il flusso completo seguito da QRHunt quando un utente effettua la scansione di un QR Code.

L'obiettivo è definire il comportamento del sistema indipendentemente dalla tecnologia utilizzata (REST API, Web App, App mobile, ecc.).

Il documento descrive esclusivamente il flusso logico del dominio e non i dettagli implementativi.

---

## Flusso generale

Ogni scansione segue sempre il seguente flusso.

```
Utente

↓

Scansiona un QR Code

↓

Il sistema riceve il token

↓

Ricerca del Checkpoint associato

↓

Recupero del Path

↓

Recupero o creazione della Participation

↓

Validation Engine

↓

Validazione fallita?
        │
   ┌────┴────┐
   │         │
 SI         NO
   │         │
   ▼         ▼

Registrazione   Registrazione
Event           Checkpoint
                nella Participation

                ↓

                Aggiornamento
                Participation

                ↓

                Registrazione
                Event

                ↓

Restituzione del ValidationResult
```

---

# 1. Risoluzione del token

Il sistema riceve il token contenuto nel QR Code.

Il token viene utilizzato per individuare univocamente il relativo Checkpoint.

Se il token non esiste la richiesta viene rifiutata.

Le richieste con token inesistenti non generano alcun Event.

---

# 2. Recupero del Path

Una volta individuato il Checkpoint il sistema determina il Path di appartenenza.

Tutte le operazioni successive vengono effettuate esclusivamente all'interno dello stesso Path.

---

# 3. Recupero della Participation

Il sistema ricerca una Participation relativa all'utente autenticato e al Path individuato.

Se non esiste viene creata automaticamente.

Ogni utente può possedere una sola Participation per ciascun Path.

---

# 4. Validazione

Il sistema invoca il Validation Engine passando:

- la Participation;
- il Checkpoint da validare.

Il Validation Engine restituisce sempre un `ValidationResult`.

Il Validation Engine non modifica mai lo stato dell'applicazione.

---

# 5. Validazione fallita

Se il Validation Engine restituisce un risultato negativo:

- non viene registrato il Checkpoint nella tabella `wp_qrhunt_participation_checkpoints`;
- la Participation non viene modificata;
- viene registrato un Event;
- viene restituito il `ValidationResult`.

Il livello di presentazione costruisce il messaggio da mostrare all'utente utilizzando il `ValidationResult`.

Qualora il Checkpoint definisca anche un `blocked_message`, esso potrà essere mostrato insieme al messaggio generato automaticamente.

---

# 6. Validazione riuscita

Se il Validation Engine restituisce un risultato positivo:

- viene registrato il Checkpoint nella tabella `wp_qrhunt_participation_checkpoints`;
- viene aggiornato lo stato della Participation;
- viene registrato un Event;
- viene restituito il `ValidationResult`.

---

# 7. Aggiornamento della Participation

Dopo una validazione riuscita il sistema aggiorna automaticamente lo stato della Participation.

In particolare determina:

- percorso ancora in corso (`in_progress`);
- percorso terminato (`finished`);
- percorso completato (`completed`).

Le regole che determinano tali stati sono definite dal dominio del plugin.

---

# 8. Registrazione degli Event

Ogni scansione elaborata genera un Event.

Gli Event rappresentano esclusivamente lo storico delle operazioni effettuate dal sistema.

Gli Event sono immutabili.

Lo stato corrente della progressione del giocatore è invece rappresentato dalla tabella `wp_qrhunt_participation_checkpoints`.

---

# Principi di progettazione

Il flusso di scansione segue i seguenti principi.

- ogni componente possiede una singola responsabilità;
- il Validation Engine è completamente stateless;
- il Validation Engine non salva dati;
- il Validation Engine non modifica la Participation;
- il Validation Engine restituisce esclusivamente un `ValidationResult`;
- il servizio di scansione è responsabile dell'orchestrazione dell'intero flusso;
- il servizio di scansione aggiorna la Participation;
- il servizio di scansione registra il Checkpoint validato;
- il servizio di scansione registra gli Event;
- il livello di presentazione costruisce i messaggi mostrati all'utente.

Questa separazione garantisce che la logica di dominio rimanga indipendente dall'interfaccia utente e dal protocollo di comunicazione utilizzato.