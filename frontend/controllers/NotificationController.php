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
     * Visualizza tutte le notifiche dell'utente corrente, incluse quelle "di sistema"
     * (sender_user_id null, mostrate come provenienti da "Sistema": assenze, scadenze,
     * richieste documenti, ecc.).
     *
     * @return string
     */
    public function actionIndex()
    {
        $userId = Yii::$app->user->id;

        // Query base: tutte le notifiche indirizzate all'utente corrente.
        $query = Notification::find()
            ->where(['recipient_user_id' => $userId])
            ->with(['senderUser', 'recipientUser'])
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
            }
        }

        // Filtro testuale (min 3 caratteri)
        $q = trim((string) Yii::$app->request->get('q', ''));
        if (mb_strlen($q) >= 3) {
            $query->andWhere([
                'or',
                ['like', 'title', $q],
                ['like', 'message', $q],
            ]);
        } else {
            $q = '';
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

        // Statistiche per header (scoped utente corrente)
        $baseQuery = Notification::find()
            ->where(['recipient_user_id' => $userId]);

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
            'q' => $q,
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
            $userId = Yii::$app->user->id;
            $baseQuery = Notification::find()
                ->where(['recipient_user_id' => $userId]);

            $totalCount = $baseQuery->count();
            $unreadCount = (clone $baseQuery)->andWhere(['read_at' => null])->count();

            return [
                'success' => true,
                'data' => [
                    'total_count' => $totalCount,
                    'unread_count' => $unreadCount,
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
            ->andWhere(['recipient_user_id' => Yii::$app->user->id])
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
