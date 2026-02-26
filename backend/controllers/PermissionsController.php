<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use common\models\AuthItem;
use common\models\AuthItemChild;
use common\models\AuthAssignment;

class PermissionsController extends Controller
{
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

    public function actionIndex()
    {
        $query = AuthItem::find()
            ->where(['type' => AuthItem::TYPE_PERMISSION])
            ->orderBy(['name' => SORT_ASC]);

        $search = Yii::$app->request->get('search');
        if ($search) {
            $query->andWhere([
                'or',
                ['like', 'name', $search],
                ['like', 'description', $search],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'search' => $search,
        ]);
    }

    public function actionCreate()
    {
        $model = new AuthItem();
        $model->type = AuthItem::TYPE_PERMISSION;

        if ($model->load(Yii::$app->request->post())) {
            $model->created_at = time();
            $model->updated_at = time();
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Permesso creato con successo.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($name)
    {
        $model = $this->findModel($name);

        if ($model->load(Yii::$app->request->post())) {
            $model->updated_at = time();
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Permesso aggiornato con successo.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($name)
    {
        $model = $this->findModel($name);

        // Count usages
        $roleCount = AuthItemChild::find()->where(['child' => $name])->count();
        $userCount = AuthAssignment::find()->where(['item_name' => $name])->count();

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            // Remove all assignments first
            AuthItemChild::deleteAll(['child' => $name]);
            AuthAssignment::deleteAll(['item_name' => $name]);

            if ($model->delete()) {
                return ['status' => 'success', 'message' => 'Permesso eliminato con successo.'];
            }
            return ['status' => 'error', 'message' => 'Errore durante l\'eliminazione.'];
        }

        // Remove all assignments first
        AuthItemChild::deleteAll(['child' => $name]);
        AuthAssignment::deleteAll(['item_name' => $name]);

        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Permesso eliminato con successo.');
        } else {
            Yii::$app->session->setFlash('error', 'Errore durante l\'eliminazione del permesso.');
        }

        return $this->redirect(['index']);
    }

    protected function findModel($name)
    {
        $model = AuthItem::find()
            ->where(['name' => $name, 'type' => AuthItem::TYPE_PERMISSION])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Il permesso richiesto non esiste.');
    }
}
