<?php

namespace common\components;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Yii;
use yii\base\Component;
use yii\base\InvalidArgumentException;

class JwtComponent extends Component
{
    /** @var string Percorso della chiave privata */
    public $privateKeyPath;

    /** @var string Percorso della chiave pubblica */
    public $publicKeyPath;

    /** @var string Algoritmo di firma JWT */
    public $algorithm = 'RS256';

    /** @var int Durata del token in secondi (default: 3600 = 1 ora) */
    public $tokenDuration = 3600;

    /** @var int Durata del refresh token in secondi (default: 2592000 = 30 giorni) */
    public $refreshTokenDuration = 2592000;

    /** @var string Issuer del token */
    public $issuer = 'your-app-name';

    /** @var string Audience del token */
    public $audience = 'your-app-client';

    private $_privateKey;
    private $_publicKey;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // Risolvi gli alias in percorsi reali
        $privateKeyRealPath = \Yii::getAlias($this->privateKeyPath);
        $publicKeyRealPath = \Yii::getAlias($this->publicKeyPath);

        if (!$this->privateKeyPath || !file_exists($privateKeyRealPath)) {
            throw new InvalidArgumentException('La chiave privata JWT non è stata trovata: ' . $privateKeyRealPath);
        }

        if (!$this->publicKeyPath || !file_exists($publicKeyRealPath)) {
            throw new InvalidArgumentException('La chiave pubblica JWT non è stata trovata: ' . $publicKeyRealPath);
        }

        $this->_privateKey = file_get_contents($privateKeyRealPath);
        $this->_publicKey = file_get_contents($publicKeyRealPath);
    }

    /**
     * Genera un token JWT
     * 
     * @param array $payload I dati da includere nel token
     * @return string Il token JWT generato
     */
    public function generateToken($payload)
    {
        $now = time();

        $tokenPayload = array_merge([
            'iat' => $now,                      // Issued At
            'iss' => Yii::$app->params['jwt']['issuer'],                     // Issuer
            'aud' => Yii::$app->params['jwt']['audience'],               // Audience
            'nbf' => $now,                      // Not Before
            'exp' => $now + Yii::$app->params['jwt']['tokenDuration'], // Expiration Time
        ], $payload);

        return JWT::encode($tokenPayload, $this->_privateKey, $this->algorithm);
    }

    /**
     * Genera un refresh token
     * 
     * @param array $payload I dati da includere nel refresh token
     * @return string Il refresh token generato
     */
    public function generateRefreshToken($payload)
    {
        $now = time();

        $refreshTokenPayload = array_merge([
            'iat' => $now,                              // Issued At
            'iss' => Yii::$app->params['jwt']['issuer'],                     // Issuer
            'aud' => Yii::$app->params['jwt']['audience'],                   // Audience
            'nbf' => $now,                              // Not Before
            'exp' => $now +  Yii::$app->params['jwt']['refreshTokenDuration'], // Expiration Time
            'type' => 'refresh'                         // Token type
        ], $payload);

        return JWT::encode($refreshTokenPayload, $this->_privateKey, $this->algorithm);
    }

    /**
     * Verifica e decodifica un token JWT
     * 
     * @param string $token Il token JWT da verificare
     * @return object|false I dati decodificati o false in caso di errore
     */
    public function validateToken($token)
    {
        try {
            return JWT::decode($token, new Key($this->_publicKey, $this->algorithm));
        } catch (\Exception $e) {
            $error = $e->getMessage();

            return false;
        }
    }

    /**
     * Ottiene il timestamp di scadenza di un token
     * 
     * @param string $token Il token JWT
     * @return int|false Il timestamp di scadenza o false in caso di errore
     */
    public function getTokenExpiry($token)
    {
        $decoded = $this->validateToken($token);
        return $decoded ? $decoded->exp : false;
    }
}
