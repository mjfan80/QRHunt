# QRHunt – User Flows

**Versione:** 0.1 (Draft)

---

# 1. Introduzione

Questo documento descrive il comportamento del plugin nelle principali situazioni operative.

Ogni flusso rappresenta il comportamento atteso del sistema.

---

# 2. Utente non autenticato

## Precondizioni

- L'utente apre un QR Code.
- L'utente non è autenticato.

## Flusso

1. Il plugin verifica l'autenticazione.
2. L'utente viene reindirizzato alla pagina di login di WordPress.
3. Dopo il login l'utente viene riportato automaticamente al QR Code richiesto.
4. Il flusso riprende come se la richiesta fosse appena stata effettuata.

## Risultato

- Nessun Evento viene registrato prima dell'autenticazione.
- Nessuna Partecipazione viene creata prima dell'autenticazione.

---

# 3. Prima scansione del Percorso

## Precondizioni

- Utente autenticato.
- Nessuna Partecipazione esistente.
- QR Code valido.

## Flusso

1. Il plugin risolve il token.
2. Recupera il Checkpoint.
3. Recupera il Percorso.
4. Crea la Partecipazione.
5. Valida il Checkpoint.
6. Registra l'Evento.
7. Mostra il contenuto del Checkpoint.

## Risultato

- Partecipazione creata.
- Primo Checkpoint validato.
- Evento registrato.

---

# 4. Scansione di un Checkpoint valido

## Precondizioni

- Partecipazione esistente.
- Dipendenze soddisfatte.

## Flusso

1. Il plugin risolve il token.
2. Verifica le Dipendenze.
3. Valida il Checkpoint.
4. Registra l'Evento.
5. Aggiorna la Partecipazione.
6. Mostra il contenuto.

## Risultato

- Checkpoint validato.
- Evento registrato.

---

# 5. Scansione duplicata

## Precondizioni

- Il Checkpoint è già stato validato.

## Flusso

1. Il plugin riconosce il Checkpoint già validato.
2. Registra comunque un Evento.
3. Mostra il contenuto del Checkpoint con un messaggio che avvisa della scansione duplicata .

## Risultato

- Nessuna modifica della progressione.
- Evento registrato.

---

# 6. Prerequisito non soddisfatto

## Precondizioni

- Esiste almeno una Dipendenza di tipo "after" non soddisfatta.

## Flusso

1. Il plugin verifica le Dipendenze.
2. Interrompe la validazione.
3. Registra l'Evento.
4. Mostra il messaggio previsto.

## Risultato

- Checkpoint non validato.
- Evento registrato.

---

# 7. Checkpoint non più valido

## Precondizioni

- Esiste almeno una Dipendenza di tipo "before" non soddisfatta.

## Flusso

1. Il plugin verifica le Dipendenze.
2. Interrompe la validazione.
3. Registra l'Evento.
4. Mostra il messaggio previsto.

## Risultato

- Checkpoint non validato.
- Evento registrato.

---

# 8. Percorso terminato

## Precondizioni

- L'utente valida il Checkpoint finale.
- Tutte le condizioni richieste per la terminazione sono soddisfatte.

## Flusso

1. Il plugin valida il Checkpoint.
2. Aggiorna lo stato della Partecipazione.
3. Registra l'Evento.
4. Mostra il messaggio di conclusione.

## Risultato

- Partecipazione nello stato "Terminata".

---

# 9. Percorso completato

## Precondizioni

- La Partecipazione è Terminata.
- Tutti i Checkpoint obbligatori risultano validati.

## Flusso

1. Il plugin verifica il completamento.
2. Aggiorna lo stato della Partecipazione.
3. Registra l'Evento.
4. Mostra il messaggio di completamento.

## Risultato

- Partecipazione nello stato "Completata".

---

# 10. Token inesistente

## Precondizioni

- Il token richiesto non esiste.

## Flusso

1. Il plugin tenta la risoluzione del token.
2. Nessun Checkpoint viene trovato.

## Risultato

- Viene mostrato un messaggio che informa che il QR Code non è valido.
- Nessun Evento viene registrato.
- Nessuna Partecipazione viene creata.
---

# 11. Percorso non ancora aperto

## Precondizioni

- La data di apertura non è ancora stata raggiunta.

## Flusso

1. Il plugin verifica lo stato del Percorso.
2. Interrompe il flusso.
3. Mostra il messaggio previsto.

## Risultato

- Nessun Evento registrato.
- Nessuna validazione.

---

# 12. Percorso chiuso

## Precondizioni

- Il Percorso non è più disponibile.

## Flusso

1. Il plugin verifica lo stato del Percorso.
2. Interrompe il flusso.
3. Mostra il messaggio previsto.

## Risultato

- Nessun Evento registrato.
- Nessuna validazione.

---

# 13. Partecipazione annullata

## Precondizioni

- La Partecipazione è stata annullata da un amministratore.

## Flusso

1. Il plugin rileva lo stato della Partecipazione.
2. Interrompe il flusso.
3. Mostra il messaggio previsto.

## Risultato

- Nessuna validazione.
- Nessun Evento registrato.

---

# 14. Errore interno

## Precondizioni

- Si verifica un errore non previsto durante l'elaborazione.

## Flusso

1. Il plugin interrompe l'elaborazione.
2. Registra l'errore nel log, se configurato.
3. Mostra un messaggio generico all'utente.

## Risultato

- Nessuna inconsistenza nei dati.
- Nessun dettaglio tecnico mostrato all'utente.