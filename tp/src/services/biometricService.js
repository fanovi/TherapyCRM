import * as Keychain from 'react-native-keychain';
import AsyncStorage from '@react-native-async-storage/async-storage';
import {Platform} from 'react-native';
import {apiClient} from './axiosConfig';
import {API_CONFIG} from '../config/api';

const KEYCHAIN_SERVICE = 'cms-terapisti-biometric';
const STORAGE_KEY_REGISTERED = 'biometric_registered';
const STORAGE_KEY_DECLINED = 'biometric_enrollment_declined';

export const biometricService = {
  /**
   * Verifica se il dispositivo supporta la biometria.
   * @returns {{ available: boolean, biometryType: string|null }}
   */
  async isBiometricAvailable() {
    try {
      const biometryType = await Keychain.getSupportedBiometryType();
      return {
        available: biometryType !== null,
        biometryType,
      };
    } catch (error) {
      console.log('Biometric availability check failed:', error.message);
      return {available: false, biometryType: null};
    }
  },

  /**
   * Verifica se l'utente ha registrato la biometria (legge flag da AsyncStorage, nessun prompt).
   * @returns {boolean}
   */
  async isBiometricRegistered() {
    try {
      const registered = await AsyncStorage.getItem(STORAGE_KEY_REGISTERED);
      return registered === 'true';
    } catch {
      return false;
    }
  },

  /**
   * Registra la biometria: chiama il server per generare un token, poi lo salva nel Keychain protetto da biometria.
   * @param {string} authToken - JWT valido dell'utente
   * @param {string} deviceName - es. "iOS 18.0"
   * @returns {{ success: boolean, error?: string }}
   */
  async registerBiometric(authToken, deviceName) {
    try {
      const platform = Platform.OS; // 'ios' o 'android'

      // Chiama il server per ottenere il token biometrico
      const formData = new FormData();
      formData.append('device_name', deviceName);
      formData.append('platform', platform);

      console.log('🔐 Biometric register URL:', API_CONFIG.ENDPOINTS.BIOMETRIC_REGISTER);
      console.log('🔐 Biometric register authToken:', authToken ? authToken.substring(0, 20) + '...' : 'NULL');

      const response = await apiClient.post(
        API_CONFIG.ENDPOINTS.BIOMETRIC_REGISTER,
        formData,
        {
          headers: {
            Authorization: `Bearer ${authToken}`,
            'Content-Type': 'multipart/form-data',
          },
        },
      );

      console.log('🔐 Biometric register response:', JSON.stringify(response.data));

      if (!response.data?.success || !response.data?.data?.biometric_token) {
        return {
          success: false,
          error: response.data?.message || 'Errore nella registrazione',
        };
      }

      const biometricToken = response.data.data.biometric_token;

      // Salva nel Keychain con protezione biometrica
      await Keychain.setGenericPassword('biometric', biometricToken, {
        service: KEYCHAIN_SERVICE,
        accessControl: Keychain.ACCESS_CONTROL.BIOMETRY_ANY,
        accessible: Keychain.ACCESSIBLE.WHEN_PASSCODE_SET_THIS_DEVICE_ONLY,
      });

      // Segna come registrato
      await AsyncStorage.setItem(STORAGE_KEY_REGISTERED, 'true');
      // Rimuovi eventuale flag "rifiutato"
      await AsyncStorage.removeItem(STORAGE_KEY_DECLINED);

      return {success: true};
    } catch (error) {
      console.error('Biometric registration failed:', error.message);
      return {
        success: false,
        error: error.message || 'Errore nella registrazione biometrica',
      };
    }
  },

  /**
   * Login biometrico: legge il token dal Keychain (scatta prompt biometrico), poi chiama il server.
   * @returns {{ success: boolean, token?: string, user?: object, error?: string }}
   */
  async biometricLogin() {
    try {
      // Leggi dal Keychain - scatta il prompt biometrico del sistema
      const credentials = await Keychain.getGenericPassword({
        service: KEYCHAIN_SERVICE,
        accessControl: Keychain.ACCESS_CONTROL.BIOMETRY_ANY,
      });

      if (!credentials || !credentials.password) {
        return {success: false, error: 'Nessun token biometrico trovato'};
      }

      const biometricToken = credentials.password;

      // Chiama il server per il login
      const formData = new FormData();
      formData.append('biometric_token', biometricToken);

      console.log('🔐 Biometric login URL:', API_CONFIG.ENDPOINTS.BIOMETRIC_LOGIN);

      const response = await apiClient.post(
        API_CONFIG.ENDPOINTS.BIOMETRIC_LOGIN,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        },
      );

      if (!response.data?.success || !response.data?.data) {
        // Token invalido/scaduto - pulizia locale
        await this.clearBiometricData();
        return {
          success: false,
          error: response.data?.message || 'Token biometrico non valido',
        };
      }

      const {user, access_token} = response.data.data;

      // Estrai il token JWT
      let actualToken = null;
      if (typeof access_token === 'object' && access_token !== null) {
        actualToken = access_token.token;
      } else if (typeof access_token === 'string') {
        actualToken = access_token;
      }

      // Trasforma i dati utente nello stesso formato dell'app
      const transformedUser = {
        id: user.id.toString(),
        email: user.email,
        role: user.user_type === 'paziente' ? 'patient' : 'therapist',
        firstName: user.nome,
        lastName: user.cognome,
        fullName: `${user.nome} ${user.cognome}`,
        codiceFiscale: user.codice_fiscale || '',
        telefono: user.telefono || '',
        dataNascita: user.data_nascita || '',
        indirizzo: user.indirizzo || '',
        status: user.status || 'attivo',
        isFirstLogin: false,
        isPasswordResetRequired: false,
        patients: user.patients || [],
        ...(user.user_type === 'terapista' && {
          specializzazione: user.specializzazione || '',
          numeroAlbo: user.numero_albo || '',
        }),
      };

      return {
        success: true,
        token: actualToken,
        user: transformedUser,
      };
    } catch (error) {
      console.log('Biometric login failed:', error.message);

      // Se l'utente ha cancellato il prompt biometrico
      if (
        error.message?.includes('cancel') ||
        error.message?.includes('Cancel') ||
        error.message?.includes('user canceled') ||
        error.message?.includes('Authentication failed')
      ) {
        return {success: false, error: 'cancelled'};
      }

      return {
        success: false,
        error: error.message || 'Errore nel login biometrico',
      };
    }
  },

  /**
   * Disabilita la biometria: revoca sul server + pulizia locale.
   * @param {string} authToken - JWT valido
   * @returns {{ success: boolean, error?: string }}
   */
  async disableBiometric(authToken) {
    try {
      // Revoca tutti i dispositivi sul server
      await apiClient.post(
        API_CONFIG.ENDPOINTS.BIOMETRIC_REVOKE,
        new FormData(),
        {
          headers: {
            Authorization: `Bearer ${authToken}`,
            'Content-Type': 'multipart/form-data',
          },
        },
      );

      // Pulizia locale
      await this.clearBiometricData();

      return {success: true};
    } catch (error) {
      console.error('Biometric disable failed:', error.message);
      // Pulizia locale comunque
      await this.clearBiometricData();
      return {
        success: false,
        error: error.message || 'Errore nella disabilitazione',
      };
    }
  },

  /**
   * Pulizia locale: rimuove token dal Keychain e flag da AsyncStorage.
   */
  async clearBiometricData() {
    try {
      await Keychain.resetGenericPassword({service: KEYCHAIN_SERVICE});
    } catch {
      // Ignora errori di Keychain
    }
    try {
      await AsyncStorage.removeItem(STORAGE_KEY_REGISTERED);
    } catch {
      // Ignora errori di AsyncStorage
    }
  },

  /**
   * Verifica se l'utente ha rifiutato l'enrollment biometrico.
   * @returns {boolean}
   */
  async hasDeclinedEnrollment() {
    try {
      const declined = await AsyncStorage.getItem(STORAGE_KEY_DECLINED);
      return declined === 'true';
    } catch {
      return false;
    }
  },

  /**
   * Segna che l'utente ha rifiutato l'enrollment.
   */
  async setEnrollmentDeclined() {
    try {
      await AsyncStorage.setItem(STORAGE_KEY_DECLINED, 'true');
    } catch {
      // Ignora
    }
  },

  /**
   * Ritorna l'etichetta localizzata per il tipo di biometria.
   * @param {string} type - Tipo restituito da Keychain (FaceID, TouchID, Fingerprint, etc.)
   * @returns {string}
   */
  getBiometricLabel(type) {
    switch (type) {
      case Keychain.BIOMETRY_TYPE.FACE_ID:
        return 'Face ID';
      case Keychain.BIOMETRY_TYPE.TOUCH_ID:
        return 'Touch ID';
      case Keychain.BIOMETRY_TYPE.FINGERPRINT:
        return 'Impronta digitale';
      case Keychain.BIOMETRY_TYPE.FACE:
        return 'Riconoscimento facciale';
      case Keychain.BIOMETRY_TYPE.IRIS:
        return 'Iris';
      default:
        return 'Biometria';
    }
  },
};
