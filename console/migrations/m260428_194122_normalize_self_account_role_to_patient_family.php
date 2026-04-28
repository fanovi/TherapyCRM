<?php

use yii\db\Migration;

/**
 * Normalizza il ruolo RBAC degli account "io stesso" (relationship_type = 'self').
 *
 * Storia:
 * - In `PatientController::actionCreateCredentials` gli account creati con
 *   `relationship_type = 'self'` ricevevano il ruolo `patient`.
 * - La migration m260303_100000 ha rimosso `app_login` dal ruolo `patient`,
 *   lasciandolo solo a `therapist` e `patient_family`.
 * - Conseguenza: gli utenti "io stesso" non potevano piu' loggarsi all'app
 *   mobile e non comparivano nelle liste account in /patient/accounts e
 *   /patient/view-account (entrambe filtrano per role = 'patient_family').
 *
 * Fix:
 * - Tutti gli account legati ad un paziente (presenti in `account_patients`)
 *   ricevono il ruolo `patient_family`. La distinzione self/familiare resta
 *   in `account_patients.relationship_type` (mai in RBAC).
 * - Questa migration aggiorna gli utenti esistenti con ruolo `patient`
 *   convertendoli a `patient_family` quando hanno almeno una riga in
 *   `account_patients`.
 *
 * Il fix lato codice (assign sempre 'patient_family') viene fatto in
 * `PatientController::actionCreateCredentials`.
 */
class m260428_194122_normalize_self_account_role_to_patient_family extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $patientFamilyRole = $auth->getRole('patient_family');
        if (!$patientFamilyRole) {
            echo "Ruolo 'patient_family' non trovato. Migration interrotta.\n";
            return false;
        }

        // Utenti con ruolo 'patient' che hanno almeno una riga in account_patients.
        $userIds = (new \yii\db\Query())
            ->select('aa.user_id')
            ->distinct()
            ->from(['aa' => 'auth_assignment'])
            ->innerJoin(['ap' => 'account_patients'], 'ap.user_id = aa.user_id')
            ->where(['aa.item_name' => 'patient'])
            ->column();

        if (empty($userIds)) {
            echo "Nessun utente da normalizzare.\n";
            return true;
        }

        echo "Trovati " . count($userIds) . " utenti da normalizzare da 'patient' a 'patient_family'.\n";

        $updated = 0;
        $skipped = 0;
        foreach ($userIds as $userId) {
            $userId = (int) $userId;

            // Se l'utente ha gia' anche 'patient_family', basta rimuovere 'patient'.
            $alreadyFamily = $this->db->createCommand(
                "SELECT 1 FROM auth_assignment WHERE user_id = :uid AND item_name = 'patient_family' LIMIT 1",
                [':uid' => $userId]
            )->queryScalar();

            if ($alreadyFamily) {
                $this->db->createCommand()
                    ->delete('auth_assignment', ['user_id' => $userId, 'item_name' => 'patient'])
                    ->execute();
                echo "  - User $userId: aveva entrambi i ruoli, rimosso 'patient'.\n";
                $skipped++;
                continue;
            }

            // Update in place: cambia item_name preservando created_at.
            $rows = $this->db->createCommand()
                ->update(
                    'auth_assignment',
                    ['item_name' => 'patient_family'],
                    ['user_id' => $userId, 'item_name' => 'patient']
                )
                ->execute();

            if ($rows > 0) {
                echo "  ✓ User $userId: 'patient' -> 'patient_family'.\n";
                $updated++;
            }
        }

        echo "\nTotale: $updated convertiti, $skipped gia' 'patient_family' (rimosso doppio).\n";

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * Rollback: riconverte a 'patient' i `patient_family` che hanno
     * `account_patients.relationship_type = 'self'`. Approssimazione del
     * comportamento storico (non perfettamente reversibile perche' alcuni
     * utenti potrebbero essere stati creati come patient_family fin dall'inizio
     * con relation 'self' dopo il fix).
     */
    public function safeDown()
    {
        $userIds = (new \yii\db\Query())
            ->select('aa.user_id')
            ->distinct()
            ->from(['aa' => 'auth_assignment'])
            ->innerJoin(['ap' => 'account_patients'], 'ap.user_id = aa.user_id')
            ->where([
                'aa.item_name' => 'patient_family',
                'ap.relationship_type' => 'self',
            ])
            ->column();

        if (empty($userIds)) {
            echo "Nessun utente da ripristinare.\n";
            return true;
        }

        $reverted = 0;
        foreach ($userIds as $userId) {
            $rows = $this->db->createCommand()
                ->update(
                    'auth_assignment',
                    ['item_name' => 'patient'],
                    ['user_id' => (int) $userId, 'item_name' => 'patient_family']
                )
                ->execute();
            if ($rows > 0) {
                $reverted++;
            }
        }

        echo "Ripristinati $reverted utenti a 'patient'.\n";
        return true;
    }
}
