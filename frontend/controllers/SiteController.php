<?php

namespace frontend\controllers;

use common\models\Appointment;
use common\models\DocumentRequest;
use common\models\Notification;
use common\models\Patient;
use common\models\TherapeuticPlan;
use common\models\Therapist;
use common\models\User;
use common\services\statistics\StatisticsService;
use frontend\models\ChangePasswordForm;
use frontend\models\ContactForm;
use frontend\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResendVerificationEmailForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\VerifyEmailForm;
use yii\base\InvalidArgumentException;
use yii\db\Expression;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use Yii;

/**
 * Site controller
 */
class SiteController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Sovrascrivere le regole di accesso per permettere accesso pubblico a azioni specifiche
        $behaviors['access']['rules'] = [
            [
                // Permettere accesso alla pagina di login, error e azioni di registrazione per tutti
                'actions' => ['login', 'error', 'signup', 'request-password-reset', 'reset-password', 'verify-email', 'resend-verification-email'],
                'allow' => true,
                'roles' => ['?', '@'],  // ? = guest, @ = authenticated
            ],
            [
                // Tutte le altre azioni solo per utenti autenticati
                'allow' => true,
                'roles' => ['@'],
            ],
        ];

        // Aggiungere comportamenti specifici per verbi
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'logout' => ['post'],
            ],
        ];

        return $behaviors;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        if ($action->id === 'error' || $action->id === 'login' || $action->id === 'request-password-reset') {
            $this->layout = 'blank';
        }

        return parent::beforeAction($action);
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->can('view_statistics')) {
            return $this->render('index', $this->emptyHomeDashboard());
        }

        $today = date('Y-m-d');
        $statisticsService = new StatisticsService();

        // Stessa definizione di /statistics: piano status=active valido oggi.
        $totalPatients = $statisticsService->countPatientsInCharge($today);

        // Nuovi piani: piani 'new' con inizio nel mese corrente (anche futuro), bozze escluse.
        // La variazione confronta gli stessi giorni (1..oggi) del mese precedente.
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $lastMonthSameDay = date('Y-m-d', strtotime($lastMonthStart . ' +' . (min((int) date('j'), (int) date('t', strtotime($lastMonthStart))) - 1) . ' days'));

        $newPlansThisMonth = $statisticsService->countPlansByType(TherapeuticPlan::PLAN_TYPE_NEW, $monthStart, $monthEnd);
        $renewalPlansThisMonth = $statisticsService->countPlansByType(TherapeuticPlan::PLAN_TYPE_RENEWAL, $monthStart, $monthEnd);
        $newPlansChange = $this->percentChange(
            $statisticsService->countPlansByType(TherapeuticPlan::PLAN_TYPE_NEW, $monthStart, $today),
            $statisticsService->countPlansByType(TherapeuticPlan::PLAN_TYPE_NEW, $lastMonthStart, $lastMonthSameDay)
        );

        $totalTherapists = (int) Therapist::find()->where(['is_active' => 1])->count();

        $todayStart = $today . ' 00:00:00';
        $todayEnd = $today . ' 23:59:59';
        $todayByStatus = (new Query())
            ->select([
                'c' => 'COUNT(*)',
                'status',
            ])
            ->from(Appointment::tableName())
            ->where(['between', 'appointment_datetime', $todayStart, $todayEnd])
            ->groupBy('status')
            ->indexBy('status')
            ->column();

        $cancelledToday = (int) ($todayByStatus[Appointment::STATUS_CANCELLED] ?? 0);
        $completedAppointmentsToday = (int) ($todayByStatus[Appointment::STATUS_COMPLETED] ?? 0);
        $scheduledToday = (int) ($todayByStatus[Appointment::STATUS_SCHEDULED] ?? 0);
        $totalAppointmentsToday = array_sum(array_map('intval', $todayByStatus)) - $cancelledToday;
        $expectedAppointmentsToday = $scheduledToday + $completedAppointmentsToday;
        $absentAppointmentsToday = $totalAppointmentsToday - $expectedAppointmentsToday;
        $appointmentCompletionRate = $expectedAppointmentsToday > 0
            ? (int) round(($completedAppointmentsToday / $expectedAppointmentsToday) * 100)
            : 0;

        $upcomingAppointments = Appointment::find()
            ->with(['patient', 'patientViaPlanTherapy', 'therapist.user.profile'])
            ->where(['>', 'appointment_datetime', date('Y-m-d H:i:s')])
            ->andWhere(['status' => Appointment::STATUS_SCHEDULED])
            ->orderBy(['appointment_datetime' => SORT_ASC])
            ->limit(5)
            ->all();

        $pendingDocumentRequests = (int) DocumentRequest::findActive()->count();
        $pickupDocumentRequests = (int) DocumentRequest::find()
            ->where(['status' => DocumentRequest::STATUS_STAMPATO])
            ->count();

        $activeTherapeuticPlans = (int) TherapeuticPlan::find()->activeAtDate($today)->count();

        $expiringUntil = date('Y-m-d', strtotime('+30 days'));
        $expiringQuery = (new Query())
            ->from(['tp' => TherapeuticPlan::tableName()])
            ->innerJoin(['p' => Patient::tableName()], 'p.id = tp.patient_id')
            ->where(['tp.status' => TherapeuticPlan::STATUS_ACTIVE])
            ->andWhere(['<=', 'tp.start_date', $today])
            ->andWhere(['>=', 'tp.end_date', $today])
            ->andWhere(['<=', 'tp.end_date', $expiringUntil]);
        $expiringPlansCount = (int) (clone $expiringQuery)->count();
        $expiringPlans = (clone $expiringQuery)
            ->select([
                'tp.id',
                'tp.end_date',
                'p.id as patient_id',
                "CONCAT(p.first_name, ' ', p.last_name) as patient_name",
                'days_until_expiry' => new Expression('DATEDIFF(tp.end_date, CURDATE())'),
            ])
            ->orderBy(['tp.end_date' => SORT_ASC])
            ->limit(8)
            ->all();

        $unreadNotifications = 0;
        if (!Yii::$app->user->isGuest) {
            $unreadNotifications = (int) Notification::findUnread()
                ->andWhere(['recipient_user_id' => Yii::$app->user->id])
                ->count();
        }

        $rangeStart = date('Y-m-d 00:00:00', strtotime('-6 days'));
        $dailyRows = (new Query())
            ->select([
                'c' => 'COUNT(*)',
                'd' => new Expression('DATE(appointment_datetime)'),
            ])
            ->from(Appointment::tableName())
            ->where(['between', 'appointment_datetime', $rangeStart, $todayEnd])
            ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
            ->groupBy(new Expression('DATE(appointment_datetime)'))
            ->indexBy('d')
            ->column();

        $dailyAppointments = [];
        $dayLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dailyAppointments[] = (int) ($dailyRows[$date] ?? 0);
            $dayLabels[] = date('d/m', strtotime($date));
        }

        $statusLabels = DocumentRequest::getStatusLabels();
        $requestsData = [];
        foreach (DocumentRequest::find()
            ->select(['status', 'count' => 'COUNT(*)'])
            ->groupBy(['status'])
            ->asArray()
            ->all() as $item) {
            $count = (int) $item['count'];
            if ($count > 0) {
                $requestsData[] = [
                    'status_name' => $statusLabels[$item['status']] ?? 'Sconosciuto',
                    'count' => $count,
                ];
            }
        }

        return $this->render('index', [
            'totalPatients' => $totalPatients,
            'newPlansThisMonth' => $newPlansThisMonth,
            'renewalPlansThisMonth' => $renewalPlansThisMonth,
            'newPlansChange' => $newPlansChange,
            'totalTherapists' => $totalTherapists,
            'totalAppointmentsToday' => $totalAppointmentsToday,
            'completedAppointmentsToday' => $completedAppointmentsToday,
            'scheduledAppointmentsToday' => $scheduledToday,
            'expectedAppointmentsToday' => $expectedAppointmentsToday,
            'absentAppointmentsToday' => $absentAppointmentsToday,
            'appointmentCompletionRate' => $appointmentCompletionRate,
            'upcomingAppointments' => $upcomingAppointments,
            'pendingDocumentRequests' => $pendingDocumentRequests,
            'pickupDocumentRequests' => $pickupDocumentRequests,
            'expiringPlansCount' => $expiringPlansCount,
            'expiringPlans' => $expiringPlans,
            'activeTherapeuticPlans' => $activeTherapeuticPlans,
            'unreadNotifications' => $unreadNotifications,
            'dailyAppointments' => $dailyAppointments,
            'dayLabels' => $dayLabels,
            'requestsData' => $requestsData,
            'hasRealRequestsData' => $requestsData !== [],
        ]);
    }

    /**
     * Dettaglio della scheda "Pazienti in carico": regime → setting → trattamento.
     *
     * @return array
     * @throws ForbiddenHttpException
     */
    public function actionPatientsInChargeBreakdown()
    {
        if (!Yii::$app->user->can('view_statistics')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare le statistiche.');
        }
        Yii::$app->response->format = Response::FORMAT_JSON;

        $today = date('Y-m-d');
        $breakdown = (new StatisticsService())->getPatientsInChargeBreakdown($today);
        $breakdown['date'] = Yii::$app->formatter->asDate($today, 'php:d/m/Y');

        return $breakdown;
    }

    /**
     * Elenco dei pazienti in carico per un trattamento del dettaglio.
     * regime_id vuoto = piani senza regime.
     *
     * @param int $setting_id
     * @param int $treatment_type_id
     * @param string $regime_id
     * @return array
     * @throws ForbiddenHttpException
     */
    public function actionPatientsInChargeList($setting_id, $treatment_type_id, $regime_id = '')
    {
        if (!Yii::$app->user->can('view_statistics')) {
            throw new ForbiddenHttpException('Non hai i permessi per visualizzare le statistiche.');
        }
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rows = (new StatisticsService())->getPatientsInChargeList(
            date('Y-m-d'),
            $regime_id === '' ? null : (int) $regime_id,
            (int) $setting_id,
            (int) $treatment_type_id
        );

        $canViewPatient = Yii::$app->user->can('view_patient');
        $formatter = Yii::$app->formatter;
        $patients = [];
        foreach ($rows as $row) {
            $patients[] = [
                'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                'url' => $canViewPatient ? Url::to(['patient/view', 'id' => $row['patient_id']]) : null,
                'plan' => $formatter->asDate($row['start_date'], 'php:d/m/Y') . ' – ' . $formatter->asDate($row['end_date'], 'php:d/m/Y'),
                'end' => $formatter->asDate($row['end_date'], 'php:d/m/Y'),
                'hours' => rtrim(rtrim(number_format((float) $row['weekly_hours'], 2, ',', ''), '0'), ','),
            ];
        }

        return ['rows' => $patients];
    }

    /**
     * Placeholder per chi non può vedere le statistiche: niente query pesanti.
     */
    protected function emptyHomeDashboard()
    {
        return [
            'totalPatients' => 0,
            'newPlansThisMonth' => 0,
            'renewalPlansThisMonth' => 0,
            'newPlansChange' => null,
            'totalTherapists' => 0,
            'totalAppointmentsToday' => 0,
            'completedAppointmentsToday' => 0,
            'scheduledAppointmentsToday' => 0,
            'expectedAppointmentsToday' => 0,
            'absentAppointmentsToday' => 0,
            'appointmentCompletionRate' => 0,
            'upcomingAppointments' => [],
            'pendingDocumentRequests' => 0,
            'pickupDocumentRequests' => 0,
            'expiringPlansCount' => 0,
            'expiringPlans' => [],
            'activeTherapeuticPlans' => 0,
            'unreadNotifications' => 0,
            'dailyAppointments' => [],
            'dayLabels' => [],
            'requestsData' => [],
            'hasRealRequestsData' => false,
        ];
    }

    /**
     * Variazione % tra due periodi. Null se il periodo precedente è 0 (evita 0% fuorviante).
     *
     * @param int $current
     * @param int $previous
     * @return float|null
     */
    protected function percentChange($current, $previous)
    {
        if ((int) $previous <= 0) {
            return null;
        }

        return round((((int) $current - (int) $previous) / (int) $previous) * 100, 1);
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            Yii::$app->session->setFlash('success', 'Thank you for registration. Please check your inbox for verification email.');
            return $this->goHome();
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        $confirmed = false;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                $confirmed = true;
            } else {
                Yii::$app->session->setFlash('error', "Spiacenti, non riusciamo a resettare la password per l'indirizzo email fornito.");
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
            'confirmed' => $confirmed,
        ]);
    }

    /**
     * Reset password page
     *
     * @param string $token
     * @return mixed
     */
    public function actionResetPassword($token = null)
    {
        // Usa layout vuoto per questa pagina
        $this->layout = 'blank';

        // Se non c'è token, mostra errore
        if (!$token) {
            Yii::$app->session->setFlash('error', 'Link di reset password non valido.');
            return $this->redirect(['site/login']);
        }

        // Verifica se il token è valido
        $user = User::findByPasswordResetToken($token);
        if (!$user) {
            Yii::$app->session->setFlash('error', 'Link di reset password scaduto o non valido.');
            return $this->redirect(['site/login']);
        }

        // Mostra la sezione "Token per app mobile" solo agli utenti che possono
        // effettivamente loggarsi nell'app (permesso RBAC `app_login`).
        // In questo modo super_admin/admin/manager/coordinator (utenti del solo
        // gestionale web) non vedono un token che non potrebbero usare.
        $auth = Yii::$app->authManager;
        $canUseMobileApp = $auth ? $auth->checkAccess($user->id, 'app_login') : false;

        // Crea il form di reset password
        $model = new ResetPasswordForm($token);

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            return $this->render('resetPassword', [
                'model' => $model,
                'token' => $token,
                'success' => true,
                'canUseMobileApp' => $canUseMobileApp,
            ]);
        }

        return $this->render('resetPassword', [
            'model' => $model,
            'token' => $token,
            'success' => false,
            'canUseMobileApp' => $canUseMobileApp,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if (($user = $model->verifyEmail()) && Yii::$app->user->login($user)) {
            Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
            return $this->goHome();
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }

    /**
     * Cambia la password dell'utente loggato
     *
     * @return mixed
     */
    public function actionChangePassword()
    {
        $model = new ChangePasswordForm();

        if ($model->load(Yii::$app->request->post()) && $model->changePassword()) {
            // Dopo il cambio password, l'auth_key viene rigenerato e la sessione viene invalidata
            // Dobbiamo fare il re-login per mantenere l'utente autenticato
            $userId = Yii::$app->user->id;
            if ($userId) {
                // Ricarica l'utente dal database per ottenere il nuovo auth_key
                $user = User::findOne($userId);
                if ($user) {
                    // Effettua il login con la nuova identità
                    Yii::$app->user->login($user, 3600 * 24 * 30); // Login per 30 giorni
                }
            }
            
            Yii::$app->session->setFlash('success', 'Password modificata con successo.');
            return $this->redirect(['site/change-password']);
        }

        return $this->render('change-password', [
            'model' => $model,
        ]);
    }

    // TODO da rimuovere
    public function actionForm()
    {
        return $this->render('form');
    }

    /**
     * Manuale d'uso - pagina di smistamento.
     *
     * @return mixed
     */
    public function actionManuale()
    {
        return $this->render('manuale');
    }

    /**
     * Manuale d'uso del gestionale web.
     *
     * @return mixed
     */
    public function actionManualeGestionale()
    {
        return $this->render('manuale-gestionale');
    }

    /**
     * Manuale d'uso dell'app mobile.
     *
     * @return mixed
     */
    public function actionManualeApp()
    {
        return $this->render('manuale-app');
    }

    /**
     * Pagina "I miei permessi": mostra ruoli e permessi dell'utente
     * loggato in sola lettura. Accessibile a chiunque sia autenticato.
     */
    public function actionMyPermissions()
    {
        $userId = Yii::$app->user->id;
        $auth = Yii::$app->authManager;

        // Lista permessi disattivati da PermissionMetadata: vanno esclusi
        // dal conteggio e dalla visualizzazione (coerente con
        // /permission/roles che filtra i 'permessi reali e attivi').
        $inactivePermissionNames = \common\models\PermissionMetadata::find()
            ->select('permission_name')
            ->where(['is_active' => 0])
            ->column();
        $inactiveSet = array_flip($inactivePermissionNames);

        $filterActive = static function (array $perms) use ($inactiveSet): array {
            return array_filter(
                $perms,
                static fn ($name) => !isset($inactiveSet[$name]),
                ARRAY_FILTER_USE_KEY
            );
        };

        $roles = $auth->getRolesByUser($userId);
        ksort($roles);

        $rolePermissions = [];
        foreach ($roles as $roleName => $role) {
            $perms = $auth->getPermissionsByRole($roleName);
            $perms = $filterActive($perms);
            ksort($perms);
            $rolePermissions[$roleName] = $perms;
        }

        $allAssignments = $auth->getAssignments($userId);
        $directPermissions = [];
        foreach ($allAssignments as $itemName => $assignment) {
            if (isset($inactiveSet[$itemName])) {
                continue;
            }
            $item = $auth->getPermission($itemName);
            if ($item !== null) {
                $directPermissions[$itemName] = $item;
            }
        }
        ksort($directPermissions);

        return $this->render('my-permissions', [
            'roles' => $roles,
            'rolePermissions' => $rolePermissions,
            'directPermissions' => $directPermissions,
        ]);
    }
}
