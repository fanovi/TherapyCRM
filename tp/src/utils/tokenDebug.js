import AsyncStorage from '@react-native-async-storage/async-storage';
import {decodeJwt, isTokenExpired, isValidJWT} from './jwt';

/**
 * Funzione di debug per analizzare lo stato dei token
 */
export const debugTokenStatus = async () => {
  try {
    console.log('🔍 === TOKEN DEBUG START ===');

    // Recupera i token da AsyncStorage
    const authToken = await AsyncStorage.getItem('authToken');
    const refreshToken = await AsyncStorage.getItem('refreshToken');
    const userString = await AsyncStorage.getItem('user');

    console.log('📦 Token in storage:', {
      authToken: authToken ? `${authToken.substring(0, 20)}...` : 'NULL',
      refreshToken: refreshToken
        ? `${refreshToken.substring(0, 20)}...`
        : 'NULL',
      user: userString ? 'PRESENTE' : 'NULL',
    });

    if (authToken) {
      console.log('🔐 Analisi authToken:');
      console.log('   - Lunghezza:', authToken.length);
      console.log('   - Numero di parti:', authToken.split('.').length);
      console.log(
        '   - È JWT valido:',
        isValidJWT(authToken) ? '✅ SÌ' : '❌ NO',
      );

      if (isValidJWT(authToken)) {
        try {
          const decoded = decodeJwt(authToken);
          if (decoded) {
            console.log('   - Decodifica: ✅ SUCCESSO');
            console.log('   - Payload:', {
              user_id: decoded.user_id,
              username: decoded.username,
              exp: decoded.exp,
              iat: decoded.iat,
            });

            if (decoded.exp) {
              const expDate = new Date(decoded.exp * 1000);
              const now = new Date();
              console.log('   - Scadenza:', expDate.toISOString());
              console.log('   - Ora corrente:', now.toISOString());
              console.log(
                '   - Scaduto:',
                isTokenExpired(authToken) ? '❌ SÌ' : '✅ NO',
              );
            }
          } else {
            console.log('   - Decodifica: ❌ FALLITA');
          }
        } catch (decodeError) {
          console.log('   - Errore decodifica:', decodeError.message);
        }
      } else {
        console.log('   - Token semplice (non JWT)');
        console.log('   - Considerato valido per default');
      }
    }

    if (userString) {
      try {
        const user = JSON.parse(userString);
        console.log('👤 Dati utente:', {
          id: user.id,
          email: user.email,
          firstName: user.firstName,
          lastName: user.lastName,
        });
      } catch (userError) {
        console.log('❌ Errore parsing utente:', userError.message);
      }
    }

    console.log('🔍 === TOKEN DEBUG END ===');

    return {
      hasAuthToken: !!authToken,
      hasRefreshToken: !!refreshToken,
      hasUser: !!userString,
      tokenValid: authToken ? !isTokenExpired(authToken) : false,
    };
  } catch (error) {
    console.error('❌ Errore debug token:', error);
    return null;
  }
};

/**
 * Pulisce tutti i token e dati utente (per debug)
 */
export const clearAllTokens = async () => {
  try {
    await AsyncStorage.multiRemove(['authToken', 'refreshToken', 'user']);
    console.log('🧹 Tutti i token sono stati rimossi');
    return true;
  } catch (error) {
    console.error('❌ Errore pulizia token:', error);
    return false;
  }
};
