<?php

namespace common\helpers;

use Yii;
use common\models\Notification;
use common\models\User;

/**
 * Helper per la gestione delle comunicazioni interne del gestionale
 * Facilita la creazione di comunicazioni automatiche da cron jobs e dal sistema
 */
class CommunicationHelper
{
    /**
     * Invia una comunicazione interna a uno o più utenti
     *
     * @param array|int $recipientUserIds ID utente/i destinatario/i
     * @param string $title Titolo della comunicazione
     * @param string $message Messaggio (opzionale)
     * @param int|null $senderUserId ID utente mittente (null per sistema)
     * @param string $type Tipo di comunicazione (default: internal_communication)
     * @param bool $requiresReadConfirmation Se richiede conferma di lettura
     * @param string|null $scheduledFor Data/ora programmata (formato Y-m-d H:i:s)
     * @return array Risultato con successo e dettagli
     */
    public static function sendCommunication(
        $recipientUserIds,
        string $title,
        string $message = '',
        ?int $senderUserId = null,
        string $type = Notification::TYPE_INTERNAL_COMMUNICATION,
        bool $requiresReadConfirmation = false,
        ?string $scheduledFor = null
    ): array {
        // Normalizza destinatari come array
        if (!is_array($recipientUserIds)) {
            $recipientUserIds = [$recipientUserIds];
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($recipientUserIds as $recipientId) {
            try {
                // Verifica che il destinatario esista
                $recipient = User::findOne($recipientId);
                if (!$recipient) {
                    $results[] = [
                        'recipient_id' => $recipientId,
                        'success' => false,
                        'error' => "Utente destinatario ID $recipientId non trovato"
                    ];
                    $errorCount++;
                    continue;
                }

                // Crea la comunicazione
                $notification = new Notification();
                $notification->recipient_user_id = $recipientId;
                $notification->sender_user_id = $senderUserId;
                $notification->notification_type = $type;
                $notification->title = $title;
                $notification->message = $message;
                $notification->requires_read_confirmation = $requiresReadConfirmation;
                $notification->scheduled_for = $scheduledFor;

                if ($notification->save()) {
                    $results[] = [
                        'recipient_id' => $recipientId,
                        'notification_id' => $notification->id,
                        'success' => true
                    ];
                    $successCount++;

                    // Log per audit
                    Yii::info("Comunicazione interna creata: ID {$notification->id}, destinatario: {$recipientId}, titolo: {$title}", 'communication');
                } else {
                    $results[] = [
                        'recipient_id' => $recipientId,
                        'success' => false,
                        'error' => 'Errore nel salvataggio: ' . implode(', ', $notification->getFirstErrors())
                    ];
                    $errorCount++;
                }

            } catch (\Exception $e) {
                $results[] = [
                    'recipient_id' => $recipientId,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $errorCount++;

                // Log errore
                Yii::error("Errore creazione comunicazione per utente $recipientId: " . $e->getMessage(), 'communication');
            }
        }

        return [
            'success' => $successCount > 0,
            'total_sent' => $successCount,
            'total_errors' => $errorCount,
            'results' => $results
        ];
    }

    /**
     * Invia una comunicazione a tutti gli utenti con un determinato ruolo
     *
     * @param string|array $roles Ruolo/i destinatario/i
     * @param string $title Titolo della comunicazione
     * @param string $message Messaggio (opzionale)
     * @param int|null $senderUserId ID utente mittente
     * @param string $type Tipo di comunicazione
     * @return array Risultato con successo e dettagli
     */
    public static function sendToRole(
        $roles,
        string $title,
        string $message = '',
        ?int $senderUserId = null,
        string $type = Notification::TYPE_INTERNAL_COMMUNICATION
    ): array {
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        // Trova tutti gli utenti con i ruoli specificati
        $auth = Yii::$app->authManager;
        $userIds = [];

        foreach ($roles as $roleName) {
            $assignments = $auth->getUserIdsByRole($roleName);
            $userIds = array_merge($userIds, $assignments);
        }

        $userIds = array_unique($userIds);

        if (empty($userIds)) {
            return [
                'success' => false,
                'total_sent' => 0,
                'total_errors' => 0,
                'results' => [],
                'error' => 'Nessun utente trovato per i ruoli specificati: ' . implode(', ', $roles)
            ];
        }

        return self::sendCommunication($userIds, $title, $message, $senderUserId, $type);
    }

    /**
     * Invia una comunicazione a tutti gli amministratori e manager
     *
     * @param string $title Titolo della comunicazione
     * @param string $message Messaggio
     * @param int|null $senderUserId ID utente mittente
     * @return array Risultato
     */
    public static function sendToManagement(
        string $title,
        string $message = '',
        ?int $senderUserId = null
    ): array {
        return self::sendToRole(['admin', 'manager'], $title, $message, $senderUserId);
    }

    /**
     * Crea comunicazioni di sistema predefinite
     */

    /**
     * Notifica nuova richiesta documento da paziente
     */
    public static function notifyNewDocumentRequest(int $patientId, string $documentType): array
    {
        $patient = User::find()
            ->joinWith(['patient'])
            ->where(['user.id' => $patientId])
            ->one();

        if (!$patient) {
            return ['success' => false, 'error' => 'Paziente non trovato'];
        }

        $patientName = $patient->profile ? $patient->profile->getFullName() : $patient->username;

        return self::sendToManagement(
            'Nuova richiesta documento',
            "Il paziente $patientName ha richiesto il documento: $documentType.\n\nAccedi al gestionale per elaborare la richiesta."
        );
    }

    /**
     * Notifica nuovo paziente registrato
     */
    public static function notifyNewPatientRegistration(int $patientId): array
    {
        $patient = User::find()
            ->joinWith(['patient', 'profile'])
            ->where(['user.id' => $patientId])
            ->one();

        if (!$patient) {
            return ['success' => false, 'error' => 'Paziente non trovato'];
        }

        $patientName = $patient->profile ? $patient->profile->getFullName() : $patient->username;

        return self::sendToManagement(
            'Nuovo paziente registrato',
            "Un nuovo paziente si è registrato: $patientName ({$patient->email}).\n\nAccedi al gestionale per completare la configurazione del profilo."
        );
    }

    /**
     * Notifica scadenza piano terapeutico
     */
    public static function notifyTherapeuticPlanExpiring(int $planId, string $patientName, string $expiryDate): array
    {
        return self::sendToRole(
            ['admin', 'manager', 'coordinator'],
            'Piano terapeutico in scadenza',
            "Il piano terapeutico del paziente $patientName scadrà il $expiryDate.\n\nÈ necessario programmare il rinnovo o la conclusione del piano."
        );
    }

    /**
     * Notifica superamento soglia assenze
     */
    public static function notifyAbsenceThresholdExceeded(int $patientId, string $patientName, float $absencePercentage): array
    {
        return self::sendToRole(
            ['admin', 'manager', 'coordinator'],
            'Soglia assenze superata',
            "Il paziente $patientName ha superato la soglia del 10% di assenze ingiustificate.\n\nPercentuale attuale: " . number_format($absencePercentage, 1) . "%\n\nÈ richiesto un intervento."
        );
    }

    /**
     * Notifica appuntamento non confermato
     */
    public static function notifyUnconfirmedAppointment(int $appointmentId, string $patientName, string $appointmentDate): array
    {
        return self::sendToRole(
            ['admin', 'manager'],
            'Appuntamento non confermato',
            "L'appuntamento del paziente $patientName previsto per $appointmentDate non è stato ancora confermato.\n\nControllare lo stato dell'appuntamento."
        );
    }

    /**
     * Utility per validare i parametri di una comunicazione
     */
    private static function validateCommunicationParams(array $params): array
    {
        $errors = [];

        if (empty($params['title'])) {
            $errors[] = 'Il titolo è obbligatorio';
        }

        if (isset($params['type']) && !in_array($params['type'], [
            Notification::TYPE_INFO,
            Notification::TYPE_REMINDER,
            Notification::TYPE_DEADLINE,
            Notification::TYPE_MANDATORY_READ,
            Notification::TYPE_INTERNAL_COMMUNICATION
        ])) {
            $errors[] = 'Tipo di comunicazione non valido';
        }

        return $errors;
    }

    /**
     * Ottiene statistiche sulle comunicazioni
     */
    public static function getStatistics(int $days = 30): array
    {
        $fromDate = date('Y-m-d H:i:s', strtotime("-$days days"));

        $total = Notification::find()
            ->where(['notification_type' => Notification::TYPE_INTERNAL_COMMUNICATION])
            ->andWhere(['>=', 'created_at', $fromDate])
            ->count();

        $read = Notification::find()
            ->where(['notification_type' => Notification::TYPE_INTERNAL_COMMUNICATION])
            ->andWhere(['>=', 'created_at', $fromDate])
            ->andWhere(['not', ['read_at' => null]])
            ->count();

        $unread = $total - $read;

        return [
            'period_days' => $days,
            'total_sent' => $total,
            'total_read' => $read,
            'total_unread' => $unread,
            'read_percentage' => $total > 0 ? round(($read / $total) * 100, 1) : 0
        ];
    }
} 