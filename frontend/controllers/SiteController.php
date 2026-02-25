<?php

namespace frontend\controllers;

use common\models\Appointment;
use common\models\DocumentRequest;
use common\models\Notification;
use common\models\Patient;
use common\models\TherapeuticPlan;
use common\models\Therapist;
use common\models\User;
use frontend\models\ChangePasswordForm;
use frontend\models\ContactForm;
use frontend\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResendVerificationEmailForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\VerifyEmailForm;
use yii\base\InvalidArgumentException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
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
        // Statistiche pazienti
        $totalPatients = Patient::find()->count();
        $newPatientsThisMonth = Patient::find()
            ->where(['>=', 'created_at', date('Y-m-01 00:00:00')])
            ->count();
        $lastMonthPatients = Patient::find()
            ->where(['between', 'created_at', date('Y-m-01 00:00:00', strtotime('-1 month')), date('Y-m-t 23:59:59', strtotime('-1 month'))])
            ->count();
        $patientsGrowthPercentage = $lastMonthPatients > 0 ? round((($newPatientsThisMonth - $lastMonthPatients) / $lastMonthPatients) * 100, 2) : 0;

        // Statistiche terapisti
        $totalTherapists = Therapist::find()->where(['is_active' => 1])->count();
        $newTherapistsThisMonth = Therapist::find()
            ->where(['is_active' => 1])
            ->andWhere(['>=', 'created_at', date('Y-m-01 00:00:00')])
            ->count();

        // Statistiche appuntamenti
        $totalAppointmentsToday = Appointment::find()
            ->where(['between', 'appointment_datetime', date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
            ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
            ->count();

        $completedAppointmentsToday = Appointment::find()
            ->where(['between', 'appointment_datetime', date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
            ->andWhere(['status' => Appointment::STATUS_COMPLETED])
            ->count();

        $upcomingAppointments = Appointment::find()
            ->alias('a')
            ->innerJoin('plan_therapies pt', 'pt.id = a.plan_therapy_id')
            ->innerJoin('therapeutic_plans tp', 'tp.id = pt.therapeutic_plan_id')
            ->innerJoin('patients p', 'p.id = tp.patient_id')
            ->innerJoin('therapists t', 't.id = a.therapist_id')
            ->innerJoin('users u', 'u.id = t.user_id')
            ->innerJoin('user_profiles up', 'up.user_id = u.id')
            ->where(['>', 'a.appointment_datetime', date('Y-m-d H:i:s')])
            ->andWhere(['a.status' => Appointment::STATUS_SCHEDULED])
            ->andWhere(['is not', 'p.first_name', null])
            ->andWhere(['is not', 'p.last_name', null])
            ->andWhere(['is not', 'up.first_name', null])
            ->andWhere(['is not', 'up.last_name', null])
            ->limit(5)
            ->orderBy(['a.appointment_datetime' => SORT_ASC])
            ->with(['patient', 'therapist.user.profile'])
            ->all();

        // Statistiche richieste documenti
        $pendingDocumentRequests = DocumentRequest::find()
            ->where(['in', 'status', [DocumentRequest::STATUS_INVIATA, DocumentRequest::STATUS_PRESA_IN_CARICO]])
            ->count();

        $completedDocumentRequestsThisMonth = DocumentRequest::find()
            ->where(['status' => DocumentRequest::STATUS_CONSEGNATO])
            ->andWhere(['>=', 'created_at', date('Y-m-01 00:00:00')])
            ->count();

        $lastMonthCompletedRequests = DocumentRequest::find()
            ->where(['status' => DocumentRequest::STATUS_CONSEGNATO])
            ->andWhere(['between', 'created_at', date('Y-m-01 00:00:00', strtotime('-1 month')), date('Y-m-t 23:59:59', strtotime('-1 month'))])
            ->count();

        $requestsGrowthPercentage = $lastMonthCompletedRequests > 0 ? round((($completedDocumentRequestsThisMonth - $lastMonthCompletedRequests) / $lastMonthCompletedRequests) * 100, 2) : 0;

        // Piani terapeutici attivi (con end_date >= oggi)
        $activeTherapeuticPlans = TherapeuticPlan::find()
            ->where(['>=', 'end_date', date('Y-m-d')])
            ->count();

        // Notifiche non lette dell'utente
        $unreadNotifications = 0;
        if (!Yii::$app->user->isGuest) {
            $unreadNotifications = Notification::find()
                ->where(['recipient_user_id' => Yii::$app->user->id])
                ->andWhere(['read_at' => null])
                ->count();
        }

        // Appuntamenti degli ultimi 7 giorni (più realistico)
        $dailyAppointments = [];
        $dayLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = Appointment::find()
                ->where(['between', 'appointment_datetime', "$date 00:00:00", "$date 23:59:59"])
                ->andWhere(['!=', 'status', Appointment::STATUS_CANCELLED])
                ->count();
            $dailyAppointments[] = $count;
            $dayLabels[] = date('d/m', strtotime($date));
        }

        // Richieste documenti per stato con dati realistici
        $documentRequestsByStatus = DocumentRequest::find()
            ->select(['status', 'count(*) as count'])
            ->groupBy(['status'])
            ->asArray()
            ->all();

        // Converto gli ID stato in nomi leggibili e filtro stati con 0 richieste
        $statusLabels = DocumentRequest::getStatusLabels();
        $requestsData = [];
        $hasRealData = false;

        // Usa solo dati reali dal database
        if (!empty($documentRequestsByStatus)) {
            foreach ($documentRequestsByStatus as $item) {
                $count = (int) $item['count'];
                if ($count > 0) {  // Solo stati con richieste effettive
                    $requestsData[] = [
                        'status_name' => $statusLabels[$item['status']] ?? 'Sconosciuto',
                        'count' => $count
                    ];
                    $hasRealData = true;
                }
            }
        }

        // Se non ci sono dati reali, il grafico non verrà mostrato
        // La vista controllerà se $requestsData è vuoto per nascondere il componente

        return $this->render('index', [
            'totalPatients' => $totalPatients,
            'newPatientsThisMonth' => $newPatientsThisMonth,
            'patientsGrowthPercentage' => $patientsGrowthPercentage,
            'totalTherapists' => $totalTherapists,
            'newTherapistsThisMonth' => $newTherapistsThisMonth,
            'totalAppointmentsToday' => $totalAppointmentsToday,
            'completedAppointmentsToday' => $completedAppointmentsToday,
            'upcomingAppointments' => $upcomingAppointments,
            'pendingDocumentRequests' => $pendingDocumentRequests,
            'completedDocumentRequestsThisMonth' => $completedDocumentRequestsThisMonth,
            'requestsGrowthPercentage' => $requestsGrowthPercentage,
            'activeTherapeuticPlans' => $activeTherapeuticPlans,
            'unreadNotifications' => $unreadNotifications,
            'dailyAppointments' => $dailyAppointments,
            'dayLabels' => $dayLabels,
            'requestsData' => $requestsData,
            'hasRealRequestsData' => $hasRealData,
        ]);
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
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Controlla la tua email per le istruzioni successive.');

                return $this->goHome();
            }

            Yii::$app->session->setFlash('error', "Spiacenti, non riusciamo a resettare la password per l'indirizzo email fornito.");
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
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

        // Determina il ruolo dell'utente
        $auth = Yii::$app->authManager;
        $userRoles = $auth->getRolesByUser($user->id);
        $userRoleNames = array_keys($userRoles);

        // Determina se l'utente è admin/manager (non deve vedere il token per app mobile)
        $isAdminOrManager = false;
        foreach ($userRoleNames as $roleName) {
            if (in_array($roleName, ['admin', 'manager'])) {
                $isAdminOrManager = true;
                break;
            }
        }

        // Crea il form di reset password
        $model = new ResetPasswordForm($token);

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            return $this->render('resetPassword', [
                'model' => $model,
                'token' => $token,
                'success' => true,
                'isAdminOrManager' => $isAdminOrManager
            ]);
        }

        return $this->render('resetPassword', [
            'model' => $model,
            'token' => $token,
            'success' => false,
            'isAdminOrManager' => $isAdminOrManager
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
}
