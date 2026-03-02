<?php

namespace common\helpers;

use Yii;
use common\models\Notification;
use common\models\User;

/**
 * Helper class per funzioni di utilità per le notifiche
 */
class NotificationHelper
{
    /**
     * Invia una notifica rapida a N utenti
     *
     * @param array|int $userIds Array di user_ids o singolo user_id
     * @param string $title Titolo della notifica
     * @param string $message Messaggio della notifica
     * @param string $type Tipo di notifica
     * @param array $data Dati aggiuntivi per OneSignal
     * @param bool $skipPush Se true, non invia notifiche push
     * @return array Risultato dell'invio
     */
    public static function sendToUsers($userIds, $title, $message, $type = Notification::TYPE_INFO, $data = [], $skipPush = false)
    {
        try {
            return Yii::$app->notificationService->sendNotification($userIds, $title, $message, $type, null, false, null, $data, $skipPush);
        } catch (\Exception $e) {
            Yii::error('NotificationHelper::sendToUsers error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Invia una notifica a tutti gli utenti con un determinato ruolo
     *
     * @param string $roleName Nome del ruolo
     * @param string $title Titolo
     * @param string $message Messaggio
     * @param string $type Tipo notifica
     * @param array $data Dati aggiuntivi
     * @param bool $skipPush Se true, non invia notifiche push
     * @return array
     */
    public static function sendToRole($roleName, $title, $message, $type = Notification::TYPE_INFO, $data = [], $skipPush = false)
    {
        $userIds = static::getUserIdsByRole($roleName);
        if (empty($userIds)) {
            return [
                'success' => false,
                'error' => "Nessun utente trovato con il ruolo '{$roleName}'"
            ];
        }

        return static::sendToUsers($userIds, $title, $message, $type, $data, $skipPush);
    }

    /**
     * Invia una notifica a tutti i terapisti
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @param bool $skipPush Se true, non invia notifiche push
     * @return array
     */
    public static function sendToTherapists($title, $message, $type = Notification::TYPE_INFO, $data = [], $skipPush = false)
    {
        return static::sendToRole('therapist', $title, $message, $type, $data, $skipPush);
    }

    /**
     * Invia una notifica a tutti i coordinatori
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @param bool $skipPush Se true, non invia notifiche push
     * @return array
     */
    public static function sendToCoordinators($title, $message, $type = Notification::TYPE_INFO, $data = [], $skipPush = false)
    {
        return static::sendToRole('coordinator', $title, $message, $type, $data, $skipPush);
    }

    /**
     * Invia una notifica a tutti gli amministratori
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @param bool $skipPush Se true, non invia notifiche push
     * @return array
     */
    public static function sendToAdmins($title, $message, $type = Notification::TYPE_INFO, $data = [], $skipPush = false)
    {
        return static::sendToRole('admin', $title, $message, $type, $data, $skipPush);
    }

     /**
     * Invia una notifica a tutti gli amministratori
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @param bool $skipPush Se true, non invia notifiche push
     * @return array
     */
    public static function sendToManagers($title, $message, $type = Notification::TYPE_INFO, $data = [], $skipPush = false)
    {
        return static::sendToRole('manager', $title, $message, $type, $data, $skipPush);
    }

    /**
     * Invia una notifica programmata utilizzando un template
     *
     * @param string $templateCode Codice del template
     * @param array|int $userIds Destinatari
     * @param array $variables Variabili per il template
     * @param string $scheduledFor Data programmazione (Y-m-d H:i:s)
     * @param array $data Dati aggiuntivi
     * @return array
     */
    public static function sendScheduledFromTemplate($templateCode, $userIds, $variables = [], $scheduledFor = null, $data = [])
    {
        try {
            return Yii::$app->notificationService->sendFromTemplate($templateCode, $userIds, $variables, null, $scheduledFor, $data);
        } catch (\Exception $e) {
            Yii::error('NotificationHelper::sendScheduledFromTemplate error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Invia notifiche di promemoria per piani terapeutici in scadenza
     *
     * @param int $daysBefore Giorni prima della scadenza
     * @return array Statistiche dell'invio
     */
    public static function sendPlanExpirationReminders($daysBefore = 7)
    {
        $results = [
            'notifications_sent' => 0,
            'errors' => []
        ];

        try {
            // Query per trovare i piani in scadenza
            $expiringPlans = \common\models\TherapeuticPlan::find()
                ->where(['status' => 'active'])
                ->andWhere(['>=', 'end_date', date('Y-m-d')])
                ->andWhere(['<=', 'end_date', date('Y-m-d', strtotime("+{$daysBefore} days"))])
                ->with(['patient', 'createdBy'])
                ->all();

            foreach ($expiringPlans as $plan) {
                if (!$plan->patient || !$plan->createdBy) {
                    continue;
                }

                $variables = [
                    'patient_name' => $plan->patient->last_name . ' ' . $plan->patient->first_name,
                    'end_date' => Yii::$app->formatter->asDate($plan->end_date),
                ];

                $result = static::sendScheduledFromTemplate(
                    'PLAN_EXPIRING',
                    $plan->created_by,
                    $variables
                );

                if ($result['notifications_created'] > 0) {
                    $results['notifications_sent']++;
                } else {
                    $results['errors'][] = "Errore invio per piano ID {$plan->id}";
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Yii::error('Errore invio promemoria scadenze: ' . $e->getMessage(), __METHOD__);
        }

        return $results;
    }

    /**
     * Invia notifiche per soglie di assenze superate
     *
     * @param int $thresholdPercentage Soglia percentuale (default 30%)
     * @return array
     */
    public static function sendAbsenceThresholdNotifications($thresholdPercentage = 30)
    {
        $results = [
            'notifications_sent' => 0,
            'errors' => []
        ];

        try {
            // Query per trovare pazienti che hanno superato la soglia di assenze
            $sql = "
                SELECT 
                    p.id as patient_id,
                    CONCAT(p.last_name, ' ', p.first_name) as patient_name,
                    tp.created_by,
                    COUNT(a.id) as total_appointments,
                    COUNT(CASE WHEN a.status IN ('absent_justified', 'absent_not_justified') THEN 1 END) as total_absences,
                    CASE 
                        WHEN COUNT(a.id) > 0 THEN
                            ROUND((COUNT(CASE WHEN a.status IN ('absent_justified', 'absent_not_justified') THEN 1 END) / COUNT(a.id)) * 100, 2)
                        ELSE 0
                    END as absence_percentage
                FROM patients p
                JOIN therapeutic_plans tp ON p.id = tp.patient_id
                JOIN plan_therapies pt ON tp.id = pt.therapeutic_plan_id
                JOIN appointments a ON pt.id = a.plan_therapy_id
                WHERE tp.end_date > CURDATE()
                  AND a.appointment_datetime < NOW()
                  AND a.status NOT IN ('cancelled', 'scheduled')
                GROUP BY p.id, tp.created_by, p.first_name, p.last_name
                HAVING COUNT(a.id) > 0 AND absence_percentage >= :threshold
            ";

            $command = Yii::$app->db->createCommand($sql);
            $command->bindValue(':threshold', $thresholdPercentage);
            $patientsOverThreshold = $command->queryAll();

            foreach ($patientsOverThreshold as $patientData) {
                $variables = [
                    'patient_name' => $patientData['patient_name'],
                    'percentage' => $patientData['absence_percentage']
                ];

                $result = static::sendToUsers(
                    $patientData['created_by'],
                    'Soglia assenze superata',
                    "Il paziente {$patientData['patient_name']} ha superato la soglia di assenze ({$patientData['absence_percentage']}%)",
                    Notification::TYPE_INFO
                );

                if ($result['notifications_created'] > 0) {
                    $results['notifications_sent']++;
                } else {
                    $results['errors'][] = "Errore invio per paziente {$patientData['patient_name']}";
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Yii::error('Errore invio notifiche soglia assenze: ' . $e->getMessage(), __METHOD__);
        }

        return $results;
    }

    /**
     * Genera il contenuto HTML per notifiche di richieste documenti
     * Il contenuto varia in base alla tipologia di richiesta
     *
     * @param array $requestData Dati della richiesta (patient_id, therapeutic_plan_id, etc.)
     * @param array $requestType Dati della tipologia di richiesta
     * @param User $currentUser Utente che ha fatto la richiesta
     * @return string HTML formattato per la notifica
     */
    public static function buildDocumentRequestNotificationHtml($requestData, $requestType, $currentUser)
    {
        $patientId = $requestData['patient_id'] ?? null;
        $therapeuticPlanId = $requestData['therapeutic_plan_id'] ?? null;
        $therapyId = $requestData['therapy_id'] ?? null;

        // Recupera informazioni del paziente
        $patientInfo = self::getPatientInfo($patientId);
        
        // Link al paziente (sempre presente) - usa URL assoluto al frontend
        $patientLink = $patientId ? 
            self::createFrontendUrl('patient/view', ['id' => $patientId]) : 
            '#';

        // Costruisci il messaggio base
        $htmlMessage = "<b>Nuova richiesta: {$requestType['name']}</b><br>";
        $htmlMessage .= "Paziente: <a href='$patientLink'>{$patientInfo['display_name']}</a><br>";

        // Aggiungi informazioni specifiche per tipologia
        switch ($requestType['therapeutic_plan_rule'] ?? 'PLAN_OPTIONAL') {
            case 'PLAN_REQUIRED':
                // Tipologie che richiedono piano terapeutico
                if ($therapeuticPlanId) {
                    $planLink = self::createFrontendUrl('therapeutic-plan/view', ['id' => $therapeuticPlanId]);
                    $htmlMessage .= "Piano terapeutico: <a href='$planLink'>#{$therapeuticPlanId}</a><br>";
                }
                
                if ($therapyId) {
                    $htmlMessage .= "Terapia specifica: #{$therapyId}<br>";
                }
                break;

            case 'PLAN_NOT_ALLOWED':
                // Tipologie generali (certificati, documenti amministrativi)
                $htmlMessage .= "<em>Richiesta generale (non legata a piano terapeutico)</em><br>";
                break;

            case 'PLAN_OPTIONAL':
                // Tipologie flessibili
                if ($therapeuticPlanId) {
                    $planLink = self::createFrontendUrl('therapeutic-plan/view', ['id' => $therapeuticPlanId]);
                    $htmlMessage .= "Piano terapeutico: <a href='$planLink'>#{$therapeuticPlanId}</a><br>";
                } else {
                    $htmlMessage .= "<em>Richiesta generale</em><br>";
                }
                break;
        }

        // Aggiungi note se presenti e richieste dal tipo
        if (!empty($requestData['notes']) && ($requestType['require_notes'] ?? false)) {
            $notes = strlen($requestData['notes']) > 100 ? 
                     substr($requestData['notes'], 0, 100) . '...' : 
                     $requestData['notes'];
            $htmlMessage .= "Note: <em>" . htmlspecialchars($notes) . "</em><br>";
        }

        // Aggiungi periodo se presente (per certificati medici, etc.)
        if (!empty($requestData['date_from']) && !empty($requestData['date_to'])) {
            $dateFrom = date('d/m/Y', strtotime($requestData['date_from']));
            $dateTo = date('d/m/Y', strtotime($requestData['date_to']));
            $htmlMessage .= "Periodo: <b>{$dateFrom} - {$dateTo}</b><br>";
        }

        // Informazioni sul richiedente
        $requesterName = $currentUser->profile ? 
                        $currentUser->profile->last_name . ' ' . $currentUser->profile->first_name :
                        $currentUser->email;
        $htmlMessage .= "Richiedente: <b>{$requesterName}</b>";

        // Aggiungi priorità basata sulla tipologia
        if (strpos(strtolower($requestType['name']), 'urgente') !== false || 
            strpos(strtolower($requestType['name']), 'emergency') !== false) {
            $htmlMessage = "🚨 " . $htmlMessage;
        }

        return $htmlMessage;
    }

    /**
     * Crea URL assoluto per l'applicazione frontend
     *
     * @param string $route Route del frontend (es: 'patient/view')
     * @param array $params Parametri della route
     * @return string URL assoluto al frontend
     */
    private static function createFrontendUrl($route, $params = [])
    {
        // Ottieni il base URL del progetto
        $baseUrl = 'http://localhost/TherapyCRM';
        
        // Costruisci la query string se ci sono parametri
        $queryString = '';
        if (!empty($params)) {
            $queryString = '?' . http_build_query($params);
        }
        
        return "{$baseUrl}/{$route}{$queryString}";
    }

    /**
     * Recupera informazioni del paziente per le notifiche
     *
     * @param int|null $patientId ID del paziente
     * @return array Array con display_name e altre info del paziente
     */
    private static function getPatientInfo($patientId)
    {
        if (!$patientId) {
            return [
                'display_name' => 'Paziente sconosciuto',
                'full_name' => 'N/A',
                'id' => null
            ];
        }

        try {
            // Importa il modello Patient
            $patient = \common\models\Patient::findOne($patientId);
            
            if (!$patient) {
                return [
                    'display_name' => "Paziente #{$patientId} (non trovato)",
                    'full_name' => 'N/A',
                    'id' => $patientId
                ];
            }

            $fullName = trim($patient->last_name . ' ' . $patient->first_name);
            $displayName = !empty($fullName) ? "{$fullName} (#{$patientId})" : "Paziente #{$patientId}";

            return [
                'display_name' => $displayName,
                'full_name' => $fullName,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'id' => $patientId
            ];

        } catch (\Exception $e) {
            // Log dell'errore ma non interrompere la notifica
            Yii::error("Error retrieving patient info for ID {$patientId}: " . $e->getMessage(), __METHOD__);
            
            return [
                'display_name' => "Paziente #{$patientId} (errore caricamento)",
                'full_name' => 'N/A',
                'id' => $patientId
            ];
        }
    }

    /**
     * Invia broadcast di emergenza a tutti gli utenti attivi
     *
     * @param string $title
     * @param string $message
     * @param array $data
     * @return array
     */
    public static function sendEmergencyBroadcast($title, $message, $data = [])
    {
        try {
            return Yii::$app->notificationService->sendBroadcast(
                $title,
                $message,
                Notification::TYPE_MANDATORY_READ,
                null,
                array_merge($data, ['emergency' => true])
            );
        } catch (\Exception $e) {
            Yii::error('NotificationHelper::sendEmergencyBroadcast error: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene gli user_ids degli utenti con un determinato ruolo
     *
     * @param string $roleName
     * @return array
     */
    public static function getUserIdsByRole($roleName)
    {
        if (!Yii::$app->authManager) {
            return [];
        }

        try {
            $userIds = [];
            $assignments = Yii::$app->authManager->getUserIdsByRole($roleName);
            
            foreach ($assignments as $userId => $assignment) {
                $userIds[] = (int)$assignment;
            }
            
            return $userIds;

        } catch (\Exception $e) {
            Yii::error('Errore recupero utenti per ruolo: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    /**
     * Ottiene tutti gli user_ids degli utenti attivi nel sistema
     *
     * @return array
     */
    public static function getAllActiveUserIds()
    {
        return User::find()
            ->select('id')
            ->where(['status' => User::STATUS_ACTIVE])
            ->column();
    }

    /**
     * Verifica se un utente ha notifiche non lette
     *
     * @param int $userId
     * @return bool
     */
    public static function hasUnreadNotifications($userId)
    {
        return Yii::$app->notificationService->getUnreadCount($userId) > 0;
    }

    /**
     * Ottiene il conteggio delle notifiche non lette per un utente
     *
     * @param int $userId
     * @return int
     */
    public static function getUnreadCount($userId)
    {
        return Yii::$app->notificationService->getUnreadCount($userId);
    }
} 