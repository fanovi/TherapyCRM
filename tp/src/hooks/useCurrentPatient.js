import {useSelector, useDispatch} from 'react-redux';
import {selectPatient} from '../slices/patientSlice';

/**
 * Hook per gestire il paziente attualmente selezionato
 */
export const useCurrentPatient = () => {
  const dispatch = useDispatch();
  const {patients, currentPatient, isLoading, error} = useSelector(
    state => state.patient,
  );
  const {user} = useSelector(state => state.auth);

  /**
   * Cambia il paziente attualmente selezionato
   * @param {number} patientId - ID del paziente da selezionare
   */
  const switchPatient = patientId => {
    dispatch(selectPatient(patientId));
  };

  /**
   * Verifica se l'utente corrente ha l'autorità parentale per il paziente selezionato
   * @returns {boolean}
   */
  const hasParentalAuthority = () => {
    return currentPatient?.has_parental_authority || false;
  };

  /**
   * Ottiene l'ID del paziente corrente
   * @returns {number|null}
   */
  const getCurrentPatientId = () => {
    return (
      currentPatient?.patient_id || currentPatient?.account_patient_id || null
    );
  };

  /**
   * Verifica se ci sono più pazienti disponibili
   * @returns {boolean}
   */
  const hasMultiplePatients = () => {
    return patients && patients.length > 1;
  };

  /**
   * Ottiene la relazione dell'utente con il paziente corrente
   * @returns {string}
   */
  const getRelationship = () => {
    if (!currentPatient?.relationship) return '';

    const relationshipMap = {
      parent: 'Genitore',
      guardian: 'Tutore',
      self: 'Se stesso',
    };

    return (
      relationshipMap[currentPatient.relationship] ||
      currentPatient.relationship
    );
  };

  /**
   * Verifica se c'è un paziente selezionato
   * @returns {boolean}
   */
  const hasSelectedPatient = () => {
    return !!currentPatient;
  };

  return {
    // Dati
    user,
    patients,
    currentPatient,
    isLoading,
    error,

    // Funzioni
    switchPatient,
    hasParentalAuthority,
    getCurrentPatientId,
    hasMultiplePatients,
    getRelationship,
    hasSelectedPatient,

    // Dati computati per convenience
    patientId: getCurrentPatientId(),
    patientName: currentPatient?.patient_name || '',
    relationship: getRelationship(),
    multiplePatients: hasMultiplePatients(),
    parentalAuthority: hasParentalAuthority(),
  };
};
