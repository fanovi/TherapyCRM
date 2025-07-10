<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

class CalendarController extends Controller
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
                        'roles' => ['@'], // Solo utenti autenticati
                    ],
                ],
            ],
        ];
    }

    /**
     * Renderizza l'app calendario
     * 
     * @param int|null $id_patient ID del paziente
     * @param int|null $id_therapist ID del terapista
     * @return string
     */
    public function actionIndex($id_patient = null, $id_therapist = null)
    {
        // Validazione: solo uno dei due parametri può essere presente
        if ($id_patient && $id_therapist) {
            throw new \yii\web\BadRequestHttpException('Non è possibile specificare sia id_patient che id_therapist');
        }

        return $this->render('index', [
            'idPatient' => $id_patient,
            'idTherapist' => $id_therapist,
        ]);
    }
} 