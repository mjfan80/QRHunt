# AI Handover

**Ultimo aggiornamento:** 2026-08-12 — Release 1.0.0 verificata

---

# Stato del progetto

QuestUno è un plugin WordPress per la gestione di percorsi basati su QR Code.

Il progetto segue un'architettura Repository / Service.

La documentazione presente nella cartella `docs/` costituisce la specifica autorevole del progetto.

Prima di qualsiasi modifica va sempre verificata la coerenza tra documentazione e codice.

---

# Milestone completate

## Core Engine

- Bootstrap
- Activation
- Database
- Domain Model
- Validation Engine
- Event Model
- Participation Progress
- Scan Orchestration

## Administration

- CPT Checkpoint
- CRUD Percorsi
- CRUD Gruppi
- Gestione Dipendenze
- Gestione QR Code
- Dashboard
- Pagina Eventi e consultazione globale Event
- Statistiche aggregate per Path
- Archiviazione Path
- Verifica configurazione Path
- Colonna Configurazione nella lista Percorsi

## Public Flow

- Routing pubblico
- Token Resolution
- Player Flow

---

# Architettura

Pattern utilizzati:

- Repository
- Service
- Controller
- Model

Il dominio non deve contenere logica WordPress.

I Controller devono limitarsi a:

- ricevere la richiesta;
- delegare ai Service;
- renderizzare la risposta.

I Service contengono la logica applicativa.

I Repository accedono esclusivamente al database.

---

# Decisioni architetturali

## Routing pubblico

Ogni QR contiene esclusivamente un URL pubblico.

Formato:

```
https://<dominio>/questuno/checkpoint/<token>
```

Il dominio viene costruito tramite `home_url()`.

---

## ScanService

È l'unico orchestratore della scansione.

Gestisce:

- validazione;
- registrazione Event;
- aggiornamento Participation;
- aggiornamento progressione.

I controller non devono chiamare direttamente ValidationService.

---

## Participation

La Participation viene creata automaticamente **solo** quando:

- il checkpoint è quello iniziale del Path;
- non esiste già una Participation valida.

Negli altri casi il flusso viene interrotto.

---

## Event

Non vengono registrati Event quando:

- il token non esiste;
- il giocatore non è autenticato;
- non esiste una Participation e il checkpoint non è quello iniziale.

Una prima scansione del Checkpoint iniziale viene validata prima di creare la Participation. Se fallisce non genera né Participation né Event; se riesce, persiste coerentemente Participation, progressione ed Event.

---

## Group

Un Group senza Checkpoint non è mai considerato completato.

---

## QR Code

Libreria utilizzata:

- endroid/qr-code

Funzionalità:

- PNG
- SVG
- stampa A4

I QR vengono generati al download.

---

# Stato corrente

Funziona:

- Routing pubblico
- Player Flow
- Login
- Creazione Participation
- Validazione
- Event
- Download QR
- Stampa QR
- Plugin Check pulito
- Dashboard amministrativa
- Riepilogo percorsi/checkpoint/gruppi/partecipazioni
- Ultime scansioni (da testare in realtà)
- Quick actions
- Pagina Eventi con filtri
- Statistiche aggregate raggiungibili dal singolo Path
- Verifica configurazione Path con errori bloccanti e warning
- Colonna Configurazione per i Path pubblicati
- Archiviazione e ripristino Path (ripristino in bozza)

---

# TODO

## Post-1.0

- Duplicazione Path
- Duplicazione Checkpoint
- Statistiche specifiche per Checkpoint

## Verifiche finali

- Verifica manuale delle ultime scansioni Dashboard completata.
- Release 1.0.0: verifiche finali superate.

---

# Convenzioni

- piccoli commit;
- nessun refactoring non richiesto;
- Plugin Check sempre pulito;
- WordPress Coding Standards;
- usare esclusivamente API ufficiali WordPress;
- evitare duplicazioni;
- verificare sempre la documentazione prima del codice.

---

# Note

Quando esistono ambiguità tra documentazione e codice:

1. fermarsi;
2. spiegare l'incoerenza;
3. attendere una decisione;
4. solo dopo implementare.

Non introdurre mai nuove regole di dominio autonomamente.
