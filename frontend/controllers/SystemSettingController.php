<?php

namespace frontend\controllers;

use Yii;
use common\models\SystemSetting;

/**
 * SystemSettingController gestisce le impostazioni di sistema
 */
class SystemSettingController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Sovrascrive access control per richiedere permesso admin
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['create_admin'], // Solo admin
                ],
            ],
        ];

        return $behaviors;
    }
}
