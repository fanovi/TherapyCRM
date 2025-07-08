#!/bin/bash

# Carica NVM
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"

# Assicurati che node sia nel PATH
export PATH="/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin:$PATH"

# Variabili specifiche per Android Studio
export ANDROID_HOME="$HOME/Library/Android/sdk"
export PATH="$PATH:$ANDROID_HOME/emulator"
export PATH="$PATH:$ANDROID_HOME/tools"
export PATH="$PATH:$ANDROID_HOME/tools/bin"
export PATH="$PATH:$ANDROID_HOME/platform-tools"

# Variabili Node.js per Gradle
export GRADLE_OPTS="-Dorg.gradle.project.nodejs.dir=/Users/marcorispoli/.nvm/versions/node/v21.7.1"
export GRADLE_OPTS="$GRADLE_OPTS -Dorg.gradle.project.nodejs.executable=/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin/node"
export GRADLE_OPTS="$GRADLE_OPTS -Dorg.gradle.project.npm.executable=/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin/npm"
export GRADLE_OPTS="$GRADLE_OPTS -Dorg.gradle.project.npx.executable=/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin/npx"

# Forza il percorso di node per tutte le applicazioni
export NODE_BINARY="/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin/node"

# Chiudi Android Studio se è già aperto
echo "Chiudendo Android Studio se è aperto..."
pkill -f "Android Studio" || true

# Attendi un momento
sleep 2

# Avvia Android Studio
echo "Avvio Android Studio con Node.js configurato..."
echo "Node version: $(node --version)"
echo "Node path: $(which node)"
echo "Global node path: $(/usr/local/bin/node --version 2>/dev/null || echo 'Global node not found')"
echo "GRADLE_OPTS: $GRADLE_OPTS"

open -a "Android Studio"

echo "Android Studio avviato con le variabili ambiente corrette!" 