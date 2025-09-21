<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;

class TestController extends Controller
{
    public function actionIndex()
    {
        print_r(Yii::$app->codiceFiscaleGenerator->generaCodiceFiscale('Fasano', 'Vito', '1987-10-28', 'M', 'Eboli'));
        exit;
    }
}