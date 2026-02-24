# TherapyCRM - Manuale d'Uso

**Centro Medico San Luca - Sistema di Gestione Terapeutica**

*Versione 1.0 - Febbraio 2026*

---

## Indice

### Parte 1 - Il Gestionale (Applicazione Web)

1. [Accesso al Sistema](#1-accesso-al-sistema)
2. [La Dashboard](#2-la-dashboard)
3. [Gestione Pazienti](#3-gestione-pazienti)
4. [Gestione Terapisti](#4-gestione-terapisti)
5. [Piani Terapeutici](#5-piani-terapeutici)
6. [Calendario e Appuntamenti](#6-calendario-e-appuntamenti)
7. [Gestione Assenze](#7-gestione-assenze)
8. [Richieste Documenti](#8-richieste-documenti)
9. [Notifiche e Comunicazioni](#9-notifiche-e-comunicazioni)
10. [Statistiche e Report](#10-statistiche-e-report)
11. [Gruppi Coordinatori](#11-gruppi-coordinatori)
12. [Gestione Utenti](#12-gestione-utenti)
13. [Ricerca Globale](#13-ricerca-globale)

### Parte 2 - L'App Mobile

14. [Primo Accesso all'App](#14-primo-accesso-allapp)
15. [App per il Paziente/Familiare](#15-app-per-il-pazientefamiliare)
16. [App per il Terapista](#16-app-per-il-terapista)
17. [Notifiche Push e Notifiche Bloccanti](#17-notifiche-push-e-notifiche-bloccanti)

### Parte 3 - Automatismi del Sistema

18. [Processi Automatici (Cron Job)](#18-processi-automatici-cron-job)
19. [Flussi di Lavoro Automatizzati](#19-flussi-di-lavoro-automatizzati)

### Appendici

- [A - Ruoli e Permessi](#appendice-a---ruoli-e-permessi)
- [B - Stati degli Appuntamenti](#appendice-b---stati-degli-appuntamenti)
- [C - Stati delle Richieste Documenti](#appendice-c---stati-delle-richieste-documenti)
- [D - Domande Frequenti (FAQ)](#appendice-d---domande-frequenti-faq)

---

# PARTE 1 - IL GESTIONALE (Applicazione Web)

---

## 1. Accesso al Sistema

### 1.1 Login

Per accedere al gestionale, aprire il browser e navigare all'indirizzo del sistema. Si presenta la schermata di login.

**Come effettuare il login:**

1. Inserire il proprio **indirizzo email** nel campo "Email"
2. Inserire la **password** nel campo "Password"
3. (Facoltativo) Spuntare "Ricordami" per restare collegati
4. Cliccare su **"Accedi"**

> **Nota:** Al primo accesso potrebbe essere richiesto di cambiare la password. Seguire le istruzioni a schermo per impostarne una nuova.

### 1.2 Password Dimenticata

Se non si ricorda la password:

1. Nella schermata di login, cliccare su **"Hai dimenticato la password?"**
2. Inserire l'indirizzo email associato al proprio account
3. Cliccare su **"Invia"**
4. Controllare la propria casella email (anche la cartella spam)
5. Cliccare sul link ricevuto via email
6. Inserire la nuova password e confermarla
7. Tornare alla schermata di login e accedere con la nuova password

> **Importante:** Il link di reset scade dopo 24 ore. Se scaduto, ripetere la procedura.

### 1.3 Cambio Password

Per cambiare la password una volta connessi:

1. Cliccare sul proprio **nome utente** in alto a destra
2. Selezionare **"Cambia Password"**
3. Inserire la password attuale
4. Inserire la nuova password
5. Confermare la nuova password
6. Cliccare **"Salva"**

> **Nota:** Dopo il cambio password, tutte le altre sessioni attive verranno disconnesse per sicurezza.

---

## 2. La Dashboard

Dopo il login, si viene portati alla **Dashboard** (pagina principale).

### 2.1 Cosa Mostra la Dashboard

La dashboard presenta un riepilogo generale dell'attivita del centro:

- **Pazienti totali** - Numero di pazienti attivi nel sistema
- **Nuovi pazienti del mese** - Pazienti registrati nel mese corrente
- **Terapisti attivi** - Numero di terapisti operativi
- **Piani terapeutici attivi** - Piani attualmente in corso
- **Piani in scadenza** - Piani che scadranno a breve (da rinnovare)
- **Ore settimanali di trattamento** - Ore di terapia erogate nella settimana
- **Grafico crescita pazienti** - Andamento degli ultimi 6 mesi
- **Top 5 trattamenti** - Le tipologie di terapia piu richieste
- **Appuntamenti ultimi 7 giorni** - Distribuzione giornaliera
- **Richieste documenti per stato** - Panoramica delle richieste pendenti
- **Assenze del mese** - Riepilogo assenze

### 2.2 Navigazione

**Barra laterale (menu a sinistra):** Contiene tutte le sezioni del gestionale. Le voci visibili dipendono dal proprio ruolo (vedi [Appendice A](#appendice-a---ruoli-e-permessi)).

**Barra superiore:**
- **Icona campanella** - Notifiche (il numero indica le notifiche non lette)
- **Nome utente** - Menu personale (profilo, cambio password, logout)

**Breadcrumb (percorso di navigazione):** In alto sotto la barra, mostra il percorso della pagina corrente (es. Home > Pazienti > Dettaglio Paziente).

---

## 3. Gestione Pazienti

### 3.1 Elenco Pazienti

**Menu:** Pazienti

La pagina mostra la lista di tutti i pazienti registrati nel sistema.

**Funzionalita disponibili:**
- **Ricerca** - Cercare per nome, cognome, codice fiscale o email
- **Filtri** - Filtrare per distretto, stato attivo/inattivo
- **Ordinamento** - Cliccare sulle intestazioni delle colonne per ordinare
- **Paginazione** - 15 pazienti per pagina, navigare con i pulsanti in basso

### 3.2 Creare un Nuovo Paziente

1. Dalla lista pazienti, cliccare il pulsante **"Nuovo Paziente"** (o icona "+")
2. Compilare i dati richiesti:

   **Dati Anagrafici:**
   - Nome e Cognome (obbligatori)
   - Data di nascita
   - Luogo di nascita
   - Genere

   **Codice Fiscale:**
   - Il sistema puo **generarlo automaticamente** dai dati anagrafici (cliccare "Genera Codice Fiscale")
   - Oppure inserirlo manualmente

   **Contatti:**
   - Email
   - Telefono
   - Indirizzo

   **Assegnazione:**
   - Distretto di appartenenza

   **Foto:** (facoltativa)
   - Caricare una foto del paziente

3. Cliccare **"Salva"**

> **Nota sulla privacy:** Email, telefono e indirizzo vengono criptati automaticamente nel database per proteggere i dati sensibili.

### 3.3 Visualizzare un Paziente

Dalla lista pazienti, cliccare sul **nome del paziente** o sull'icona **"Visualizza"** (occhio).

La scheda paziente mostra:
- **Dati anagrafici** completi
- **Piani terapeutici** associati (attivi ed scaduti)
- **Appuntamenti** programmati e passati
- **Storico trattamenti**
- **Documenti** richiesti

### 3.4 Modificare un Paziente

1. Aprire la scheda del paziente
2. Cliccare il pulsante **"Modifica"** (icona matita)
3. Modificare i campi desiderati
4. Cliccare **"Salva"**

### 3.5 Account Familiari (Gestione Multi-Paziente)

Un account familiare permette a un genitore o tutore di gestire piu pazienti (es. una madre con due figli in terapia).

**Come creare un collegamento familiare:**

1. Dalla scheda del paziente, accedere alla sezione **"Account"**
2. Cliccare **"Collega Paziente"**
3. Cercare il paziente da collegare
4. Selezionare il **tipo di relazione** (madre, padre, tutore, altro)
5. Indicare se ha **potesta genitoriale**
6. Confermare il collegamento

**Come scollegare un paziente:**

1. Dalla sezione Account, individuare il paziente collegato
2. Cliccare **"Scollega"**
3. Confermare la rimozione

> **Nota:** L'account familiare e quello che il paziente/famiglia usa per accedere all'app mobile. Un unico accesso permette di vedere tutti i familiari collegati.

### 3.6 Credenziali per l'App Mobile

Quando si crea un paziente, il sistema genera automaticamente le credenziali per l'accesso all'app mobile. Queste credenziali possono essere:

- **Inviate via email** al paziente/familiare
- **Scaricate come PDF** per consegna cartacea
- **Rigenerate** in caso di smarrimento

---

## 4. Gestione Terapisti

### 4.1 Elenco Terapisti

**Menu:** Terapisti

Mostra tutti i terapisti registrati con:
- Nome e cognome
- Specializzazione/i
- Stato (attivo/inattivo)
- Distretto di appartenenza

**Filtri disponibili:** per specializzazione, distretto, disponibilita.

### 4.2 Creare un Nuovo Terapista

1. Cliccare **"Nuovo Terapista"**
2. Compilare i dati:

   **Dati Account:**
   - Email (sara usata per il login)
   - La password viene generata automaticamente dal sistema

   **Dati Personali:**
   - Nome e Cognome
   - Telefono

   **Dati Professionali:**
   - **Specializzazione/i** - Selezionare una o piu specializzazioni (es. Logopedia, Terapia Psicomotoria, Educazione Speciale, Terapia Occupazionale, ecc.)
   - Distretto di assegnazione

3. Cliccare **"Salva"**

Il sistema creera automaticamente:
- L'account utente del terapista
- Le credenziali di accesso (per gestionale e app)

### 4.3 Gestione Credenziali Terapista

Dalla scheda del terapista:

- **Resettare la password:** Cliccare "Reset Password" - genera una nuova password temporanea
- **Scaricare le credenziali in PDF:** Cliccare "Scarica Credenziali PDF" - genera un documento con email e password da consegnare al terapista
- **Inviare credenziali via email:** Il sistema puo inviare automaticamente le credenziali al terapista

### 4.4 Attivare/Disattivare un Terapista

1. Aprire la scheda del terapista
2. Cliccare **"Modifica"**
3. Cambiare lo stato da Attivo a Inattivo (o viceversa)
4. Salvare

> **Nota:** Un terapista disattivato non puo accedere al sistema ma i suoi dati storici vengono mantenuti.

---

## 5. Piani Terapeutici

### 5.1 Cosa e un Piano Terapeutico

Il piano terapeutico e il documento che definisce il percorso di cura di un paziente. Contiene:
- Il **regime** di terapia (tipologia e frequenza)
- Le **terapie** specifiche assegnate (es. 2 sedute di logopedia + 1 di psicomotricita a settimana)
- Il **periodo** di validita (data inizio e fine)
- I **terapisti** assegnati a ciascuna terapia

### 5.2 Elenco Piani Terapeutici

**Menu:** Piani Terapeutici

La lista mostra:
- Paziente associato
- Regime di terapia
- Date di inizio e fine
- Stato (attivo, scaduto, bozza)
- Giorni rimanenti alla scadenza

**Filtri:** per paziente, regime, distretto, stato.

### 5.3 Creare un Piano Terapeutico

1. Cliccare **"Nuovo Piano"**
2. **Selezionare il paziente** dall'elenco a tendina
3. **Scegliere il regime** (tipo/frequenza di terapia)
4. **Impostare le date:**
   - Data inizio
   - Data fine
5. **Aggiungere le terapie** (una o piu):

   Per ogni terapia cliccare "Aggiungi Terapia" e specificare:
   - **Tipo di trattamento** (es. Logopedia, Psicomotricita, ecc.)
   - **Frequenza** - Quante sedute a settimana (es. 2 volte/settimana)
   - **Durata seduta** - Quanto dura ogni sessione in minuti (es. 45 minuti)
   - **Terapista** - Chi condurra le sedute
   - **Ciclo** - Tipo di ciclo (regolare, recupero, avanzamento, ecc.)

6. Cliccare **"Salva"**

**Cosa succede dopo il salvataggio:**
- Il sistema genera automaticamente gli **appuntamenti ricorrenti** in base alla frequenza e al periodo
- Gli appuntamenti compaiono nel **calendario** del terapista e del paziente
- Il sistema pianifica le **notifiche automatiche** di scadenza (a 90, 60, 30 e 15 giorni dalla fine)

### 5.4 Modificare un Piano Terapeutico

1. Aprire il piano dalla lista
2. Cliccare **"Modifica"**
3. E possibile:
   - Cambiare le date di inizio/fine
   - Aggiungere o rimuovere terapie
   - Cambiare il terapista assegnato
   - Modificare frequenza e durata delle sedute
4. Cliccare **"Salva"**

> **Attenzione:** Modificare un piano potrebbe influire sugli appuntamenti gia generati. Il sistema chiedera conferma prima di applicare le modifiche.

### 5.5 Setting e Regimi

I **regimi** definiscono le configurazioni predefinite per i piani terapeutici:
- Combinazioni di terapie standard
- Frequenze predefinite
- Durate delle sedute
- Setting terapeutici disponibili:
  - **Ambulatoriale** - Sedute presso il centro
  - **Domiciliare** - Sedute a domicilio del paziente
  - **Piccolo Gruppo** - Sedute di gruppo ristretto
  - **Centro Diurno** - Presso il centro diurno
  - **Semiconvitto** - Modalita semiconvitto

### 5.6 Scadenza e Rinnovo

Quando un piano si avvicina alla scadenza:

1. Il sistema invia **notifiche automatiche** alla famiglia del paziente (tramite app) a:
   - 90 giorni dalla scadenza
   - 60 giorni dalla scadenza
   - 30 giorni dalla scadenza
   - 15 giorni dalla scadenza

2. Il piano compare nella sezione **"Piani in scadenza"** della dashboard

3. Alla scadenza, il piano viene automaticamente marcato come **"Scaduto"**

4. Per rinnovare, creare un **nuovo piano terapeutico** per lo stesso paziente

---

## 6. Calendario e Appuntamenti

### 6.1 Visualizzazione Calendario

**Menu:** Calendario

Il calendario mostra tutti gli appuntamenti programmati con una vista:
- **Mensile** - Panoramica del mese con indicatori colorati per giorno
- **Settimanale** - Dettaglio della settimana con fasce orarie
- **Giornaliera** - Tutti gli appuntamenti del giorno selezionato

**Codice colori:** Ogni tipo di trattamento ha un colore diverso per facilitare la lettura.

### 6.2 Filtri del Calendario

Il calendario puo essere filtrato per:
- **Terapista** - Vedere solo gli appuntamenti di un terapista specifico
- **Paziente** - Vedere solo gli appuntamenti di un paziente specifico
- **Tipo di trattamento** - Filtrare per tipologia di terapia

### 6.3 Dettaglio Appuntamento

Cliccando su un appuntamento nel calendario si vedono:
- **Data e ora** della seduta
- **Durata** in minuti
- **Paziente** (nome e cognome)
- **Terapista** (nome, cognome e specializzazione)
- **Tipo di trattamento**
- **Stato** attuale (vedi [Appendice B](#appendice-b---stati-degli-appuntamenti))
- **Note** eventuali
- **Categoria** (regolare, recupero, avanzamento, extra, compensazione)
- **Origine** (da piano terapeutico o privato)

### 6.4 Modificare lo Stato di un Appuntamento

Gli stati possibili sono:
- **Programmato** - Appuntamento confermato, non ancora avvenuto
- **Completato** - Seduta effettuata con successo
- **Assenza Giustificata** - Paziente assente con motivazione valida
- **Assenza Non Giustificata** - Paziente assente senza preavviso
- **Cancellato** - Annullato prima dell'esecuzione
- **Terapista Assente** - Il terapista non era disponibile

Per cambiare stato:
1. Cliccare sull'appuntamento
2. Selezionare il nuovo **stato**
3. (Facoltativo) Aggiungere una **nota**
4. Salvare

> **Nota:** Gli appuntamenti passati che risultano ancora "Programmato" vengono automaticamente marcati come "Completato" dal sistema (vedi [Automatismi](#18-processi-automatici-cron-job)).

### 6.5 Pattern di Appuntamenti

I **pattern** sono le regole di ripetizione degli appuntamenti generati dai piani terapeutici.

Esempio: Se un piano prevede "Logopedia 2 volte a settimana (lunedi e mercoledi), 45 minuti", il sistema genera automaticamente tutti gli appuntamenti del lunedi e mercoledi per la durata del piano.

I pattern tengono conto di:
- Le **fasce orarie disponibili** del terapista
- Le **assenze programmate** del terapista
- Le **sostituzioni** (se un terapista e sostituito da un collega)

---

## 7. Gestione Assenze

### 7.1 Assenze dei Terapisti

**Menu:** Assenze

Questa sezione gestisce le assenze dei terapisti (ferie, malattia, permessi).

### 7.2 Registrare un'Assenza

1. Cliccare **"Nuova Assenza"**
2. **Selezionare il terapista**
3. **Impostare il periodo:**
   - Data inizio assenza
   - Data fine assenza
4. **Scegliere il tipo di assenza:**
   - **Ferie** - Assenza per vacanza
   - **Malattia** - Assenza per motivi di salute
   - **Permesso** - Assenza autorizzata breve
   - **Altro** - Altra motivazione
5. **Gestione appuntamenti nel periodo:** Il sistema chiede se si desidera **aggiornare automaticamente** tutti gli appuntamenti del terapista nel periodo indicato come "Terapista Assente"
6. Cliccare **"Salva"**

**Cosa succede:**
- L'assenza viene registrata nel sistema
- Se confermato, tutti gli appuntamenti del terapista nel periodo vengono marcati come **"Terapista Assente"**
- L'assenza viene conteggiata nelle statistiche
- Il coordinatore del gruppo viene informato

### 7.3 Assenze dei Pazienti

Le assenze dei pazienti vengono registrate a livello di singolo appuntamento:

1. Dal **Calendario**, aprire l'appuntamento interessato
2. Cambiare lo stato in **"Assenza Giustificata"** o **"Assenza Non Giustificata"**
3. Inserire la **motivazione** (facoltativa per le non giustificate)
4. Salvare

Oppure tramite l'**app mobile** del terapista (vedi [sezione 16](#16-app-per-il-terapista)).

### 7.4 Sostituzioni Terapista

Quando un terapista e assente, e possibile assegnare un **sostituto**:

1. Dalla scheda dell'assenza, indicare il terapista sostituto
2. Il periodo di sostituzione viene definito
3. Gli appuntamenti del periodo possono essere riassegnati al sostituto
4. Il terapista sostituto vedra gli appuntamenti nel proprio calendario

---

## 8. Richieste Documenti

### 8.1 Panoramica

Il sistema permette ai pazienti/familiari di richiedere documenti (certificati medici, relazioni terapeutiche, attestati, ecc.) tramite l'app mobile. Gli operatori del gestionale gestiscono il flusso di lavorazione.

### 8.2 Elenco Richieste

**Menu:** Richieste Documenti

La lista mostra tutte le richieste con:
- Paziente richiedente
- Tipo di documento
- Data della richiesta
- Stato attuale
- Giorni stimati per l'evasione

**Filtri:** per stato, paziente, tipo di documento, periodo.

### 8.3 Flusso di Lavorazione delle Richieste

Ogni richiesta passa attraverso 4 stati:

```
INVIATA  -->  IN LAVORAZIONE  -->  STAMPATO  -->  CONSEGNATO
```

| Stato | Significato | Chi puo impostarlo |
|-------|-------------|-------------------|
| **Inviata** | Richiesta appena ricevuta dal paziente | Automatico (invio dall'app) |
| **In Lavorazione** | L'operatore ha preso in carico la richiesta | Amministratore |
| **Stampato** | Il documento e stato preparato e stampato | Amministratore |
| **Consegnato** | Il documento e stato consegnato al paziente | Manager |

### 8.4 Gestire una Richiesta

**Come Amministratore:**

1. Aprire la richiesta dalla lista
2. Verificare i dettagli (paziente, tipo documento, motivazione)
3. Cliccare **"Aggiorna Stato"**
4. Selezionare il nuovo stato:
   - Da "Inviata" a **"In Lavorazione"** (presa in carico)
   - Da "In Lavorazione" a **"Stampato"** (documento pronto)
5. Salvare

**Come Manager:**

1. Aprire la richiesta in stato "Stampato"
2. Cliccare **"Aggiorna Stato"**
3. Selezionare **"Consegnato"** (conferma consegna al paziente)
4. Salvare

> **Nota:** Il paziente riceve una notifica nell'app ad ogni cambio di stato della sua richiesta.

### 8.5 Tipi di Documenti Disponibili

I tipi di documenti configurati nel sistema includono:
- **Certificato Medico** - Richiesta certificato per assenza
- **Relazione Terapeutica** - Report sul percorso terapeutico
- **Attestato di Frequenza** - Certificazione delle sedute frequentate
- **Riepilogo Piano Terapeutico** - Sintesi del piano in corso

Ogni tipo ha:
- **Tempo stimato** di evasione (es. 3 giorni lavorativi)
- Eventuali **campi obbligatori** (motivazione, date di riferimento)

---

## 9. Notifiche e Comunicazioni

### 9.1 Centro Notifiche

**Icona campanella** in alto a destra nella barra superiore.

Il numero rosso indica le **notifiche non lette**.

Cliccando si apre l'elenco delle notifiche recenti. Per vedere tutte le notifiche, cliccare **"Vedi tutte"**.

### 9.2 Tipi di Notifiche

| Tipo | Descrizione |
|------|-------------|
| **Comunicazione Interna** | Messaggi dal sistema o da altri operatori |
| **Promemoria** | Promemoria per eventi imminenti |
| **Scadenza** | Avvisi di scadenza (piani terapeutici, documenti) |
| **Scadenza Piano Terapeutico** | Avviso specifico per piani in scadenza |
| **Rinnovo Piano** | Notifica di rinnovo piano |
| **Stato Richiesta Documento** | Aggiornamento sullo stato di una richiesta |
| **Promemoria Appuntamento** | Promemoria per appuntamenti imminenti |
| **Informativa** | Comunicazioni generali |

### 9.3 Leggere e Gestire le Notifiche

1. Cliccare sulla **notifica** per aprirla
2. Leggere il contenuto completo
3. La notifica viene automaticamente marcata come **letta**

**Dalla lista notifiche:**
- Filtrare per tipo (tutte, non lette, lette)
- Ordinare per data
- 15 notifiche per pagina

---

## 10. Statistiche e Report

### 10.1 Dashboard Statistiche

**Menu:** Statistiche

La pagina principale delle statistiche offre una panoramica con:

- **Metriche chiave:**
  - Totale pazienti (attivi, nuovi del mese, con trattamenti multipli)
  - Terapisti attivi
  - Piani terapeutici (totali, attivi, in scadenza, nuovi del mese)
  - Ore settimanali di trattamento

- **Grafici:**
  - Andamento crescita pazienti (ultimi 6 mesi)
  - Top 5 tipologie di trattamento
  - Distribuzione appuntamenti degli ultimi 7 giorni
  - Richieste documenti per stato
  - Assenze del mese

### 10.2 Statistiche Assenze

**Menu:** Statistiche > Assenze

Analisi dettagliata delle assenze con:
- **Tasso di assenza mensile** - Percentuale di appuntamenti saltati
- **Assenze per motivazione** - Distribuzione per malattia, ferie, permessi, ecc.
- **Assenze per terapista** - Chi ha il maggior numero di assenze
- **Assenze per tipo di trattamento** - Quali terapie hanno piu assenze
- **Classifica assenteisti** - Terapisti con piu assenze
- **Andamento nel tempo** - Tendenza delle assenze

**Filtri disponibili:**
- Periodo (data inizio - data fine)
- Terapista specifico
- Paziente specifico
- Tipo di trattamento
- Motivazione

**Esportazione:** Cliccare **"Esporta in Excel"** per scaricare i dati filtrati in formato .xlsx.

### 10.3 Statistiche Pazienti

**Menu:** Statistiche > Pazienti

- Distribuzione demografica (eta, genere)
- Pazienti attivi per distretto
- Pazienti con trattamenti multipli
- Andamento nuove iscrizioni
- Tasso di permanenza

### 10.4 Statistiche Trattamenti

**Menu:** Statistiche > Trattamenti

- Trattamenti piu richiesti
- Combinazioni di trattamenti piu comuni
- Ore per tipo di trattamento
- Utilizzo terapisti per trattamento

### 10.5 Statistiche Piani Terapeutici

**Menu:** Statistiche > Piani

- Piani per stato (attivi, scaduti, bozza)
- Piani per regime
- Analisi durata piani
- Tasso di scadenza
- Piani in scadenza nei prossimi 30 giorni

### 10.6 Esportazione Dati

Da qualsiasi pagina di statistiche e possibile:
1. Applicare i filtri desiderati
2. Cliccare **"Esporta in Excel"**
3. Il file .xlsx viene scaricato con i dati filtrati

---

## 11. Gruppi Coordinatori

### 11.1 Cosa Sono i Gruppi Coordinatori

I gruppi coordinatori organizzano i terapisti sotto la supervisione di un coordinatore. Ogni gruppo ha:
- Un **coordinatore** responsabile
- Uno o piu **terapisti** assegnati
- Un **distretto** di riferimento

### 11.2 Creare un Gruppo

*(Solo Amministratori e Manager)*

1. **Menu:** Gruppi Coordinatori > **"Nuovo Gruppo"**
2. Inserire il **nome del gruppo**
3. Selezionare il **coordinatore** dall'elenco
4. Aggiungere i **terapisti** al gruppo
5. Assegnare il **distretto**
6. Cliccare **"Salva"**

### 11.3 Gestire un Gruppo

Dalla scheda del gruppo e possibile:
- Vedere l'elenco dei terapisti con le loro specializzazioni
- **Aggiungere** nuovi terapisti al gruppo
- **Rimuovere** terapisti dal gruppo
- **Cambiare** il coordinatore
- Visualizzare i pazienti assegnati ai terapisti del gruppo

> **Nota:** Un terapista puo appartenere a piu gruppi contemporaneamente.

---

## 12. Gestione Utenti

### 12.1 Amministratori

**Menu:** Gestione Utenti > Amministratori

Gli amministratori hanno accesso completo al sistema. Per crearne uno:

1. Cliccare **"Nuovo Amministratore"**
2. Compilare: username, email, password, dati personali (nome, cognome, telefono, indirizzo)
3. Assegnare il distretto
4. Salvare

### 12.2 Manager

**Menu:** Gestione Utenti > Manager

I manager gestiscono le operazioni quotidiane. Creazione simile agli amministratori ma con permessi limitati (vedi [Appendice A](#appendice-a---ruoli-e-permessi)).

### 12.3 Coordinatori

**Menu:** Gestione Utenti > Coordinatori

I coordinatori supervisionano gruppi di terapisti. Creazione simile, con associazione ai gruppi coordinatori.

---

## 13. Ricerca Globale

### 13.1 Come Funziona

La **barra di ricerca** nella parte superiore del gestionale permette di cercare rapidamente:

- **Terapisti** - per nome, cognome o specializzazione
- **Pazienti** - per nome, cognome o codice fiscale
- **Account pazienti** - per email o nome del familiare

**Come usarla:**

1. Digitare almeno **2 caratteri** nella barra di ricerca
2. I risultati appaiono in tempo reale raggruppati per categoria
3. Cliccare sul risultato desiderato per aprire la scheda corrispondente

### 13.2 Generazione Codice Fiscale

Il sistema include un generatore automatico di codice fiscale italiano:

1. Inserire i dati anagrafici del paziente (nome, cognome, data di nascita, genere, luogo di nascita)
2. Cliccare **"Genera Codice Fiscale"**
3. Il sistema calcola automaticamente il codice fiscale
4. Verificare il codice e confermarlo

---

# PARTE 2 - L'APP MOBILE

---

## 14. Primo Accesso all'App

### 14.1 Installazione

L'app **TherapyCRM** e disponibile per dispositivi Android. Scaricarla e installarla seguendo le istruzioni fornite dal centro.

### 14.2 Login

1. Aprire l'app
2. Inserire **email** e **password** fornite dal centro
3. Toccare **"Accedi"**

> **Nota:** Le credenziali vengono fornite dal centro al momento della registrazione del paziente o del terapista (via email o su foglio cartaceo).

### 14.3 Primo Accesso - Cambio Password Obbligatorio

Al primo login, l'app richiede di **cambiare la password**:

1. Inserire una **nuova password** personale
2. Confermarla
3. Toccare **"Conferma"**
4. Si viene reindirizzati alla schermata principale

### 14.4 Password Dimenticata

1. Nella schermata di login, toccare **"Password dimenticata?"**
2. Inserire l'email associata all'account
3. Toccare **"Invia"**
4. Controllare l'email e cliccare sul link ricevuto
5. Impostare la nuova password
6. Tornare all'app e accedere

### 14.5 Due Tipi di Accesso

L'app si presenta in modo diverso a seconda del tipo di utente:

| | **Paziente/Familiare** | **Terapista** |
|---|---|---|
| **Schermata Home** | Prossimi appuntamenti | Dashboard con statistiche |
| **Calendario** | Vedi/annulla i propri appuntamenti | Vedi appuntamenti di tutti i pazienti assegnati |
| **Richieste** | Invia e traccia richieste documenti | Non disponibile |
| **Notifiche** | Riceve notifiche dal centro | Riceve notifiche sui pazienti |
| **Profilo** | Dati personali, info terapista | Dati professionali, specializzazione |
| **Funzioni esclusive** | Reclami/feedback | Segna assenze, aggiungi note, gestione presenze |

---

## 15. App per il Paziente/Familiare

### 15.1 Schermata Home

La home mostra i **prossimi 3 appuntamenti** programmati con:
- Data e ora
- Tipo di terapia
- Nome del terapista
- Stato dell'appuntamento

Trascinare verso il basso per **aggiornare** i dati.

**Se l'account gestisce piu pazienti** (account familiare), compare un **selettore paziente** per scegliere di quale familiare visualizzare le informazioni. Toccare il nome in alto per cambiare paziente.

### 15.2 Calendario

La scheda **Calendario** mostra:

**Vista Mensile:**
- I giorni con appuntamenti sono **evidenziati** con un pallino colorato
- Toccare un giorno per vederne il dettaglio

**Vista Giornaliera:**
- Lista degli appuntamenti del giorno selezionato
- Per ogni appuntamento: ora, durata, terapista, tipo di terapia, stato

### 15.3 Annullare un Appuntamento

1. Nel calendario, toccare l'appuntamento da annullare
2. Toccare **"Annulla Appuntamento"**
3. Inserire la **motivazione** dell'annullamento
4. Confermare

> **Importante:** L'annullamento e possibile solo con almeno **24 ore di anticipo** rispetto all'orario dell'appuntamento. Appuntamenti entro le 24 ore non possono essere annullati dall'app.

### 15.4 Richieste Documenti

La scheda **Richieste** permette di:

**Vedere le richieste esistenti:**
- Lista di tutte le richieste inviate
- Filtro per stato (Inviata, In Lavorazione, Stampato, Consegnato)
- Statistiche rapide (pendenti, in corso, completate)
- Codice colore per lo stato:
  - Giallo = Inviata
  - Blu = In Lavorazione
  - Arancione = Stampato
  - Verde = Consegnato

**Inviare una nuova richiesta:**

1. Toccare il pulsante **"Nuova Richiesta"** o l'icona "+"
2. Selezionare il **tipo di documento** dall'elenco (es. Certificato Medico, Relazione Terapeutica)
3. Aggiungere **note** facoltative
4. Toccare **"Invia Richiesta"**
5. La richiesta appare nella lista con stato "Inviata"

**Seguire lo stato:**
- Toccare una richiesta per vederne i dettagli
- Lo storico degli stati mostra ogni passaggio (quando e da chi e stato aggiornato)
- Quando il documento e pronto (stato "Stampato" o "Consegnato"), e possibile **scaricarlo** direttamente dall'app

### 15.5 Notifiche

La scheda **Notifiche** mostra tutti i messaggi ricevuti:

- Notifiche di **cambio stato richieste** documenti
- Notifiche di **scadenza piano terapeutico** (90, 60, 30, 15 giorni prima)
- **Comunicazioni** dal centro
- **Promemoria** appuntamenti

Le notifiche non lette sono evidenziate. Toccare una notifica per leggerla e segnarla come letta.

### 15.6 Profilo

La scheda **Profilo** mostra:
- Nome e cognome
- Email
- Codice fiscale
- Telefono
- Data di nascita
- Informazioni sul terapista assegnato

**Reclami/Feedback:**

1. Dal profilo, toccare **"Reclami"**
2. Inserire un **titolo** per il reclamo
3. Scrivere una **descrizione dettagliata**
4. Toccare **"Invia"**

### 15.7 Selezione Paziente (Account Multipli)

Se l'account e collegato a **piu pazienti** (es. genitore con piu figli):

- Un pulsante flottante o un selettore in alto permette di **cambiare paziente**
- Tutte le schermate (home, calendario, richieste) si aggiornano per il paziente selezionato
- Ogni paziente ha i propri appuntamenti, richieste e notifiche indipendenti

---

## 16. App per il Terapista

### 16.1 Dashboard

La home del terapista mostra una **dashboard** con:

- **Numero di pazienti** assegnati
- **Appuntamenti della settimana** - Conteggio totale
- **Ore lavorate questa settimana** - Somma delle sedute effettuate
- **Appuntamenti di oggi** - Quanti ne ha per la giornata

Le sedute sono raggruppate per **setting terapeutico**:
- Ambulatoriale (icona ospedale)
- Domiciliare (icona casa)
- Piccolo Gruppo (icona gruppo)
- Ambulatoriale + PG (icona combinata)
- Centro Diurno (icona sole)
- Semiconvitto (icona edificio)

### 16.2 I Miei Pazienti

La scheda **Pazienti** mostra l'elenco di tutti i pazienti assegnati al terapista:

- Nome e cognome
- Data di nascita
- Data dell'ultimo appuntamento
- Stato del paziente
- Informazioni di contatto

### 16.3 Agenda/Calendario

La scheda **Agenda** funziona come il calendario del paziente ma con funzionalita aggiuntive:

**Vista Mensile:**
- Giorni con appuntamenti evidenziati
- Toccare un giorno per vederne il dettaglio

**Vista Giornaliera:**
- Lista appuntamenti con nome paziente, ora, tipo di trattamento
- Per ogni appuntamento e possibile:

**Segnare un Paziente come Assente:**

1. Toccare l'appuntamento
2. Toccare **"Segna Assenza"**
3. Selezionare il **tipo di assenza:**
   - **Giustificata** - Il paziente ha una motivazione valida
   - **Non Giustificata** - Assenza senza preavviso
4. Selezionare la **motivazione:**
   - Paziente non si e presentato
   - Arrivo in ritardo
   - Non collaborativo
   - Problemi di salute
   - Problemi familiari
   - Rifiuto
   - Altro
5. Confermare

**Per appuntamenti di gruppo:** Se l'appuntamento e una seduta di gruppo, il sistema chiede se applicare l'assenza a **tutto il gruppo** o solo al singolo paziente.

**Aggiungere Note all'Appuntamento:**

1. Toccare l'appuntamento
2. Toccare **"Aggiungi Nota"**
3. Scrivere la nota
4. Salvare

### 16.4 Notifiche

Il terapista riceve notifiche relative ai propri pazienti:
- Aggiornamenti sugli appuntamenti
- Comunicazioni dal centro
- Avvisi importanti

### 16.5 Profilo

Il profilo del terapista mostra:
- Nome e cognome
- Email
- Specializzazione/i
- Numero di licenza professionale

Da qui e possibile effettuare il **Logout**.

---

## 17. Notifiche Push e Notifiche Bloccanti

### 17.1 Notifiche Push

L'app utilizza il servizio **OneSignal** per inviare notifiche push sul telefono anche quando l'app e chiusa.

**Cosa riceve l'utente:**
- Notifica sonora e visiva sul telefono
- Anteprima del messaggio
- Toccando la notifica si apre l'app alla schermata pertinente

**Tipi di notifiche push:**
- Promemoria appuntamenti
- Aggiornamenti stato richieste documenti
- Scadenza piano terapeutico
- Comunicazioni urgenti dal centro

> **Nota:** Per ricevere le notifiche push, assicurarsi che le notifiche siano **abilitate** nelle impostazioni del telefono per l'app TherapyCRM.

### 17.2 Notifiche Bloccanti

Alcune notifiche sono classificate come **bloccanti**: impediscono l'utilizzo dell'app finche non vengono lette e confermate.

**Come funzionano:**

1. All'apertura dell'app (o durante l'uso), appare una **schermata modale** che copre l'intera app
2. La notifica mostra un messaggio importante che richiede attenzione
3. L'utente **deve leggere** il contenuto della notifica
4. Toccare **"Ho letto e confermo"** per chiudere la notifica
5. L'app torna utilizzabile normalmente

**Quando vengono inviate:**
- Comunicazioni urgenti dal centro
- Avvisi importanti che richiedono presa visione obbligatoria
- Variazioni significative al piano terapeutico

> **Nota:** Il sistema controlla automaticamente ogni 30 secondi se ci sono nuove notifiche bloccanti. Non e possibile "saltare" queste notifiche.

### 17.3 Sicurezza dell'App

L'app include protezioni di sicurezza:
- **Protezione screenshot** - Non e possibile fare screenshot o registrare lo schermo dell'app (per proteggere i dati sensibili dei pazienti)
- **Sessione sicura** - Il token di accesso scade automaticamente e l'utente viene disconnesso se inattivo per troppo tempo
- **Logout automatico** - In caso di problemi di autenticazione, l'app effettua il logout automatico

---

# PARTE 3 - AUTOMATISMI DEL SISTEMA

---

## 18. Processi Automatici (Cron Job)

Il sistema esegue automaticamente diverse operazioni a intervalli regolari, senza necessita di intervento manuale.

### 18.1 Completamento Automatico Appuntamenti

**Frequenza:** Eseguito periodicamente durante la giornata

**Cosa fa:**
- Cerca tutti gli appuntamenti con stato "Programmato" la cui data e ora + durata sono gia passate
- Li marca automaticamente come **"Completato"**
- Processa 100 appuntamenti alla volta

**Esempio pratico:**
> Un appuntamento delle ore 10:00 della durata di 45 minuti resta "Programmato" fino alle 10:45. Dopo quell'ora, se nessuno lo ha aggiornato manualmente, il sistema lo segna come "Completato" alla prossima esecuzione del processo.

**Perche e utile:** Evita che appuntamenti gia avvenuti restino in stato "Programmato", mantenendo puliti il calendario e le statistiche.

### 18.2 Notifiche Scadenza Piano Terapeutico

**Frequenza:** Ogni giorno alle 2:00 di notte

**Cosa fa:**
1. Controlla tutti i piani terapeutici attivi
2. Calcola i giorni rimanenti alla scadenza
3. Invia notifiche automatiche ai **familiari con potesta genitoriale** alle seguenti soglie:

| Giorni alla scadenza | Notifica inviata |
|---------------------|-----------------|
| 90 giorni | "Il piano terapeutico di [Nome Paziente] scade tra 90 giorni (data: [Data])" |
| 60 giorni | "Il piano terapeutico di [Nome Paziente] scade tra 60 giorni (data: [Data])" |
| 30 giorni | "Il piano terapeutico di [Nome Paziente] scade tra 30 giorni (data: [Data])" |
| 15 giorni | "Il piano terapeutico di [Nome Paziente] scade tra 15 giorni (data: [Data])" |

**Importante:**
- Ogni notifica viene inviata **una sola volta** per soglia (nessun duplicato)
- Le notifiche arrivano sia nell'app che come notifica push sul telefono
- Il sistema tiene traccia di quali notifiche sono gia state inviate

**Esempio pratico:**
> Il piano terapeutico di Mario Rossi scade il 30 maggio 2026. Il 1 marzo (90 giorni prima) il sistema invia la prima notifica alla madre. Il 31 marzo (60 giorni prima) invia la seconda. E cosi via a 30 e 15 giorni.

### 18.3 Aggiornamento Stato Piani Terapeutici

**Frequenza:** Ogni giorno a mezzanotte (00:30)

**Cosa fa:**
- Controlla la data di fine di tutti i piani terapeutici attivi
- I piani con data fine passata vengono automaticamente marcati come **"Scaduto"**
- I piani archiviati vengono gestiti di conseguenza

**Esempio pratico:**
> Se un piano termina il 15 marzo 2026, alla mezzanotte del 16 marzo il sistema lo marca automaticamente come "Scaduto".

### 18.4 Pulizia Comunicazioni Vecchie

**Frequenza:** Periodica (configurabile)

**Cosa fa:**
- Rimuove le comunicazioni interne piu vecchie di **90 giorni**
- Mantiene pulito il database
- Preserva le notifiche importanti (legate a piani terapeutici)

### 18.5 Statistiche Comunicazioni

Il sistema genera periodicamente statistiche sull'invio delle comunicazioni:
- Numero di notifiche inviate
- Tasso di lettura
- Tempo medio di risposta

---

## 19. Flussi di Lavoro Automatizzati

### 19.1 Creazione Piano Terapeutico - Flusso Completo

```
1. Amministratore crea il piano
         |
2. Sistema genera gli appuntamenti ricorrenti
         |
3. Appuntamenti compaiono nel calendario
   (terapista e paziente li vedono nell'app)
         |
4. Man mano che gli appuntamenti avvengono:
   - Terapista segna presenze/assenze dall'app
   - Oppure il sistema li completa automaticamente
         |
5. A 90/60/30/15 giorni dalla scadenza:
   - Notifiche automatiche alla famiglia
         |
6. Alla scadenza:
   - Piano marcato come "Scaduto"
   - Amministratore valuta il rinnovo
```

### 19.2 Richiesta Documento - Flusso Completo

```
1. Paziente/Familiare invia richiesta dall'app
   --> Stato: INVIATA
   --> Notifica all'ufficio
         |
2. Amministratore prende in carico
   --> Stato: IN LAVORAZIONE
   --> Notifica al paziente nell'app
         |
3. Documento preparato e stampato
   --> Stato: STAMPATO
   --> Notifica al paziente
         |
4. Manager conferma la consegna
   --> Stato: CONSEGNATO
   --> Notifica finale al paziente
   --> Paziente puo scaricare il documento dall'app
```

### 19.3 Assenza Terapista - Flusso Completo

```
1. Registrazione assenza nel gestionale
   (tipo: ferie/malattia/permesso)
         |
2. Il sistema chiede se aggiornare gli appuntamenti
         |
3. Se confermato: tutti gli appuntamenti nel periodo
   vengono marcati come "Terapista Assente"
         |
4. (Facoltativo) Assegnazione terapista sostituto
         |
5. Il sostituto vede gli appuntamenti nella propria app
         |
6. Statistiche aggiornate automaticamente
```

### 19.4 Assenza Paziente - Flusso Completo

```
1. Paziente non si presenta alla seduta
         |
2. Terapista segna l'assenza dall'app:
   - Tipo: Giustificata / Non Giustificata
   - Motivazione
         |
3. Se seduta di gruppo: opzione per marcare
   tutto il gruppo come assente
         |
4. Sistema aggiorna le statistiche assenze
         |
5. (Se giustificata con recupero):
   possibilita di programmare una seduta di recupero
```

---

# APPENDICI

---

## Appendice A - Ruoli e Permessi

### Tabella Riepilogativa dei Ruoli

| Funzionalita | Amministratore | Manager | Coordinatore | Terapista |
|---|:---:|:---:|:---:|:---:|
| **Dashboard** | Completa | Completa | Limitata | Limitata |
| **Gestire Amministratori** | Si | No | No | No |
| **Gestire Manager** | Si | No | No | No |
| **Gestire Coordinatori** | Si | Si | No | No |
| **Gestire Terapisti** | Si | Si | No | No |
| **Creare Pazienti** | Si | Si | No | No |
| **Visualizzare Pazienti** | Tutti | Tutti | Propri terapisti | Propri |
| **Modificare Pazienti** | Si | Si | No | No |
| **Creare Piani Terapeutici** | Si | Si | No | No |
| **Visualizzare Piani** | Tutti | Tutti | Propri gruppi | Propri |
| **Modificare Piani** | Si | Si | No | No |
| **Calendario** | Tutto | Tutto | Proprio gruppo | Proprio |
| **Creare Assenze** | Si | Si | Si (proprio gruppo) | Si (proprie) |
| **Gestire Richieste Documenti** | Si (fino a Stampato) | Si (Consegnato) | No | No |
| **Visualizzare Statistiche** | Tutte | Tutte | Limitate | No |
| **Esportare Dati** | Si | Si | No | No |
| **Gruppi Coordinatori** | Si | Si | Visualizzare | No |
| **Notifiche** | Tutte | Tutte | Proprie | Proprie |
| **Ricerca Globale** | Si | Si | Limitata | Limitata |

### Dettaglio Ruoli

**Amministratore:** Accesso completo a tutte le funzionalita. Puo gestire l'intero sistema, creare e modificare utenti di qualsiasi ruolo, gestire i piani terapeutici e le configurazioni.

**Manager:** Gestisce le operazioni quotidiane. Puo gestire coordinatori e terapisti, creare piani terapeutici, gestire le richieste documenti (in particolare la fase finale di consegna), visualizzare le statistiche. Non puo gestire altri manager o amministratori.

**Coordinatore:** Supervisiona un gruppo di terapisti. Puo visualizzare (ma non modificare) i pazienti e i piani terapeutici dei terapisti del proprio gruppo. Puo gestire le assenze dei propri terapisti. Accesso limitato alle statistiche.

**Terapista:** Accesso essenziale per il proprio lavoro quotidiano. Vede il proprio calendario e i propri pazienti. Puo segnare le assenze e aggiungere note agli appuntamenti tramite l'app mobile. Permessi molto limitati sul gestionale web.

---

## Appendice B - Stati degli Appuntamenti

| Stato | Icona/Colore | Significato | Chi lo imposta |
|---|---|---|---|
| **Programmato** (scheduled) | Blu | Appuntamento confermato, non ancora avvenuto | Sistema (automatico) |
| **Completato** (completed) | Verde | Seduta effettuata regolarmente | Terapista o Sistema (automatico) |
| **Assenza Giustificata** (absent_justified) | Giallo | Paziente assente con motivazione valida | Terapista |
| **Assenza Non Giustificata** (absent_not_justified) | Rosso | Paziente assente senza preavviso | Terapista |
| **Cancellato** (cancelled) | Grigio | Annullato prima dell'esecuzione | Paziente (dall'app, con 24h anticipo) o Operatore |
| **Terapista Assente** (therapist_absent) | Arancione | Il terapista non era disponibile | Sistema (da registrazione assenza) o Operatore |

### Categorie di Appuntamento

| Categoria | Significato |
|---|---|
| **Regolare** | Appuntamento standard previsto dal piano terapeutico |
| **Recupero** | Seduta di recupero per un appuntamento saltato |
| **Avanzamento** | Seduta anticipata rispetto alla programmazione |
| **Extra** | Seduta aggiuntiva non prevista dal piano |
| **Compensazione** | Seduta di compensazione |

---

## Appendice C - Stati delle Richieste Documenti

| # | Stato | Colore App | Significato | Azione Successiva |
|---|---|---|---|---|
| 1 | **Inviata** | Giallo | Richiesta appena inviata dal paziente | Amministratore prende in carico |
| 2 | **In Lavorazione** | Blu | L'ufficio sta preparando il documento | Amministratore prepara e stampa |
| 3 | **Stampato** | Arancione | Documento pronto per la consegna | Manager conferma la consegna |
| 4 | **Consegnato** | Verde | Documento consegnato al paziente | Completato |

---

## Appendice D - Domande Frequenti (FAQ)

### Accesso e Password

**D: Non riesco ad accedere al sistema. Cosa faccio?**
R: Verificare di star usando l'email corretta. Se la password e dimenticata, usare la funzione "Password dimenticata" dalla schermata di login. Se il problema persiste, contattare l'amministratore del sistema.

**D: Come cambio la mia password?**
R: Nel gestionale: cliccare sul nome utente in alto a destra > Cambia Password. Nell'app: dal menu Profilo.

**D: L'app mi chiede di cambiare password al primo accesso. E normale?**
R: Si, per sicurezza il sistema richiede di impostare una password personale al primo accesso.

### Appuntamenti

**D: Come posso annullare un appuntamento dall'app?**
R: Andare nel Calendario, toccare l'appuntamento e selezionare "Annulla". Attenzione: e possibile annullare solo con almeno 24 ore di anticipo.

**D: Perche un appuntamento risulta "Completato" senza che nessuno lo abbia segnato?**
R: Il sistema segna automaticamente come "Completato" gli appuntamenti passati che risultano ancora "Programmato". Questo avviene tramite un processo automatico periodico.

**D: Come segno l'assenza di un paziente?**
R: Il terapista puo farlo dall'app mobile nella sezione Agenda, toccando l'appuntamento e selezionando "Segna Assenza". Dal gestionale, aprire l'appuntamento nel Calendario e cambiare lo stato.

### Piani Terapeutici

**D: Ricevo notifiche di scadenza piano. Cosa devo fare?**
R: Le notifiche informano che il piano terapeutico del paziente sta per scadere. Contattare il centro per valutare il rinnovo del piano. Le notifiche arrivano a 90, 60, 30 e 15 giorni dalla scadenza.

**D: Come rinnovo un piano terapeutico scaduto?**
R: Non e possibile modificare un piano scaduto. L'amministratore o il manager devono creare un nuovo piano terapeutico per il paziente.

**D: Gli appuntamenti vengono generati automaticamente?**
R: Si, quando viene creato un piano terapeutico con le terapie e le frequenze, il sistema genera automaticamente tutti gli appuntamenti ricorrenti per la durata del piano.

### Richieste Documenti

**D: Come richiedo un documento dall'app?**
R: Andare nella scheda "Richieste", toccare "Nuova Richiesta", selezionare il tipo di documento e inviare. Si ricevera una notifica ad ogni cambio di stato.

**D: Quanto tempo ci vuole per ricevere un documento?**
R: Il tempo stimato e indicato per ciascun tipo di documento (es. 3 giorni lavorativi per un certificato medico). Lo stato puo essere monitorato in tempo reale dall'app.

**D: Posso scaricare il documento dall'app?**
R: Si, quando lo stato passa a "Stampato" o "Consegnato", il documento diventa scaricabile dalla scheda dettaglio della richiesta.

### Notifiche

**D: Non ricevo le notifiche push sul telefono. Cosa faccio?**
R: Verificare che le notifiche siano abilitate nelle impostazioni del telefono per l'app TherapyCRM. Controllare anche che la connessione internet sia attiva.

**D: Cosa sono le notifiche bloccanti?**
R: Sono comunicazioni importanti che richiedono obbligatoriamente la lettura e la conferma prima di poter continuare a usare l'app. Appaiono come una schermata a tutto schermo che non puo essere chiusa senza confermare.

**D: Posso disattivare le notifiche?**
R: Le notifiche push possono essere disattivate dalle impostazioni del telefono, ma si consiglia di lasciarle attive per ricevere aggiornamenti importanti. Le notifiche bloccanti non possono essere disattivate.

### App Mobile

**D: L'app non mi permette di fare screenshot. E un bug?**
R: No, e una funzionalita di sicurezza per proteggere i dati sensibili dei pazienti. La cattura schermo e la registrazione video sono intenzionalmente bloccate.

**D: Gestisco piu figli. Come passo da un paziente all'altro?**
R: Se l'account e collegato a piu pazienti, toccare il selettore paziente (pulsante flottante o nome in alto) per scegliere il familiare di cui visualizzare le informazioni.

**D: Il terapista puo vedere le mie richieste documenti?**
R: No, i terapisti non hanno accesso alla sezione richieste documenti. Questa funzionalita e riservata agli operatori amministrativi del centro.

**D: Come terapista, posso modificare gli appuntamenti dall'app?**
R: Il terapista puo segnare le assenze e aggiungere note agli appuntamenti, ma non puo creare, modificare o cancellare appuntamenti. La gestione completa degli appuntamenti avviene tramite il gestionale web.

---

*Questo manuale si riferisce alla versione attuale del sistema TherapyCRM del Centro Medico San Luca. Per aggiornamenti o segnalazioni, contattare l'amministratore del sistema.*
