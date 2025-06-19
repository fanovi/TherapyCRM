import {useDispatch, useSelector} from 'react-redux';
import {
  loginUser as loginUserAction,
  logoutUser as logoutUserAction,
} from '../store/authSlice';
import {setPatientsFromLogin, resetPatients} from '../slices/patientSlice';

export const usePatientLogin = () => {
  const dispatch = useDispatch();
  const {isLoading, error, isAuthenticated, user} = useSelector(
    state => state.auth,
  );
  const {patients, currentPatient} = useSelector(state => state.patient);

  const loginUser = async credentials => {
    try {
      // Esegui il login
      const result = await dispatch(loginUserAction(credentials));

      if (result.type === 'auth/login/fulfilled') {
        // Se il login è riuscito e ci sono pazienti, impostali nel patient store
        const userData = result.payload.user;
        if (userData.patients && userData.patients.length > 0) {
          dispatch(setPatientsFromLogin(userData.patients));
        }
      }

      return result;
    } catch (error) {
      console.error('Errore durante il login:', error);
      throw error;
    }
  };

  const logout = async () => {
    try {
      // Reset dei pazienti prima del logout
      dispatch(resetPatients());
      // Esegui il logout
      await dispatch(logoutUserAction());
    } catch (error) {
      console.error('Errore durante il logout:', error);
    }
  };

  return {
    loginUser,
    logout,
    isLoading,
    error,
    isAuthenticated,
    user,
    patients,
    currentPatient,
  };
};
