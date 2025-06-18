<?php

namespace frontend\controllers;

use yii\web\Controller;
use yii\filters\AccessControl;

/**
 * Base controller per il frontend con autenticazione
 */
class BaseController extends Controller
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
                        // Pagine pubbliche (da sovrascrivere nei controller figli se necessario)
                        'actions' => ['error'],
                        'allow' => true,
                        'roles' => ['?', '@'], // ? = guest, @ = authenticated
                    ],
                    [
                        // Tutte le altre azioni solo per utenti autenticati
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }
} 