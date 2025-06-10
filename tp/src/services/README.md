# Authentication Service Integration

Questo servizio gestisce l'autenticazione con l'API backend del CMS Terapisti.

## Endpoint API

**Base URL:** `http://127.0.0.1:3307/cms-terapisti/TherapyCRM/api`

### Endpoints Disponibili

- **POST /auth/login** - Autenticazione utente
- **POST /auth/logout** - Logout utente  
- **GET /auth/verify** - Verifica validità token

## Utilizzo

### Login
```javascript
import { authService } from '../services/authService';

const credentials = {
  email: 'paziente1@example.com',
  password: 'password123'
};

try {
  const result = await authService.login(credentials);
  console.log('Login successful:', result);
  // result.token - Token di accesso
  // result.user - Dati utente
} catch (error) {
  console.error('Login failed:', error.message);
}
```

### Validazione Token
```javascript
const isValid = await authService.validateToken(token);
if (isValid) {
  console.log('Token is valid:', isValid);
}
```

### Logout
```javascript
await authService.logout();
```

## Struttura Dati

### Utente Paziente
```javascript
{
  id: "1",
  email: "paziente1@example.com",
  role: "patient",
  firstName: "Marco",
  lastName: "Rossi",
  fullName: "Marco Rossi",
  codiceFiscale: "RSSMRC80A01H501Z",
  telefono: "123456789",
  dataNascita: "1980-01-01",
  indirizzo: "Via Roma 123, Milano",
  status: "attivo"
}
```

### Utente Terapista
```javascript
{
  id: "1",
  email: "terapista1@example.com", 
  role: "therapist",
  firstName: "Dr. Giuseppe",
  lastName: "Verdi",
  fullName: "Dr. Giuseppe Verdi",
  specializzazione: "Fisioterapia",
  numeroAlbo: "FT12345",
  // ... altri campi comuni
}
```

## Redux Integration

Il servizio è integrato con Redux attraverso `authSlice.js`:

```javascript
import { useDispatch } from 'react-redux';
import { loginUser } from '../store/authSlice';

const dispatch = useDispatch();

// Login
dispatch(loginUser({ email, password }));

// Logout  
dispatch(logoutUser());
```

## Test

Per testare l'integrazione API:

```javascript
import { testApiConnection } from '../utils/testApi';

const result = await testApiConnection();
console.log(result);
```

## Credenziali di Test

### Pazienti
- **Email:** paziente1@example.com | **Password:** password123
- **Email:** paziente2@example.com | **Password:** password456

### Terapisti  
- **Email:** terapista1@example.com | **Password:** password789
- **Email:** terapista2@example.com | **Password:** password000

## Note Tecniche

- Il servizio usa `FormData` per l'invio delle credenziali
- I token sono salvati automaticamente in `react-native-keychain`
- La gestione errori è centralizzata con messaggi localizzati
- Supporto per CORS è abilitato lato server 