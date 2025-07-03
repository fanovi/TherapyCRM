<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use common\models\CoordinatorGroup;
use common\models\GroupTherapist;
use common\models\User;
use common\models\Therapist;
use frontend\models\CoordinatorGroupSearch;

/**
 * CoordinatorGroupController implements the CRUD actions for CoordinatorGroup model.
 * Accessible only to Admin and Manager roles.
 */
class CoordinatorGroupController extends Controller
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
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all CoordinatorGroup models.
     * @return mixed
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->can('view_coordinator_group')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i gruppi coordinatori.');
        }

        $searchModel = new CoordinatorGroupSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single CoordinatorGroup model.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        if (!Yii::$app->user->can('view_coordinator_group')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare i gruppi coordinatori.');
        }

        $model = $this->findModel($id);

        // Carica i terapisti del gruppo
        $therapists = $model->getTherapists()
            ->with(['user.profile', 'specialization', 'groupTherapists'])
            ->all();

        return $this->render('view', [
            'model' => $model,
            'therapists' => $therapists,
        ]);
    }

    /**
     * Creates a new CoordinatorGroup model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->can('create_coordinator_group')) {
            throw new ForbiddenHttpException('Non hai i permessi per creare gruppi coordinatori.');
        }

        $model = new CoordinatorGroup();

        // Get coordinators for dropdown (users with coordinator role)
        $coordinators = $this->getCoordinatorsList();

        Yii::info('Create action - Coordinators found: ' . count($coordinators), 'coordinator-group');
        Yii::info('POST data: ' . json_encode(Yii::$app->request->post()), 'coordinator-group');

        if ($model->load(Yii::$app->request->post())) {
            $selectedTherapists = Yii::$app->request->post('therapists', []);
            $roles = Yii::$app->request->post('roles', []);

            // Debug logging
            Yii::info('Form submitted - Model data: ' . json_encode($model->attributes), 'coordinator-group');
            Yii::info('Selected therapists: ' . json_encode($selectedTherapists), 'coordinator-group');
            Yii::info('Roles: ' . json_encode($roles), 'coordinator-group');

            // Validazione: almeno un terapista deve essere selezionato
            if (empty($selectedTherapists)) {
                Yii::info('Validation failed: No therapists selected', 'coordinator-group');
                Yii::$app->session->setFlash('error', 'Seleziona almeno un terapista per il gruppo.');
                return $this->render('create', [
                    'model' => $model,
                    'coordinators' => $coordinators,
                    'selectedTherapists' => $selectedTherapists,
                    'therapistRoles' => $roles,
                ]);
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Salva il gruppo
                Yii::info('Attempting to save group model', 'coordinator-group');
                if (!$model->save()) {
                    $errors = $model->getFirstErrors();
                    Yii::error('Model validation failed: ' . json_encode($errors), 'coordinator-group');
                    throw new \Exception('Errore nel salvare il gruppo: ' . implode(', ', $errors));
                }
                Yii::info('Group saved successfully with ID: ' . $model->id, 'coordinator-group');

                // Aggiungi i terapisti selezionati
                foreach ($selectedTherapists as $therapistId) {
                    $groupTherapist = new GroupTherapist();
                    $groupTherapist->group_id = $model->id;
                    $groupTherapist->therapist_id = $therapistId;
                    $groupTherapist->assigned_from = date('Y-m-d');
                    $groupTherapist->assigned_by = Yii::$app->user->id;

                    if (!$groupTherapist->save()) {
                        throw new \Exception('Errore nell\'assegnare il terapista: ' . implode(', ', $groupTherapist->getFirstErrors()));
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Gruppo coordinatore creato con successo con ' . count($selectedTherapists) . ' terapist' . (count($selectedTherapists) == 1 ? 'a.' : 'i.'));
                return $this->redirect(['view', 'id' => $model->id]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error('Exception in create action: ' . $e->getMessage(), 'coordinator-group');
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'coordinators' => $coordinators,
            'selectedTherapists' => [],
            'therapistRoles' => [],
        ]);
    }

    /**
     * Updates an existing CoordinatorGroup model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->user->can('update_coordinator_group')) {
            throw new ForbiddenHttpException('Non hai i permessi per modificare gruppi coordinatori.');
        }

        $model = $this->findModel($id);
        $coordinators = $this->getCoordinatorsList();

        // Get current group therapists for form pre-population
        $currentGroupTherapists = GroupTherapist::find()
            ->where(['group_id' => $id])
            ->andWhere(['IS', 'assigned_to', null]) // Terapisti attualmente attivi
            ->all();

        $selectedTherapists = ArrayHelper::getColumn($currentGroupTherapists, 'therapist_id');
        $therapistRoles = []; // Non usiamo più i ruoli dato che non esistono nella tabella

        if ($model->load(Yii::$app->request->post())) {
            $newSelectedTherapists = Yii::$app->request->post('therapists', []);
            $newRoles = Yii::$app->request->post('roles', []);

            // Validazione: almeno un terapista deve essere selezionato
            if (empty($newSelectedTherapists)) {
                Yii::$app->session->setFlash('error', 'Seleziona almeno un terapista per il gruppo.');
                return $this->render('update', [
                    'model' => $model,
                    'coordinators' => $coordinators,
                    'selectedTherapists' => $newSelectedTherapists,
                    'therapistRoles' => $newRoles,
                ]);
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Salva il gruppo
                if (!$model->save()) {
                    throw new \Exception('Errore nel salvare il gruppo: ' . implode(', ', $model->getFirstErrors()));
                }

                // Disattiva tutti i terapisti attuali (imposta data fine)
                GroupTherapist::updateAll(
                    ['assigned_to' => date('Y-m-d')],
                    ['group_id' => $id, 'assigned_to' => null]
                );

                // Aggiungi i terapisti selezionati
                foreach ($newSelectedTherapists as $therapistId) {
                    $groupTherapist = GroupTherapist::find()
                        ->where(['group_id' => $id, 'therapist_id' => $therapistId])
                        ->andWhere(['IS', 'assigned_to', null])
                        ->one();

                    if (!$groupTherapist) {
                        // Crea nuovo record
                        $groupTherapist = new GroupTherapist();
                        $groupTherapist->group_id = $id;
                        $groupTherapist->therapist_id = $therapistId;
                        $groupTherapist->assigned_from = date('Y-m-d');
                        $groupTherapist->assigned_by = Yii::$app->user->id;
                    } else {
                        // Riattiva il record esistente
                        $groupTherapist->assigned_to = null;
                        $groupTherapist->assigned_by = Yii::$app->user->id;
                    }

                    if (!$groupTherapist->save()) {
                        throw new \Exception('Errore nell\'assegnare il terapista: ' . implode(', ', $groupTherapist->getFirstErrors()));
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Gruppo coordinatore aggiornato con successo con ' . count($newSelectedTherapists) . ' terapist' . (count($newSelectedTherapists) == 1 ? 'a.' : 'i.'));
                return $this->redirect(['view', 'id' => $model->id]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
            'coordinators' => $coordinators,
            'selectedTherapists' => $selectedTherapists,
            'therapistRoles' => $therapistRoles,
        ]);
    }

    /**
     * Deletes an existing CoordinatorGroup model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if (!Yii::$app->user->can('delete_coordinator_group')) {
            throw new ForbiddenHttpException('Non hai i permessi per eliminare gruppi coordinatori.');
        }

        $model = $this->findModel($id);
        
        // Controlla se ci sono terapisti assegnati
        $therapistCount = $model->getTherapists()->count();
        if ($therapistCount > 0) {
            Yii::$app->session->setFlash('error', "Impossibile eliminare il gruppo: sono presenti $therapistCount terapisti assegnati. Rimuovere prima tutti i terapisti dal gruppo.");
            return $this->redirect(['view', 'id' => $id]);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Gruppo coordinatore eliminato con successo.');

        return $this->redirect(['index']);
    }



    /**
     * Finds the CoordinatorGroup model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id
     * @return CoordinatorGroup the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CoordinatorGroup::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La pagina richiesta non esiste.');
    }

    /**
     * Get list of coordinators for dropdown
     * @return array
     */
    private function getCoordinatorsList()
    {
        // Find all users with coordinator role using assignment table query
        $coordinatorUsers = Yii::$app->db->createCommand('
            SELECT DISTINCT aa.user_id 
            FROM auth_assignment aa 
            WHERE aa.item_name = :roleName
        ')->bindValue(':roleName', 'coordinator')->queryColumn();

        if (empty($coordinatorUsers)) {
            return [];
        }

        // Get user profiles for these coordinators
        $users = User::find()
            ->with('profile')
            ->where(['id' => $coordinatorUsers, 'status' => User::STATUS_ACTIVE])
            ->all();

        $coordinators = [];
        foreach ($users as $user) {
            $name = $user->profile ? $user->profile->first_name . ' ' . $user->profile->last_name : $user->email;
            $coordinators[$user->id] = $name . ' (' . $user->email . ')';
        }

        return $coordinators;
    }
} 