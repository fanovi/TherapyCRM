#!/bin/bash
# Script per configurare l'ambiente per Android Studio

# Esporta il PATH con Node.js
export PATH="/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin:$PATH"
export NODE_PATH="/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin/node"

# Avvia Android Studio con l'ambiente configurato
exec "$@"
