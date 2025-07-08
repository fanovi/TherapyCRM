<?php

namespace frontend\controllers;

use frontend\models\ResendVerificationEmailForm;
use frontend\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use frontend\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use common\models\Patient;
use common\models\Therapist;
use common\models\Appointment;
use common\models\DocumentRequest;
use common\models\TherapeuticPlan;
use common\models\Notification;
use common\models\User;

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
                'roles' => ['?', '@'], // ? = guest, @ = authenticated
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
        if ($action->id === 'error' || $action->id === 'login') {
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
            ->where(['>', 'appointment_datetime', date('Y-m-d H:i:s')])
            ->andWhere(['status' => Appointment::STATUS_SCHEDULED])
            ->limit(5)
            ->orderBy(['appointment_datetime' => SORT_ASC])
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

        // Piani terapeutici attivi
        $activeTherapeuticPlans = TherapeuticPlan::find()
            ->where(['status' => 'active'])
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
        
        // Se non ci sono dati nel database, creo dati di esempio realistici
        if (empty($documentRequestsByStatus)) {
            $requestsData = [
                ['status_name' => 'Inviata', 'count' => 12],
                ['status_name' => 'Presa in carico', 'count' => 8],
                ['status_name' => 'Stampato', 'count' => 5],
                ['status_name' => 'Consegnato', 'count' => 25],
            ];
        } else {
            // Usa dati reali e filtra quelli con count = 0
            foreach ($documentRequestsByStatus as $item) {
                $count = (int)$item['count'];
                if ($count > 0) {  // Solo stati con richieste effettive
                    $requestsData[] = [
                        'status_name' => $statusLabels[$item['status']] ?? 'Sconosciuto',
                        'count' => $count
                    ];
                }
            }
            
            // Se tutti gli stati hanno 0 richieste, mostra almeno qualcosa di rappresentativo
            if (empty($requestsData)) {
                $totalRequests = DocumentRequest::find()->count();
                if ($totalRequests == 0) {
                    // Database completamente vuoto - usa dati di esempio
                    $requestsData = [
                        ['status_name' => 'Inviata', 'count' => 8],
                        ['status_name' => 'Presa in carico', 'count' => 5],
                        ['status_name' => 'Stampato', 'count' => 3],
                        ['status_name' => 'Consegnato', 'count' => 15],
                    ];
                } else {
                    // Ci sono richieste ma tutti con stato 0? Mostra comunque qualcosa
                    $requestsData = [
                        ['status_name' => 'Richieste Presenti', 'count' => $totalRequests],
                    ];
                }
            }
        }

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
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            }

            Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
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

    //TODO da rimuovere
    public function actionForm()
    {
        return $this->render('form');
    }
}
