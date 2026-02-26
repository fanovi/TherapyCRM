<?php

namespace backend\controllers;

use Yii;
use common\models\SystemSetting;
use common\models\UserTwoFactorAuth;
use common\services\TwoFactorService;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * SystemSettingController gestisce le impostazioni di sistema nel backend
 */
class SystemSettingController extends Controller
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
                        'roles' => ['@'], // Solo utenti autenticati nel backend
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'update-two-factor' => ['POST'],
                    'disable-user-2fa' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Pagina impostazioni 2FA
     */
    public function actionTwoFactor()
    {
        $settings = SystemSetting::getByCategory('2fa');

        return $this->render('two-factor', [
            'settings' => $settings,
        ]);
    }

    /**
     * Salva impostazioni 2FA
     */
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

        // Svuota cache dopo aggiornamento
        SystemSetting::clearCache();

        Yii::$app->session->setFlash('success', 'Impostazioni 2FA aggiornate con successo.');

        return $this->redirect(['two-factor']);
    }

    /**
     * Disabilita 2FA per un utente specifico
     */
    public function actionDisableUser2fa($userId)
    {
        $tfaService = new TwoFactorService();
        $tfaService->disableForUser($userId);

        Yii::$app->session->setFlash('success', '2FA disabilitato per l\'utente.');

        return $this->redirect(Yii::$app->request->referrer ?: ['two-factor']);
    }
}
