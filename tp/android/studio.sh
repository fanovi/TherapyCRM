#!/bin/bash

# Script to launch Android Studio with correct Node.js path
export PATH="/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin:$PATH"
export NODE_PATH="/Users/marcorispoli/.nvm/versions/node/v21.7.1/bin/node"

# Launch Android Studio
open -a "Android Studio"

echo "Android Studio launched with Node.js path configured"