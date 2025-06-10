<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;

class SiteController extends Controller
{
    public function actionIndex()
    {
        $data = Yii::$app->request->post();
        return[
            'success' => true,
            'message' => 'API TherapyCRM',
            'data' => $data
        ];
    }
}