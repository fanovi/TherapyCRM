<?php

namespace frontend\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\models\Notification;
use common\models\User;

/**
 * Controller per gestire le comunicazioni interne del gestionale
 */
class CommunicationController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Solo utenti autenticati
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'mark-read' => ['POST'],
                    'mark-all-read' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * Visualizza tutte le comunicazioni dell'utente corrente
     *
     * @return string
     */
    public function actionIndex()
    {
        $userId = Yii::$app->user->id;
        
        // Query base per le comunicazioni dell'utente
        $query = Notification::find()
            ->where(['recipient_user_id' => $userId])
            ->with(['senderUser']) // Include info mittente
            ->orderBy(['created_at' => SORT_DESC]);

        // Filtro per tipo (opzionale)
        $type = Yii::$app->request->get('type');
        if ($type && in_array($type, ['all', 'internal_communication', 'unread'])) {
            if ($type === 'internal_communication') {
                $query->andWhere(['notification_type' => Notification::TYPE_INTERNAL_COMMUNICATION]);
            } elseif ($type === 'unread') {
                $query->andWhere(['read_at' => null]);
            }
            // 'all' non aggiunge filtri
        }

        // Configurazione provider dati
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 15,
                'pageParam' => 'page',
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC]
            ]
        ]);

        // Statistiche per header
        $totalCount = Notification::findByUser($userId)->count();
        $unreadCount = Notification::findByUser($userId)->andWhere(['read_at' => null])->count();
        $internalCount = Notification::findByUser($userId)
            ->andWhere(['notification_type' => Notification::TYPE_INTERNAL_COMMUNICATION])
            ->count();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'totalCount' => $totalCount,
            'unreadCount' => $unreadCount,
            'internalCount' => $internalCount,
            'currentType' => $type ?: 'all',
        ]);
    }

    /**
     * Visualizza i dettagli di una singola comunicazione
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $notification = $this->findModel($id);
        
        // Segna automaticamente come visualizzata se non lo è già
        if (!$notification->isViewed()) {
            $notification->markAsViewed();
        }

        return $this->render('view', [
            'model' => $notification,
        ]);
    }

    /**
     * Segna una comunicazione come letta
     *
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionMarkRead($id)
    {
        $notification = $this->findModel($id);
        
        if (!$notification->isRead()) {
            $notification->markAsRead();
            
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'success' => true,
                    'message' => 'Comunicazione segnata come letta',
                    'read_at' => $notification->read_at
                ];
            }
            
            Yii::$app->session->setFlash('success', 'Comunicazione segnata come letta.');
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => true,
                'message' => 'Comunicazione già letta',
                'read_at' => $notification->read_at
            ];
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Segna tutte le comunicazioni non lette come lette
     *
     * @return Response
     */
    public function actionMarkAllRead()
    {
        $userId = Yii::$app->user->id;
        
        $updated = Notification::updateAll(
            ['read_at' => date('Y-m-d H:i:s')],
            [
                'and',
                ['recipient_user_id' => $userId],
                ['read_at' => null]
            ]
        );

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => true,
                'message' => "Segnate come lette $updated comunicazioni",
                'updated_count' => $updated
            ];
        }

        Yii::$app->session->setFlash('success', "Segnate come lette $updated comunicazioni.");
        return $this->redirect(['index']);
    }

    /**
     * API per dropdown header - restituisce ultime comunicazioni
     *
     * @return Response
     */
    public function actionHeaderPreview()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $userId = Yii::$app->user->id;
        
        // Ultime 5 comunicazioni non lette + ultime 3 lette per riempire dropdown
        $unread = Notification::findByUser($userId)
            ->andWhere(['read_at' => null])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(5)
            ->all();
            
        $read = Notification::findByUser($userId)
            ->andWhere(['not', ['read_at' => null]])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(3)
            ->all();

        $notifications = array_merge($unread, $read);
        
        // Ordina per data di creazione
        usort($notifications, function($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });

        // Prendi solo le prime 6 per il dropdown
        $notifications = array_slice($notifications, 0, 6);

        $result = [];
        foreach ($notifications as $notification) {
            $result[] = [
                'id' => $notification->id,
                'title' => $notification->title,
                'message_preview' => mb_substr(strip_tags($notification->message), 0, 80) . 
                                   (mb_strlen($notification->message) > 80 ? '...' : ''),
                'notification_type' => $notification->notification_type,
                'type_label' => $notification->getTypeOptions()[$notification->notification_type] ?? 'Comunicazione',
                'is_read' => $notification->isRead(),
                'created_at' => $notification->created_at,
                'time_ago' => $this->timeAgo($notification->created_at),
                'sender_name' => $notification->senderUser ? $notification->senderUser->username : 'Sistema',
                'view_url' => \yii\helpers\Url::to(['communication/view', 'id' => $notification->id])
            ];
        }

        return [
            'success' => true,
            'data' => [
                'notifications' => $result,
                'unread_count' => count($unread),
                'total_count' => Notification::findByUser($userId)->count()
            ]
        ];
    }

    /**
     * Trova il modello in base all'ID e verifica che appartenga all'utente corrente
     *
     * @param int $id
     * @return Notification
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = Notification::find()
            ->where([
                'id' => $id,
                'recipient_user_id' => Yii::$app->user->id
            ])
            ->with(['senderUser'])
            ->one();

        if ($model === null) {
            throw new NotFoundHttpException('La comunicazione richiesta non esiste o non hai i permessi per visualizzarla.');
        }

        return $model;
    }

    /**
     * Helper per calcolare il tempo trascorso
     *
     * @param string $datetime
     * @return string
     */
    private function timeAgo($datetime)
    {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'Ora';
        } elseif ($time < 3600) {
            $minutes = floor($time / 60);
            return $minutes . ' min fa';
        } elseif ($time < 86400) {
            $hours = floor($time / 3600);
            return $hours . ' ore fa';
        } elseif ($time < 2592000) {
            $days = floor($time / 86400);
            return $days . ' giorni fa';
        } else {
            return date('d/m/Y', strtotime($datetime));
        }
    }
} 