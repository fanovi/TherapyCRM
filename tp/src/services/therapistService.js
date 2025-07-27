import {apiClient} from './axiosConfig';

export const therapistService = {
  /**
   * Recupera i dati della dashboard del terapista
   */
  async getDashboardData() {
    try {
      const response = await apiClient.post(
        '/calendar/therapist-dashboard',
        {},
      );
      return response.data;
    } catch (error) {
      console.error('Errore recupero dashboard terapista:', error);
      throw error;
    }
  },

  /**
   * Recupera gli appuntamenti del terapista per una data specifica
   */
  async getAppointments(date) {
    try {
      const response = await apiClient.post(
        '/calendar/therapist-appointments',
        {
          date: date,
        },
      );
      return response.data;
    } catch (error) {
      console.error('Errore recupero appuntamenti terapista:', error);
      throw error;
    }
  },

  /**
   * Recupera gli appuntamenti del terapista per un mese
   */
  async getMonthlyAppointments(month, year, therapistId) {
    try {
      const response = await apiClient.get(
        `/therapeutic-plan-manager/get-therapist-appointments?therapistId=${therapistId}&month=${month}&year=${year}`,
      );
      return response.data;
    } catch (error) {
      console.error('Errore recupero appuntamenti mensili terapista:', error);
      throw error;
    }
  },
};
