# Calendar App Integration con Yii2

## Panoramica

L'applicazione Calendar è stata integrata con successo nel sistema TherapyCRM utilizzando Yii2. L'integrazione permette di utilizzare l'app React del calendario direttamente all'interno del framework Yii2.

## Struttura dei File

### 1. Controller

- **File**: `frontend/controllers/CalendarController.php`
- **Funzione**: Gestisce le richieste per l'app calendario
- **Caratteristiche**:
  - Controllo accesso per utenti autenticati
  - Gestione parametri `id_patient` e `id_therapist`
  - Validazione mutua esclusiva dei parametri

### 2. Vista

- **File**: `frontend/views/calendar/index.php`
- **Funzione**: Renderizza l'interfaccia per l'app React
- **Caratteristiche**:
  - Registrazione automatica di CSS e JS
  - Passaggio parametri GET a React
  - Integrazione con YiiAsset

### 3. Configurazione URL

- **File**: `frontend/config/main.php`
- **Route disponibili**:
  - `/calendar` - Vista calendario normale
  - `/calendar/123` - Vista filtrata per paziente (ID 123)
  - `/calendar/therapist/456` - Vista filtrata per terapista (ID 456)

### 4. Build React

- **Directory**: `frontend/web/calendar-app/dist/`
- **File generati**:
  - `index.css` - Stili dell'applicazione
  - `index.js` - JavaScript dell'applicazione
  - `index.html` - Template HTML (non utilizzato da Yii2)

## Configurazione Build

### package.json

```json
{
  "scripts": {
    "build": "vite build --outDir ./dist --base=/calendar-app/dist/",
    "build:dev": "vite build --mode development --outDir ./dist --base=/calendar-app/dist/"
  }
}
```

### vite.config.ts

```typescript
{
  build: {
    outDir: "./dist",
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: "index.js",
        chunkFileNames: "[name]-[hash].js",
        assetFileNames: (assetInfo) => {
          if (assetInfo.name === "index.css") return "index.css";
          return "[name]-[hash][extname]";
        },
      },
    },
  },
}
```

## Utilizzo

### 1. Build dell'applicazione React

```bash
cd frontend/web/calendar-app
npm run build
```

### 2. Accesso alle URL

- **Vista normale**: `http://localhost/calendar`
- **Vista paziente**: `http://localhost/calendar/123`
- **Vista terapista**: `http://localhost/calendar/therapist/456`

## Sicurezza

- **Controllo accesso**: Solo utenti autenticati possono accedere
- **Validazione parametri**: Controllo mutua esclusività tra `id_patient` e `id_therapist`
- **CSRF Protection**: Integrato automaticamente tramite YiiAsset

## Passaggio Parametri

I parametri vengono passati da Yii2 a React attraverso:

1. **Data attributes**: `data-query-params` sul div root
2. **JavaScript**: Script che aggiorna l'URL del browser
3. **React**: L'app React legge i parametri dall'URL

## Troubleshooting

### Problema: File CSS/JS non caricati

- Verificare che i file siano stati generati in `dist/`
- Controllare i permessi dei file
- Verificare la configurazione del web server

### Problema: Parametri non passati correttamente

- Verificare la configurazione delle route URL
- Controllare il JavaScript nella vista
- Verificare che React legga correttamente i parametri URL

### Problema: Errori di build

- Verificare la configurazione di Vite
- Controllare le dipendenze npm
- Verificare la sintassi del codice React

## Estensioni Future

1. **API Integration**: Aggiungere endpoint API per dati calendario
2. **Caching**: Implementare cache per migliorare performance
3. **Lazy Loading**: Caricare l'app React solo quando necessario
4. **Service Worker**: Supporto offline per l'app calendario
