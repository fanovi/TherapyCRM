<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;
use common\models\Notification;
use common\models\NotificationTemplate;

/**
 * Controller per gestire l'invio e la gestione delle notifiche
 */
class NotificationController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Aggiungi il JwtAuthBehavior per proteggere le azioni del controller
        $behaviors['jwt'] = [
            'class' => 'common\components\JwtAuthBehavior',
            'excludeActions' => [], // Tutte le azioni richiedono autenticazione
        ];
        
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        return $behaviors;
    }

    /**
     * Invia una notifica a uno o più utenti
     * 
     * POST /api/notifications/send
     * 
     * Body:
     * {
     *   "user_ids": [1, 2, 3] | 1,
     *   "title": "Titolo notifica",
     *   "message": "Messaggio della notifica",
     *   "type": "info|reminder|deadline|mandatory_read",
     *   "requires_read_confirmation": false,
     *   "scheduled_for": "2024-12-25 09:00:00",
     *   "data": {"custom": "data"}
     * }
     */
    public function actionSend()
    {
        $request = Yii::$app->request;
        $params = $request->getBodyParams();

        // Validazione parametri richiesti
        if (empty($params['user_ids']) || empty($params['title']) || empty($params['message'])) {
            return [
                'success' => false,
                'error' => 'user_ids, title e message sono richiesti'
            ];
        }

        try {
            $result = Yii::$app->notificationService->sendNotification(
                $params['user_ids'],
                $params['title'],
                $params['message'],
                $params['type'] ?? Notification::TYPE_INFO,
                Yii::$app->user->id,
                $params['requires_read_confirmation'] ?? false,
                $params['scheduled_for'] ?? null,
                $params['data'] ?? []
            );

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Yii::error('Errore invio notifica: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Invia una notifica usando un template
     * 
     * POST /api/notifications/send-template
     * 
     * Body:
     * {
     *   "template_code": "PLAN_EXPIRING",
     *   "user_ids": [1, 2, 3],
     *   "variables": {
     *     "patient_name": "Mario Rossi",
     *     "end_date": "2024-12-31"
     *   },
     *   "scheduled_for": "2024-12-25 09:00:00"
     * }
     */
    public function actionSendTemplate()
    {
        $request = Yii::$app->request;
        $params = $request->getBodyParams();

        if (empty($params['template_code']) || empty($params['user_ids'])) {
            return [
                'success' => false,
                'error' => 'template_code e user_ids sono richiesti'
            ];
        }

        try {
            $result = Yii::$app->notificationService->sendFromTemplate(
                $params['template_code'],
                $params['user_ids'],
                $params['variables'] ?? [],
                Yii::$app->user->id,
                $params['scheduled_for'] ?? null,
                $params['data'] ?? []
            );

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Yii::error('Errore invio notifica da template: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Invia una notifica broadcast a tutti gli utenti
     * 
     * POST /api/notifications/broadcast
     * 
     * Body:
     * {
     *   "title": "Titolo broadcast",
     *   "message": "Messaggio broadcast",
     *   "type": "info",
     *   "data": {"custom": "data"}
     * }
     */
    public function actionBroadcast()
    {
        $request = Yii::$app->request;
        $params = $request->getBodyParams();

        if (empty($params['title']) || empty($params['message'])) {
            return [
                'success' => false,
                'error' => 'title e message sono richiesti'
            ];
        }

        try {
            $result = Yii::$app->notificationService->sendBroadcast(
                $params['title'],
                $params['message'],
                $params['type'] ?? Notification::TYPE_INFO,
                Yii::$app->user->id,
                $params['data'] ?? []
            );

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Yii::error('Errore invio broadcast: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Segna una notifica come letta
     * 
     * POST /api/notifications/{id}/mark-read
     */
    public function actionMarkRead($id)
    {
        try {
            $success = Yii::$app->notificationService->markAsRead($id, Yii::$app->user->id);
            
            return [
                'success' => $success,
                'message' => $success ? 'Notifica segnata come letta' : 'Notifica non trovata'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene le notifiche non lette dell'utente corrente
     * 
     * GET /api/notifications/unread
     */
    public function actionUnread()
    {
        $limit = Yii::$app->request->get('limit', 50);
        
        try {
            $notifications = Yii::$app->notificationService->getUnreadNotifications(Yii::$app->user->id, $limit);
            $count = Yii::$app->notificationService->getUnreadCount(Yii::$app->user->id);

            return [
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $count
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene tutte le notifiche dell'utente corrente
     * 
     * GET /api/notifications
     */
    public function actionIndex()
    {
        $page = Yii::$app->request->get('page', 1);
        $limit = Yii::$app->request->get('limit', 10); // Default 10 per pagina
        $offset = ($page - 1) * $limit;

        try {
            $query = Notification::findByUser(Yii::$app->user->id)
                ->with(['senderUser']) // Include informazioni mittente
                ->orderBy(['created_at' => SORT_DESC]);

            $total = $query->count();
            $notifications = $query
                ->offset($offset)
                ->limit($limit)
                ->all();

            // Prepara dati ottimizzati per la lista (solo campi necessari)
            $notificationsList = array_map(function($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'notification_type' => $notification->notification_type,
                    'requires_read_confirmation' => $notification->requires_read_confirmation,
                    'read_at' => $notification->read_at,
                    'viewed_at' => $notification->viewed_at,
                    'created_at' => $notification->created_at,
                    // Preview del messaggio (primi 100 caratteri)
                    'message_preview' => mb_substr(strip_tags($notification->message), 0, 100) . 
                                       (mb_strlen($notification->message) > 100 ? '...' : ''),
                    'sender_name' => $notification->senderUser ? 
                                   $notification->senderUser->username : 'Sistema'
                ];
            }, $notifications);

            return [
                'success' => true,
                'data' => [
                    'notifications' => $notificationsList,
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'pages' => ceil($total / $limit),
                        'has_next' => $page < ceil($total / $limit),
                        'has_prev' => $page > 1
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Yii::error('Errore recupero notifiche: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene il dettaglio completo di una notifica
     * 
     * GET /api/notifications/{id}
     */
    public function actionView($id)
    {
        try {
            $notification = Notification::find()
                ->where([
                    'id' => $id,
                    'recipient_user_id' => Yii::$app->user->id
                ])
                ->with(['senderUser'])
                ->one();

            if (!$notification) {
                return [
                    'success' => false,
                    'error' => 'Notifica non trovata'
                ];
            }

            // Segna come visualizzata se non lo è già
            if (!$notification->viewed_at) {
                $notification->markAsViewed();
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'notification_type' => $notification->notification_type,
                    'requires_read_confirmation' => $notification->requires_read_confirmation,
                    'read_at' => $notification->read_at,
                    'viewed_at' => $notification->viewed_at,
                    'scheduled_for' => $notification->scheduled_for,
                    'sent_at' => $notification->sent_at,
                    'created_at' => $notification->created_at,
                    'sender' => $notification->senderUser ? [
                        'id' => $notification->senderUser->id,
                        'username' => $notification->senderUser->username,
                        'email' => $notification->senderUser->email
                    ] : null
                ]
            ];

        } catch (\Exception $e) {
            Yii::error('Errore recupero dettaglio notifica: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene i template di notifica disponibili
     * 
     * GET /api/notifications/templates
     */
    public function actionTemplates()
    {
        try {
            $templates = NotificationTemplate::find()
                ->where(['is_active' => true])
                ->orderBy(['code' => SORT_ASC])
                ->all();

            return [
                'success' => true,
                'data' => $templates
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Elabora le notifiche programmate (da chiamare via cron)
     * 
     * POST /api/notifications/process-scheduled
     */
    public function actionProcessScheduled()
    {
        try {
            $result = Yii::$app->notificationService->processScheduledNotifications();

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (\Exception $e) {
            Yii::error('Errore elaborazione notifiche programmate: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verifica se l'utente ha notifiche bloccanti da leggere
     * 
     * GET /api/notifications/has-blocking
     */
    public function actionHasBlocking()
    {
        try {
            $count = Notification::find()
                ->where([
                    'recipient_user_id' => Yii::$app->user->id,
                    'requires_read_confirmation' => true,
                    'read_at' => null
                ])
                ->count();

            $hasBlocking = $count > 0;

            Yii::info("Utente " . Yii::$app->user->id . " ha {$count} notifiche bloccanti", __METHOD__);

            return [
                'success' => true,
                'data' => [
                    'has_blocking_notifications' => $hasBlocking,
                    'blocking_count' => $count,
                    'app_should_be_blocked' => $hasBlocking
                ]
            ];

        } catch (\Exception $e) {
            Yii::error('Errore verifica notifiche bloccanti: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ottiene le notifiche bloccanti (requires_read_confirmation = true e non lette)
     * 
     * GET /api/notifications/blocking
     */
    public function actionBlocking()
    {
        try {
            $notifications = Notification::find()
                ->where([
                    'recipient_user_id' => Yii::$app->user->id,
                    'requires_read_confirmation' => true,
                    'read_at' => null
                ])
                ->with(['senderUser'])
                ->orderBy(['created_at' => SORT_ASC])
                ->all();

            Yii::info("Trovate " . count($notifications) . " notifiche bloccanti per utente " . Yii::$app->user->id, __METHOD__);

            return [
                'success' => true,
                'data' => $notifications
            ];

        } catch (\Exception $e) {
            Yii::error('Errore recupero notifiche bloccanti: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Conferma la lettura di una notifica bloccante
     * 
     * POST /api/notifications/{id}/confirm-read
     */
    public function actionConfirmRead($id)
    {
        try {
            $notification = Notification::find()
                ->where([
                    'id' => $id,
                    'recipient_user_id' => Yii::$app->user->id,
                    'requires_read_confirmation' => true
                ])
                ->one();

            if (!$notification) {
                return [
                    'success' => false,
                    'error' => 'Notifica non trovata o non autorizzata'
                ];
            }

            if ($notification->read_at) {
                return [
                    'success' => true,
                    'message' => 'Notifica già confermata come letta'
                ];
            }

            // Segna come letta
            $notification->markAsRead();

            Yii::info("Notifica bloccante {$id} confermata come letta dall'utente " . Yii::$app->user->id, __METHOD__);

            return [
                'success' => true,
                'message' => 'Lettura confermata'
            ];

        } catch (\Exception $e) {
            Yii::error("Errore conferma lettura notifica {$id}: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Segna una notifica come visualizzata (ma non necessariamente confermata)
     * 
     * POST /api/notifications/{id}/mark-viewed
     */
    public function actionMarkViewed($id)
    {
        try {
            $notification = Notification::find()
                ->where([
                    'id' => $id,
                    'recipient_user_id' => Yii::$app->user->id
                ])
                ->one();

            if (!$notification) {
                return [
                    'success' => false,
                    'error' => 'Notifica non trovata'
                ];
            }

            if ($notification->viewed_at) {
                return [
                    'success' => true,
                    'message' => 'Notifica già visualizzata',
                    'viewed_at' => $notification->viewed_at
                ];
            }

            // Segna come visualizzata
            $notification->markAsViewed();

            Yii::info("Notifica {$id} visualizzata dall'utente " . Yii::$app->user->id . " alle " . $notification->viewed_at, __METHOD__);

            return [
                'success' => true,
                'message' => 'Notifica segnata come visualizzata',
                'viewed_at' => $notification->viewed_at
            ];

        } catch (\Exception $e) {
            Yii::error("Errore segnando notifica {$id} come visualizzata: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crea una notifica di test per sviluppo/debugging
     * 
     * POST /api/notifications/create-test
     * 
     * Body:
     * {
     *   "type": "blocking|normal",
     *   "title": "Titolo personalizzato (opzionale)",
     *   "message": "Messaggio personalizzato (opzionale)",
     *   "recipient_user_id": 123 (opzionale, default: utente corrente)
     * }
     */
    public function actionCreateTest()
    {
        // Solo in ambiente di sviluppo
        if (!YII_DEBUG) {
            return [
                'success' => false,
                'error' => 'Endpoint disponibile solo in ambiente di sviluppo'
            ];
        }

        $request = Yii::$app->request;
        $params = $request->getBodyParams();
        
        $type = $params['type'] ?? 'normal';
        $recipientUserId = $params['recipient_user_id'] ?? Yii::$app->user->id;

        try {
            $notification = new Notification();
            $notification->recipient_user_id = $recipientUserId;
            $notification->sender_user_id = Yii::$app->user->id;
            
            if ($type === 'blocking') {
                $notification->title = $params['title'] ?? 'Test Notifica Bloccante - ' . date('H:i:s');
                $notification->message = $params['message'] ?? 'Questa è una notifica di test che richiede conferma di lettura. L\'app dovrebbe essere bloccata fino alla conferma. Test creato alle ' . date('Y-m-d H:i:s');
                $notification->notification_type = Notification::TYPE_MANDATORY_READ;
                $notification->requires_read_confirmation = true;
            } else {
                $notification->title = $params['title'] ?? 'Test Notifica Normale - ' . date('H:i:s');
                $notification->message = $params['message'] ?? 'Questa è una notifica di test normale. Non blocca l\'app. Test creato alle ' . date('Y-m-d H:i:s');
                $notification->notification_type = Notification::TYPE_INFO;
                $notification->requires_read_confirmation = false;
            }

            if (!$notification->save()) {
                return [
                    'success' => false,
                    'error' => 'Errore salvataggio notifica',
                    'errors' => $notification->errors
                ];
            }

            Yii::info("Notifica di test {$type} creata con ID {$notification->id} per utente {$recipientUserId}", __METHOD__);

            return [
                'success' => true,
                'message' => "Notifica di test {$type} creata con successo",
                'data' => [
                    'id' => $notification->id,
                    'type' => $type,
                    'title' => $notification->title,
                    'requires_read_confirmation' => $notification->requires_read_confirmation,
                    'recipient_user_id' => $notification->recipient_user_id,
                    'created_at' => $notification->created_at
                ]
            ];

        } catch (\Exception $e) {
            Yii::error("Errore creazione notifica di test: " . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 