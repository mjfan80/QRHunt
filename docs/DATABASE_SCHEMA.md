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

I Gruppi consentono di modellare checkpoint obbligatori senza imporre un ordine di visita.

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

- after
- before

### target_type

- checkpoint
- group

---

## Regole di valutazione

Nella versione 1.0 tutte le Dipendenze vengono valutate con logica AND.

La logica OR non è prevista al momento.

---

## Note

La tabella contiene esclusivamente la definizione delle regole.

L'interpretazione delle Dipendenze è demandata al motore di validazione.

Le Foreign Key non vengono create fisicamente.

L'integrità referenziale è garantita dal plugin.