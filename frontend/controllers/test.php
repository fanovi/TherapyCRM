<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use common\models\AppointmentPattern;
use common\models\Appointment;
use common\models\PlanTherapy;
use common\models\TherapeuticPlan;
use common\models\Therapist;
use common\models\Patient;
use common\models\PrivateCycle;
use common\models\TreatmentType;
use DateTime;
use Exception;
use yii\filters\Cors;

/**
 * TherapeuticPlanManagerController gestisce la creazione di pattern e appuntamenti
 * per i piani terapeutici e appuntamenti privati
 */
class TherapeuticPlanManagerController extends Controller
{
    /**
     * {@inheritdoc}
     * TEMPORANEAMENTE DISABILITATO PER TESTING
     */
    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'corsFilter' => [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    'Access-Control-Expose-Headers' => ['Content-Disposition'],
                    'Access-Control-Request-Headers' => ['*'],
                ]
            ],
        ];
    }

    /**
     * Disabilita la validazione CSRF per questo controller (solo per testing)
     */
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    /**
     * Crea un pattern di appuntamenti e genera i relativi appuntamenti
     * 
     * @return array
     */
 

    /**
     * Crea un singolo appuntamento (da piano terapeutico)
     * 
     * @return array
     */
   

  

   

  

    /**
     * Ottiene i dati anagrafici di un paziente con tutte le terapie disponibili
     * Include sia piani terapeutici che possibilità di appuntamenti privati
     * 
     * @return array
     */
   

    /**
     * Ottiene gli appuntamenti di un terapista per un mese specifico
     * Include sia appuntamenti da piano terapeutico che privati
     * 
     * @return array
     */
  

   

  

  

    

   

   

    

  

  

   

   

   

  

    /**
     * Valida i campi obbligatori per la creazione del pattern
     * 
     * @param array $data
     * @throws BadRequestHttpException
     */
   

    // ... tutti gli altri metodi helper esistenti rimangono invariati ...
}