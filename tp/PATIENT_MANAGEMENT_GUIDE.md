# Guida al Sistema di Gestione Pazienti

Questa guida spiega come utilizzare il nuovo sistema di gestione pazienti nell'app Therapy CRM.

## Panoramica

Il sistema permette agli utenti di:

- Visualizzare il proprio nome e quello del paziente attivo
- Gestire più pazienti associati al proprio account
- Switchare tra diversi pazienti
- Mantenere la selezione del paziente tra le sessioni

## Struttura della Risposta di Login

```json
{
  "success": true,
  "message": "Login effettuato con successo",
  "data": {
    "user": {
      "id": 27,
      "email": "paziente@test.it",
      "nome": "Anna",
      "cognome": "Bianchi",
      "user_type": "paziente",
      "patients": [
        {
          "patient_id": 1,
          "patient_name": "Giulia Bianchi",
          "relationship": "parent",
          "has_parental_authority": true,
          "account_patient_id": 1
        }
      ]
    },
    "access_token": "...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

## Componenti Principali

### 1. PatientSlice (Redux)

Gestisce lo stato dei pazienti nell'app:

- `patients`: Array dei pazienti associati
- `currentPatient`: Paziente attualmente selezionato
- `isLoading`: Stato di caricamento
- `error`: Eventuali errori

### 2. PatientSelector Component

Componente UI che mostra:

- Nome dell'utente loggato ("Benvenuto, Nome Cognome")
- Nome del paziente attivo
- Modal per selezionare un paziente diverso (se più di uno)

### 3. usePatientLogin Hook

Hook personalizzato che gestisce:

- Login con impostazione automatica dei pazienti
- Logout con pulizia dei dati pazienti
- Accesso ai dati utente e paziente corrente

## Come Utilizzare

### 1. Nel tuo componente di Login

```javascript
import {usePatientLogin} from '../hooks/usePatientLogin';

const LoginScreen = () => {
  const {loginUser, isLoading, error} = usePatientLogin();

  const handleLogin = async credentials => {
    try {
      await loginUser(credentials);
      // Il login gestirà automaticamente i pazienti
    } catch (error) {
      console.error('Login failed:', error);
    }
  };

  // ... resto del componente
};
```

### 2. Nelle tue schermate principali

```javascript
import PatientSelector from '../components/PatientSelector';
import {useSelector} from 'react-redux';

const YourScreen = () => {
  const {user} = useSelector(state => state.auth);
  const {currentPatient} = useSelector(state => state.patient);

  return (
    <View>
      <PatientSelector />

      {currentPatient && (
        <Text>Visualizzando i dati di: {currentPatient.patient_name}</Text>
      )}

      {/* Resto del contenuto specifico del paziente */}
    </View>
  );
};
```

### 3. Per switchare tra pazienti

```javascript
import {useDispatch} from 'react-redux';
import {selectPatient} from '../slices/patientSlice';

const switchToPatient = patientId => {
  dispatch(selectPatient(patientId));
};
```

## Redux State Structure

```javascript
// Store structure
{
  auth: {
    user: {
      id: "27",
      firstName: "Anna",
      lastName: "Bianchi",
      patients: [...]
    },
    // ... altri campi auth
  },
  patient: {
    patients: [
      {
        patient_id: 1,
        patient_name: "Giulia Bianchi",
        relationship: "parent",
        has_parental_authority: true,
        account_patient_id: 1
      }
    ],
    currentPatient: {
      patient_id: 1,
      patient_name: "Giulia Bianchi",
      // ...
    }
  }
}
```

## Configurazione del Store

Assicurati che lo store includa il patientSlice:

```javascript
// store/index.js
import patientReducer from '../slices/patientSlice';

export const store = configureStore({
  reducer: {
    auth: persistedAuthReducer,
    patient: persistedPatientReducer, // Aggiungi questo
    // ... altri reducer
  },
});
```

## Persistenza

I dati dei pazienti e la selezione corrente vengono automaticamente salvati e ripristinati tra le sessioni dell'app grazie alla configurazione di redux-persist.

## Casi d'Uso

### Utente con un solo paziente

- Il paziente viene selezionato automaticamente al login
- Il PatientSelector mostra solo le informazioni senza possibilità di cambio

### Utente con più pazienti

- Al login viene selezionato il primo paziente (o quello precedentemente selezionato)
- Il PatientSelector permette di aprire un modal per cambiare paziente
- La selezione viene mantenuta tra le sessioni

### Logout

- Tutti i dati dei pazienti vengono cancellati automaticamente
- Lo stato viene ripristinato ai valori iniziali

## Note Tecniche

- Il sistema è completamente compatibile con l'attuale architettura Redux
- La persistenza è configurata per salvare solo i dati necessari
- Il PatientSelector è un componente riutilizzabile in qualsiasi schermata
- Il sistema gestisce automaticamente i casi edge (nessun paziente, un paziente, più pazienti)
