# 🔧 Debug API Loop - Risoluzione

## Problema Identificato

Le chiamate API a `/TherapyCRM/api/requests` andavano in loop infinito a causa di:

1. **Hook `useCurrentPatient` non ottimizzato**: Restituiva sempre nuovi oggetti e funzioni ad ogni render
2. **Dipendenze `useEffect` instabili**: `hasSelectedPatient` era una funzione ricreata ad ogni render
3. **Funzioni non memoizzate**: `loadRequests` e `calculateStats` venivano ricreate ad ogni render

## Modifiche Applicate

### 1. PatientRequestsScreen.js

```javascript
// ❌ PRIMA - Causava loop infinito
const {currentPatient, patientId, hasSelectedPatient} = useCurrentPatient();
useEffect(() => {
  if (hasSelectedPatient && patientId) {
    // hasSelectedPatient è una funzione!
    loadRequests();
  }
}, [selectedFilter, patientId, hasSelectedPatient]); // Dipendenza instabile

// ✅ DOPO - Risolto
const {currentPatient, patientId} = useCurrentPatient();
const hasSelectedPatient = !!currentPatient; // Valore primitivo stabile

const loadRequests = useCallback(async () => {
  // ... logica
}, [patientId, selectedFilter, calculateStats]); // Dipendenze stabili

useEffect(() => {
  if (hasSelectedPatient && patientId) {
    loadRequests();
  }
}, [hasSelectedPatient, patientId, loadRequests]); // Tutte dipendenze stabili
```

### 2. useCurrentPatient.js Hook

```javascript
// ✅ Ottimizzato con useCallback e useMemo
const switchPatient = useCallback(
  patientId => {
    dispatch(selectPatient(patientId));
  },
  [dispatch],
);

const patientId = useMemo(() => getCurrentPatientId(), [getCurrentPatientId]);

return useMemo(
  () => ({
    // ... tutti i valori
  }),
  [
    /* dipendenze specifiche */
  ],
);
```

### 3. CreateRequestScreen.js

```javascript
// ✅ Stessa correzione applicata
const {currentPatient, patientId} = useCurrentPatient();
const hasSelectedPatient = !!currentPatient; // Valore primitivo
```

## Come Verificare la Risoluzione

### 1. Network Tab (Chrome DevTools)

```bash
# Aprire DevTools → Network → XHR
# Dovrebbe vedere:
# ✅ Una sola chiamata iniziale a /api/requests
# ❌ Non più chiamate ripetute ogni secondo
```

### 2. Console Logs

```javascript
// Nel PatientRequestsScreen, aggiungere temporaneamente:
console.log('🔄 loadRequests chiamata:', new Date().toISOString());

// Dovrebbe vedere:
// ✅ Un solo log al caricamento iniziale
// ✅ Un log quando cambia filtro
// ❌ Non più log continui
```

### 3. React DevTools Profiler

```bash
# Aprire React DevTools → Profiler
# Registrare per 10 secondi
# Dovrebbe vedere:
# ✅ Render stabili senza re-render continui
# ✅ useEffect non in loop
```

## Test di Regressione

### Scenario 1: Caricamento Iniziale

1. Aprire app
2. Navigare a PatientRequestsScreen
3. **Atteso**: Una sola chiamata API
4. **Verificare**: Network tab mostra 1 richiesta

### Scenario 2: Cambio Filtro

1. Selezionare filtro diverso (es. "In Attesa")
2. **Atteso**: Una nuova chiamata API
3. **Verificare**: Network tab mostra 1 nuova richiesta

### Scenario 3: Refresh

1. Pull-to-refresh
2. **Atteso**: Una chiamata API per il refresh
3. **Verificare**: Network tab mostra 1 richiesta di refresh

### Scenario 4: Inattività

1. Lasciare schermo aperto per 30 secondi
2. **Atteso**: Nessuna chiamata API automatica
3. **Verificare**: Network tab non mostra nuove richieste

## Metriche di Performance

### Prima (Con Loop)

- 🔴 **API Calls**: ~60 chiamate/minuto
- 🔴 **Re-renders**: Continui
- 🔴 **Battery Usage**: Alto
- 🔴 **Network Usage**: Eccessivo

### Dopo (Risolto)

- ✅ **API Calls**: 1 chiamata iniziale + on-demand
- ✅ **Re-renders**: Solo quando necessario
- ✅ **Battery Usage**: Normale
- ✅ **Network Usage**: Ottimizzato

## Note Tecniche

### Cause Comuni di Loop in React Native

1. **Dipendenze useEffect instabili** (oggetti/funzioni ricreate)
2. **Hook non ottimizzati** (senza useCallback/useMemo)
3. **State updates in render** (setState durante render)
4. **Props drilling** con oggetti sempre nuovi

### Best Practices Applicate

1. **useCallback** per funzioni stabili
2. **useMemo** per valori computati
3. **Dipendenze primitive** negli useEffect
4. **Memoizzazione hook personalizzati**

### Pattern da Evitare

```javascript
// ❌ MAI fare questo
useEffect(() => {
  // logica
}, [someObject]); // Oggetto ricreato ogni render

// ❌ MAI fare questo
const {someFunction} = useCustomHook();
useEffect(() => {
  someFunction();
}, [someFunction]); // Funzione ricreata ogni render

// ✅ Fare sempre questo
const {someValue} = useCustomHook();
const stableFunction = useCallback(() => {
  // logica
}, [someValue]);

useEffect(() => {
  stableFunction();
}, [stableFunction]); // Funzione stabile
```
