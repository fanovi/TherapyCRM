#!/usr/bin/env node

/**
 * Script di test rapido per notifiche bloccanti
 *
 * Uso:
 * node test-blocking-notifications.js --token YOUR_TOKEN --action create
 * node test-blocking-notifications.js --token YOUR_TOKEN --action check
 * node test-blocking-notifications.js --token YOUR_TOKEN --action multiple
 */

const https = require('https');
const http = require('http');

// Configurazione
const CONFIG = {
  baseUrl: 'http://localhost/TherapyCRM/api',
  // baseUrl: 'https://your-domain.com/TherapyCRM/api', // Per produzione
};

// Parse arguments
const args = process.argv.slice(2);
const token = getArg('--token');
const action = getArg('--action') || 'create';

function getArg(name) {
  const index = args.indexOf(name);
  return index !== -1 ? args[index + 1] : null;
}

// Colori per console
const colors = {
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  reset: '\x1b[0m',
  bold: '\x1b[1m',
};

function log(color, message) {
  console.log(color + message + colors.reset);
}

function makeRequest(method, path, data = null) {
  return new Promise((resolve, reject) => {
    const url = new URL(CONFIG.baseUrl + path);
    const options = {
      hostname: url.hostname,
      port: url.port || (url.protocol === 'https:' ? 443 : 80),
      path: url.pathname + url.search,
      method: method,
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
    };

    const client = url.protocol === 'https:' ? https : http;

    const req = client.request(options, res => {
      let body = '';
      res.on('data', chunk => (body += chunk));
      res.on('end', () => {
        try {
          const response = JSON.parse(body);
          resolve({status: res.statusCode, data: response});
        } catch (e) {
          resolve({status: res.statusCode, data: body});
        }
      });
    });

    req.on('error', reject);

    if (data) {
      req.write(JSON.stringify(data));
    }

    req.end();
  });
}

async function createBlockingNotification(title = null, message = null) {
  log(colors.blue, '🧪 Creando notifica bloccante...');

  const payload = {
    type: 'blocking',
    title: title || `🚨 Test Bloccante ${new Date().toLocaleTimeString()}`,
    message:
      message ||
      `Notifica di test creata alle ${new Date().toLocaleString()}. Questa deve essere confermata per sbloccare l'app.`,
  };

  try {
    const response = await makeRequest(
      'POST',
      '/notifications/create-test',
      payload,
    );

    if (response.data.success) {
      log(colors.green, '✅ Notifica bloccante creata con successo!');
      console.log('   ID:', response.data.data.id);
      console.log('   Titolo:', response.data.data.title);
      console.log(
        '   Richiede conferma:',
        response.data.data.requires_read_confirmation,
      );
      log(colors.yellow, "⏱️  L'app dovrebbe bloccarsi entro 30 secondi...");
    } else {
      log(colors.red, '❌ Errore: ' + response.data.error);
    }
  } catch (error) {
    log(colors.red, '❌ Errore connessione: ' + error.message);
  }
}

async function createMultipleNotifications(count = 3) {
  log(colors.blue, `🧪 Creando ${count} notifiche bloccanti...`);

  for (let i = 1; i <= count; i++) {
    const payload = {
      type: 'blocking',
      title: `📋 Notifica ${i}/${count}`,
      message: `Questa è la notifica numero ${i} di ${count}. Tutte devono essere confermate per sbloccare l'app.`,
    };

    try {
      const response = await makeRequest(
        'POST',
        '/notifications/create-test',
        payload,
      );

      if (response.data.success) {
        log(
          colors.green,
          `✅ Notifica ${i}/${count} creata (ID: ${response.data.data.id})`,
        );
      } else {
        log(
          colors.red,
          `❌ Errore notifica ${i}/${count}: ` + response.data.error,
        );
      }

      // Pausa tra creazioni
      if (i < count) {
        await new Promise(resolve => setTimeout(resolve, 500));
      }
    } catch (error) {
      log(
        colors.red,
        `❌ Errore connessione notifica ${i}/${count}: ` + error.message,
      );
    }
  }

  log(
    colors.yellow,
    `⏱️  L'app dovrebbe mostrare ${count} notifiche bloccanti entro 30 secondi...`,
  );
}

async function checkBlockingStatus() {
  log(colors.blue, '🔍 Verificando stato notifiche bloccanti...');

  try {
    const response = await makeRequest('GET', '/notifications/has-blocking');

    if (response.data.success) {
      const data = response.data.data;

      if (data.has_blocking_notifications) {
        log(
          colors.yellow,
          `⚠️  ATTENZIONE: ${data.blocking_count} notifiche bloccanti attive`,
        );
        log(colors.yellow, "🔒 L'app dovrebbe essere bloccata");
      } else {
        log(colors.green, '✅ Nessuna notifica bloccante');
        log(colors.green, "🔓 L'app dovrebbe essere libera");
      }

      console.log('   Dettagli:', JSON.stringify(data, null, 2));
    } else {
      log(colors.red, '❌ Errore: ' + response.data.error);
    }
  } catch (error) {
    log(colors.red, '❌ Errore connessione: ' + error.message);
  }
}

async function listBlockingNotifications() {
  log(colors.blue, '📋 Recuperando lista notifiche bloccanti...');

  try {
    const response = await makeRequest('GET', '/notifications/blocking');

    if (response.data.success) {
      const notifications = response.data.data;

      if (notifications.length === 0) {
        log(colors.green, '✅ Nessuna notifica bloccante');
      } else {
        log(
          colors.yellow,
          `📝 Trovate ${notifications.length} notifiche bloccanti:`,
        );

        notifications.forEach((notif, index) => {
          console.log(`\n   ${index + 1}. ID: ${notif.id}`);
          console.log(`      Titolo: ${notif.title}`);
          console.log(`      Creata: ${notif.created_at}`);
          console.log(`      Visualizzata: ${notif.viewed_at || 'No'}`);
          console.log(`      Letta: ${notif.read_at || 'No'}`);
        });
      }
    } else {
      log(colors.red, '❌ Errore: ' + response.data.error);
    }
  } catch (error) {
    log(colors.red, '❌ Errore connessione: ' + error.message);
  }
}

async function createNormalNotification() {
  log(colors.blue, '📨 Creando notifica normale (non bloccante)...');

  const payload = {
    type: 'normal',
    title: `📨 Test Normale ${new Date().toLocaleTimeString()}`,
    message: `Notifica normale creata alle ${new Date().toLocaleString()}. Questa NON blocca l'app.`,
  };

  try {
    const response = await makeRequest(
      'POST',
      '/notifications/create-test',
      payload,
    );

    if (response.data.success) {
      log(colors.green, '✅ Notifica normale creata!');
      console.log('   ID:', response.data.data.id);
      console.log(
        '   Blocca app:',
        response.data.data.requires_read_confirmation,
      );
    } else {
      log(colors.red, '❌ Errore: ' + response.data.error);
    }
  } catch (error) {
    log(colors.red, '❌ Errore connessione: ' + error.message);
  }
}

function showHelp() {
  console.log(`
${colors.bold}🧪 Test Script Notifiche Bloccanti${colors.reset}

${colors.blue}Uso:${colors.reset}
  node test-blocking-notifications.js --token YOUR_TOKEN --action ACTION

${colors.blue}Azioni disponibili:${colors.reset}
  ${colors.green}create${colors.reset}      Crea una notifica bloccante
  ${colors.green}multiple${colors.reset}    Crea 3 notifiche bloccanti
  ${colors.green}check${colors.reset}       Verifica stato notifiche bloccanti
  ${colors.green}list${colors.reset}        Lista notifiche bloccanti attive
  ${colors.green}normal${colors.reset}      Crea notifica normale (non bloccante)
  ${colors.green}help${colors.reset}        Mostra questo aiuto

${colors.blue}Esempi:${colors.reset}
  ${colors.yellow}# Crea notifica bloccante${colors.reset}
  node test-blocking-notifications.js --token abc123 --action create
  
  ${colors.yellow}# Verifica stato${colors.reset}
  node test-blocking-notifications.js --token abc123 --action check
  
  ${colors.yellow}# Crea multiple notifiche${colors.reset}
  node test-blocking-notifications.js --token abc123 --action multiple

${colors.blue}Note:${colors.reset}
  • Il token è visibile nei log dell'app o in AsyncStorage
  • L'app dovrebbe reagire entro 30 secondi (polling)
  • Controlla i log con: tail -f frontend/runtime/logs/app.log | grep -i notification
`);
}

// Main execution
async function main() {
  if (!token) {
    log(colors.red, '❌ Token richiesto! Usa --token YOUR_TOKEN');
    showHelp();
    process.exit(1);
  }

  log(colors.bold, `\n🚀 Test Notifiche Bloccanti - TherapyCRM`);
  log(colors.blue, `📡 Endpoint: ${CONFIG.baseUrl}`);
  log(colors.blue, `🎬 Azione: ${action}`);
  console.log();

  switch (action) {
    case 'create':
      await createBlockingNotification();
      break;
    case 'multiple':
      await createMultipleNotifications();
      break;
    case 'check':
      await checkBlockingStatus();
      break;
    case 'list':
      await listBlockingNotifications();
      break;
    case 'normal':
      await createNormalNotification();
      break;
    case 'help':
      showHelp();
      break;
    default:
      log(colors.red, `❌ Azione non riconosciuta: ${action}`);
      showHelp();
      process.exit(1);
  }

  console.log();
}

// Run script
if (require.main === module) {
  main().catch(error => {
    log(colors.red, '💥 Errore fatale: ' + error.message);
    process.exit(1);
  });
}
