<?php

namespace api\controllers;

use common\models\AccountPatient;
use Yii;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;
use common\models\Complaint;
use yii\db\Expression;

/**
 * @OA\Tag(
 *     name="Reclami",
 *     description="Operazioni relative ai reclami dei pazienti"
 * )
 */
class ComplaintController extends Controller
{
    /**
     * Configura i behavior del controller
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Solo autenticazione JWT necessaria - tutto il resto è configurato globalmente:
        // - CORS: gestito in api/web/.htaccess
        // - JSON format: configurato in api/config/main.php
        $behaviors['jwtAuth'] = [
            'class' => \common\components\JwtAuthBehavior::class,
            'excludeActions' => [], // Tutte le azioni richiedono autenticazione
        ];
        
        return $behaviors;
    }

    /**
     * Gestisce le azioni consentite
     */
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    /**
     * @OA\Post(
     *     path="/complaints",
     *     summary="Crea un nuovo reclamo",
     *     description="Crea un nuovo reclamo per il paziente specificato. È possibile creare un solo reclamo per paziente al giorno.",
     *     operationId="createComplaint",
     *     tags={"Reclami"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dati del reclamo",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"patient_id", "title", "description"},
     *                 @OA\Property(
     *                     property="patient_id",
     *                     type="integer",
     *                     description="ID del paziente per cui creare il reclamo",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="title",
     *                     type="string",
     *                     maxLength=255,
     *                     description="Titolo del reclamo",
     *                     example="Problema con appuntamento"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     description="Descrizione dettagliata del reclamo",
     *                     example="L'appuntamento del giorno 10/01 è stato cancellato senza preavviso"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reclamo creato con successo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reclamo creato con successo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Errore di validazione o reclamo già presente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Dettagli degli errori di validazione"
     *             ),
     *             @OA\Property(property="message", type="string", example="Errori di validazione dei campi obbligatori")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Paziente non accessibile o reclamo già creato oggi",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="data", type="object"),
     *                     @OA\Property(property="message", type="string", example="Paziente non trovato o non accessibile")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=false),
     *                     @OA\Property(property="data", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="message", type="string", example="Oggi è stato già creato un reclamo per il paziente")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorizzato",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Token non valido"),
     *             @OA\Property(property="code", type="string", example="UNAUTHORIZED")
     *         )
     *     ),
     *     @OA\Response(
     *         response=405,
     *         description="Metodo non permesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="data", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="message", type="string", example="Metodo non permesso")
     *         )
     *     )
     * )
     *
     * Crea un nuovo reclamo
     * POST /complaints
     * 
     * Questo endpoint permette agli utenti autenticati di creare nuovi reclami
     * per i pazienti a cui hanno accesso.
     * 
     * Headers richiesti:
     * - Authorization: Bearer {jwt_token}
     * - Content-Type: application/json
     * 
     * Vincoli:
     * - L'utente deve avere accesso al paziente (relazione AccountPatient)
     * - È possibile creare un solo reclamo per paziente al giorno
     */
    public function actionCreate()
    {
        try {

            if(Yii::$app->request->isGet) {
                return [
                    'success' => false,
                    'data' => [],
                    'message' => 'Metodo non permesso'
                ];
            }

            // Verifica che l'utente sia autenticato
            $currentUser = Yii::$app->user->identity;
            if (!$currentUser) {
                throw new UnauthorizedHttpException('Utente non autenticato');
            }

            // Ottieni i dati della richiesta (JSON automaticamente parsato dal main.php)
            $request = Yii::$app->request;
            $data = $request->getBodyParams();

            $complaint = new Complaint();
            $complaint->account_id = $currentUser->id;
            $complaint->patient_id = $data['patient_id'];
            $complaint->title = $data['title'];
            $complaint->description = $data['description'];
            $complaint->created_at = date('Y-m-d H:i:s');

            if(!AccountPatient::find()->where(['user_id' => $complaint->account_id, 'patient_id' => $complaint->patient_id])->one()) {
                return [
                    'success' => false,
                    'data' => $complaint->errors,
                    'message' => 'Paziente non trovato o non accessibile'
                ];
            }

            if(Complaint::find()->where(['account_id' => $complaint->account_id, 'patient_id' => $complaint->patient_id])->andWhere(new Expression('DATE(created_at) = CURDATE()'))->one()) {
                return [
                    'success' => false,
                    'data' => [],
                    'message' => 'Oggi è stato già creato un reclamo per il paziente'
                ];
            }

            if(!$complaint->validate()) {
                return [
                    'success' => false,
                    'data' => $complaint->errors,
                    'message' => 'Errori di validazione dei campi obbligatori'
                ];
            }

            if(!$complaint->save()) {
                return [
                    'success' => false,
                    'data' => $complaint->errors,
                    'message' => 'Errore nel salvare il reclamo'
                ];
            }

            return [
                'success' => true,
                'data' => $complaint->validate(),
                'message' => 'Reclamo creato con successo'
            ];

        } catch (UnauthorizedHttpException $e) {
            return $this->formatErrorResponse('UNAUTHORIZED', $e->getMessage(), [], 401);

        } catch (\Exception $e) {
            // Gestisci errori specifici di accesso e permessi
            $message = $e->getMessage();
            
            // Errori di accesso paziente
            if (strpos($message, 'Non hai i permessi per fare richieste per questo paziente') !== false) {
                return $this->formatErrorResponse('ACCESS_DENIED', $message, [], 403);
            }
            
            // Errori di accesso paziente con lista accessibili
            if (strpos($message, 'Pazienti accessibili:') !== false) {
                return $this->formatErrorResponse('ACCESS_DENIED', $message, [], 403);
            }
            
            // Errori AccountPatient non trovato
            if (strpos($message, 'AccountPatient non trovato') !== false || strpos($message, 'Nessun AccountPatient trovato') !== false) {
                return $this->formatErrorResponse('ACCESS_DENIED', 'Non hai accesso a nessun paziente. Contatta l\'amministratore.', [], 403);
            }
            
            // Errori paziente non trovato
            if (strpos($message, 'Paziente non trovato') !== false) {
                return $this->formatErrorResponse('NOT_FOUND', 'Paziente non trovato o non accessibile', [], 404);
            }

            // Log dell'errore per debugging
            Yii::error("Error in RequestsController::actionCreate: " . $message, __METHOD__);
            Yii::error("Stack trace: " . $e->getTraceAsString(), __METHOD__);

            return $this->formatErrorResponse('INTERNAL_ERROR', 'Errore interno del server', [], 500);
        }
    }

    /**
     * Formatta una risposta di errore secondo lo standard API
     */
    private function formatErrorResponse($errorCode, $message, $details = [], $statusCode = 400)
    {
        Yii::$app->response->statusCode = $statusCode;
        
        $response = [
            'success' => false,
            'error' => $message,
            'code' => $errorCode
        ];
        
        if (!empty($details)) {
            $response['details'] = $details;
        }
        
        return $response;
    }
} 