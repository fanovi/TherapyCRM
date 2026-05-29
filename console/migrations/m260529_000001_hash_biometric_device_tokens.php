<?php

use yii\db\Migration;

/**
 * Migra i token biometrici esistenti da testo in chiaro ad hash SHA-256.
 *
 * I client conservano il token in chiaro nel Keychain: hashando le righe
 * esistenti in-place (SHA2 == hash('sha256') lato PHP) il login biometrico
 * continua a funzionare senza richiedere una nuova registrazione.
 *
 * Il filtro LENGTH(biometric_token) = 128 rende la migrazione idempotente:
 * i token in chiaro sono lunghi 128 caratteri, gli hash 64, quindi una
 * seconda esecuzione non re-hasha valori già hashati.
 */
class m260529_000001_hash_biometric_device_tokens extends Migration
{
    public function safeUp()
    {
        $this->execute(
            "UPDATE {{%biometric_device}}
             SET biometric_token = SHA2(biometric_token, 256)
             WHERE LENGTH(biometric_token) = 128"
        );
    }

    public function safeDown()
    {
        // Irreversibile: l'hash non può essere riconvertito nel token originale.
        echo "m260529_000001_hash_biometric_device_tokens non è reversibile: gli hash non possono tornare in chiaro.\n";
        return false;
    }
}
