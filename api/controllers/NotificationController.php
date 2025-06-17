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
        
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
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
        $limit = Yii::$app->request->get('limit', 20);
        $offset = ($page - 1) * $limit;

        try {
            $query = Notification::findByUser(Yii::$app->user->id)
                ->orderBy(['created_at' => SORT_DESC]);

            $total = $query->count();
            $notifications = $query
                ->offset($offset)
                ->limit($limit)
                ->all();

            return [
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'pages' => ceil($total / $limit)
                    ]
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
} 