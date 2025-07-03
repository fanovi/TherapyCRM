<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use common\models\DocumentRequest;
use common\models\RequestStatus;
use frontend\models\DocumentRequestSearch;

/**
 * DocumentRequestController implements the CRUD operations for DocumentRequest model.
 */
class DocumentRequestController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'actions' => ['index', 'view', 'update-status'],
                            'roles' => ['@'],
                            'matchCallback' => function ($rule, $action) {
                                // Admin e Manager possono accedere
                                return Yii::$app->user->can('manage_documents') || 
                                       Yii::$app->user->can('view_documents');
                            }
                        ],
                    ],
                ],
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'update-status' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        // Disabilita la validazione CSRF per l'azione update-status
        if ($action->id === 'update-status') {
            $this->enableCsrfValidation = false;
        }
        
        return parent::beforeAction($action);
    }

    /**
     * Lists all DocumentRequest models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new DocumentRequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single DocumentRequest model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Updates the status of a DocumentRequest model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     * @throws ForbiddenHttpException if user doesn't have permission
     */
    public function actionUpdateStatus($id = null)
    {
        // Se l'ID viene passato via POST (per AJAX)
        if (!$id) {
            $id = Yii::$app->request->post('id');
        }
        
        if (!$id) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'ID richiesta non specificato.'];
            }
            throw new NotFoundHttpException('ID richiesta non specificato.');
        }
        
        $model = $this->findModel($id);
        $newStatus = Yii::$app->request->post('status');
        
        // Log per debugging
        Yii::info("Update status request - ID: $id, Current Status: {$model->status}, New Status: $newStatus", __METHOD__);
        
        if (!$newStatus) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Stato non specificato.'];
            }
            Yii::$app->session->setFlash('error', 'Stato non specificato.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // Controlla se si sta tentando di impostare lo stesso stato
        if ($model->status == $newStatus) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Lo stato è già quello selezionato.'];
            }
            Yii::$app->session->setFlash('error', 'Lo stato è già quello selezionato.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // Controllo permessi basato sul ruolo
        if (!$this->canUpdateStatus($model, $newStatus)) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Non hai i permessi per aggiornare questo stato.'];
            }
            throw new ForbiddenHttpException('Non hai i permessi per aggiornare questo stato.');
        }

        if ($model->changeStatus($newStatus, Yii::$app->user->id)) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return [
                    'success' => true,
                    'newStatus' => $model->status,
                    'statusLabel' => $model->getStatusLabel(),
                ];
            }
            Yii::$app->session->setFlash('success', 'Stato aggiornato con successo.');
        } else {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Errore durante l\'aggiornamento dello stato.'];
            }
            Yii::$app->session->setFlash('error', 'Errore durante l\'aggiornamento dello stato.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Controlla se l'utente può aggiornare lo stato
     * @param DocumentRequest $model
     * @param int $newStatus
     * @return bool
     */
    protected function canUpdateStatus($model, $newStatus)
    {
        // Se la richiesta è già stata consegnata, nessuno può più modificarla
        if ($model->status == RequestStatus::STATUS_CONSEGNATO) {
            return false;
        }
        
        // Admin può impostare stati 1, 2, 3 (Inviata, Presa in carico, Stampato)
        if (Yii::$app->user->can('manage_documents')) {
            return in_array($newStatus, [
                RequestStatus::STATUS_INVIATA,
                RequestStatus::STATUS_PRESA_IN_CARICO,
                RequestStatus::STATUS_STAMPATO
            ]);
        }

        // Manager può impostare solo stato 4 (Consegnato)
        if (Yii::$app->user->can('view_documents')) {
            return $newStatus == RequestStatus::STATUS_CONSEGNATO;
        }

        return false;
    }

    /**
     * Finds the DocumentRequest model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return DocumentRequest the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = DocumentRequest::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La richiesta richiesta non esiste.');
    }
} 