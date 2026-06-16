<?php

namespace frontend\controllers;

use Yii;
use common\models\Absence;
use common\models\ActivityLog;
use common\models\Appointment;
use common\models\Therapist;
use common\models\Notification;
use common\models\AbsenceSearch;
use common\models\PatientAbsenceSearch;
use common\helpers\NotificationHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use common\models\CoordinatorGroup;
use common\models\GroupTherapist;
use yii\web\ForbiddenHttpException;

/**
 * AbsenceController implements the CRUD actions for Absence model.
 */
class AbsenceController extends Controller
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
                        'actions' => ['index', 'view', 'daily', 'daily-detail'],
                        'allow' => true,
                        'roles' => ['view_absence'],
                    ],
                    [
                        'actions' => ['patients', 'legacy-patients', 'remove-patient-absence'],
                        'allow' => true,
                        'roles' => ['view_patient_absence', 'manage_patient_absence'],
                    ],
                    [
                        'actions' => [
                            'create-patient-absence',
                            'search-patients-absence',
                            'patient-appointments-absence',
                            'mark-patients-absent',
                        ],
                        'allow' => true,
                        'roles' => ['manage_patient_absence'],
                    ],
                    [
                        'actions' => ['create', 'update', 'delete', 'check-appointments', 'ajax-save', 'ajax-get'],
                        'allow' => true,
                        'roles' => ['create_absence'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'remove-patient-absence' => ['POST'],
                    'mark-patients-absent' => ['POST'],
                    'ajax-save' => ['POST'],
                    'ajax-get' => ['GET'],
                ],
            ],
        ];
    }

    /**
     * Salva (create o update) un'Absence via AJAX e ritorna JSON.
     * Usato dalla modale Swal su /absence/index per evitare il redirect a
     * /absence/create e /absence/update.
     *
     * POST params: tutti i campi del model (therapist_id, start_date, end_date,
     * start_time, end_time, type, notes, id opzionale per update).
     *
     * @return array
     */
    public function actionAjaxSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = Yii::$app->request->post('Absence')['id'] ?? Yii::$app->request->post('id');
        $model = $id ? $this->findModel((int) $id) : new Absence();

        $isNew = $model->isNewRecord;
        if ($isNew) {
            $model->created_by = Yii::$app->user->id;
            $model->status = Absence::STATUS_APPROVED;
        }

        // Coordinator: scope sui terapisti del proprio gruppo
        $coordinatorTherapistIds = $this->isCoordinatorOnly()
            ? $this->getCoordinatorTherapistIds()
            : null;

        if (!$model->load(Yii::$app->request->post())) {
            return ['success' => false, 'error' => 'Nessun dato ricevuto'];
        }

        if ($coordinatorTherapistIds !== null && !in_array($model->therapist_id, $coordinatorTherapistIds)) {
            return ['success' => false, 'error' => 'Non puoi gestire assenze per terapisti fuori dal tuo gruppo'];
        }

        $model->reason = $this->getReasonLabel($model->type);

        if (!$model->save()) {
            return [
                'success' => false,
                'error' => 'Validazione fallita',
                'errors' => $model->getErrors(),
            ];
        }

        // Solo alla CREAZIONE: notifica (gestionale) direzione + coordinatori del terapista.
        // Questo e' il percorso realmente usato dalla modale su /absence/index.
        if ($isNew) {
            $affectedCount = count($this->getAppointmentsInRange(
                $model->therapist_id,
                $model->start_date,
                $model->end_date
            ));
            $this->sendTherapistAbsenceNotifications($model, $affectedCount);
        }

        return [
            'success' => true,
            'message' => $isNew ? 'Assenza creata' : 'Assenza aggiornata',
            'data' => [
                'id' => $model->id,
            ],
        ];
    }

    /**
     * Ritorna i dati di un'Absence in JSON per pre-popolare la modale di edit.
     * @return array
     */
    public function actionAjaxGet($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findModel((int) $id);

        if ($this->isCoordinatorOnly()) {
            $coordinatorTherapistIds = $this->getCoordinatorTherapistIds();
            if (!in_array($model->therapist_id, $coordinatorTherapistIds)) {
                return ['success' => false, 'error' => 'Non autorizzato'];
            }
        }

        return [
            'success' => true,
            'data' => [
                'id' => $model->id,
                'therapist_id' => $model->therapist_id,
                'start_date' => $model->start_date,
                'end_date' => $model->end_date,
                'start_time' => $model->start_time ? substr($model->start_time, 0, 5) : null,
                'end_time' => $model->end_time ? substr($model->end_time, 0, 5) : null,
                'type' => $model->type,
                'notes' => $model->notes,
            ],
        ];
    }

    /**
     * Lists all Absence models.
     * @return mixed
     */

    /**
     * Check if the current user is a coordinator (not manager/admin).
     * @return bool
     */
    private function isCoordinatorOnly()
    {
        return Yii::$app->user->can('coordinator')
            && !Yii::$app->user->can('manager')
            && !Yii::$app->user->can('admin');
    }

    /**
     * Get therapist IDs in the current coordinator's group.
     * Returns [0] if no group found (shows empty results).
     * @return array
     */
    private function getCoordinatorTherapistIds()
    {
        $coordinatorGroup = CoordinatorGroup::find()
            ->where(['coordinator_user_id' => Yii::$app->user->id])
            ->one();

        if ($coordinatorGroup) {
            $ids = GroupTherapist::find()
                ->select('therapist_id')
                ->where(['group_id' => $coordinatorGroup->id])
                ->andWhere(['assigned_to' => null])
                ->column();
            return !empty($ids) ? $ids : [0];
        }
        return [0];
    }

    /**
     * Get filtered therapist dropdown list for coordinator.
     * @param array $therapistIds
     * @return array
     */
    private function getFilteredTherapistsList($therapistIds)
    {
        if (empty($therapistIds) || $therapistIds === [0]) {
            return [];
        }
        return ArrayHelper::map(
            Therapist::find()
                ->joinWith('user.profile')
                ->where(['therapists.id' => $therapistIds])
                ->andWhere(['therapists.is_active' => 1])
                ->orderBy('user_profiles.last_name, user_profiles.first_name')
                ->all(),
            'id',
            function ($model) {
                return $model->user->profile->last_name . ' ' . $model->user->profile->first_name;
            }
        );
    }

    public function actionIndex()
    {
        $searchModel = new AbsenceSearch();
        $therapistsList = null;

        if ($this->isCoordinatorOnly()) {
            $therapistIds = $this->getCoordinatorTherapistIds();
            $searchModel->therapistIds = $therapistIds;
            $therapistsList = $this->getFilteredTherapistsList($therapistIds);
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'therapistsList' => $therapistsList,
        ]);
    }

    /**
     * Daily presence/absence overview.
     * @param string|null $date Date in Y-m-d format, defaults to today
     * @return mixed
     */
    public function actionDaily($date = null)
    {
        $date = $date ?: date('Y-m-d');
        $groupName = null;

        // Determine if current user is coordinator only
        $isCoordinator = Yii::$app->user->can('coordinator')
            && !Yii::$app->user->can('manager')
            && !Yii::$app->user->can('admin');

        if ($isCoordinator) {
            // Coordinator: get only their group's therapists
            $coordinatorGroup = CoordinatorGroup::find()
                ->where(['coordinator_user_id' => Yii::$app->user->id])
                ->one();

            if ($coordinatorGroup) {
                $groupName = $coordinatorGroup->name;
                $therapistIds = GroupTherapist::find()
                    ->select('therapist_id')
                    ->where(['group_id' => $coordinatorGroup->id])
                    ->andWhere(['assigned_to' => null])
                    ->column();
            } else {
                $therapistIds = [];
            }

            $therapists = Therapist::find()
                ->joinWith(['user.profile', 'specialization'])
                ->where(['therapists.id' => $therapistIds])
                ->andWhere(['therapists.is_active' => 1])
                ->orderBy('user_profiles.last_name, user_profiles.first_name')
                ->all();
        } else {
            // Manager/admin: get all active therapists
            $therapists = Therapist::find()
                ->joinWith(['user.profile', 'specialization'])
                ->where(['therapists.is_active' => 1])
                ->orderBy('user_profiles.last_name, user_profiles.first_name')
                ->all();
        }

        // Get therapist IDs for absence query
        $therapistIds = ArrayHelper::getColumn($therapists, 'id');

        // Query approved absences for the given date
        $absences = Absence::find()
            ->onDate($date)
            ->approved()
            ->andWhere(['therapist_id' => $therapistIds])
            ->indexBy('therapist_id')
            ->all();

        // Build flat rows for GridView (ArrayDataProvider).
        $rows = [];
        foreach ($therapists as $therapist) {
            $absence = $absences[$therapist->id] ?? null;
            $profile = $therapist->user->profile ?? null;
            $rows[] = [
                'id' => $therapist->id,
                'last_name' => $profile->last_name ?? '',
                'first_name' => $profile->first_name ?? '',
                'full_name' => $profile ? trim($profile->last_name . ' ' . $profile->first_name) : '',
                'specialization' => $therapist->specialization->name ?? 'N/D',
                'calendar_color' => $therapist->calendar_color ?: '#6B7280',
                'status' => $absence ? 'absent' : 'present',
                'absence_type' => $absence ? $absence->getTypeLabel() : null,
                'absence_start' => $absence->start_date ?? null,
                'absence_end' => $absence->end_date ?? null,
                'absence_id' => $absence->id ?? null,
            ];
        }

        // Filter via GridView filterModel
        $filterModel = new \frontend\models\DailyAbsenceFilter();
        $filterModel->load(Yii::$app->request->queryParams);
        $filteredRows = $filterModel->validate() ? $filterModel->apply($rows) : $rows;

        $dataProvider = new \yii\data\ArrayDataProvider([
            'allModels' => $filteredRows,
            'key' => 'id',
            'pagination' => ['pageSize' => 30],
            'sort' => [
                'attributes' => ['full_name', 'last_name', 'specialization', 'status'],
                'defaultOrder' => ['last_name' => SORT_ASC],
            ],
        ]);

        // Lista distinta delle specializzazioni presenti nelle righe (per dropdown filtro)
        $specializationOptions = array_filter(array_unique(ArrayHelper::getColumn($rows, 'specialization')));
        sort($specializationOptions);
        $specializationOptions = array_combine($specializationOptions, $specializationOptions);

        return $this->render('daily', [
            'dataProvider' => $dataProvider,
            'filterModel' => $filterModel,
            'totalCount' => count($rows),
            'absentCount' => count($absences),
            'presentCount' => count($rows) - count($absences),
            'date' => $date,
            'groupName' => $groupName,
            'specializationOptions' => $specializationOptions,
        ]);
    }

    /**
     * AJAX: ritorna i dettagli di un'assenza in JSON per la modale del Riepilogo Giornaliero.
     * Filtro coordinator: solo assenze dei terapisti del proprio gruppo.
     * @param int $id
     * @return array
     */
    public function actionDailyDetail($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = Absence::findOne($id);
        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['success' => false, 'error' => 'Assenza non trovata.'];
        }

        if ($this->isCoordinatorOnly()) {
            $therapistIds = $this->getCoordinatorTherapistIds();
            if (!in_array($model->therapist_id, $therapistIds)) {
                Yii::$app->response->statusCode = 403;
                return ['success' => false, 'error' => 'Non autorizzato.'];
            }
        }

        $therapistName = '-';
        if ($model->therapist && $model->therapist->user && $model->therapist->user->profile) {
            $p = $model->therapist->user->profile;
            $therapistName = trim($p->last_name . ' ' . $p->first_name);
        }

        return [
            'success' => true,
            'data' => [
                'id' => $model->id,
                'therapist' => $therapistName,
                'start_date' => Yii::$app->formatter->asDate($model->start_date, 'php:d/m/Y'),
                'end_date' => Yii::$app->formatter->asDate($model->end_date, 'php:d/m/Y'),
                'duration_days' => $model->getDurationDays(),
                'type' => $model->getTypeLabel(),
                'reason' => $model->reason ?: '-',
                'notes' => $model->notes ?: '-',
                'status' => $model->getStatusLabel(),
                'is_approved' => $model->isApproved(),
                'can_update' => Yii::$app->user->can('update_absence') || Yii::$app->user->can('create_absence'),
                'can_delete' => Yii::$app->user->can('delete_absence'),
                'update_url' => \yii\helpers\Url::to(['update', 'id' => $model->id]),
                'delete_url' => \yii\helpers\Url::to(['delete', 'id' => $model->id]),
            ],
        ];
    }

    /**
     * Lists patient absences (appointments with absent status).
     * @return mixed
     */
    public function actionPatients()
    {
        // Unifica con la pagina di gestione assenze: la grid mostra direttamente
        // la lista pazienti, click apre modale con dettaglio appointment + bulk.
        $searchModel = new \frontend\models\PatientSearch();
        $dataProvider = $searchModel->searchDataProvider(Yii::$app->request->queryParams);
        return $this->render('create-patient-absence', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Mantenuto come alias retrocompatibilita': stesso comportamento di actionPatients.
     */
    public function actionLegacyPatients()
    {
        $searchModel = new PatientAbsenceSearch();

        $therapistsList = null;

        if ($this->isCoordinatorOnly()) {
            $therapistIds = $this->getCoordinatorTherapistIds();
            $searchModel->therapistIds = $therapistIds;
            $therapistsList = $this->getFilteredTherapistsList($therapistIds);
        }

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('patients', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'therapistsList' => $therapistsList,
        ]);
    }

    /**
     * Displays a single Absence model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        // Coordinators can only view absences of therapists in their group
        if ($this->isCoordinatorOnly()) {
            $therapistIds = $this->getCoordinatorTherapistIds();
            if (!in_array($model->therapist_id, $therapistIds)) {
                throw new ForbiddenHttpException('Non hai i permessi per visualizzare questa assenza.');
            }
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Absence model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Absence();
        $model->status = Absence::STATUS_APPROVED;
        $model->created_by = Yii::$app->user->id;
        $model->approved_by = Yii::$app->user->id;
        $model->approved_at = date('Y-m-d H:i:s');

        // Get coordinator's therapist IDs for validation
        $coordinatorTherapistIds = null;
        if ($this->isCoordinatorOnly()) {
            $coordinatorTherapistIds = $this->getCoordinatorTherapistIds();
        }

        if ($model->load(Yii::$app->request->post())) {
            // Coordinator can only create absences for their group's therapists
            if ($coordinatorTherapistIds !== null && !in_array($model->therapist_id, $coordinatorTherapistIds)) {
                throw new ForbiddenHttpException('Non puoi creare assenze per terapisti fuori dal tuo gruppo.');
            }

            $transaction = Yii::$app->db->beginTransaction();
            $model->reason = $this->getReasonLabel($model->type);

            try {
                if ($model->save()) {
                    // Check if we need to update appointments
                    $updateAppointments = Yii::$app->request->post('update_appointments', false);
                    $affectedCount = 0;

                    if ($updateAppointments) {
                        $appointments = $this->getAppointmentsInRange(
                            $model->therapist_id,
                            $model->start_date,
                            $model->end_date
                        );

                        foreach ($appointments as $appointment) {
                            $appointment->status = Appointment::STATUS_THERAPIST_ABSENT;
                            $appointment->save(false);
                        }
                        $affectedCount = count($appointments);

                        Yii::$app->session->setFlash('info',
                            'Assenza creata con successo. ' . $affectedCount . ' appuntamenti sono stati aggiornati.'
                        );
                    } else {
                        Yii::$app->session->setFlash('success', 'Assenza creata con successo.');
                    }

                    $transaction->commit();

                    // Notifica (solo gestionale) direzione + coordinatori del terapista assente.
                    $this->sendTherapistAbsenceNotifications($model, $affectedCount);

                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Errore durante la creazione dell\'assenza: ' . $e->getMessage());
            }
        }

        // Filter therapist dropdown for coordinators
        if ($coordinatorTherapistIds !== null) {
            $therapists = $this->getFilteredTherapistsList($coordinatorTherapistIds);
        } else {
            $therapists = ArrayHelper::map(
                Therapist::find()
                    ->joinWith('user.profile')
                    ->where(['therapists.is_active' => 1])
                    ->orderBy('user_profiles.last_name, user_profiles.first_name')
                    ->all(),
                'id',
                function($model) {
                    return $model->user->profile->last_name . ' ' . $model->user->profile->first_name;
                }
            );
        }

        return $this->render('create', [
            'model' => $model,
            'therapists' => $therapists,
        ]);
    }

    private function getReasonLabel($reason)
    {

        $reasonLabels = [
            Absence::TYPE_VACATION => 'Assenza per vacanza',
            Absence::TYPE_SICK_LEAVE => 'Assenza per malattia',
            Absence::TYPE_PERSONAL => 'Assenza per motivi personali',
            Absence::TYPE_TRAINING => 'Assenza per formazione',
            Absence::TYPE_OTHER => 'Altro',
        ];

        return $reasonLabels[$reason] ?? $reason;
    }

    /**
     * Invia notifiche per un'assenza terapista creata da gestionale.
     * Destinatari (solo gestionale, niente push, coerente con le assenze paziente):
     * la direzione (manager/admin via sendToManagement) e i coordinatori dei gruppi
     * a cui appartiene il terapista assente.
     *
     * @param Absence $absence
     * @param int $affectedAppointments appuntamenti messi in 'terapista assente'
     */
    private function sendTherapistAbsenceNotifications($absence, $affectedAppointments)
    {
        try {
            $therapist = $absence->therapist;
            $therapistName = ($therapist && $therapist->user && $therapist->user->profile)
                ? trim($therapist->user->profile->first_name . ' ' . $therapist->user->profile->last_name)
                : 'Terapista non disponibile';

            $startDate = (new \DateTime($absence->start_date))->format('d/m/Y');
            $endDate = (new \DateTime($absence->end_date))->format('d/m/Y');
            $period = $startDate === $endDate ? "il {$startDate}" : "dal {$startDate} al {$endDate}";

            $user = Yii::$app->user->identity;
            $insertedBy = ($user && $user->profile)
                ? 'Operatore ' . $user->profile->first_name . ' ' . $user->profile->last_name
                : 'Operatore';

            $extraNotes = !empty($absence->notes) ? "\nNote: {$absence->notes}" : '';

            $title = 'Assenza Terapista Inserita dal Gestionale';
            $message = "Inserita un'assenza per il terapista {$therapistName} {$period}.\n"
                . "Motivo: {$absence->reason}{$extraNotes}\n"
                . "Appuntamenti programmati nel periodo: {$affectedAppointments}\n"
                . "Inserita da: {$insertedBy}";

            $data = [
                'absence_id' => $absence->id,
                'therapist_id' => $absence->therapist_id,
                'type' => 'therapist_absence_created',
            ];

            // Direzione: solo gestionale (skipPush=true).
            NotificationHelper::sendToManagement(
                $title,
                $message,
                Notification::TYPE_INFO,
                $data,
                true
            );

            // Coordinatori dei gruppi del terapista assente: solo gestionale (skipPush=true).
            NotificationHelper::sendToTherapistCoordinators(
                $absence->therapist_id,
                $title,
                $message,
                Notification::TYPE_INFO,
                $data,
                true
            );
        } catch (\Exception $e) {
            Yii::error("Errore invio notifiche assenza terapista: " . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Updates an existing Absence model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        // Coordinator can only update absences of their group's therapists
        if ($this->isCoordinatorOnly()) {
            $coordinatorTherapistIds = $this->getCoordinatorTherapistIds();
            if (!in_array($model->therapist_id, $coordinatorTherapistIds)) {
                throw new ForbiddenHttpException('Non hai i permessi per modificare questa assenza.');
            }
        }

        if ($model->load(Yii::$app->request->post())) {
            // Prevent coordinator from reassigning to therapist outside group
            if ($this->isCoordinatorOnly()) {
                $coordinatorTherapistIds = $this->getCoordinatorTherapistIds();
                if (!in_array($model->therapist_id, $coordinatorTherapistIds)) {
                    throw new ForbiddenHttpException('Non puoi assegnare assenze a terapisti fuori dal tuo gruppo.');
                }
            }

            $model->reason = $this->getReasonLabel($model->type);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Assenza aggiornata con successo.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            Yii::$app->session->setFlash('error', 'Errore durante l\'aggiornamento dell\'assenza: ' . $model->errors);
        }

        // Filter therapist dropdown for coordinators
        if ($this->isCoordinatorOnly()) {
            $therapists = $this->getFilteredTherapistsList($this->getCoordinatorTherapistIds());
        } else {
            $therapists = ArrayHelper::map(
                Therapist::find()
                    ->joinWith('user.profile')
                    ->where(['therapists.is_active' => 1])
                    ->orderBy('user_profiles.last_name, user_profiles.first_name')
                    ->all(),
                'id',
                function($model) {
                    return $model->user->profile->last_name . ' ' . $model->user->profile->first_name;
                }
            );
        }

        return $this->render('update', [
            'model' => $model,
            'therapists' => $therapists,
        ]);
    }

    /**
     * Deletes an existing Absence model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Coordinator can only delete absences of their group's therapists
        if ($this->isCoordinatorOnly()) {
            $therapistIds = $this->getCoordinatorTherapistIds();
            if (!in_array($model->therapist_id, $therapistIds)) {
                throw new ForbiddenHttpException('Non hai i permessi per eliminare questa assenza.');
            }
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Assenza eliminata con successo.');

        return $this->redirect(['index']);
    }

    /**
     * AJAX action to check appointments in date range
     * @return mixed
     */
    public function actionCheckAppointments()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $therapistId = Yii::$app->request->post('therapist_id');
        $startDate = Yii::$app->request->post('start_date');
        $endDate = Yii::$app->request->post('end_date');
        
        if (!$therapistId || !$startDate || !$endDate) {
            return ['count' => 0, 'appointments' => []];
        }
        
        $appointments = $this->getAppointmentsInRange($therapistId, $startDate, $endDate);
        
        $appointmentDetails = [];
        foreach ($appointments as $appointment) {
            $patient = $appointment->getActualPatient();
            $appointmentDetails[] = [
                'date' => Yii::$app->formatter->asDate($appointment->appointment_datetime),
                'time' => Yii::$app->formatter->asTime($appointment->appointment_datetime, 'short'),
                'patient' => $patient ? $patient->getFullName() : 'N/A',
                'type' => $appointment->getAppointmentTypeLabel(),
            ];
        }
        
        return [
            'count' => count($appointments),
            'appointments' => $appointmentDetails
        ];
    }

    /**
     * Get appointments in date range for a therapist
     * @param int $therapistId
     * @param string $startDate
     * @param string $endDate
     * @return Appointment[]
     */
    protected function getAppointmentsInRange($therapistId, $startDate, $endDate)
    {
        return Appointment::find()
            ->where(['therapist_id' => $therapistId])
            ->andWhere(['>=', 'appointment_datetime', $startDate . ' 00:00:00'])
            ->andWhere(['<=', 'appointment_datetime', $endDate . ' 23:59:59'])
            ->andWhere(['status' => Appointment::STATUS_SCHEDULED])
            ->orderBy('appointment_datetime')
            ->all();
    }

    /**
     * AJAX action to remove patient absence and restore appointment to scheduled.
     * @return mixed
     */
    public function actionRemovePatientAbsence()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        if (!$id) {
            return ['success' => false, 'error' => 'ID appuntamento mancante'];
        }

        $appointment = Appointment::findOne($id);
        if (!$appointment) {
            return ['success' => false, 'error' => 'Appuntamento non trovato'];
        }

        // Verifica che lo status sia assente
        if (!in_array($appointment->status, [Appointment::STATUS_ABSENT_JUSTIFIED, Appointment::STATUS_ABSENT_NOT_JUSTIFIED])) {
            return ['success' => false, 'error' => 'L\'appuntamento non ha stato assente'];
        }

        // Coordinator can only remove absences for their group's therapists
        if ($this->isCoordinatorOnly()) {
            $therapistIds = $this->getCoordinatorTherapistIds();
            if (!in_array($appointment->therapist_id, $therapistIds)) {
                return ['success' => false, 'error' => 'Non hai i permessi per questa operazione.'];
            }
        }

        // Nessun vincolo temporale per gestionale (admin onnipotente).
        // Avviso "<1h" gestito lato frontend con conferma.

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Identifica l'operatore per le notifiche
            $user = Yii::$app->user->identity;
            $removedBy = 'Operatore';
            if ($user && $user->profile) {
                $removedBy = 'Operatore ' . $user->profile->first_name . ' ' . $user->profile->last_name;
            }

            // Salva lo status precedente per il log
            $oldStatus = $appointment->status;

            // Pre-check: verifica che lo slot non sia stato occupato da un altro
            // appuntamento attivo dopo la cancellazione (overlap reale: due
            // intervalli si sovrappongono se start < otherEnd AND end > otherStart).
            // Restituisce un messaggio chiaro invece di lasciare scattare la
            // validazione del modello. Allineato a Appointment::validateTherapistAvailability.
            $startStr = $appointment->appointment_datetime;
            $endStr = date('Y-m-d H:i:s',
                strtotime($appointment->appointment_datetime) + ($appointment->duration_minutes * 60));
            $existingEnd = new \yii\db\Expression('DATE_ADD(appointment_datetime, INTERVAL duration_minutes MINUTE)');
            $conflictQuery = Appointment::find()
                ->where(['therapist_id' => $appointment->therapist_id])
                ->andWhere(['!=', 'id', $appointment->id])
                ->andWhere([
                    'or',
                    ['and',
                        ['<=', 'appointment_datetime', $startStr],
                        ['>',  $existingEnd, $startStr],
                    ],
                    ['and',
                        ['<',  'appointment_datetime', $endStr],
                        ['>=', 'appointment_datetime', $startStr],
                    ],
                ])
                ->andWhere(['not in', 'status', [
                    Appointment::STATUS_CANCELLED,
                    Appointment::STATUS_COMPLETED,
                    Appointment::STATUS_ABSENT_JUSTIFIED,
                    Appointment::STATUS_ABSENT_NOT_JUSTIFIED,
                    Appointment::STATUS_THERAPIST_ABSENT,
                ]]);
            if (!empty($appointment->group_session_id)) {
                $conflictQuery->andWhere(['!=', 'group_session_id', $appointment->group_session_id]);
            }
            $hasConflict = $conflictQuery->exists();
            if ($hasConflict) {
                $transaction->rollBack();
                return [
                    'success' => false,
                    'error' => 'Impossibile ripristinare l\'appuntamento: nello stesso orario il terapista ha gia\' un altro appuntamento attivo. Cancella l\'altro appuntamento prima di rimuovere l\'assenza.',
                ];
            }

            // Riporta lo status a scheduled
            $appointment->status = Appointment::STATUS_SCHEDULED;

            if (!$appointment->save()) {
                throw new \Exception("Errore nel salvataggio: " . json_encode($appointment->errors));
            }

            // Log esplicito dell'operazione di rimozione assenza
            $activityLog = new ActivityLog([
                'user_id' => Yii::$app->user->id,
                'action' => ActivityLog::ACTION_UPDATE,
                'entity_name' => 'Appointment',
                'entity_id' => $appointment->id,
                'old_values' => json_encode([
                    'status' => $oldStatus,
                    'operation' => 'absence_active',
                ]),
                'new_values' => json_encode([
                    'status' => Appointment::STATUS_SCHEDULED,
                    'operation' => 'absence_removed',
                    'removed_by' => $removedBy,
                    'source' => 'gestionale',
                ]),
                'ip_address' => Yii::$app->request->getUserIP(),
                'user_agent' => Yii::$app->request->getUserAgent(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            if (!$activityLog->save()) {
                Yii::warning('Failed to save activity log for absence removal: ' . json_encode($activityLog->getErrors()), __METHOD__);
            }

            // Invia notifiche
            $this->sendRemoveAbsenceNotifications($appointment, $removedBy);

            $transaction->commit();

            Yii::info("Assenza rimossa per appuntamento {$id} da {$removedBy} (gestionale)", __METHOD__);

            return ['success' => true];
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Errore rimozione assenza paziente: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Invia notifiche di rimozione assenza
     */
    private function sendRemoveAbsenceNotifications($appointment, $removedBy)
    {
        try {
            $patient = $appointment->getActualPatient();
            $therapist = $appointment->therapist;
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);

            $patientName = $patient ? $patient->getFullName() : 'Paziente non disponibile';
            $therapistName = ($therapist && $therapist->user && $therapist->user->profile)
                ? ($therapist->user->profile->first_name . ' ' . $therapist->user->profile->last_name)
                : 'Terapista non disponibile';
            $appointmentDate = $appointmentDateTime->format('d/m/Y');
            $appointmentTime = $appointmentDateTime->format('H:i');

            // Notifica al manager
            $managerTitle = 'Assenza Rimossa dal Gestionale';
            $managerMessage = "L'assenza per l'appuntamento del {$appointmentDate} alle {$appointmentTime} è stata rimossa.\nPaziente: {$patientName}\nTerapista: {$therapistName}\nRimossa da: {$removedBy}";
            $managerData = [
                'appointment_id' => $appointment->id,
                'removed_by' => $removedBy,
                'type' => 'absence_removed'
            ];

            NotificationHelper::sendToManagement(
                $managerTitle,
                $managerMessage,
                Notification::TYPE_INFO,
                $managerData,
                true
            );

            // Notifica ai coordinatori dei gruppi del terapista (stesso contenuto dei manager)
            if ($therapist) {
                NotificationHelper::sendToTherapistCoordinators(
                    $therapist->id,
                    $managerTitle,
                    $managerMessage,
                    Notification::TYPE_INFO,
                    $managerData,
                    true
                );
            }

            // Notifica al terapista
            if ($therapist) {
                NotificationHelper::sendToUsers(
                    [$therapist->user_id],
                    'Assenza Rimossa - Appuntamento Ripristinato',
                    "L'assenza del paziente {$patientName} per l'appuntamento del {$appointmentDate} alle {$appointmentTime} è stata rimossa dal gestionale.\nL'appuntamento è stato ripristinato come confermato.\nRimossa da: {$removedBy}",
                    Notification::TYPE_INFO,
                    [
                        'appointment_id' => $appointment->id,
                        'removed_by' => $removedBy,
                        'type' => 'absence_removed'
                    ]
                );
            }

            // Notifica al paziente: solo self + familiari con autorita' parentale.
            if ($patient) {
                $patientUserIds = \common\models\AccountPatient::getNotifiableUserIdsForPatient($patient->id);

                if (!empty($patientUserIds)) {
                    NotificationHelper::sendToUsers(
                        $patientUserIds,
                        'Appuntamento Ripristinato',
                        "L'assenza per l'appuntamento del {$appointmentDate} alle {$appointmentTime} con il terapista {$therapistName} è stata rimossa.\nL'appuntamento è stato ripristinato come confermato.",
                        Notification::TYPE_INFO,
                        [
                            'appointment_id' => $appointment->id,
                            'type' => 'absence_removed'
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Yii::error("Errore invio notifiche rimozione assenza: " . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Finds the Absence model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Absence the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Absence::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La pagina richiesta non esiste.');
    }

    // =========================================================================
    // CREAZIONE ASSENZE PAZIENTE DA GESTIONALE (manage_patient_absence)
    // =========================================================================

    /**
     * Pagina UI per creare assenze paziente in blocco.
     * Permission: manage_patient_absence
     */
    public function actionCreatePatientAbsence()
    {
        // Unificato in actionPatients
        return $this->redirect(['patients']);
    }

    /**
     * AJAX: ricerca pazienti per autocomplete.
     */
    public function actionSearchPatientsAbsence($q = '', $page = 1, $pageSize = 20)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim((string) $q);
        $page = max(1, (int) $page);
        $pageSize = min(200, max(1, (int) $pageSize));

        $query = \common\models\Patient::find()
            ->select(['id', 'first_name', 'last_name', 'birth_date', 'fiscal_code'])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC]);

        if ($q !== '') {
            $query->andWhere([
                'or',
                ['like', 'first_name', $q],
                ['like', 'last_name', $q],
                ['like', 'fiscal_code', $q],
                ['like', 'CONCAT(first_name, " ", last_name)', $q],
                ['like', 'CONCAT(last_name, " ", first_name)', $q],
                ['like', 'birth_date', $q],
            ]);
        }

        $total = (int) $query->count();
        $offset = ($page - 1) * $pageSize;
        $patients = $query->offset($offset)->limit($pageSize)->asArray()->all();

        $items = array_map(function ($p) {
            return [
                'id' => (int) $p['id'],
                'name' => trim($p['first_name'] . ' ' . $p['last_name']),
                'fiscalCode' => $p['fiscal_code'],
                'birthDate' => $p['birth_date'],
            ];
        }, $patients);

        return [
            'success' => true,
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * AJAX: lista appuntamenti del paziente con filtri data.
     */
    public function actionPatientAppointmentsAbsence($patientId, $from = null, $to = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $patientId = (int) $patientId;
        if ($patientId <= 0) {
            return ['success' => false, 'error' => 'patientId richiesto'];
        }

        $query = Appointment::find()
            ->alias('a')
            ->leftJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
            ->leftJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
            ->leftJoin('treatment_types tt', 'tt.id = COALESCE(pt.treatment_type_id, a.treatment_type_id)')
            ->with(['therapist.user.profile', 'planTherapy.treatmentType', 'treatmentType'])
            ->where([
                'or',
                ['tp.patient_id' => $patientId],
                ['a.patient_id' => $patientId],
            ])
            ->andWhere(['not in', 'a.status', [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_COMPLETED,
            ]]);

        if ($from) {
            $query->andWhere(['>=', 'a.appointment_datetime', $from . ' 00:00:00']);
        }
        if ($to) {
            $query->andWhere(['<=', 'a.appointment_datetime', $to . ' 23:59:59']);
        }

        $items = [];
        foreach ($query->orderBy(['a.appointment_datetime' => SORT_ASC])->all() as $a) {
            $treatmentType = $a->planTherapy && $a->planTherapy->treatmentType
                ? $a->planTherapy->treatmentType->name
                : ($a->treatmentType ? $a->treatmentType->name : '-');
            $therapistName = $a->therapist && $a->therapist->user && $a->therapist->user->profile
                ? $a->therapist->user->profile->getFullName()
                : '-';
            $items[] = [
                'id' => $a->id,
                'datetime' => $a->appointment_datetime,
                'duration' => $a->duration_minutes,
                'status' => $a->status,
                'isAdminAbsence' => (bool) $a->is_admin_absence,
                'appointmentType' => $a->appointment_type,
                'treatmentType' => $treatmentType,
                'therapist' => $therapistName,
                'notes' => $a->notes,
            ];
        }

        return ['success' => true, 'items' => $items];
    }

    /**
     * Bulk: marca N appuntamenti come assenti con tipo+motivo.
     * Setta is_admin_absence=1 per non revocabilita' lato app.
     */
    public function actionMarkPatientsAbsent()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $body = Yii::$app->request->getBodyParams();
        $appointmentIds = $body['appointmentIds'] ?? [];
        $absenceType = $body['absenceType'] ?? 'justified'; // justified | not_justified
        $reason = trim($body['reason'] ?? '');
        $notes = trim($body['notes'] ?? '');

        if (!is_array($appointmentIds) || empty($appointmentIds)) {
            return ['success' => false, 'error' => 'Selezionare almeno un appuntamento'];
        }
        if (!in_array($absenceType, ['justified', 'not_justified'])) {
            return ['success' => false, 'error' => 'Tipo assenza non valido'];
        }
        if ($reason === '') {
            return ['success' => false, 'error' => 'Motivo obbligatorio'];
        }

        $newStatus = $absenceType === 'justified'
            ? Appointment::STATUS_ABSENT_JUSTIFIED
            : Appointment::STATUS_ABSENT_NOT_JUSTIFIED;

        $updated = 0;
        $skipped = [];
        $userId = Yii::$app->user->id;
        $userLabel = 'Admin ID ' . $userId;

        // Etichetta umana per "inserita da" (nelle notifiche).
        $user = Yii::$app->user->identity;
        $insertedBy = 'Operatore';
        if ($user && $user->profile) {
            $insertedBy = 'Operatore ' . $user->profile->first_name . ' ' . $user->profile->last_name;
        }

        // Appointment "successo" da notificare dopo il commit (per non inviare
        // notifiche in caso di rollback della transazione).
        $notifyAppointmentIds = [];

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($appointmentIds as $aid) {
                $appointment = Appointment::findOne((int) $aid);
                if (!$appointment) {
                    $skipped[] = ['id' => $aid, 'reason' => 'non trovato'];
                    continue;
                }
                if (in_array($appointment->status, [
                    Appointment::STATUS_CANCELLED,
                    Appointment::STATUS_COMPLETED,
                    Appointment::STATUS_ABSENT_JUSTIFIED,
                    Appointment::STATUS_ABSENT_NOT_JUSTIFIED,
                ])) {
                    $skipped[] = ['id' => $aid, 'reason' => 'stato non modificabile (' . $appointment->status . ')'];
                    continue;
                }

                $oldStatus = $appointment->status;
                $appointment->status = $newStatus;
                $appointment->is_admin_absence = true;

                $absenceNote = "ASSENZA INSERITA DA GESTIONALE - Tipo: " .
                    ($absenceType === 'justified' ? 'giustificata' : 'non giustificata') .
                    " - Motivo: {$reason}";
                if ($notes !== '') {
                    $absenceNote .= " - Note: {$notes}";
                }
                $absenceNote .= " - Da: {$userLabel} - " . date('Y-m-d H:i:s');

                $appointment->notes = !empty($appointment->notes)
                    ? $appointment->notes . "\n\n" . $absenceNote
                    : $absenceNote;

                if (!$appointment->save()) {
                    throw new \Exception("Errore salvataggio appointment {$aid}: " . json_encode($appointment->errors));
                }

                // Activity log
                $log = new ActivityLog([
                    'user_id' => $userId,
                    'action' => ActivityLog::ACTION_UPDATE,
                    'entity_name' => 'Appointment',
                    'entity_id' => $appointment->id,
                    'old_values' => json_encode(['status' => $oldStatus]),
                    'new_values' => json_encode([
                        'status' => $newStatus,
                        'operation' => 'admin_patient_absence',
                        'absence_type' => $absenceType,
                        'reason' => $reason,
                        'notes' => $notes,
                    ]),
                    'ip_address' => Yii::$app->request->getUserIP(),
                    'user_agent' => Yii::$app->request->getUserAgent(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $log->save(false);

                $notifyAppointmentIds[] = (int) $appointment->id;
                $updated++;
            }

            $transaction->commit();

            // Notifiche post-commit: manager, terapista, paziente (+familiari).
            foreach ($notifyAppointmentIds as $appointmentId) {
                $appointment = Appointment::findOne($appointmentId);
                if ($appointment) {
                    $this->sendCreateAbsenceNotifications(
                        $appointment,
                        $absenceType,
                        $reason,
                        $notes,
                        $insertedBy
                    );
                }
            }

            return [
                'success' => true,
                'updated' => $updated,
                'skipped' => $skipped,
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Errore mark-patients-absent: ' . $e->getMessage(), __METHOD__);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Invia notifiche per assenza paziente creata da gestionale.
     * Mirror di sendRemoveAbsenceNotifications.
     */
    private function sendCreateAbsenceNotifications($appointment, $absenceType, $reason, $notes, $insertedBy)
    {
        try {
            $patient = $appointment->getActualPatient();
            $therapist = $appointment->therapist;
            $appointmentDateTime = new \DateTime($appointment->appointment_datetime);

            $patientName = $patient ? $patient->getFullName() : 'Paziente non disponibile';
            $therapistName = ($therapist && $therapist->user && $therapist->user->profile)
                ? ($therapist->user->profile->first_name . ' ' . $therapist->user->profile->last_name)
                : 'Terapista non disponibile';
            $appointmentDate = $appointmentDateTime->format('d/m/Y');
            $appointmentTime = $appointmentDateTime->format('H:i');
            $absenceLabel = $absenceType === 'justified' ? 'giustificata' : 'non giustificata';

            $extraNotes = $notes !== '' ? "\nNote: {$notes}" : '';

            $data = [
                'appointment_id' => $appointment->id,
                'absence_type' => $absenceType,
                'reason' => $reason,
                'inserted_by' => $insertedBy,
                'type' => 'absence_created',
            ];

            // Manager
            $managerTitle = 'Assenza Paziente Inserita dal Gestionale';
            $managerMessage = "Inserita assenza {$absenceLabel} per l'appuntamento del {$appointmentDate} alle {$appointmentTime}.\nPaziente: {$patientName}\nTerapista: {$therapistName}\nMotivo: {$reason}{$extraNotes}\nInserita da: {$insertedBy}";

            NotificationHelper::sendToManagement(
                $managerTitle,
                $managerMessage,
                Notification::TYPE_INFO,
                $data,
                true
            );

            // Notifica ai coordinatori dei gruppi del terapista (stesso contenuto dei manager)
            if ($therapist) {
                NotificationHelper::sendToTherapistCoordinators(
                    $therapist->id,
                    $managerTitle,
                    $managerMessage,
                    Notification::TYPE_INFO,
                    $data,
                    true
                );
            }

            // Terapista
            if ($therapist) {
                NotificationHelper::sendToUsers(
                    [$therapist->user_id],
                    'Assenza Paziente Segnalata',
                    "Il paziente {$patientName} risulta assente ({$absenceLabel}) per l'appuntamento del {$appointmentDate} alle {$appointmentTime}.\nMotivo: {$reason}{$extraNotes}\nInserita da: {$insertedBy}",
                    Notification::TYPE_INFO,
                    $data
                );
            }

            // Paziente: self + familiari con autorita' parentale.
            if ($patient) {
                $patientUserIds = \common\models\AccountPatient::getNotifiableUserIdsForPatient($patient->id);
                if (!empty($patientUserIds)) {
                    NotificationHelper::sendToUsers(
                        $patientUserIds,
                        'Assenza Registrata',
                        "L'appuntamento del {$appointmentDate} alle {$appointmentTime} con il terapista {$therapistName} è stato registrato come assenza {$absenceLabel}.\nMotivo: {$reason}",
                        Notification::TYPE_INFO,
                        $data
                    );
                }
            }
        } catch (\Exception $e) {
            Yii::error("Errore invio notifiche creazione assenza: " . $e->getMessage(), __METHOD__);
        }
    }
}