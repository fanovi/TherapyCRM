<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use common\models\ActivityLog;
use common\models\User;
use common\helpers\ActivityLogHelper;
use yii\helpers\ArrayHelper;

/**
 * Controller per la gestione dei log delle attività
 */
class ActivityLogController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->user->can('super_admin');
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'cleanup' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lista dei log con filtri
     * @return string
     */
    public function actionIndex()
    {
        $query = ActivityLog::find()
            ->with(['user', 'parentLog'])
            ->orderBy(['created_at' => SORT_DESC]);

        // Filtri
        $userId = Yii::$app->request->get('user_id');
        $action = Yii::$app->request->get('action');
        $entityName = Yii::$app->request->get('entity_name');
        $dateFrom = Yii::$app->request->get('date_from');
        $dateTo = Yii::$app->request->get('date_to');
        $parentOnly = Yii::$app->request->get('parent_only');
        $searchTerm = Yii::$app->request->get('search');

        if ($userId) {
            $query->andWhere(['user_id' => $userId]);
        }

        if ($action) {
            $query->andWhere(['action' => $action]);
        }

        if ($entityName) {
            $query->andWhere(['entity_name' => $entityName]);
        }

        if ($dateFrom) {
            $query->andWhere(['>=', 'created_at', $dateFrom . ' 00:00:00']);
        }

        if ($dateTo) {
            $query->andWhere(['<=', 'created_at', $dateTo . ' 23:59:59']);
        }

        if ($parentOnly) {
            $query->andWhere(['parent_log_id' => null]);
        }

        if ($searchTerm) {
            $query->andWhere([
                'or',
                ['like', 'entity_name', $searchTerm],
                ['like', 'old_values', $searchTerm],
                ['like', 'new_values', $searchTerm],
                ['like', 'ip_address', $searchTerm]
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC]
            ],
        ]);

        // Dati per i filtri
        $users = User::find()
            ->select(['id', 'username'])
            ->asArray()
            ->all();

        $actions = [
            ActivityLog::ACTION_CREATE => 'Creazione',
            ActivityLog::ACTION_UPDATE => 'Modifica',
            ActivityLog::ACTION_DELETE => 'Eliminazione'
        ];

        $entities = ActivityLog::find()
            ->select('entity_name')
            ->distinct()
            ->orderBy('entity_name')
            ->column();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'users' => ArrayHelper::map($users, 'id', 'username'),
            'actions' => $actions,
            'entities' => array_combine($entities, $entities),
            'filters' => [
                'user_id' => $userId,
                'action' => $action,
                'entity_name' => $entityName,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'parent_only' => $parentOnly,
                'search' => $searchTerm,
            ],
        ]);
    }

    /**
     * Visualizza dettaglio di un log
     * @param int $id
     * @return string
     */
    public function actionView($id)
    {
        $model = ActivityLog::findOne($id);

        if (!$model) {
            throw new NotFoundHttpException('Log non trovato.');
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Elimina un log (solo admin)
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Pulizia dei log più vecchi di X giorni
     * @param int $days
     * @return mixed
     */
    public function actionCleanup($days = 90)
    {
        $days = (int) $days;
        if ($days < 30) {
            $days = 30; // Minimo 30 giorni
        }

        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = ActivityLog::deleteAll(['<', 'created_at', $date]);

        Yii::$app->session->setFlash('success', "Eliminati {$deleted} log più vecchi di {$days} giorni.");
        
        return $this->redirect(['index']);
    }

    /**
     * Esporta i log in Excel
     * @return mixed
     */
    public function actionExport()
    {
        $searchModel = new ActivityLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination = false; // Disabilita la paginazione per l'export

        $models = $dataProvider->getModels();
        
        return ActivityLogHelper::exportToExcel($models);
    }

    /**
     * Statistiche delle attività
     * @return mixed
     */
    public function actionStats()
    {
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-01')); // Primo del mese
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));

        // Statistiche per azione
        $actionStats = ActivityLog::find()
            ->select(['action', 'COUNT(*) as count'])
            ->where(['between', 'created_at', $dateFrom, $dateTo])
            ->groupBy('action')
            ->asArray()
            ->all();

        // Statistiche per entità
        $entityStats = ActivityLog::find()
            ->select(['entity_name', 'COUNT(*) as count'])
            ->where(['between', 'created_at', $dateFrom, $dateTo])
            ->groupBy('entity_name')
            ->orderBy('count DESC')
            ->limit(10)
            ->asArray()
            ->all();

        // Statistiche per utente
        $userStats = ActivityLog::find()
            ->select(['user_id', 'COUNT(*) as count'])
            ->with('user')
            ->where(['between', 'created_at', $dateFrom, $dateTo])
            ->groupBy('user_id')
            ->orderBy('count DESC')
            ->limit(10)
            ->asArray()
            ->all();

        // Attività per giorno
        $dailyStats = ActivityLog::find()
            ->select(['DATE(created_at) as date', 'COUNT(*) as count'])
            ->where(['between', 'created_at', $dateFrom, $dateTo])
            ->groupBy('DATE(created_at)')
            ->orderBy('date')
            ->asArray()
            ->all();

        return $this->render('stats', [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'actionStats' => $actionStats,
            'entityStats' => $entityStats,
            'userStats' => $userStats,
            'dailyStats' => $dailyStats,
        ]);
    }

    /**
     * Visualizza i log per una specifica entità
     * @param string $entity
     * @param int $id
     * @return mixed
     */
    public function actionEntity($entity, $id)
    {
        $logs = ActivityLog::findByEntity($entity, $id)
            ->with('user')
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('entity', [
            'logs' => $logs,
            'entity' => $entity,
            'entityId' => $id,
            'entityLabel' => ActivityLogHelper::getEntityLabel($entity),
        ]);
    }

    /**
     * Trova il model specificato
     * @param integer $id
     * @return ActivityLog
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = ActivityLog::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La pagina richiesta non esiste.');
    }
}

/**
 * Classe di ricerca per ActivityLog
 */
class ActivityLogSearch extends ActivityLog
{
    public $date_from;
    public $date_to;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'entity_id'], 'integer'],
            [['action', 'entity_name', 'ip_address', 'date_from', 'date_to'], 'safe'],
        ];
    }

    /**
     * Crea il data provider per la ricerca
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = ActivityLog::find()->with('user');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Filtri
        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'entity_id' => $this->entity_id,
            'action' => $this->action,
        ]);

        $query->andFilterWhere(['like', 'entity_name', $this->entity_name])
              ->andFilterWhere(['like', 'ip_address', $this->ip_address]);

        // Filtro per range di date
        if ($this->date_from) {
            $query->andWhere(['>=', 'created_at', $this->date_from]);
        }
        if ($this->date_to) {
            $query->andWhere(['<=', 'created_at', $this->date_to . ' 23:59:59']);
        }

        return $dataProvider;
    }
} 