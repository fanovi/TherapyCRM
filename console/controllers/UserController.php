<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\User;
use common\models\UserProfile;

/**
 * Controller console per la gestione utenti (creazione super_admin, ecc.).
 */
class UserController extends Controller
{
    /**
     * Crea un utente con ruolo super_admin.
     *
     * Parametri obbligatori (coerenti con User e UserProfile):
     * - email: indirizzo email (univoco, validato)
     * - password: password (8-20 caratteri, maiuscola, minuscola, numero, carattere speciale)
     * - first_name: nome (profilo)
     * - last_name: cognome (profilo)
     *
     * Uso:
     *   php yii user/create-super-admin <email> <password> <first_name> <last_name>
     *
     * Esempio:
     *   php yii user/create-super-admin admin@example.com "MyP@ssw0rd" Mario Rossi
     *
     * Nota: per first_name e last_name con spazi usare le virgolette.
     *
     * @param string $email Email (obbligatorio)
     * @param string $password Password (obbligatorio)
     * @param string $firstName Nome (obbligatorio)
     * @param string $lastName Cognome (obbligatorio)
     * @return int Exit code
     */
    public function actionCreateSuperAdmin($email, $password, $firstName, $lastName)
    {
        $email = trim($email);
        $firstName = trim($firstName);
        $lastName = trim($lastName);

        if ($email === '' || $firstName === '' || $lastName === '') {
            $this->stderr("Errore: email, first_name e last_name non possono essere vuoti.\n");
            return ExitCode::DATAERR;
        }

        if (strlen($password) < (Yii::$app->params['user.passwordMinLength'] ?? 8)) {
            $this->stderr("Errore: la password deve essere di almeno " . (Yii::$app->params['user.passwordMinLength'] ?? 8) . " caratteri.\n");
            return ExitCode::DATAERR;
        }

        if (User::findByEmail($email) !== null) {
            $this->stderr("Errore: esiste già un utente con email '{$email}'.\n");
            return ExitCode::DATAERR;
        }

        $user = new User(['scenario' => 'create']);
        $user->email = $email;
        $user->username = $email;
        $user->password = $password;
        $user->password_repeat = $password;
        $user->status = User::STATUS_ACTIVE;

        if (!$user->validate()) {
            $this->stderr("Errore validazione utente:\n");
            foreach ($user->getFirstErrors() as $attr => $msg) {
                $this->stderr("  - {$attr}: {$msg}\n");
            }
            return ExitCode::DATAERR;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$user->save(false)) {
                throw new \RuntimeException('Salvataggio utente fallito: ' . implode(', ', $user->getFirstErrors()));
            }

            $profile = new UserProfile();
            $profile->user_id = $user->id;
            $profile->first_name = $firstName;
            $profile->last_name = $lastName;

            if (!$profile->validate()) {
                throw new \RuntimeException('Validazione profilo: ' . implode(', ', $profile->getFirstErrors()));
            }
            if (!$profile->save(false)) {
                throw new \RuntimeException('Salvataggio profilo fallito.');
            }

            $auth = Yii::$app->authManager;
            $superAdminRole = $auth->getRole('super_admin');
            if (!$superAdminRole) {
                throw new \RuntimeException("Ruolo 'super_admin' non trovato. Eseguire prima: php yii migrate");
            }
            $auth->assign($superAdminRole, $user->id);

            $transaction->commit();

            $this->stdout("✓ Utente super_admin creato con successo.\n");
            $this->stdout("  ID: {$user->id}\n");
            $this->stdout("  Email: {$user->email}\n");
            $this->stdout("  Nome: {$profile->first_name} {$profile->last_name}\n");

            return ExitCode::OK;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stderr("Errore: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
