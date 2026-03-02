/**
 * Utility functions per gestire i dati utente
 */

/**
 * Ottiene il nome completo dell'utente considerando vari formati
 * @param {Object} user - Oggetto utente
 * @returns {string} Nome completo dell'utente
 */
export const getUserDisplayName = user => {
  if (!user) return 'Utente';

  // Lista di valori che consideriamo "invalidi" per il nome
  const invalidNames = [
    'undefined undefined',
    'undefined',
    '',
    null,
    undefined,
  ];

  // Se fullName contiene valori invalidi, ignoralo
  const fullName = invalidNames.includes(user.fullName) ? null : user.fullName;
  const full_name = invalidNames.includes(user.full_name)
    ? null
    : user.full_name;
  const name = invalidNames.includes(user.name) ? null : user.name;
  const username = invalidNames.includes(user.username) ? null : user.username;

  // Costruisci nome da firstName e lastName solo se sono validi
  const firstName = invalidNames.includes(user.firstName)
    ? ''
    : user.firstName || '';
  const first_name = invalidNames.includes(user.first_name)
    ? ''
    : user.first_name || '';
  const lastName = invalidNames.includes(user.lastName)
    ? ''
    : user.lastName || '';
  const last_name = invalidNames.includes(user.last_name)
    ? ''
    : user.last_name || '';

  const constructedName = `${lastName || last_name} ${
    firstName || first_name
  }`.trim();

  // Se abbiamo costruito un nome valido, usalo
  if (constructedName.length > 0 && constructedName !== 'undefined undefined') {
    return constructedName;
  }

  // Fallback su altri campi
  if (fullName) return fullName;
  if (full_name) return full_name;
  if (name) return name;
  if (username) return username;

  // Ultimo fallback: usa la parte prima del @ dell'email
  if (user.email) {
    const emailName = user.email.split('@')[0];
    // Migliora la visualizzazione dell'email
    if (emailName === 'paziente') {
      return 'Paziente';
    }
    return emailName.charAt(0).toUpperCase() + emailName.slice(1);
  }

  return 'Utente';
};

/**
 * Ottiene le iniziali del nome utente
 * @param {string} name - Nome dell'utente
 * @returns {string} Iniziali del nome
 */
export const getUserInitials = name => {
  if (!name || name === 'Utente') return '?';

  // Gestisce casi speciali
  if (name === 'Paziente') return 'P';
  if (name === 'Terapista') return 'T';
  if (name === 'Genitore') return 'G';

  // Se è una singola parola (es. nome utente), prendi le prime 2 lettere
  if (name.split(' ').length === 1) {
    return name.substring(0, 2).toUpperCase();
  }

  // Se sono multiple parole, prendi la prima lettera di ogni parola
  return name
    .split(' ')
    .map(n => n.charAt(0))
    .join('')
    .toUpperCase();
};

/**
 * Ottiene il ruolo dell'utente in formato leggibile
 * @param {Object} user - Oggetto utente
 * @returns {string} Ruolo dell'utente
 */
export const getUserRole = user => {
  if (!user) return 'Utente';

  if (user.user_type === 'parent') return 'Genitore';
  if (user.user_type === 'patient') return 'Paziente';
  if (user.user_type === 'therapist') return 'Terapista';

  return user.role || user.user_type || 'Utente';
};

/**
 * Debug completo dei dati utente
 * @param {Object} user - Oggetto utente
 * @param {string} context - Contesto del debug
 */
export const debugUserData = (user, context = 'User Debug') => {
  console.log(`🔍 === ${context.toUpperCase()} ===`);
  console.log('🔍 User full object:', user);
  console.log('🔍 User keys:', user ? Object.keys(user) : 'No user');

  // Campi nome
  console.log('📝 Name fields:');
  console.log('   - fullName:', user?.fullName);
  console.log('   - full_name:', user?.full_name);
  console.log('   - name:', user?.name);
  console.log('   - firstName:', user?.firstName);
  console.log('   - first_name:', user?.first_name);
  console.log('   - lastName:', user?.lastName);
  console.log('   - last_name:', user?.last_name);
  console.log('   - username:', user?.username);

  // Campi contatto
  console.log('📞 Contact fields:');
  console.log('   - email:', user?.email);
  console.log('   - telefono:', user?.telefono);
  console.log('   - phone:', user?.phone);
  console.log('   - telephone:', user?.telephone);

  // Campi ruolo
  console.log('👤 Role fields:');
  console.log('   - role:', user?.role);
  console.log('   - user_type:', user?.user_type);
  console.log('   - roles array:', user?.roles);

  // Campi personali
  console.log('🏠 Personal fields:');
  console.log('   - codiceFiscale:', user?.codiceFiscale);
  console.log('   - codice_fiscale:', user?.codice_fiscale);
  console.log('   - dataNascita:', user?.dataNascita);
  console.log('   - data_nascita:', user?.data_nascita);
  console.log('   - birth_date:', user?.birth_date);
  console.log('   - indirizzo:', user?.indirizzo);
  console.log('   - address:', user?.address);

  // Display name finale
  const displayName = getUserDisplayName(user);
  const userRole = getUserRole(user);
  const initials = getUserInitials(displayName);

  console.log('✨ Final display name:', displayName);
  console.log('🎭 Final role:', userRole);
  console.log('🔤 Final initials:', initials);
  console.log('🔧 Applied fixes for undefined values:', {
    'Fixed fullName': user?.fullName === 'undefined undefined' ? 'YES' : 'NO',
    'Using email fallback':
      !displayName || displayName === 'Paziente' ? 'YES' : 'NO',
    'Email source': user?.email,
  });
};

/**
 * Ottiene un campo dall'utente con fallback multipli
 * @param {Object} user - Oggetto utente
 * @param {Array} fieldNames - Array di nomi di campo da provare
 * @param {string} defaultValue - Valore di default
 * @returns {string} Valore del campo
 */
export const getUserField = (
  user,
  fieldNames,
  defaultValue = 'Non specificato',
) => {
  if (!user) return defaultValue;

  for (const fieldName of fieldNames) {
    if (user[fieldName]) {
      return user[fieldName];
    }
  }

  return defaultValue;
};
