import {createSlice} from '@reduxjs/toolkit';

const initialState = {
  patients: [], // Lista dei pazienti associati all'utente
  currentPatient: null, // Paziente attualmente selezionato
  isLoading: false,
  error: null,
};

const patientSlice = createSlice({
  name: 'patient',
  initialState,
  reducers: {
    // Imposta la lista dei pazienti (dal login)
    setPatientsFromLogin: (state, action) => {
      state.patients = action.payload;
      // Se c'è solo un paziente, lo seleziona automaticamente
      if (action.payload.length === 1) {
        state.currentPatient = action.payload[0];
      }
      // Se non c'è un paziente corrente e ci sono pazienti, seleziona il primo
      else if (action.payload.length > 0 && !state.currentPatient) {
        state.currentPatient = action.payload[0];
      }
    },

    // Seleziona un paziente specifico
    selectPatient: (state, action) => {
      const patient = state.patients.find(
        p =>
          p.patient_id === action.payload ||
          p.account_patient_id === action.payload,
      );
      if (patient) {
        state.currentPatient = patient;
      }
    },

    // Reset dei dati pazienti (logout)
    resetPatients: state => {
      state.patients = [];
      state.currentPatient = null;
      state.isLoading = false;
      state.error = null;
    },

    // Caricamento
    setLoading: (state, action) => {
      state.isLoading = action.payload;
    },

    // Errore
    setError: (state, action) => {
      state.error = action.payload;
    },

    // Pulisci errore
    clearError: state => {
      state.error = null;
    },
  },
});

export const {
  setPatientsFromLogin,
  selectPatient,
  resetPatients,
  setLoading,
  setError,
  clearError,
} = patientSlice.actions;

export default patientSlice.reducer;
