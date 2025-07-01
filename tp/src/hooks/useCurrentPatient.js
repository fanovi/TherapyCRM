import {useSelector, useDispatch} from 'react-redux';
import {useMemo, useCallback} from 'react';
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
  const switchPatient = useCallback(
    patientId => {
      dispatch(selectPatient(patientId));
    },
    [dispatch],
  );

  /**
   * Verifica se l'utente corrente ha l'autorità parentale per il paziente selezionato
   * @returns {boolean}
   */
  const hasParentalAuthority = useCallback(() => {
    return currentPatient?.has_parental_authority || false;
  }, [currentPatient?.has_parental_authority]);

  /**
   * Ottiene l'ID del paziente corrente
   * @returns {number|null}
   */
  const getCurrentPatientId = useCallback(() => {
    return (
      currentPatient?.patient_id || currentPatient?.account_patient_id || null
    );
  }, [currentPatient?.patient_id, currentPatient?.account_patient_id]);

  /**
   * Verifica se ci sono più pazienti disponibili
   * @returns {boolean}
   */
  const hasMultiplePatients = useCallback(() => {
    return patients && patients.length > 1;
  }, [patients]);

  /**
   * Ottiene la relazione dell'utente con il paziente corrente
   * @returns {string}
   */
  const getRelationship = useCallback(() => {
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
  }, [currentPatient?.relationship]);

  /**
   * Verifica se c'è un paziente selezionato
   * @returns {boolean}
   */
  const hasSelectedPatient = useCallback(() => {
    return !!currentPatient;
  }, [currentPatient]);

  // Valori memoizzati per evitare ricreazioni
  const patientId = useMemo(() => getCurrentPatientId(), [getCurrentPatientId]);
  const patientName = useMemo(
    () => currentPatient?.patient_name || '',
    [currentPatient?.patient_name],
  );
  const relationship = useMemo(() => getRelationship(), [getRelationship]);
  const multiplePatients = useMemo(
    () => hasMultiplePatients(),
    [hasMultiplePatients],
  );
  const parentalAuthority = useMemo(
    () => hasParentalAuthority(),
    [hasParentalAuthority],
  );

  return useMemo(
    () => ({
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
      patientId,
      patientName,
      relationship,
      multiplePatients,
      parentalAuthority,
    }),
    [
      user,
      patients,
      currentPatient,
      isLoading,
      error,
      switchPatient,
      hasParentalAuthority,
      getCurrentPatientId,
      hasMultiplePatients,
      getRelationship,
      hasSelectedPatient,
      patientId,
      patientName,
      relationship,
      multiplePatients,
      parentalAuthority,
    ],
  );
};
