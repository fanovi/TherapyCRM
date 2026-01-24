import apiClient from '../services/axiosConfig';

/**
 * Invia un nuovo reclamo
 * @param {Object} complaintData
 * @param {string} complaintData.title - Titolo del reclamo
 * @param {string} complaintData.description - Descrizione dettagliata del reclamo
 * @param {number} complaintData.account_id - ID dell'account che sta aprendo il reclamo
 * @param {number} complaintData.patient_id - ID del paziente di riferimento
 * @returns {Promise<Object>}
 */
export const submitComplaint = async complaintData => {
  try {
    const response = await apiClient.post('/complaint/create', complaintData);
    return response.data;
  } catch (error) {
    console.error('Errore invio reclamo:', error);
    throw error;
  }
};

/**
 * Ottiene la lista dei reclami per un paziente
 * @param {number} patientId - ID del paziente
 * @returns {Promise<Object>}
 */
export const getComplaints = async patientId => {
  try {
    const response = await apiClient.get('/complaint/list', {
      params: {patient_id: patientId},
    });
    return response.data;
  } catch (error) {
    console.error('Errore recupero reclami:', error);
    throw error;
  }
};
