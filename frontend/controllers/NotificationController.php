<?php

namespace frontend\controllers;

use common\models\Notification;
use common\models\User;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;

/**
 * Controller per gestire le notifiche del sistema
 */
class NotificationController extends BaseController
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
                        'roles' => ['view_notifications'],  // Solo utenti con permesso
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'mark-read-api' => ['POST'],
                    'stats-api' => ['GET'],
                ],
            ],
        ]);
    }

    /**
     * Visualizza tutte le notifiche del sistema (quelle con sender_user_id NON null)
     *
     * @return string
     */
    public function actionIndex()
    {
        // Query base per le notifiche con mittente (inviate da utenti)
        $query = Notification::find()
            ->where(['not', ['sender_user_id' => null]])  // Solo notifiche con mittente
            ->with(['senderUser', 'recipientUser'])  // Include info mittente e destinatario
            ->orderBy(['created_at' => SORT_DESC]);

        // Filtri opzionali
        $type = Yii::$app->request->get('type');
        $status = Yii::$app->request->get('status');

        if ($type && in_array($type, array_keys(Notification::getTypeOptions()))) {
            $query->andWhere(['notification_type' => $type]);
        }

        if ($status) {
            if ($status === 'unread') {
                $query->andWhere(['read_at' => null]);
            } elseif ($status === 'read') {
                $query->andWhere(['not', ['read_at' => null]]);
            } elseif ($status === 'unsent') {
                $query->andWhere(['sent_at' => null]);
            } elseif ($status === 'sent') {
                $query->andWhere(['not', ['sent_at' => null]]);
            }
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
        $baseQuery = Notification::find()->where(['not', ['sender_user_id' => null]]);

        $totalCount = $baseQuery->count();
        $unreadCount = (clone $baseQuery)->andWhere(['read_at' => null])->count();
        $sentCount = (clone $baseQuery)->andWhere(['not', ['sent_at' => null]])->count();
        $unsentCount = (clone $baseQuery)->andWhere(['sent_at' => null])->count();

        // Statistiche per tipo
        $typeStats = [];
        foreach (Notification::getTypeOptions() as $typeKey => $typeLabel) {
            $typeStats[$typeKey] = [
                'label' => $typeLabel,
                'count' => (clone $baseQuery)->andWhere(['notification_type' => $typeKey])->count()
            ];
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'totalCount' => $totalCount,
            'unreadCount' => $unreadCount,
            'sentCount' => $sentCount,
            'unsentCount' => $unsentCount,
            'typeStats' => $typeStats,
            'currentType' => $type ?: 'all',
            'currentStatus' => $status ?: 'all',
        ]);
    }

    /**
     * Visualizza i dettagli di una singola notifica
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $notification = $this->findModel($id);

        return $this->render('view', [
            'model' => $notification,
        ]);
    }

    /**
     * API per ottenere statistiche aggiornate delle notifiche
     *
     * @return Response
     */
    public function actionStatsApi()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $baseQuery = Notification::find()->where(['not', ['sender_user_id' => null]]);

            $totalCount = $baseQuery->count();
            $unreadCount = (clone $baseQuery)->andWhere(['read_at' => null])->count();
            $sentCount = (clone $baseQuery)->andWhere(['not', ['sent_at' => null]])->count();
            $unsentCount = (clone $baseQuery)->andWhere(['sent_at' => null])->count();

            return [
                'success' => true,
                'data' => [
                    'total_count' => $totalCount,
                    'unread_count' => $unreadCount,
                    'sent_count' => $sentCount,
                    'unsent_count' => $unsentCount,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
        } catch (\Exception $e) {
            Yii::error('Errore in actionStatsApi: ' . $e->getMessage(), __METHOD__);

            return [
                'success' => false,
                'message' => 'Errore nel recupero delle statistiche'
            ];
        }
    }

    /**
     * Trova il modello in base all'ID
     *
     * @param int $id
     * @return Notification
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = Notification::find()
            ->where(['id' => $id])
            ->andWhere(['not', ['sender_user_id' => null]])  // Solo notifiche con mittente
            ->with(['senderUser', 'recipientUser'])
            ->one();

        if ($model === null) {
            throw new NotFoundHttpException('La notifica richiesta non esiste.');
        }

        return $model;
    }

    /**
     * Helper per formattare data e ora di invio
     *
     * @param string $datetime
     * @return string
     */
    private function timeAgo($datetime)
    {
        $timestamp = strtotime($datetime);
        $today = date('Y-m-d');
        $notificationDate = date('Y-m-d', $timestamp);

        if ($notificationDate === $today) {
            // Oggi: mostra solo l'ora
            return date('H:i', $timestamp);
        } elseif ($notificationDate === date('Y-m-d', strtotime('-1 day'))) {
            // Ieri: mostra "Ieri alle H:i"
            return 'Ieri alle ' . date('H:i', $timestamp);
        } else {
            // Altre date: mostra "gg/mm/yyyy alle H:i"
            return date('d/m/Y \a\l\l\e H:i', $timestamp);
        }
    }
}
