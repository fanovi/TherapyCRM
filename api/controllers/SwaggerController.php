<?php

namespace api\controllers;

use yii\web\Controller;
use yii\web\Response;
use Yii;

/**
 * Controller per la documentazione Swagger
 */
class SwaggerController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::class,
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => false,
                ],
            ],
        ];
    }

    /**
     * Genera il file JSON di Swagger
     * @return Response
     */
    public function actionJson()
    {
        $swagger = \OpenApi\Generator::scan([
            Yii::getAlias('@api/controllers/AuthController.php')
        ], ['validate' => false]);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->headers->set('Access-Control-Allow-Origin', '*');
        
        return json_decode($swagger->toJson(), true);
    }

    /**
     * Mostra l'interfaccia Swagger UI
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = false;
        
        // Forza il formato HTML per questa action
        Yii::$app->response->format = Response::FORMAT_HTML;
        Yii::$app->response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        
        return $this->render('index');
    }
} 