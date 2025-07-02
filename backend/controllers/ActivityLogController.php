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
                        'roles' => ['admin'], // Solo admin possono vedere i log
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
     * Lista i log delle attività con filtri
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ActivityLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Dati per i filtri dropdown
        $users = ArrayHelper::map(
            User::find()->orderBy('username')->all(),
            'id',
            'username'
        );

        $entities = ActivityLog::find()
            ->select('entity_name')
            ->distinct()
            ->orderBy('entity_name')
            ->column();

        $actions = [
            ActivityLog::ACTION_CREATE => 'Creato',
            ActivityLog::ACTION_UPDATE => 'Modificato',
            ActivityLog::ACTION_DELETE => 'Eliminato',
        ];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'users' => $users,
            'entities' => $entities,
            'actions' => $actions,
        ]);
    }

    /**
     * Visualizza i dettagli di un singolo log
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
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