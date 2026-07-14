# QRHunt – Database Schema

**Versione:** 0.1 (Draft)

---

# 1. Tabella `wp_qrhunt_paths`

## Scopo

Contiene i Percorsi di gioco.

Non contiene informazioni sui Checkpoint, sulle Partecipazioni o sugli Eventi.

---

## Struttura

| Campo | Tipo | NULL | Default | Note |
|-------|------|------|---------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| name | VARCHAR(255) | NO | | Nome del Percorso |
| description | TEXT | YES | NULL | Descrizione breve |
| status | VARCHAR(20) | NO | draft | Stato del Percorso |
| start_checkpoint_id | BIGINT UNSIGNED | YES | NULL | Checkpoint iniziale |
| finish_checkpoint_id | BIGINT UNSIGNED | YES | NULL | Checkpoint finale |
| opening_date | DATETIME | YES | NULL | Apertura del Percorso |
| closing_date | DATETIME | YES | NULL | Chiusura del Percorso |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | Data creazione |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Ultima modifica |

---

## Indici

Primary Key

```
PRIMARY KEY (id)
```

Indici

```
INDEX (status)

INDEX (opening_date)

INDEX (closing_date)
```

---

## Vincoli logici

- Il nome del Percorso non deve essere vuoto.
- Il Checkpoint iniziale e finale devono appartenere allo stesso Percorso.
- Un Percorso può essere pubblicato solo se possiede un Checkpoint iniziale e uno finale.
- Il Checkpoint iniziale e quello finale devono essere differenti.

---

## Note

I riferimenti ai Checkpoint sono logici.

Le Foreign Key non vengono create fisicamente, in accordo con le linee guida di WordPress.

L'integrità referenziale è garantita dal plugin.

post_id: riferimento al post del Custom Post Type qrhunt_path. Realizza una relazione 1:1 tra il contenuto WordPress e i dati di dominio memorizzati nella tabella del plugin.

Il Checkpoint iniziale rappresenta il punto di ingresso del Percorso.

La prima scansione valida del Checkpoint iniziale crea automaticamente la Participation qualora non esista già.

---

# 2. Tabella `wp_qrhunt_checkpoints`

## Scopo

Contiene le informazioni tecniche dei Checkpoint.

Il contenuto editoriale del Checkpoint rimane nel relativo Custom Post Type di WordPress.

---

## Struttura

| Campo | Tipo | NULL | Default | Note |
|-------|------|------|---------|------|
| post_id | BIGINT UNSIGNED | NO | | Primary Key. Identificativo del Custom Post Type WordPress |
| path_id | BIGINT UNSIGNED | NO | | Percorso di appartenenza |
| group_id | BIGINT UNSIGNED | YES | NULL | Gruppo di appartenenza, facoltativo |
| token | CHAR(16) | NO | | Token pubblico univoco |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | Data creazione |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Ultima modifica |

---

## Indici

Primary Key

```
PRIMARY KEY (post_id)
```

Indici

```
INDEX (path_id)

INDEX (group_id)

UNIQUE (token)
```

---

## Vincoli logici

- Ogni Checkpoint appartiene ad un solo Percorso.
- Un Checkpoint può appartenere ad un solo Gruppo oppure a nessun Gruppo.
- Il token deve essere univoco.
- Il token viene generato automaticamente.
- Il token non può essere modificato manualmente.
- Il token viene rigenerato durante la duplicazione di un Percorso.

---

## Note

Il contenuto del Checkpoint non è memorizzato in questa tabella.

Il titolo, il contenuto, le immagini, i video e tutte le informazioni editoriali sono gestiti dal relativo Custom Post Type WordPress.

La tabella contiene esclusivamente la logica tecnica necessaria al funzionamento del plugin.

Le Foreign Key non vengono create fisicamente.

L'integrità referenziale è garantita dal plugin.

---

# 3. Tabella `wp_qrhunt_checkpoint_groups`

## Scopo

Contiene i Gruppi di Checkpoint.

Un Gruppo rappresenta un insieme logico di Checkpoint appartenenti allo stesso Percorso.

I Group consentono di definire dipendenze riferite ad un insieme di Checkpoint anziché ad un singolo elemento.

La modalità con cui un Group viene considerato completato è definita dal Validation Engine.
---

## Struttura

| Campo | Tipo | NULL | Default | Note |
|-------|------|------|---------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| path_id | BIGINT UNSIGNED | NO | | Percorso di appartenenza |
| name | VARCHAR(255) | NO | | Nome del Gruppo |
| description | TEXT | YES | NULL | Descrizione opzionale |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | Data creazione |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Ultima modifica |

---

## Indici

Primary Key

```
PRIMARY KEY (id)
```

Indici

```
INDEX (path_id)
```

---

## Vincoli logici

- Ogni Gruppo appartiene ad un solo Percorso.
- Un Gruppo può contenere zero o più Checkpoint.
- Tutti i Checkpoint appartenenti ad un Gruppo devono appartenere allo stesso Percorso del Gruppo.

---

## Note

L'appartenenza di un Checkpoint ad un Gruppo è memorizzata nella tabella `wp_qrhunt_checkpoints`.

Il Gruppo rappresenta esclusivamente un contenitore logico.

Le Foreign Key non vengono create fisicamente.

L'integrità referenziale è garantita dal plugin.

---

# 4. Tabella `wp_qrhunt_dependencies`

## Scopo

Contiene tutte le regole di progressione dei Checkpoint.

Le Dipendenze sono completamente indipendenti dai Checkpoint e rappresentano il motore logico del plugin.

---

## Struttura

| Campo | Tipo | NULL | Default | Note |
|-------|------|------|---------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| checkpoint_id | BIGINT UNSIGNED | NO | | Checkpoint a cui appartiene la regola |
| type | VARCHAR(20) | NO | | Tipo di dipendenza |
| target_type | VARCHAR(20) | NO | | Tipo della destinazione |
| target_id | BIGINT UNSIGNED | NO | | Identificativo della destinazione |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | Data creazione |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Ultima modifica |

---

## Indici

Primary Key

```
PRIMARY KEY (id)
```

Indici

```
INDEX (checkpoint_id)

INDEX (target_type, target_id)

INDEX (type)
```

---

## Vincoli logici

- Ogni Dipendenza appartiene ad un solo Checkpoint.
- Uno stesso Checkpoint può possedere più Dipendenze.
- Tutte le Dipendenze devono riferirsi ad entità appartenenti allo stesso Percorso.
- Una Dipendenza può riferirsi ad un Checkpoint oppure ad un Gruppo.

---


## Valori previsti

### type

- AFTER
- BEFORE

### target_type

- checkpoint
- group

---

## Regole di valutazione

Vedi `validation.md`.

---

## Note

La tabella contiene esclusivamente la definizione delle regole.

L'interpretazione delle Dipendenze è demandata al motore di validazione.

Le Foreign Key non vengono create fisicamente.

L'integrità referenziale è garantita dal plugin.

L'interpretazione delle dipendenze è demandata al Validation Engine.

Per i dettagli consultare `validation.md`.

---

# 5. Tabella `wp_qrhunt_participations`

## Scopo

Contiene le Partecipazioni degli utenti ai Percorsi.

Ogni record rappresenta una singola partecipazione di un utente ad un Percorso.

---

## Struttura

| Campo | Tipo | NULL | Default | Note |
|-------|------|------|---------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| user_id | BIGINT UNSIGNED | NO | | Utente WordPress |
| path_id | BIGINT UNSIGNED | NO | | Percorso |
| status | VARCHAR(20) | NO | in_progress | Stato della partecipazione |
| started_at | DATETIME | YES | NULL | Data di inizio |
| finished_at | DATETIME | YES | NULL | Data di termine |
| cancelled_at | DATETIME | YES | NULL | Data di annullamento |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | Data creazione |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Ultima modifica |

---

## Indici

Primary Key

```
PRIMARY KEY (id)
```

Indici

```
UNIQUE (user_id, path_id)

INDEX (path_id)

INDEX (status)
```

---

## Valori previsti

### status

- in_progress
- completed
- finished
- cancelled

---

## Vincoli logici

- Un utente può avere una sola Partecipazione per ciascun Percorso.
- Una Partecipazione appartiene ad un solo Utente.
- Una Partecipazione appartiene ad un solo Percorso.
- La durata del Percorso non viene memorizzata.
- Lo stato corrente della progressione viene determinato dalla tabella `wp_qrhunt_participation_checkpoints`.
- Le statistiche storiche vengono calcolate a partire dagli Eventi.
---

## Note

La Partecipazione viene creata automaticamente alla prima scansione valida di un Checkpoint del Percorso.

Le Foreign Key non vengono create fisicamente.

L'integrità referenziale è garantita dal plugin.

---

# 6. Tabella `wp_qrhunt_participation_checkpoints`

## Scopo

Contiene lo stato corrente dei Checkpoint validati da ciascuna Partecipazione.

Ogni record rappresenta un Checkpoint validato con successo nell'ambito di una specifica Partecipazione.

Questa tabella rappresenta esclusivamente lo stato corrente della progressione.

Lo storico completo delle scansioni è invece conservato nella tabella `wp_qrhunt_events`.

---

## Struttura

| Campo | Tipo | NULL | Default | Note |
|-------|------|------|---------|------|
| participation_id | BIGINT UNSIGNED | NO | | Partecipazione |
| checkpoint_id | BIGINT UNSIGNED | NO | | Checkpoint validato |
| validated_at | DATETIME | NO | CURRENT_TIMESTAMP | Data della prima validazione |

---

## Indici

Primary Key

```
PRIMARY KEY (participation_id, checkpoint_id)
```

Indici

```
INDEX (checkpoint_id)

INDEX (validated_at)
```

---

## Vincoli logici

- Ogni Partecipazione può validare un determinato Checkpoint una sola volta.
- Ogni record rappresenta esclusivamente una validazione riuscita.
- I tentativi falliti non vengono registrati in questa tabella.
- Tutti i Checkpoint devono appartenere al medesimo Path della Partecipazione.

---

## Note

Questa tabella rappresenta esclusivamente lo stato corrente della progressione del giocatore.

Lo storico completo delle scansioni, comprese quelle duplicate o non valide, è conservato nella tabella `wp_qrhunt_events`.

Le Foreign Key non vengono create fisicamente.

L'integrità referenziale è garantita dal plugin.



---

# 7. Tabella `wp_qrhunt_events`

## Scopo

Contiene lo storico completo degli Eventi generati dal plugin.

Ogni richiesta valida elaborata dal plugin genera un Evento.

Le richieste prive di autenticazione oppure riferite a token inesistenti non vengono registrate.
---

## Struttura

| Campo | Tipo | NULL | Default | Note |
|-------|------|------|---------|------|
| id | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Primary Key |
| participation_id | BIGINT UNSIGNED | NO | | Partecipazione |
| checkpoint_id | BIGINT UNSIGNED | NO | | Checkpoint interessato |
| event_type | VARCHAR(30) | NO | | Tipo di Evento |
| result | VARCHAR(30) | NO | | Esito |
| ip_address | VARCHAR(45) | YES | NULL | IPv4 o IPv6 |
| user_agent | TEXT | YES | NULL | User Agent del client |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | Data e ora dell'Evento |

---

## Indici

Primary Key

```
PRIMARY KEY (id)
```

Indici

```
INDEX (participation_id)

INDEX (checkpoint_id)

INDEX (created_at)

INDEX (event_type)

INDEX (result)
```

---

## Valori previsti

### event_type

Versione 1.0

- qr_scan

---

### result

Valori iniziali previsti

accepted
duplicate
before_failed
after_failed
path_closed
participation_cancelled

L'elenco potrà essere esteso nelle versioni future.

---

## Vincoli logici

- Ogni Evento appartiene ad una sola Partecipazione.
- Ogni Evento si riferisce ad un solo Checkpoint.
- Gli Eventi non vengono mai modificati.
- Gli Eventi non vengono mai eliminati.

Gli Eventi vengono registrati esclusivamente dopo la risoluzione del token pubblico in un Checkpoint esistente e dopo la verifica dell'autenticazione dell'utente.

---

## Privacy

La registrazione di indirizzo IP e User Agent è configurabile.

Se disabilitata:

- `ip_address` viene lasciato a NULL;
- `user_agent` viene lasciato a NULL.

---

## Note

Gli Eventi rappresentano la fonte primaria di tutte le statistiche del plugin.

Le Foreign Key non vengono create fisicamente.

L'integrità referenziale è garantita dal plugin.

Questa tabella rappresenta esclusivamente lo storico delle operazioni effettuate dal sistema.

Non rappresenta lo stato corrente della progressione del giocatore.