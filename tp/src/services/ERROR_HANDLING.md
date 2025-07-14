# Gestione Errori Migliorata

## Problema Risolto

Prima dell'aggiornamento, l'interceptor di `axiosConfig.js` forzava il logout automatico per **QUALSIASI** errore 401, anche quando non era necessario. Questo causava logout indesiderati durante errori temporanei o di caricamento dati.

## Nuova Logica di Gestione

### 1. Interceptor Response (`axiosConfig.js`)

#### Errori 401 - Analisi Intelligente

```javascript
if (response?.status === 401) {
  const errorMessage = response.data?.error || response.data?.message || '';
  const isAuthError = errorMessage.toLowerCase().includes('token') ||
                     errorMessage.toLowerCase().includes('unauthorized') ||
                     errorMessage.toLowerCase().includes('expired') ||
                     errorMessage.toLowerCase().includes('invalid');

  if (isAuthError) {
    // VERO errore di autenticazione → Logout automatico
    performAutoLogout('Errore 401 di autenticazione dal server');
    return Promise.reject({ type: 'AUTH_ERROR', ... });
  } else {
    // Errore 401 ma non di autenticazione → NO logout
    return Promise.reject({ type: 'PERMISSION_ERROR', ... });
  }
}
```

#### Tipi di Errore Restituiti

- **`AUTH_ERROR`**: Errore di autenticazione reale → Logout automatico
- **`PERMISSION_ERROR`**: Errore 401 ma non di autenticazione → NO logout
- **`NETWORK_ERROR`**: Errore di rete → NO logout
- **`SERVER_ERROR`**: Errore del server → NO logout

### 2. API Calendar (`calendar.js`)

#### Gestione Errori Specifica

```javascript
catch (error) {
  if (error.type === 'AUTH_ERROR') {
    // Lascia che l'interceptor gestisca il logout
    throw error;
  } else if (error.type === 'NETWORK_ERROR') {
    // Trasforma in errore calendario
    throw { type: 'CALENDAR_ERROR', message: 'Errore di connessione...' };
  } else if (error.type === 'SERVER_ERROR') {
    // Trasforma in errore calendario
    throw { type: 'CALENDAR_ERROR', message: 'Errore del server...' };
  } else {
    // Errore generico
    throw { type: 'CALENDAR_ERROR', message: 'Errore imprevisto...' };
  }
}
```

### 3. UI Screen (`PatientCalendarScreen.js`)

#### Gestione Errori Differenziata

```javascript
catch (error) {
  if (error.type === 'AUTH_ERROR') {
    // Logout automatico in corso - non mostrare alert
    console.log('Errore di autenticazione, logout automatico in corso...');
  } else if (error.type === 'PERMISSION_ERROR') {
    // Errore di permessi - mostra alert ma NO logout
    Alert.alert('Accesso Negato', error.message);
  } else if (error.type === 'CALENDAR_ERROR') {
    // Errore specifico calendario - mostra alert ma NO logout
    Alert.alert('Errore Calendario', error.message);
  } else if (error.type === 'NETWORK_ERROR') {
    // Errore di rete - messaggio specifico
    Alert.alert('Errore di Connessione', 'Verifica la tua connessione...');
  } else {
    // Errore generico
    Alert.alert('Errore', 'Impossibile caricare gli appuntamenti...');
  }
}
```

## Scenari di Utilizzo

### ✅ Scenario 1: Token Scaduto

```
1. API call → 401 con message "Token expired"
2. Interceptor → isAuthError = true
3. Interceptor → performAutoLogout()
4. UI → Redirect al login (NO alert)
```

### ✅ Scenario 2: Permessi Insufficienti

```
1. API call → 401 con message "Insufficient permissions"
2. Interceptor → isAuthError = false
3. Interceptor → return PERMISSION_ERROR
4. UI → Alert "Accesso Negato" (NO logout)
```

### ✅ Scenario 3: Errore di Rete

```
1. API call → Network timeout
2. Interceptor → return NETWORK_ERROR
3. Calendar API → transform to CALENDAR_ERROR
4. UI → Alert "Errore di Connessione" (NO logout)
```

### ✅ Scenario 4: Errore Server (500)

```
1. API call → 500 Internal Server Error
2. Interceptor → return SERVER_ERROR
3. Calendar API → transform to CALENDAR_ERROR
4. UI → Alert "Errore del server" (NO logout)
```

## Benefici

### 1. **UX Migliorata**

- Nessun logout indesiderato per errori temporanei
- Messaggi di errore più specifici e utili
- Possibilità di retry senza rifare il login

### 2. **Robustezza**

- Gestione differenziata per tipo di errore
- Logging dettagliato per debugging
- Fallback graceful per errori non previsti

### 3. **Manutenibilità**

- Logica centralizzata nell'interceptor
- Tipi di errore consistenti in tutta l'app
- Facile aggiunta di nuovi tipi di errore

## Pattern di Implementazione

### Per Nuove API

```javascript
// 1. Nella funzione API
catch (error) {
  if (error.type === 'AUTH_ERROR') {
    throw error; // Lascia gestire all'interceptor
  } else {
    throw { type: 'YOUR_API_ERROR', message: '...', originalError: error };
  }
}

// 2. Nella UI
catch (error) {
  if (error.type === 'AUTH_ERROR') {
    // Logout automatico - non fare nulla
  } else if (error.type === 'YOUR_API_ERROR') {
    Alert.alert('Errore Specifico', error.message);
  } else {
    Alert.alert('Errore', 'Messaggio generico');
  }
}
```

## Testing

### Test Cases

1. **Token valido** → Successo
2. **Token scaduto** → Logout automatico
3. **Permessi insufficienti** → Alert senza logout
4. **Errore di rete** → Alert senza logout
5. **Server down** → Alert senza logout

### Debug Logging

```javascript
// Interceptor
console.log('🔐 Errore di autenticazione confermato - eseguendo logout');
console.log('⚠️ Errore 401 ma non di autenticazione - non forzando logout');

// API
console.error('❌ Errore recuperando appuntamenti paziente:', error);

// UI
console.log('Errore di autenticazione, logout automatico in corso...');
console.warn('Errore non critico per date marcate:', error.message);
```

Questa implementazione risolve il problema del logout indesiderato mantenendo la sicurezza per i veri errori di autenticazione.
