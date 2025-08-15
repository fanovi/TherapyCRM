#!/bin/bash
# Script per assicurarsi che Node.js sia disponibile per Gradle

export PATH="/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin:$PATH"
export NODE_PATH="/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin/node"

# Esegui il comando passato come argomento
exec "$@"
