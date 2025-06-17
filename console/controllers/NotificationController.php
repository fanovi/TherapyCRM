<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\helpers\NotificationHelper;

/**
 * Controller console per gestire le notifiche via cron job
 */
class NotificationController extends Controller
{
    /**
     * Elabora e invia tutte le notifiche programmate
     * 
     * Uso: ./yii notification/process-scheduled
     */
    public function actionProcessScheduled()
    {
        $this->stdout("Elaborazione notifiche programmate...\n");
        
        try {
            $result = Yii::$app->notificationService->processScheduledNotifications();
            
            $this->stdout("Risultati:\n");
            $this->stdout("- Gruppi elaborati: {$result['processed']}\n");
            $this->stdout("- Notifiche inviate: {$result['sent']}\n");
            
            if (!empty($result['errors'])) {
                $this->stdout("Errori:\n");
                foreach ($result['errors'] as $error) {
                    $this->stderr("- {$error}\n");
                }
                return self::EXIT_CODE_ERROR;
            }
            
            $this->stdout("Elaborazione completata con successo.\n");
            return self::EXIT_CODE_NORMAL;
            
        } catch (\Exception $e) {
            $this->stderr("Errore durante l'elaborazione: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Invia promemoria per piani terapeutici in scadenza
     * 
     * Uso: ./yii notification/plan-expiration-reminders [giorni]
     * 
     * @param int $days Giorni prima della scadenza (default: 7)
     */
    public function actionPlanExpirationReminders($days = 7)
    {
        $this->stdout("Invio promemoria scadenze piani terapeutici (giorni: {$days})...\n");
        
        try {
            $result = NotificationHelper::sendPlanExpirationReminders($days);
            
            $this->stdout("Risultati:\n");
            $this->stdout("- Notifiche inviate: {$result['notifications_sent']}\n");
            
            if (!empty($result['errors'])) {
                $this->stdout("Errori:\n");
                foreach ($result['errors'] as $error) {
                    $this->stderr("- {$error}\n");
                }
            }
            
            $this->stdout("Invio promemoria completato.\n");
            return self::EXIT_CODE_NORMAL;
            
        } catch (\Exception $e) {
            $this->stderr("Errore durante l'invio promemoria: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Invia notifiche per soglie di assenze superate
     * 
     * Uso: ./yii notification/absence-threshold [soglia_percentuale]
     * 
     * @param int $threshold Soglia percentuale (default: 30)
     */
    public function actionAbsenceThreshold($threshold = 30)
    {
        $this->stdout("Controllo soglie assenze (soglia: {$threshold}%)...\n");
        
        try {
            $result = NotificationHelper::sendAbsenceThresholdNotifications($threshold);
            
            $this->stdout("Risultati:\n");
            $this->stdout("- Notifiche inviate: {$result['notifications_sent']}\n");
            
            if (!empty($result['errors'])) {
                $this->stdout("Errori:\n");
                foreach ($result['errors'] as $error) {
                    $this->stderr("- {$error}\n");
                }
            }
            
            $this->stdout("Controllo soglie assenze completato.\n");
            return self::EXIT_CODE_NORMAL;
            
        } catch (\Exception $e) {
            $this->stderr("Errore durante il controllo soglie: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Invia una notifica di test
     * 
     * Uso: ./yii notification/test [user_id] [titolo] [messaggio]
     * 
     * @param int $userId ID dell'utente destinatario
     * @param string $title Titolo della notifica
     * @param string $message Messaggio della notifica
     */
    public function actionTest($userId = null, $title = 'Test Notifica', $message = 'Questa è una notifica di test')
    {
        if (!$userId) {
            $this->stderr("Specificare l'ID utente: ./yii notification/test [user_id]\n");
            return self::EXIT_CODE_ERROR;
        }

        $this->stdout("Invio notifica di test all'utente {$userId}...\n");
        
        try {
            $result = NotificationHelper::sendToUsers($userId, $title, $message);
            
            if ($result['notifications_created'] > 0) {
                $this->stdout("Notifica di test inviata con successo!\n");
                $this->stdout("- Notifiche create: {$result['notifications_created']}\n");
                $this->stdout("- Notifiche inviate: {$result['notifications_sent']}\n");
                return self::EXIT_CODE_NORMAL;
            } else {
                $this->stderr("Errore nell'invio della notifica di test.\n");
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        $this->stderr("- {$error}\n");
                    }
                }
                return self::EXIT_CODE_ERROR;
            }
            
        } catch (\Exception $e) {
            $this->stderr("Errore durante l'invio della notifica di test: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Invia una notifica broadcast di test
     * 
     * Uso: ./yii notification/test-broadcast [titolo] [messaggio]
     * 
     * @param string $title Titolo del broadcast
     * @param string $message Messaggio del broadcast
     */
    public function actionTestBroadcast($title = 'Test Broadcast', $message = 'Questo è un broadcast di test')
    {
        $this->stdout("Invio broadcast di test...\n");
        
        try {
            $result = Yii::$app->notificationService->sendBroadcast($title, $message);
            
            if ($result['broadcast_sent']) {
                $this->stdout("Broadcast di test inviato con successo!\n");
                $this->stdout("- ID notifica: {$result['notification_id']}\n");
                return self::EXIT_CODE_NORMAL;
            } else {
                $this->stderr("Errore nell'invio del broadcast di test.\n");
                return self::EXIT_CODE_ERROR;
            }
            
        } catch (\Exception $e) {
            $this->stderr("Errore durante l'invio del broadcast di test: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Mostra statistiche delle notifiche
     * 
     * Uso: ./yii notification/stats
     */
    public function actionStats()
    {
        $this->stdout("Statistiche Notifiche:\n");
        $this->stdout("======================\n");
        
        try {
            // Statistiche generali
            $totalNotifications = \common\models\Notification::find()->count();
            $sentNotifications = \common\models\Notification::find()->where(['not', ['sent_at' => null]])->count();
            $unreadNotifications = \common\models\Notification::find()->where(['read_at' => null])->count();
            $scheduledNotifications = \common\models\Notification::find()
                ->where(['not', ['scheduled_for' => null]])
                ->andWhere(['sent_at' => null])
                ->count();

            $this->stdout("Totale notifiche: {$totalNotifications}\n");
            $this->stdout("Notifiche inviate: {$sentNotifications}\n");
            $this->stdout("Notifiche non lette: {$unreadNotifications}\n");
            $this->stdout("Notifiche programmate: {$scheduledNotifications}\n");
            
            // Statistiche per tipo
            $this->stdout("\nNotifiche per tipo:\n");
            $typeStats = \common\models\Notification::find()
                ->select(['notification_type', 'COUNT(*) as count'])
                ->groupBy('notification_type')
                ->asArray()
                ->all();
                
            foreach ($typeStats as $stat) {
                $type = $stat['notification_type'] ?: 'non specificato';
                $this->stdout("- {$type}: {$stat['count']}\n");
            }
            
            // Template attivi
            $activeTemplates = \common\models\NotificationTemplate::find()
                ->where(['is_active' => true])
                ->count();
            $this->stdout("\nTemplate attivi: {$activeTemplates}\n");
            
            return self::EXIT_CODE_NORMAL;
            
        } catch (\Exception $e) {
            $this->stderr("Errore durante il recupero delle statistiche: {$e->getMessage()}\n");
            return self::EXIT_CODE_ERROR;
        }
    }
}