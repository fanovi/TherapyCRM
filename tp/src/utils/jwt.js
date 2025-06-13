// Utility functions for JWT handling

import {decode as base64Decode} from 'base-64';

/**
 * Decodifica un token JWT per estrarne il payload
 * @param {string} token - Il token JWT da decodificare
 * @returns {Object|null} - Il payload decodificato o null in caso di errore
 */
export const decodeJwt = token => {
  try {
    if (!token) return null;

    // Dividi il token nelle sue parti (header, payload, signature)
    const parts = token.split('.');
    if (parts.length !== 3) {
      console.warn('Invalid JWT format');
      return null;
    }

    const base64Url = parts[1];
    // Converti la codifica base64url in base64 standard
    let base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');

    // Aggiungi padding se necessario
    while (base64.length % 4) {
      base64 += '=';
    }

    // Decodifica il payload e convertilo in oggetto JSON
    return JSON.parse(base64Decode(base64));
  } catch (e) {
    console.error('JWT decode error:', e);
    return null;
  }
};

/**
 * Verifica se un token è un JWT valido
 * @param {string} token - Il token da verificare
 * @returns {boolean} - True se è un JWT valido
 */
export const isValidJWT = token => {
  if (!token || typeof token !== 'string') return false;
  const parts = token.split('.');
  return parts.length === 3;
};

/**
 * Verifica se un token JWT è scaduto
 * @param {string} token - Il token JWT da verificare
 * @returns {boolean} - True se il token è scaduto, altrimenti false
 */
export const isTokenExpired = token => {
  try {
    console.log('🔍 === CHECKING TOKEN EXPIRATION ===');

    // Se non è un JWT valido, non possiamo verificare la scadenza
    if (!isValidJWT(token)) {
      console.log('⚠️ Token non è un JWT, assumo che sia valido');
      return false;
    }

    const decoded = decodeJwt(token);
    console.log('📦 Decoded token:', decoded ? 'SUCCESS' : 'FAILED');

    // Se la decodifica fallisce o il token non ha un campo exp, consideriamolo scaduto
    if (!decoded || !decoded.exp) {
      console.warn('❌ Token without expiration or invalid');
      return true;
    }

    // Converti exp da secondi (standard JWT) a millisecondi (standard JS)
    const expTimestamp = decoded.exp * 1000;
    const currentTime = Date.now();

    // Confronta i timestamp
    const isExpired = expTimestamp < currentTime;

    if (isExpired) {
      console.log('❌ Token expired at:', new Date(expTimestamp));
      console.log('⏰ Current time:', new Date(currentTime));
    } else {
      console.log('✅ Token valid until:', new Date(expTimestamp));
    }

    return isExpired;
  } catch (e) {
    console.error('❌ Error checking token expiration:', e);
    return true;
  }
};

/**
 * Estrae i dati utente dal token JWT
 * @param {string} token - Il token JWT da analizzare
 * @returns {Object} - I dati utente estratti dal token
 */
export const extractUserDataFromToken = token => {
  const decoded = decodeJwt(token);
  if (!decoded) return null;

  // Estrai i dati utente dal payload JWT
  return {
    id: decoded.user_id,
    username: decoded.username,
    // Aggiungi qui altre proprietà utente presenti nel token
  };
};

/**
 * Calcola il tempo rimanente prima della scadenza del token
 * @param {string} token - Il token JWT
 * @returns {number} - Secondi rimanenti prima della scadenza, o 0 se scaduto
 */
export const getTokenRemainingTime = token => {
  const decoded = decodeJwt(token);
  if (!decoded || !decoded.exp) return 0;

  const expirationTime = decoded.exp * 1000;
  const currentTime = Date.now();
  const remainingTime = Math.max(0, expirationTime - currentTime);

  return Math.floor(remainingTime / 1000); // Converti in secondi
};
