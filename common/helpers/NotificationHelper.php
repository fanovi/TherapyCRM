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
     * @return array Risultato dell'invio
     */
    public static function sendToUsers($userIds, $title, $message, $type = Notification::TYPE_INFO, $data = [])
    {
        try {
            return Yii::$app->notificationService->sendNotification($userIds, $title, $message, $type, null, false, null, $data);
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
     * @return array
     */
    public static function sendToRole($roleName, $title, $message, $type = Notification::TYPE_INFO, $data = [])
    {
        $userIds = static::getUserIdsByRole($roleName);
        if (empty($userIds)) {
            return [
                'success' => false,
                'error' => "Nessun utente trovato con il ruolo '{$roleName}'"
            ];
        }

        return static::sendToUsers($userIds, $title, $message, $type, $data);
    }

    /**
     * Invia una notifica a tutti i terapisti
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @return array
     */
    public static function sendToTherapists($title, $message, $type = Notification::TYPE_INFO, $data = [])
    {
        return static::sendToRole('therapist', $title, $message, $type, $data);
    }

    /**
     * Invia una notifica a tutti i coordinatori
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @return array
     */
    public static function sendToCoordinators($title, $message, $type = Notification::TYPE_INFO, $data = [])
    {
        return static::sendToRole('coordinator', $title, $message, $type, $data);
    }

    /**
     * Invia una notifica a tutti gli amministratori
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @return array
     */
    public static function sendToAdmins($title, $message, $type = Notification::TYPE_INFO, $data = [])
    {
        return static::sendToRole('admin', $title, $message, $type, $data);
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
                    'patient_name' => $plan->patient->first_name . ' ' . $plan->patient->last_name,
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
            // Questa query dipende dalla struttura delle tabelle absences e appointments
            $sql = "
                SELECT 
                    p.id as patient_id,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    tp.created_by,
                    COUNT(a.id) as total_appointments,
                    COUNT(abs.id) as total_absences,
                    ROUND((COUNT(abs.id) / COUNT(a.id)) * 100, 2) as absence_percentage
                FROM patients p
                JOIN therapeutic_plans tp ON p.id = tp.patient_id
                JOIN appointments a ON tp.id = a.therapeutic_plan_id
                LEFT JOIN absences abs ON a.id = abs.appointment_id
                WHERE tp.status = 'active'
                GROUP BY p.id, tp.created_by
                HAVING absence_percentage >= :threshold
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
            $assignments = Yii::$app->authManager->getAssignments($roleName);
            
            foreach ($assignments as $userId => $assignment) {
                $userIds[] = (int)$userId;
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