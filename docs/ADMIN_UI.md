# QRHunt – Amministrazione

**Versione:** 0.1 (Draft)

---

# 1. Menu principale

QRHunt aggiunge un nuovo menu nell'amministrazione WordPress.

```
QRHunt

├── Dashboard

├── Percorsi

├── Checkpoint

├── Partecipazioni

├── Eventi

└── Impostazioni
```

---

# 2. Dashboard

La Dashboard mostra una panoramica del plugin.

Informazioni previste:

- numero Percorsi;
- Percorsi attivi;
- Partecipazioni attive;
- ultime scansioni;
- statistiche sintetiche.

---

# 3. Percorsi

La schermata mostra, per impostazione predefinita, i Percorsi non archiviati.

L'amministratore può scegliere di visualizzare anche i Percorsi archiviati.

Operazioni disponibili:

- Nuovo
- Modifica
- Duplica
- Archivia
- Elimina

---

## Creazione Percorso

Campi previsti

- Nome
- Descrizione
- Stato
- Data apertura
- Data chiusura
- Checkpoint iniziale
- Checkpoint finale

---

# 4. Checkpoint

I Checkpoint vengono gestiti tramite il Custom Post Type.

La schermata di modifica del Checkpoint aggiunge un metabox QRHunt.

---

## Configurazione

Campi previsti

- Percorso
- Gruppo
- Token (sola lettura)
- QR Code
- Dipendenze
- Pulsante di anteprima

---

# 5. Dipendenze

# 5. Regole del Checkpoint

Le Dipendenze vengono configurate tramite il metabox QRHunt presente nella schermata di modifica del Checkpoint.

Per semplificare la configurazione, il metabox è suddiviso in due modalità.

---

## Modalità standard

Per la maggior parte dei casi è sufficiente configurare un solo prerequisito ed un solo vincolo.

Sono disponibili i seguenti campi.

### Deve essere già stato completato

Elenco dei Checkpoint appartenenti al medesimo Percorso.

È possibile selezionare un solo Checkpoint.

---

### Non deve essere già stato completato

Elenco dei Checkpoint appartenenti al medesimo Percorso.

È possibile selezionare un solo Checkpoint.

---

## Configurazione avanzata

La modalità avanzata consente di definire regole più complesse.

Per ciascuna sezione è possibile aggiungere una o più Dipendenze.

Ogni Dipendenza può riferirsi a:

- un Checkpoint;
- un Gruppo.

Nella versione 1.0 tutte le Dipendenze vengono valutate con logica AND.

---

## Note

Le regole possono essere configurate solo dopo il primo salvataggio del Checkpoint.

Questo consente di selezionare esclusivamente Checkpoint e Gruppi già esistenti.
---

# 6. Partecipazioni

La schermata mostra:

- utente;
- percorso;
- stato;
- data inizio;
- data termine;
- checkpoint validati.

Operazioni:

- visualizza;
- annulla.

---

# 7. Eventi

La schermata mostra:

- data;
- utente;
- percorso;
- checkpoint;
- tipo;
- esito.

Sono disponibili filtri per:

- percorso;
- utente;
- intervallo temporale;
- esito.

---

# 8. Impostazioni

Configurazioni previste.

## Privacy

- registra indirizzo IP;
- registra User Agent.

---

## QR Code

- formato PNG;
- formato SVG;
- logo;
- livello di correzione.

---

## Internazionalizzazione

Configurazione della lingua del plugin.

---

# 9. Principi

L'interfaccia amministrativa deve:

- utilizzare componenti standard di WordPress;
- evitare pagine personalizzate quando non necessarie;
- essere coerente con il resto dell'amministrazione WordPress;
- risultare utilizzabile anche con un elevato numero di Percorsi e Checkpoint.