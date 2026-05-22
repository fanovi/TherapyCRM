<?php

namespace common\components;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

/**
 * Servizio per gestire l'invio di notifiche push tramite OneSignal
 */
class OneSignalService extends Component
{
    /**
     * @var string OneSignal App ID
     */
    public $appId;

    /**
     * @var string OneSignal REST API Key
     */
    public $restApiKey;

    /**
     * @var string OneSignal API URL
     */
    public $apiUrl = 'https://onesignal.com/api/v1';

    /**
     * @var bool Se false, salta tutte le chiamate a OneSignal (no push reali).
     * Le notifiche vengono solo persistite a DB. Utile su ambienti test che
     * condividono la stessa app OneSignal della produzione: evita di mandare
     * push a device reali con user_id collidenti.
     * Configurabile via common/config/params-local.php (non versionato).
     */
    public $enabled = true;

    /**
     * @var Client HTTP client
     */
    private $_httpClient;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // Se disabilitato (es. server test), nessuna validazione chiavi ne'
        // setup HTTP client: tutte le send saranno no-op.
        if (!$this->enabled) {
            Yii::info('OneSignal disabilitato (enabled=false): push verranno saltati.', __METHOD__);
            return;
        }

        if (empty($this->appId)) {
            throw new \InvalidArgumentException('OneSignal App ID is required');
        }

        if (empty($this->restApiKey)) {
            throw new \InvalidArgumentException('OneSignal REST API Key is required');
        }

        $this->_httpClient = new Client([
            'baseUrl' => $this->apiUrl,
            'requestConfig' => [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Key ' . $this->restApiKey,
                ],
            ],
        ]);
    }

    /**
     * Invia una notifica a uno o più utenti tramite tag
     *
     * @param string|array $userIds Singolo user_id o array di user_ids
     * @param string $title Titolo della notifica
     * @param string $message Messaggio della notifica
     * @param array $data Dati aggiuntivi da includere nella notifica
     * @param array $options Opzioni aggiuntive per OneSignal
     * @return array Risposta dell'API OneSignal
     * @throws \Exception
     */
    public function sendToUsers($userIds, $title, $message, $data = [], $options = [])
    {
        if (!$this->enabled) {
            Yii::info('OneSignal skip sendToUsers (disabled). user_ids=' . json_encode($userIds), __METHOD__);
            return ['skipped' => true, 'reason' => 'onesignal_disabled', 'recipients' => 0];
        }
        // Normalizza userIds in array
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        // Crea i filtri per i tag (ogni user_id è un tag)
        $filters = $this->createUserTagFilters($userIds);

        // Prepara il payload della notifica
        $payload = array_merge([
            'app_id' => $this->appId,
            'filters' => $filters,
            'headings' => ['en' => $title, 'it' => $title],
            'contents' => ['en' => $message, 'it' => $message],
        ], $options);

        // Aggiungi dati personalizzati se forniti
        if (!empty($data)) {
            $payload['data'] = $data;
        }

        try {
            $response = $this->_httpClient->post('notifications', json_encode($payload), [
                'Content-Type' => 'application/json'
            ])->send();
            
            if ($response->isOk) {
                $result = $response->data;
                $recipients = isset($result['recipients']) ? (int)$result['recipients'] : null;
                $apiErrors = $result['errors'] ?? null;
                if ($recipients === 0 || !empty($apiErrors)) {
                    Yii::error(
                        'OneSignal: nessun destinatario raggiunto. user_ids=' . json_encode($userIds)
                        . ' recipients=' . json_encode($recipients)
                        . ' errors=' . json_encode($apiErrors)
                        . ' response=' . json_encode($result),
                        __METHOD__
                    );
                } else {
                    Yii::error(
                        'OneSignal notification sent successfully: user_ids=' . json_encode($userIds)
                        . ' recipients=' . json_encode($recipients)
                        . ' response=' . json_encode($result),
                        __METHOD__
                    );
                }
                return $result;
            } else {
                $error = 'OneSignal API error: ' . $response->statusCode . ' - ' . $response->content;
                Yii::error($error, __METHOD__);
                throw new \Exception($error);
            }
        } catch (\Exception $e) {
            Yii::error('OneSignal request failed: ' . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    /**
     * Invia una notifica agli utenti in logout (user_id = -1)
     *
     * @param string $title Titolo della notifica
     * @param string $message Messaggio della notifica
     * @param array $data Dati aggiuntivi
     * @param array $options Opzioni aggiuntive
     * @return array
     * @throws \Exception
     */
    public function sendToLoggedOutUsers($title, $message, $data = [], $options = [])
    {
        if (!$this->enabled) {
            Yii::info('OneSignal skip sendToLoggedOutUsers (disabled).', __METHOD__);
            return ['skipped' => true, 'reason' => 'onesignal_disabled', 'recipients' => 0];
        }
        $payload = array_merge([
            'app_id' => $this->appId,
            'filters' => [
                [
                    'field' => 'tag',
                    'key' => 'user_id',
                    'relation' => '=',
                    'value' => '-1'
                ]
            ],
            'headings' => ['en' => $title, 'it' => $title],
            'contents' => ['en' => $message, 'it' => $message],
        ], $options);

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        try {
            $response = $this->_httpClient->post('notifications', json_encode($payload), [
                'Content-Type' => 'application/json'
            ])->send();
            
            if ($response->isOk) {
                $result = $response->data;
                Yii::info('OneSignal notification sent to logged out users: ' . json_encode($result), __METHOD__);
                return $result;
            } else {
                $error = 'OneSignal API error: ' . $response->statusCode . ' - ' . $response->content;
                Yii::error($error, __METHOD__);
                throw new \Exception($error);
            }
        } catch (\Exception $e) {
            Yii::error('OneSignal logged out users request failed: ' . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    /**
     * Invia una notifica a tutti gli utenti dell'app
     *
     * @param string $title Titolo della notifica
     * @param string $message Messaggio della notifica
     * @param array $data Dati aggiuntivi
     * @param array $options Opzioni aggiuntive
     * @return array
     * @throws \Exception
     */
    public function sendToAll($title, $message, $data = [], $options = [])
    {
        if (!$this->enabled) {
            Yii::info('OneSignal skip sendToAll (disabled).', __METHOD__);
            return ['skipped' => true, 'reason' => 'onesignal_disabled', 'recipients' => 0];
        }
        $payload = array_merge([
            'app_id' => $this->appId,
            'included_segments' => ['All'],
            'headings' => ['en' => $title, 'it' => $title],
            'contents' => ['en' => $message, 'it' => $message],
        ], $options);

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        try {
            $response = $this->_httpClient->post('notifications', json_encode($payload), [
                'Content-Type' => 'application/json'
            ])->send();
            
            if ($response->isOk) {
                $result = $response->data;
                Yii::info('OneSignal broadcast notification sent successfully: ' . json_encode($result), __METHOD__);
                return $result;
            } else {
                $error = 'OneSignal API error: ' . $response->statusCode . ' - ' . $response->content;
                Yii::error($error, __METHOD__);
                throw new \Exception($error);
            }
        } catch (\Exception $e) {
            Yii::error('OneSignal broadcast request failed: ' . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    /**
     * Invia una notifica programmata
     *
     * @param string|array $userIds
     * @param string $title
     * @param string $message
     * @param string $sendAfter Timestamp nel formato ISO 8601 (es: 2023-12-25T09:00:00.000Z)
     * @param array $data
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function sendScheduled($userIds, $title, $message, $sendAfter, $data = [], $options = [])
    {
        $options['send_after'] = $sendAfter;
        return $this->sendToUsers($userIds, $title, $message, $data, $options);
    }

    /**
     * Crea i filtri per i tag degli utenti
     * OneSignal supporta filtri OR tra i tag
     * Esclude automaticamente user_id = -1 (utenti in logout)
     *
     * @param array $userIds
     * @return array
     */
    private function createUserTagFilters($userIds)
    {
        $filters = [];
        
        // Rimuovi eventuali valori -1 o 0 dalla lista (utenti in logout)
        $validUserIds = array_filter($userIds, function($userId) {
            return $userId > 0;
        });
        
        if (empty($validUserIds)) {
            // Se non ci sono utenti validi, crea un filtro che non matcha nulla
            return [
                [
                    'field' => 'tag',
                    'key' => 'user_id',
                    'relation' => '=',
                    'value' => 'invalid_user'
                ]
            ];
        }
        
        foreach ($validUserIds as $index => $userId) {
            $filter = [
                'field' => 'tag',
                'key' => 'user_id',
                'relation' => '=',
                'value' => (string)$userId
            ];
            
            if ($index > 0) {
                $filter['operator'] = 'OR';
            }
            
            $filters[] = $filter;
        }

        return $filters;
    }

    /**
     * Ottiene le statistiche di una notifica
     *
     * @param string $notificationId
     * @return array
     * @throws \Exception
     */
    public function getNotificationStats($notificationId)
    {
        try {
            $response = $this->_httpClient->get("notifications/{$notificationId}")->send();
            
            if ($response->isOk) {
                return $response->data;
            } else {
                $error = 'OneSignal API error: ' . $response->statusCode . ' - ' . $response->content;
                Yii::error($error, __METHOD__);
                throw new \Exception($error);
            }
        } catch (\Exception $e) {
            Yii::error('OneSignal stats request failed: ' . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }

    /**
     * Cancella una notifica programmata
     *
     * @param string $notificationId
     * @return array
     * @throws \Exception
     */
    public function cancelNotification($notificationId)
    {
        try {
            $response = $this->_httpClient->delete("notifications/{$notificationId}")->send();
            
            if ($response->isOk) {
                return $response->data;
            } else {
                $error = 'OneSignal API error: ' . $response->statusCode . ' - ' . $response->content;
                Yii::error($error, __METHOD__);
                throw new \Exception($error);
            }
        } catch (\Exception $e) {
            Yii::error('OneSignal cancel request failed: ' . $e->getMessage(), __METHOD__);
            throw $e;
        }
    }
} 