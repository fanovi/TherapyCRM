<?php

namespace backend\controllers;

use Yii;
use common\models\SystemSetting;
use common\models\User;
use common\models\UserTwoFactorAuth;
use common\models\Therapist;
use common\models\AccountPatient;
use common\services\TwoFactorService;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class SystemSettingController extends Controller
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
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'update-two-factor' => ['POST'],
                    'enable-user-2fa' => ['POST'],
                    'disable-user-2fa' => ['POST'],
                    'enable-all-2fa' => ['POST'],
                    'update-calendar' => ['POST'],
                ],
            ],
        ];
    }

    public function actionTwoFactor()
    {
        $settings = SystemSetting::getByCategory('2fa');

        // Prendi solo gli utenti che usano l'app mobile (terapisti + account pazienti)
        $therapistUserIds = Therapist::find()->select('user_id')->column();
        $patientUserIds = AccountPatient::find()->select('user_id')->distinct()->column();
        $appUserIds = array_unique(array_merge($therapistUserIds, $patientUserIds));

        $users = [];
        if (!empty($appUserIds)) {
            $users = User::find()
                ->where(['id' => $appUserIds, 'status' => User::STATUS_ACTIVE])
                ->with(['profile', 'twoFactorAuth'])
                ->orderBy(['id' => SORT_ASC])
                ->all();
        }

        // Mappa per sapere il tipo di ogni utente
        $userTypes = [];
        foreach ($therapistUserIds as $uid) {
            $userTypes[$uid] = 'Terapista';
        }
        foreach ($patientUserIds as $uid) {
            if (!isset($userTypes[$uid])) {
                $userTypes[$uid] = 'Paziente';
            }
        }

        return $this->render('two-factor', [
            'settings' => $settings,
            'users' => $users,
            'userTypes' => $userTypes,
        ]);
    }

    public function actionUpdateTwoFactor()
    {
        $request = Yii::$app->request;

        $fields = [
            'enabled' => 'boolean',
            'remember_device_enabled' => 'boolean',
            'remember_device_days' => 'integer',
            'email_otp_expiry_seconds' => 'integer',
            'max_otp_attempts' => 'integer',
        ];

        foreach ($fields as $key => $type) {
            $value = $request->post($key);
            if ($type === 'boolean') {
                $value = $value ? '1' : '0';
            }
            if ($value !== null) {
                SystemSetting::setValue('2fa', $key, $value);
            }
        }

        SystemSetting::clearCache();
        Yii::$app->session->setFlash('success', 'Impostazioni 2FA aggiornate con successo.');
        return $this->redirect(['two-factor']);
    }

    public function actionEnableUser2fa($userId)
    {
        $user = User::findOne($userId);
        if (!$user) {
            throw new NotFoundHttpException('Utente non trovato.');
        }

        $tfaService = new TwoFactorService();
        $tfaService->enableForUser($userId, 'email');

        $name = $user->profile ? $user->profile->getFullName() : $user->email;
        Yii::$app->session->setFlash('success', "2FA abilitato per {$name}.");
        return $this->redirect(['two-factor']);
    }

    public function actionDisableUser2fa($userId)
    {
        $user = User::findOne($userId);
        if (!$user) {
            throw new NotFoundHttpException('Utente non trovato.');
        }

        $tfaService = new TwoFactorService();
        $tfaService->disableForUser($userId);

        $name = $user->profile ? $user->profile->getFullName() : $user->email;
        Yii::$app->session->setFlash('success', "2FA disabilitato per {$name}.");
        return $this->redirect(['two-factor']);
    }

    public function actionEnableAll2fa()
    {
        // Solo utenti app mobile (terapisti + account pazienti)
        $therapistUserIds = Therapist::find()->select('user_id')->column();
        $patientUserIds = AccountPatient::find()->select('user_id')->distinct()->column();
        $appUserIds = array_unique(array_merge($therapistUserIds, $patientUserIds));

        $users = User::find()
            ->where(['id' => $appUserIds, 'status' => User::STATUS_ACTIVE])
            ->all();

        $tfaService = new TwoFactorService();
        $count = 0;

        foreach ($users as $user) {
            $tfa = UserTwoFactorAuth::findOne(['user_id' => $user->id]);
            if (!$tfa || !$tfa->is_enabled) {
                $tfaService->enableForUser($user->id, 'email');
                $count++;
            }
        }

        Yii::$app->session->setFlash('success', "2FA abilitato per {$count} utenti.");
        return $this->redirect(['two-factor']);
    }

    public function actionCalendar()
    {
        $settings = SystemSetting::getByCategory('calendar');
        $dateLimits = SystemSetting::getPatientCalendarDateLimits();

        return $this->render('calendar', [
            'settings' => $settings,
            'dateLimits' => $dateLimits,
        ]);
    }

    public function actionUpdateCalendar()
    {
        $request = Yii::$app->request;

        $fields = [
            'patient_past_range_value' => 'integer',
            'patient_past_range_unit' => 'string',
            'patient_future_range_value' => 'integer',
            'patient_future_range_unit' => 'string',
        ];

        foreach ($fields as $key => $type) {
            $value = $request->post($key);
            if ($value !== null) {
                SystemSetting::setValue('calendar', $key, $value);
            }
        }

        SystemSetting::clearCache();
        Yii::$app->session->setFlash('success', 'Impostazioni calendario aggiornate con successo.');
        return $this->redirect(['calendar']);
    }
}
