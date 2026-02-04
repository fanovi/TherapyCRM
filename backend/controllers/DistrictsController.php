<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use common\models\District;
use yii\web\Response;

/**
 * Controller per la gestione dei distretti
 */
class DistrictsController extends Controller
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
                ],
            ],
        ];
    }

    /**
     * Lista dei distretti
     * @return string
     */
    public function actionIndex()
    {
        $query = District::find()->orderBy(['code' => SORT_ASC]);

        // Filtro per ricerca
        $search = Yii::$app->request->get('search');
        if ($search) {
            $query->andWhere([
                'or',
                ['like', 'code', $search],
                ['like', 'name', $search],
                ['like', 'asl_reference', $search],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['code' => SORT_ASC]
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'search' => $search,
        ]);
    }

    /**
     * Visualizza un distretto
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        // Statistiche sui pazienti del distretto
        $patientsCount = $model->getPatients()->count();

        return $this->render('view', [
            'model' => $model,
            'patientsCount' => $patientsCount,
        ]);
    }

    /**
     * Crea un nuovo distretto
     * @return string|Response|array
     */
    public function actionCreate()
    {
        $model = new District();

        if ($model->load(Yii::$app->request->post())) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                if ($model->save()) {
                    return [
                        'status' => 'success',
                        'message' => 'Distretto creato con successo.',
                        'redirect' => ['view', 'id' => $model->id]
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Errore durante la creazione del distretto.',
                        'errors' => $model->errors
                    ];
                }
            } else {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Distretto creato con successo.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Modifica un distretto
     * @param int $id
     * @return string|Response|array
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                if ($model->save()) {
                    return [
                        'status' => 'success',
                        'message' => 'Distretto modificato con successo.',
                        'redirect' => ['view', 'id' => $model->id]
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Errore durante la modifica del distretto.',
                        'errors' => $model->errors
                    ];
                }
            } else {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Distretto modificato con successo.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Elimina un distretto
     * @param int $id
     * @return Response|array
     * @throws NotFoundHttpException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Verifica se ci sono pazienti associati
        $patientsCount = $model->getPatients()->count();
        if ($patientsCount > 0) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'status' => 'error',
                    'message' => "Impossibile eliminare il distretto. Ci sono {$patientsCount} pazienti associati."
                ];
            } else {
                Yii::$app->session->setFlash('error', "Impossibile eliminare il distretto. Ci sono {$patientsCount} pazienti associati.");
                return $this->redirect(['view', 'id' => $id]);
            }
        }

        if ($model->delete()) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'status' => 'success',
                    'message' => 'Distretto eliminato con successo.',
                    'redirect' => ['index']
                ];
            } else {
                Yii::$app->session->setFlash('success', 'Distretto eliminato con successo.');
            }
        } else {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'status' => 'error',
                    'message' => 'Errore durante l\'eliminazione del distretto.'
                ];
            } else {
                Yii::$app->session->setFlash('error', 'Errore durante l\'eliminazione del distretto.');
            }
        }

        return $this->redirect(['index']);
    }

    /**
     * Trova il model specificato
     * @param int $id
     * @return District
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = District::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La pagina richiesta non esiste.');
    }
}
