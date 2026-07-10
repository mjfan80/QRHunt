# QRHunt – Software Requirements Specification (SRS)

**Versione:** 0.1 (Draft)
**Stato:** In lavorazione

---

# 1. Introduzione

## 1.1 Scopo del progetto

QRHunt è un plugin per WordPress che consente di creare esperienze interattive basate su checkpoint raggiungibili tramite QR Code.

Il plugin è progettato per essere completamente generico e può essere utilizzato per:

* cacce al tesoro;
* percorsi a tappe;
* eventi;
* musei;
* fiere;
* percorsi didattici;
* parchi tematici;
* visite guidate;
* qualsiasi attività in cui un partecipante debba trovare uno o più checkpoint.

QRHunt non è legato a uno specifico tema o evento.

---

## 1.2 Obiettivi

Il plugin deve permettere di:

* creare uno o più percorsi indipendenti;
* creare checkpoint associati ai percorsi;
* generare automaticamente i QR Code;
* registrare le scansioni effettuate dai partecipanti;
* verificare automaticamente le regole di progressione;
* fornire statistiche e strumenti di amministrazione;
* esportare i dati.

---

## 1.3 Filosofia del progetto

QRHunt gestisce esclusivamente la logica del gioco.

WordPress gestisce invece i contenuti.

Il plugin dovrà integrarsi il più possibile con le funzionalità native di WordPress evitando di reinventare strumenti già disponibili.

---

# 2. Glossario

## Percorso (Path)

Insieme di checkpoint appartenenti allo stesso gioco o evento.

Esempi:

* Halloween 2026
* Percorso Bambini
* Escape Room
* Museo

Ogni percorso è indipendente dagli altri.

---

## Checkpoint

Punto del percorso identificato da un QR Code.

Ogni checkpoint possiede un proprio contenuto visualizzato al partecipante.

Un checkpoint appartiene sempre ad un solo percorso.

---

## Partecipante

Utente WordPress autenticato.

L'autenticazione è demandata a WordPress.

QRHunt utilizza l'utente autenticato senza gestire direttamente il sistema di login.

---

## Partecipazione

Relazione tra un partecipante e un percorso.

Ogni partecipante può avere una sola partecipazione per ciascun percorso.

Un partecipante può invece partecipare a percorsi differenti.

La partecipazione viene creata automaticamente alla scansione del primo checkpoint del percorso.

---

## Tentativo

Ogni apertura di un QR Code genera un tentativo.

Il tentativo può avere esito positivo oppure negativo.

Tutti i tentativi vengono registrati.

---

# 3. Modello logico

Utente

↓

Partecipazione

↓

Percorso

↓

Tentativi

↓

Checkpoint

---

# 4. Stati della partecipazione

Una partecipazione può assumere i seguenti stati.

## Non iniziata

Il partecipante è registrato al sito ma non ha ancora iniziato il percorso.

---

## In corso

Il primo checkpoint è stato registrato correttamente.

Il percorso è iniziato.

---

## Completata

Il partecipante ha soddisfatto la condizione di completamento del percorso.

La condizione sarà configurabile.

Nella versione iniziale sarà possibile definire un checkpoint finale obbligatorio.

---

## Annullata

Partecipazione invalidata manualmente da un amministratore.

Non viene eliminato alcun dato storico.

---

# 5. Regole dei checkpoint

Ogni checkpoint può definire due vincoli indipendenti.

## Prerequisito

Indica quale checkpoint deve essere già stato completato affinché il checkpoint corrente possa essere registrato.

Se il prerequisito non è soddisfatto:

* il tentativo viene registrato;
* il checkpoint non viene assegnato;
* viene mostrato un messaggio al partecipante.

---

## Non valido dopo

Indica il checkpoint oltre il quale il checkpoint corrente non potrà più essere registrato.

Se il checkpoint indicato è già stato trovato:

* il tentativo viene registrato;
* il checkpoint non viene assegnato;
* viene mostrato un messaggio al partecipante.

---

Entrambe le regole sono opzionali.

L'assenza di regole rende il checkpoint completamente libero.

---

# 6. Regole di partecipazione

* Ogni percorso può essere completato una sola volta da ciascun partecipante.
* Una partecipazione non viene eliminata.
* Un amministratore può annullare una partecipazione.
* I checkpoint già registrati possono essere nuovamente scansionati.
* Le scansioni duplicate non assegnano nuovi punti né modificano la progressione.
* Le scansioni duplicate vengono registrate come tentativi.

---

# 7. QR Code

Ogni checkpoint possiede un QR Code generato automaticamente dal plugin.

Il QR Code contiene esclusivamente l'URL del checkpoint.

Il plugin dovrà supportare almeno:

* PNG;
* SVG;
* logo centrale;
* livelli di correzione errore;
* rigenerazione automatica;
* download.

---

# 8. Gestione contenuti

I checkpoint saranno gestiti come Custom Post Type di WordPress.

In questo modo potranno utilizzare tutte le funzionalità native dell'editor Gutenberg, comprese immagini, video, audio, blocchi e revisioni.

I percorsi saranno invece entità proprie del plugin e non Custom Post Type.

---

# 9. Internazionalizzazione

Il plugin dovrà essere sviluppato fin dall'inizio utilizzando il sistema standard di traduzione di WordPress.

Le lingue inizialmente previste sono:

* Italiano
* Inglese

---

# 10. Principi progettuali

Il plugin dovrà:

* utilizzare esclusivamente API ufficiali di WordPress;
* seguire gli standard di coding WordPress;
* essere predisposto per la pubblicazione nel repository ufficiale;
* evitare dipendenze obbligatorie da altri plugin;
* integrare automaticamente eventuali plugin di Social Login compatibili senza richiederli.

---

**Fine documento – Versione 0.1**
