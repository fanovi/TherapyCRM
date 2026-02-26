<?php

namespace common\services;

use Yii;
use common\models\User;
use common\models\UserTwoFactorAuth;
use common\models\TrustedDevice;
use common\models\SystemSetting;
use PragmaRX\Google2FA\Google2FA;

/**
 * Service per la gestione dell'autenticazione a due fattori (2FA)
 */
class TwoFactorService
{
    /**
     * Verifica se il 2FA è richiesto per l'utente
     *
     * @param User $user
     * @param string|null $deviceToken Token del dispositivo per "ricorda dispositivo"
     * @return bool
     */
    public function is2faRequired($user, $deviceToken = null)
    {
        // 1. Se 2FA non è abilitato globalmente, non è richiesto
        if (!SystemSetting::is2faEnabled()) {
            return false;
        }

        // 2. Controlla se l'utente ha 2FA abilitato
        $tfa = UserTwoFactorAuth::findOne(['user_id' => $user->id]);
        if (!$tfa || !$tfa->is_enabled) {
            return false;
        }

        // 3. Se "ricorda dispositivo" è abilitato e il device è fidato, non è richiesto
        if (SystemSetting::isRememberDeviceEnabled() && !empty($deviceToken)) {
            if (TrustedDevice::isDeviceTrusted($user->id, $deviceToken)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ottiene il metodo 2FA preferito dall'utente
     *
     * @param int $userId
     * @return string 'email' o 'totp'
     */
    public function getPreferredMethod($userId)
    {
        $tfa = UserTwoFactorAuth::findOne(['user_id' => $userId]);
        return $tfa ? $tfa->preferred_method : 'email';
    }

    /**
     * Invia OTP via email
     *
     * @param User $user
     * @return bool
     */
    public function sendEmailOtp($user)
    {
        $tfa = UserTwoFactorAuth::findOrCreate($user->id);
        $code = $tfa->generateEmailOtp();

        return $this->sendOtpEmail($user, $code);
    }

    /**
     * Verifica un codice OTP email
     *
     * @param int $userId
     * @param string $code
     * @return bool
     */
    public function verifyEmailOtp($userId, $code)
    {
        $tfa = UserTwoFactorAuth::findOne(['user_id' => $userId]);
        if (!$tfa) {
            return false;
        }

        return $tfa->validateEmailOtp($code);
    }

    /**
     * Inizializza il setup TOTP per un utente
     *
     * @param User $user
     * @return array ['secret' => string, 'otpauth_uri' => string]
     */
    public function initTotpSetup($user)
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $issuer = SystemSetting::getTotpIssuerName();

        $tfa = UserTwoFactorAuth::findOrCreate($user->id);
        $tfa->setTotpSecretEncrypted($secret);
        $tfa->totp_confirmed_at = null;
        $tfa->save();

        // Genera URI per QR code (lato client genererà il QR)
        $otpauthUri = $google2fa->getQRCodeUrl($issuer, $user->email, $secret);

        return [
            'secret' => $secret,
            'otpauth_uri' => $otpauthUri,
        ];
    }

    /**
     * Conferma il setup TOTP con il primo codice valido
     *
     * @param int $userId
     * @param string $code
     * @return bool
     */
    public function confirmTotpSetup($userId, $code)
    {
        $tfa = UserTwoFactorAuth::findOne(['user_id' => $userId]);
        if (!$tfa) {
            return false;
        }

        $secret = $tfa->getTotpSecretDecrypted();
        if (!$secret) {
            return false;
        }

        $google2fa = new Google2FA();
        if (!$google2fa->verifyKey($secret, $code)) {
            return false;
        }

        $tfa->totp_confirmed_at = time();
        $tfa->preferred_method = 'totp';
        $tfa->is_enabled = 1;
        return $tfa->save();
    }

    /**
     * Verifica un codice TOTP durante il login
     *
     * @param int $userId
     * @param string $code
     * @return bool
     */
    public function verifyTotpCode($userId, $code)
    {
        $tfa = UserTwoFactorAuth::findOne(['user_id' => $userId]);
        if (!$tfa) {
            return false;
        }

        $secret = $tfa->getTotpSecretDecrypted();
        if (!$secret) {
            return false;
        }

        $google2fa = new Google2FA();
        return $google2fa->verifyKey($secret, $code);
    }

    /**
     * Abilita 2FA per un utente
     *
     * @param int $userId
     * @param string $method 'email' o 'totp'
     * @return bool
     */
    public function enableForUser($userId, $method = 'email')
    {
        $tfa = UserTwoFactorAuth::findOrCreate($userId);
        $tfa->is_enabled = 1;
        $tfa->preferred_method = $method;
        return $tfa->save();
    }

    /**
     * Disabilita 2FA per un utente
     *
     * @param int $userId
     * @return bool
     */
    public function disableForUser($userId)
    {
        $tfa = UserTwoFactorAuth::findOne(['user_id' => $userId]);
        if (!$tfa) {
            return true;
        }

        $tfa->is_enabled = 0;
        $tfa->totp_secret = null;
        $tfa->totp_confirmed_at = null;
        $tfa->email_otp_code = null;
        $tfa->email_otp_expires_at = null;
        $tfa->email_otp_attempts = 0;
        $result = $tfa->save();

        // Revoca tutti i dispositivi fidati
        TrustedDevice::revokeAllForUser($userId);

        return $result;
    }

    /**
     * Invia email con codice OTP
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    private function sendOtpEmail($user, $code)
    {
        try {
            $profile = $user->profile;
            $userName = $profile ? $profile->first_name : $user->email;

            $htmlBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px;'>
                    <h2 style='color: #333; margin-bottom: 20px;'>Codice di verifica - " . Yii::$app->name . "</h2>

                    <p style='color: #666; line-height: 1.6;'>Ciao {$userName},</p>

                    <p style='color: #666; line-height: 1.6;'>
                        Il tuo codice di verifica per accedere è:
                    </p>

                    <div style='text-align: center; margin: 30px 0;'>
                        <span style='background-color: #007bff; color: white; padding: 15px 30px; font-size: 32px; font-weight: bold; letter-spacing: 8px; border-radius: 8px; display: inline-block;'>
                            {$code}
                        </span>
                    </div>

                    <p style='color: #666; line-height: 1.6;'>
                        Questo codice scade tra <strong>" . (SystemSetting::getEmailOtpExpirySeconds() / 60) . " minuti</strong>.
                    </p>

                    <p style='color: #666; line-height: 1.6;'>
                        Se non hai richiesto questo codice, ignora questa email e assicurati che nessuno stia tentando di accedere al tuo account.
                    </p>

                    <hr style='border: none; border-top: 1px solid #dee2e6; margin: 20px 0;'>

                    <p style='color: #999; font-size: 12px; text-align: center;'>
                        Questa email è stata inviata automaticamente. Non rispondere a questo messaggio.
                    </p>
                </div>
            </div>";

            $textBody = "Codice di verifica - " . Yii::$app->name . "\n\n"
                . "Ciao {$userName},\n\n"
                . "Il tuo codice di verifica per accedere è: {$code}\n\n"
                . "Questo codice scade tra " . (SystemSetting::getEmailOtpExpirySeconds() / 60) . " minuti.\n\n"
                . "Se non hai richiesto questo codice, ignora questa email.";

            return Yii::$app
                ->mailer
                ->compose()
                ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
                ->setTo($user->email)
                ->setSubject('Codice di verifica - ' . Yii::$app->name)
                ->setHtmlBody($htmlBody)
                ->setTextBody($textBody)
                ->send();
        } catch (\Exception $e) {
            Yii::error('Failed to send 2FA OTP email: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
