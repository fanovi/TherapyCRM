#!/bin/bash

# Script per build e rilascio di TherapyCRM
# Utilizzo: ./build-release.sh [clean|build|bundle|all]

set -e

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funzioni di utilità
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verifica prerequisiti
check_prerequisites() {
    print_info "Verifico prerequisiti..."
    
    if ! command -v node &> /dev/null; then
        print_error "Node.js non trovato. Installa Node.js 18+"
        exit 1
    fi
    
    if ! command -v npm &> /dev/null; then
        print_error "npm non trovato. Installa npm"
        exit 1
    fi
    
    if [ ! -d "android" ]; then
        print_error "Directory android non trovata. Esegui lo script dalla root del progetto"
        exit 1
    fi
    
    print_success "Prerequisiti verificati"
}

# Pulizia
clean_build() {
    print_info "Pulisco build precedenti..."
    
    # Pulizia React Native
    rm -rf node_modules
    npm install
    
    # Pulizia Android
    cd android
    ./gradlew clean
    cd ..
    
    # Rimuovi bundle e assets generati
    rm -rf android/app/src/main/assets/index.android.bundle
    rm -rf android/app/src/main/res/drawable-*
    rm -rf android/app/src/main/res/raw
    
    print_success "Pulizia completata"
}

# Build del bundle JavaScript
build_js_bundle() {
    print_info "Creo bundle JavaScript per produzione..."
    
    # Crea directory assets se non esiste
    mkdir -p android/app/src/main/assets
    
    # Bundle JavaScript
    npx react-native bundle \
        --platform android \
        --dev false \
        --entry-file index.js \
        --bundle-output android/app/src/main/assets/index.android.bundle \
        --assets-dest android/app/src/main/res \
        --sourcemap-output android/app/src/main/assets/index.android.bundle.map
    
    print_success "Bundle JavaScript creato"
}

# Build APK
build_apk() {
    print_info "Creo APK di produzione..."
    
    cd android
    ./gradlew assembleRelease
    
    if [ $? -eq 0 ]; then
        APK_PATH="app/build/outputs/apk/release/app-release.apk"
        if [ -f "$APK_PATH" ]; then
            APK_SIZE=$(du -h "$APK_PATH" | cut -f1)
            print_success "APK creato: $APK_PATH (Dimensione: $APK_SIZE)"
        else
            print_error "APK non trovato in $APK_PATH"
            exit 1
        fi
    else
        print_error "Errore durante la creazione dell'APK"
        exit 1
    fi
    
    cd ..
}

# Build AAB
build_aab() {
    print_info "Creo Android App Bundle (AAB)..."
    
    cd android
    ./gradlew bundleRelease
    
    if [ $? -eq 0 ]; then
        AAB_PATH="app/build/outputs/bundle/release/app-release.aab"
        if [ -f "$AAB_PATH" ]; then
            AAB_SIZE=$(du -h "$AAB_PATH" | cut -f1)
            print_success "AAB creato: $AAB_PATH (Dimensione: $AAB_SIZE)"
        else
            print_error "AAB non trovato in $AAB_PATH"
            exit 1
        fi
    else
        print_error "Errore durante la creazione dell'AAB"
        exit 1
    fi
    
    cd ..
}

# Verifica keystore
verify_keystore() {
    print_info "Verifico keystore di release..."
    
    if [ ! -f "android/app/release.keystore" ]; then
        print_warning "Keystore di release non trovato. Verifica la configurazione in gradle.properties"
        return 1
    fi
    
    print_success "Keystore di release trovato"
    return 0
}

# Crea pacchetto di rilascio
create_release_package() {
    print_info "Creo pacchetto di rilascio..."
    
    RELEASE_DIR="RELEASE_PACKAGE"
    mkdir -p "$RELEASE_DIR"
    
    # Copia file di rilascio
    cp android/app/build/outputs/apk/release/app-release.apk "$RELEASE_DIR/"
    cp android/app/build/outputs/bundle/release/app-release.aab "$RELEASE_DIR/"
    
    # Copia keystore (per backup)
    cp android/app/release.keystore "$RELEASE_DIR/"
    
    # Copia configurazioni
    cp android/app/build.gradle "$RELEASE_DIR/"
    cp android/gradle.properties "$RELEASE_DIR/"
    
    # Crea file di informazioni
    echo "TherapyCRM - Build Info" > "$RELEASE_DIR/build-info.txt"
    echo "Data: $(date)" >> "$RELEASE_DIR/build-info.txt"
    echo "Versione: $(grep 'versionName' android/app/build.gradle | grep -o '".*"' | tr -d '"')" >> "$RELEASE_DIR/build-info.txt"
    echo "Version Code: $(grep 'versionCode' android/app/build.gradle | grep -o '[0-9]*')" >> "$RELEASE_DIR/build-info.txt"
    echo "Package: $(grep 'applicationId' android/app/build.gradle | grep -o '".*"' | tr -d '"')" >> "$RELEASE_DIR/build-info.txt"
    
    print_success "Pacchetto di rilascio creato in $RELEASE_DIR/"
}

# Mostra informazioni sui file creati
show_build_info() {
    print_info "=== INFORMAZIONI BUILD ==="
    
    if [ -f "android/app/build/outputs/apk/release/app-release.apk" ]; then
        APK_SIZE=$(du -h "android/app/build/outputs/apk/release/app-release.apk" | cut -f1)
        echo "APK: android/app/build/outputs/apk/release/app-release.apk ($APK_SIZE)"
    fi
    
    if [ -f "android/app/build/outputs/bundle/release/app-release.aab" ]; then
        AAB_SIZE=$(du -h "android/app/build/outputs/bundle/release/app-release.aab" | cut -f1)
        echo "AAB: android/app/build/outputs/bundle/release/app-release.aab ($AAB_SIZE)"
    fi
    
    echo ""
    print_info "=== COMANDI UTILI ==="
    echo "Test APK: adb install android/app/build/outputs/apk/release/app-release.apk"
    echo "Verifica keystore: keytool -list -v -keystore android/app/release.keystore"
    echo "Pacchetto completo: RELEASE_PACKAGE/"
}

# Menu principale
main() {
    case "${1:-all}" in
        "clean")
            check_prerequisites
            clean_build
            ;;
        "build")
            check_prerequisites
            verify_keystore
            build_js_bundle
            build_apk
            ;;
        "bundle")
            check_prerequisites
            verify_keystore
            build_js_bundle
            build_aab
            ;;
        "all")
            check_prerequisites
            verify_keystore
            build_js_bundle
            build_apk
            build_aab
            create_release_package
            ;;
        *)
            echo "Utilizzo: $0 [clean|build|bundle|all]"
            echo "  clean  - Pulisce build precedenti"
            echo "  build  - Crea solo APK"
            echo "  bundle - Crea solo AAB"
            echo "  all    - Crea tutto (default)"
            exit 1
            ;;
    esac
    
    show_build_info
}

# Esegui script
main "$@"
