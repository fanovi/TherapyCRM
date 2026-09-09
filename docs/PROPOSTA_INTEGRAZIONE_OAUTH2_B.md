# Proposta di integrazione con la piattaforma esterna "B"

> **Documento di proposta.** Descrive come proponiamo di collegare la piattaforma **B** al nostro sistema: chi possiede quale dato, come ci si autentica, quali chiamate esistono e cosa serve da entrambe le parti. Il dettaglio tecnico completo (campi, payload, codici di errore) è nell'analisi tecnica allegata e non viene ripetuto qui.

**Versione:** 1.0 · **Data:** 2026-07-14 · **Stato:** da validare con il gestore di B

---

## 1. In sintesi

La piattaforma B e il nostro sistema si scambiano dati **senza intervento umano**: due server che si parlano direttamente.

La proposta si regge su tre scelte:

1. **B è la fonte di verità per le anagrafiche.** Pazienti, terapisti e piani terapeutici nascono su B e vengono inviati a noi. Sul nostro gestionale diventano di sola lettura.
2. **Gli appuntamenti sono nostri.** Li creiamo e li gestiamo noi; B li legge. Quando un appuntamento si chiude (visita effettuata o paziente assente) avvisiamo B automaticamente.
3. **L'integrazione è un modulo isolato.** Credenziali, endpoint e chiavi di firma dedicati, separati da qualsiasi altra interfaccia del sistema.

Ne consegue una modifica al gestionale interno: **la creazione e la modifica di pazienti, terapisti e piani vengono rimosse dall'interfaccia**, perché quei dati arrivano da B.

---

## 2. Chi possiede quale dato

È il punto da validare per primo: tutto il resto discende da qui.

| Dato | Fonte | Direzione | Cosa succede |
| --- | --- | --- | --- |
| **Pazienti** | B | B → noi | B crea, aggiorna, disattiva. Noi leggiamo. |
| **Terapisti** | B | B → noi | B crea, aggiorna, disattiva. **Le credenziali di accesso le generiamo noi.** |
| **Piani terapeutici** | B | B → noi | B crea, aggiorna, chiude. Include le righe di terapia. |
| **Appuntamenti** | Noi | noi → B | B li legge: calendario per terapista, elenco per piano. |
| **Chiusura appuntamento** | Noi | noi → B (push) | Avvisiamo B a ogni visita effettuata o assenza del paziente. |
| **Dizionari** (regimi, trattamenti, setting, specializzazioni, distretti) | Noi | noi → B | B li legge per sapere quali codici usare. |

**Restano fuori dall'integrazione** (gestione interamente interna): cicli di sedute private, ricorrenze, sostituzioni, e le assenze del terapista per ferie o malattia.

---

## 3. Come B si autentica

Proponiamo **OAuth2 con grant Client Credentials**, lo standard per il dialogo server-to-server dove non c'è un utente che fa login.

Il meccanismo in quattro passi:

1. Consegniamo a B, una tantum e su canale sicuro, un `client_id` e un `client_secret`.
2. B li presenta al nostro endpoint token e riceve un **access token** valido **un'ora**.
3. B allega il token a ogni chiamata; noi lo verifichiamo di volta in volta.
4. Alla scadenza, B ne chiede semplicemente un altro.

Non esiste un refresh token: questo tipo di grant non lo prevede, perché non c'è nessuna sessione utente da mantenere aperta.

**Cosa proponiamo nel dettaglio:**

- Token in formato **JWT firmato RS256**, con una coppia di chiavi dedicata solo a questa integrazione.
- Ogni token emesso è **revocabile** prima della scadenza, se serve tagliare l'accesso in fretta.
- **Permessi separati per ambito** (`scope`), così B ottiene solo quello che gli serve: scrittura pazienti, scrittura terapisti, scrittura piani, lettura appuntamenti, lettura dizionari.

---

## 4. Le chiamate che mettiamo a disposizione

Tutte le chiamate sono in JSON, sotto un percorso versionato `/v1`, con il token in header.

**B scrive verso di noi:**

| Cosa | Chiamata |
| --- | --- |
| Crea o aggiorna un paziente | `POST /v1/patients` |
| Disattiva un paziente | `POST /v1/patients/{id}/deactivate` |
| Crea o aggiorna un terapista | `POST /v1/therapists` |
| Disattiva un terapista | `POST /v1/therapists/{id}/deactivate` |
| Crea o aggiorna un piano (con le sue terapie) | `POST /v1/therapeutic-plans` |
| Chiude un piano | `POST /v1/therapeutic-plans/{id}/deactivate` |

Le create e le update sono la **stessa chiamata**: se il record esiste lo aggiorniamo, altrimenti lo creiamo. B non deve tenere il conto di cosa ha già inviato.

**B legge da noi:**

| Cosa | Chiamata |
| --- | --- |
| Calendario di un terapista | `GET /v1/therapists/{id}/appointments?from=…&to=…` |
| Appuntamenti di un piano | `GET /v1/therapeutic-plans/{id}/appointments` |
| Dizionari | `GET /v1/reference/{regimes\|treatment-types\|settings\|specializations\|districts}` |

**Noi notifichiamo B:**

Quando un appuntamento si chiude inviamo una notifica a un endpoint che B ci indica. Copre tre casi: visita effettuata, assenza giustificata del paziente, assenza non giustificata. L'assenza del *terapista* non genera notifica, perché non è una chiusura dal punto di vista del paziente.

Se B non risponde riproviamo con intervalli crescenti (5 tentativi nell'arco di alcune ore), poi mettiamo l'evento in coda d'errore e segnaliamo. Ogni notifica è firmata e porta un identificativo univoco, così B può ignorare eventuali doppioni.

---

## 5. Come evitiamo i duplicati

Il rischio principale di ogni integrazione è ritrovarsi lo stesso paziente due volte. Proponiamo due livelli di difesa.

**Primo livello — l'identificativo di B.** Su ogni record salviamo l'ID che quel record ha *sul sistema di B*. È il legame stabile tra i due sistemi: finché B ce lo manda, sappiamo sempre di chi stiamo parlando.

**Secondo livello — la chiave naturale.** Al primo carico, o se B non ha ancora un ID da darci, riconosciamo il record da un dato che lo identifica di per sé:

| Entità | Riconosciuta da |
| --- | --- |
| Paziente | codice fiscale |
| Terapista | codice fiscale (in subordine, email) |
| Piano | numero di protocollo + distretto + regime |

La logica applicata a ogni scrittura è: cerco per ID di B; se non lo trovo cerco per chiave naturale e in caso lo aggancio; se non trovo nulla creo. Nella risposta diciamo sempre a B **come** abbiamo riconosciuto il record e se l'abbiamo creato — così anche una chiamata ripetuta due volte non produce un doppione.

---

## 6. Cosa chiediamo a B

Tre cose, in ordine di importanza:

1. **Rispettare l'ordine di sincronizzazione:** prima pazienti e terapisti, poi i piani. Un piano che cita un paziente che non conosciamo ancora viene rifiutato con un messaggio esplicito che dice quale riferimento manca; B sincronizza il paziente e ripete. Non inventiamo pazienti "fantasma" a partire da un piano: su dati clinici è una scorciatoia che non vogliamo prendere.
2. **Usare i nostri codici per i dizionari**, leggendoli dagli endpoint di riferimento. Cambiano di rado, conviene tenerli in cache.
3. **Indicarci l'endpoint** su cui ricevere le notifiche di chiusura appuntamento, e gli **IP** da cui B ci chiamerà (li mettiamo in allowlist come difesa aggiuntiva oltre a OAuth2).

Per il **primo carico massivo** si usano le stesse chiamate, nello stesso ordine. Se i volumi lo richiedono mettiamo a disposizione una variante a lotti (fino a 200 record per chiamata) che riporta l'esito record per record: un errore singolo non blocca il lotto.

---

_Fine documento — v1.0._
