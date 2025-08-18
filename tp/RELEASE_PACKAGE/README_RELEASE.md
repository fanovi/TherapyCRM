# TherapyCRM - Pacchetto di Rilascio

## File Creati

### 1. APK di Produzione
- **Percorso**: `android/app/build/outputs/apk/release/app-release.apk`
- **Dimensione**: ~59 MB
- **Utilizzo**: Installazione diretta su dispositivi Android

### 2. Android App Bundle (AAB)
- **Percorso**: `android/app/build/outputs/bundle/release/app-release.aab`
- **Utilizzo**: Upload su Google Play Console (formato richiesto)

## Informazioni App

- **Nome**: TherapyCRM
- **Package ID**: `com.tp.cgm.badil`
- **Versione**: 1.0
- **Build**: Release
- **Keystore**: Configurato per produzione

## Rilascio su Store

### Google Play Store
1. Accedi a [Google Play Console](https://play.google.com/console)
2. Crea una nuova app o seleziona quella esistente
3. Vai alla sezione "Release" → "Production"
4. Crea un nuovo bundle
5. Carica il file `app-release.aab`
6. Compila le informazioni richieste:
   - Descrizione breve e completa
   - Screenshot (minimo 2)
   - Icona dell'app (512x512 px)
   - Categoria: Salute e fitness
   - Contenuto per adulti: No
   - Prezzo: Gratuita
7. Invia per la revisione

### Altri Store Android
- **Amazon Appstore**: Usa il file APK
- **Huawei AppGallery**: Usa il file APK
- **Samsung Galaxy Store**: Usa il file APK

## Verifica Pre-Rilascio

### Test dell'APK
```bash
# Installa su dispositivo connesso
adb install android/app/build/outputs/apk/release/app-release.apk

# Verifica installazione
adb shell pm list packages | grep com.tp.cgm.badil
```

### Test del Bundle
```bash
# Verifica contenuto AAB
bundletool build-apks --bundle=app-release.aab --output=app.apks

# Estrai APK per test
bundletool extract-apks --apks=app.apks --output-dir=extracted-apks
```

## Note Importanti

1. **Keystore**: Conserva in sicurezza il file `release.keystore` e le password
2. **Versioni Future**: Incrementa sempre `versionCode` e `versionName` in `build.gradle`
3. **Test**: Testa sempre l'APK su dispositivi reali prima del rilascio
4. **Privacy**: Verifica che l'app rispetti le policy di privacy degli store

## Comandi Utili

### Ricostruire APK
```bash
cd android
./gradlew clean
./gradlew assembleRelease
```

### Ricostruire Bundle
```bash
cd android
./gradlew clean
./gradlew bundleRelease
```

### Verifica Keystore
```bash
keytool -list -v -keystore app/release.keystore
```

## Supporto

Per problemi tecnici o domande sul rilascio, consulta la documentazione di React Native o contatta il team di sviluppo.
