# Checklist di Rilascio - TherapyCRM

## ✅ Pre-Build Checklist

- [x] Keystore di release configurato
- [x] Version code e version name aggiornati
- [x] Bundle JavaScript creato per produzione
- [x] Proguard configurato (se necessario)
- [x] Permessi verificati nel manifest

## ✅ Build Checklist

- [x] APK di produzione creato (62 MB)
- [x] Android App Bundle (AAB) creato (31 MB)
- [x] Keystore di release utilizzato
- [x] Build completata senza errori
- [x] File di rilascio copiati in RELEASE_PACKAGE/

## ✅ Test Pre-Rilascio

### Test Funzionali
- [ ] Login e autenticazione
- [ ] Navigazione tra schermate
- [ ] Calendario e appuntamenti
- [ ] Notifiche push
- [ ] Messaggistica
- [ ] Gestione profilo utente

### Test Tecnici
- [ ] Installazione su dispositivo reale
- [ ] Avvio dell'app
- [ ] Performance e memoria
- [ ] Connessione internet
- [ ] Permessi richiesti
- [ ] Crash e errori

### Test su Dispositivi
- [ ] Android 7+ (API 24+)
- [ ] Diversi formati schermo
- [ ] Orientamento portrait/landscape
- [ ] Dispositivi con poca RAM

## ✅ Preparazione Store

### Google Play Store
- [ ] Account Google Play Console attivo
- [ ] App creata nella console
- [ ] File AAB pronto per upload
- [ ] Descrizione breve (80 caratteri max)
- [ ] Descrizione completa (4000 caratteri max)
- [ ] Screenshot (minimo 2, massimo 8)
- [ ] Icona app (512x512 px)
- [ ] Immagine feature (1024x500 px)
- [ ] Categoria selezionata (Salute e fitness)
- [ ] Contenuto per adulti: No
- [ ] Prezzo: Gratuita
- [ ] Privacy policy (se richiesta)

### Altri Store
- [ ] Amazon Appstore: APK pronto
- [ ] Huawei AppGallery: APK pronto
- [ ] Samsung Galaxy Store: APK pronto

## ✅ Metadati Richiesti

### Informazioni App
- [ ] Nome: TherapyCRM
- [ ] Package: com.tp.cgm.badil
- [ ] Versione: 1.0
- [ ] Categoria: Salute e fitness
- [ ] Età minima: 3+

### Descrizioni
- [ ] Breve: "App per la gestione di terapie e appuntamenti medici"
- [ ] Completa: Descrizione dettagliata delle funzionalità
- [ ] Parole chiave: terapia, appuntamenti, calendario, salute

### Immagini
- [ ] Icona app: 512x512 px
- [ ] Screenshot: 2-8 immagini
- [ ] Immagine feature: 1024x500 px (opzionale)

## ✅ Upload e Revisione

### Google Play Store
- [ ] File AAB caricato
- [ ] Metadati compilati
- [ ] App inviata per revisione
- [ ] Status: In revisione
- [ ] Tempo stimato: 1-7 giorni

### Altri Store
- [ ] File APK caricati
- [ ] Metadati compilati
- [ ] App inviate per approvazione

## ✅ Post-Rilascio

### Monitoraggio
- [ ] Verifica pubblicazione
- [ ] Controllo crash reports
- [ ] Monitoraggio performance
- [ ] Feedback utenti
- [ ] Analytics e metriche

### Aggiornamenti Futuri
- [ ] Incrementa version code
- [ ] Aggiorna version name
- [ ] Esegui build con stesso keystore
- [ ] Testa aggiornamento
- [ ] Carica nuovo bundle/APK

## 📋 Note Importanti

1. **Keystore**: Conserva sempre il keystore di release per aggiornamenti futuri
2. **Versioni**: Incrementa sempre version code per ogni rilascio
3. **Test**: Testa sempre su dispositivi reali prima del rilascio
4. **Metadati**: Compila accuratamente tutte le informazioni richieste
5. **Privacy**: Verifica compliance con le policy degli store

## 🚀 Comandi Utili

```bash
# Test APK su dispositivo
adb install RELEASE_PACKAGE/app-release.apk

# Verifica keystore
keytool -list -v -keystore RELEASE_PACKAGE/release.keystore

# Ricostruire per aggiornamenti
./build-release.sh all

# Pulizia build
./build-release.sh clean
```

## 📞 Supporto

- **Documentazione**: README_RELEASE.md
- **Configurazione**: store-release-config.json
- **Build Script**: build-release.sh
- **Contatto**: support@therapycrm.com

---

**Data Rilascio**: _______________
**Responsabile**: _______________
**Stato**: _______________
