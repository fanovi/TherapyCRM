<?php

namespace common\components;

use Yii;
use yii\base\Component;
use common\models\Notification;
use common\models\NotificationTemplate;
use common\models\User;

/**
 * Servizio principale per gestire le notifiche
 * Integra i modelli del database con OneSignal
 */
class NotificationService extends Component
{
    /**
     * @var OneSignalService Servizio OneSignal
     */
    private $_oneSignal;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->_oneSignal = Yii::$app->oneSignal;
    }

    /**
     * Invia una notifica a uno o più utenti
     *
     * @param int|array $userIds Singolo user_id o array di user_ids
     * @param string $title Titolo della notifica
     * @param string $message Messaggio della notifica (può essere HTML)
     * @param string $type Tipo di notifica (opzionale)
     * @param int|null $senderId ID dell'utente mittente (opzionale)
     * @param bool $requiresReadConfirmation Se richiede conferma di lettura
     * @param string|null $scheduledFor Data/ora programmazione (formato Y-m-d H:i:s)
     * @param array $data Dati aggiuntivi per OneSignal
     * @param bool $skipPush Se true, la notifica viene solo salvata nel DB e non inviata via OneSignal
     * @return array Risultato con informazioni sulle notifiche create e inviate
     * @throws \Exception
     */
    public function sendNotification(
        $userIds, 
        $title, 
        $message, 
        $type = Notification::TYPE_INFO,
        $senderId = null,
        $requiresReadConfirmation = false,
        $scheduledFor = null,
        $data = [],
        $skipPush = false
    ) {


        // Normalizza userIds in array
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        // Valida che gli utenti esistano
        $validUserIds = $this->validateUsers($userIds);
        if (empty($validUserIds)) {
            throw new \Exception('Nessun utente valido trovato');
        }

        $results = [
            'notifications_created' => 0,
            'notifications_sent' => 0,
            'errors' => [],
            'onesignal_response' => null
        ];

        // Crea le notifiche nel database
        $notifications = [];
        foreach ($validUserIds as $userId) {
            $notification = new Notification([
                'recipient_user_id' => $userId,
                'sender_user_id' => $senderId,
                'notification_type' => $type,
                'title' => $title,
                'message' => $message,
                'requires_read_confirmation' => $requiresReadConfirmation,
                'scheduled_for' => $scheduledFor,
            ]);

            if ($notification->save()) {
                $notifications[] = $notification;
                $results['notifications_created']++;
            } else {
                $results['errors'][] = 'Errore creazione notifica per utente ' . $userId . ': ' . implode(', ', $notification->getFirstErrors());
            }
        }

        // Se skipPush è true, non inviare tramite OneSignal
        if ($skipPush) {
            return $results;
        }

        // Se non ci sono notifiche programmate, invia subito tramite OneSignal
        if (empty($scheduledFor)) {
            try {
                // Assicurati che $data sia sempre un array
                if (!is_array($data)) {
                    $data = [];
                }
                
                // Aggiungi dati aggiuntivi per identificare le notifiche nel database
                $pushData = array_merge($data, [
                    'notification_ids' => array_map(function($n) { return $n->id; }, $notifications)
                ]);

                $oneSignalResponse = $this->_oneSignal->sendToUsers($validUserIds, $title, $message, $pushData);
                $results['onesignal_response'] = $oneSignalResponse;

                // Segna le notifiche come inviate
                foreach ($notifications as $notification) {
                    $notification->markAsSent();
                    $results['notifications_sent']++;
                }

            } catch (\Exception $e) {
                $results['errors'][] = 'Errore invio OneSignal: ' . $e->getMessage();
                Yii::error('Errore invio notifiche OneSignal: ' . $e->getMessage(), __METHOD__);
            }
        }

        return $results;
    }

    /**
     * Invia una notifica utilizzando un template
     *
     * @param string $templateCode Codice del template
     * @param int|array $userIds Destinatari
     * @param array $variables Variabili per sostituire i placeholder nel template
     * @param int|null $senderId Mittente
     * @param string|null $scheduledFor Data programmazione
     * @param array $data Dati aggiuntivi OneSignal
     * @return array
     * @throws \Exception
     */
    public function sendFromTemplate(
        $templateCode, 
        $userIds, 
        $variables = [], 
        $senderId = null, 
        $scheduledFor = null,
        $data = []
    ) {
        $template = NotificationTemplate::findByCode($templateCode);
        if (!$template) {
            throw new \Exception("Template '{$templateCode}' non trovato o non attivo");
        }

        $processed = $template->processTemplate($variables);
        
        return $this->sendNotification(
            $userIds,
            $processed['title'],
            $processed['message'],
            $template->type,
            $senderId,
            $template->type === NotificationTemplate::TYPE_MANDATORY_READ,
            $scheduledFor,
            $data
        );
    }

    /**
     * Invia una notifica broadcast a tutti gli utenti dell'app
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param int|null $senderId
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function sendBroadcast($title, $message, $type = Notification::TYPE_INFO, $senderId = null, $data = [])
    {
        try {
            $oneSignalResponse = $this->_oneSignal->sendToAll($title, $message, $data);
            
            // Per le notifiche broadcast, creiamo un record speciale nel database
            $notification = new Notification([
                'recipient_user_id' => null, // NULL indica broadcast
                'sender_user_id' => $senderId,
                'notification_type' => $type,
                'title' => $title,
                'message' => $message,
                'requires_read_confirmation' => false,
            ]);
            $notification->markAsSent();
            $notification->save();

            return [
                'broadcast_sent' => true,
                'onesignal_response' => $oneSignalResponse,
                'notification_id' => $notification->id
            ];

        } catch (\Exception $e) {
            Yii::error('Errore invio broadcast: ' . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    /**
     * Elabora le notifiche programmate che devono essere inviate
     *
     * @return array Statistiche delle notifiche elaborate
     */
    public function processScheduledNotifications()
    {
        $pendingNotifications = Notification::findPendingToSend()->all();
        
        $results = [
            'processed' => 0,
            'sent' => 0,
            'errors' => []
        ];

        // Raggruppa le notifiche per titolo e messaggio per ottimizzare l'invio
        $groupedNotifications = [];
        foreach ($pendingNotifications as $notification) {
            $key = md5($notification->title . $notification->message);
            $groupedNotifications[$key]['notifications'][] = $notification;
            $groupedNotifications[$key]['title'] = $notification->title;
            $groupedNotifications[$key]['message'] = $notification->message;
        }

        foreach ($groupedNotifications as $group) {
            try {
                $userIds = array_map(function($n) { return $n->recipient_user_id; }, $group['notifications']);
                $notificationIds = array_map(function($n) { return $n->id; }, $group['notifications']);

                $data = ['notification_ids' => $notificationIds];
                $oneSignalResponse = $this->_oneSignal->sendToUsers($userIds, $group['title'], $group['message'], $data);

                // Segna tutte le notifiche del gruppo come inviate
                foreach ($group['notifications'] as $notification) {
                    $notification->markAsSent();
                    $results['sent']++;
                }

                $results['processed']++;

            } catch (\Exception $e) {
                $results['errors'][] = 'Errore invio gruppo notifiche: ' . $e->getMessage();
                Yii::error('Errore invio gruppo notifiche programmate: ' . $e->getMessage(), __METHOD__);
            }
        }

        return $results;
    }

    /**
     * Segna una notifica come letta
     *
     * @param int $notificationId
     * @param int $userId (per verifica sicurezza)
     * @return bool
     */
    public function markAsRead($notificationId, $userId)
    {
        $notification = Notification::findOne([
            'id' => $notificationId,
            'recipient_user_id' => $userId
        ]);

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Ottiene le notifiche non lette di un utente
     *
     * @param int $userId
     * @param int $limit
     * @return Notification[]
     */
    public function getUnreadNotifications($userId, $limit = 50)
    {
        return Notification::findUnread()
            ->andWhere(['recipient_user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Conta le notifiche non lette di un utente
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount($userId)
    {
        return Notification::findUnread()
            ->andWhere(['recipient_user_id' => $userId])
            ->count();
    }

    /**
     * Valida che gli utenti esistano nel database
     *
     * @param array $userIds
     * @return array Array di user_ids validi
     */
    private function validateUsers($userIds)
    {
        return User::find()
            ->select('id')
            ->where(['id' => $userIds])
            ->column();
    }
} 