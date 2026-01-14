<?php

namespace api\controllers;

use common\models\AccountPatient;
use Yii;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;
use yii\web\BadRequestHttpException;
use common\models\Complaint;

/**
 * @OA\Info(
 *     title="TherapyCRM API - Richieste Pazienti",
 *     version="2.0.0",
 *     description="API per la gestione delle richieste di documenti dei pazienti nel sistema TherapyCRM",
 *     @OA\Contact(
 *         email="support@therapycrm.com",
 *         name="TherapyCRM Support"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Inserisci il token JWT ottenuto dal login. Formato: Bearer {token}"
 * )
 *
 * @OA\Tag(
 *     name="Autenticazione",
 *     description="Operazioni di autenticazione e gestione token"
 * )
 * 
 * @OA\Tag(
 *     name="Richieste",
 *     description="Operazioni relative alle richieste dei pazienti"
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
     *     path="/requests",
     *     summary="Crea una nuova richiesta documento",
     *     description="Crea una nuova richiesta di documento per il paziente specificato. La validazione è dinamica basata sui requisiti del tipo di richiesta selezionato.",
     *     operationId="createRequest",
     *     tags={"Richieste"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dati della richiesta documento",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"request_type_id", "patient_id"},
     *                 @OA\Property(
     *                     property="request_type_id",
     *                     type="integer",
     *                     description="ID della tipologia di richiesta",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="patient_id",
     *                     type="integer",
     *                     description="ID del paziente per cui fare la richiesta",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="therapeutic_plan_id",
     *                     type="integer",
     *                     nullable=true,
     *                     description="ID del piano terapeutico associato (obbligatorio se therapeutic_plan_rule = PLAN_REQUIRED, non ammesso se PLAN_NOT_ALLOWED)",
     *                     example=null
     *                 ),
     *                 @OA\Property(
     *                     property="therapy_id",
     *                     type="integer",
     *                     nullable=true,
     *                     description="ID della terapia associata (obbligatorio se require_therapy_assignment = true)",
     *                     example=null
     *                 ),
     *                 @OA\Property(
     *                     property="reason",
     *                     type="string",
     *                     maxLength=1000,
     *                     nullable=true,
     *                     description="Motivo della richiesta (obbligatorio se requires_reason = true)",
     *                     example="Certificato per assenza lavorativa dal 15/01 al 20/01"
     *                 ),
     *                 @OA\Property(
     *                     property="notes",
     *                     type="string",
     *                     maxLength=2000,
     *                     nullable=true,
     *                     description="Note aggiuntive (obbligatorie se require_notes = true)",
     *                     example="Note aggiuntive opzionali"
     *                 ),
     *                 @OA\Property(
     *                     property="date_from",
     *                     type="string",
     *                     format="date",
     *                     nullable=true,
     *                     description="Data di inizio (obbligatoria se requires_date_range = true)",
     *                     example="2025-01-15"
     *                 ),
     *                 @OA\Property(
     *                     property="date_to",
     *                     type="string",
     *                     format="date",
     *                     nullable=true,
     *                     description="Data di fine (obbligatoria se requires_date_range = true)",
     *                     example="2025-01-20"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Richiesta creata con successo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="patient_id", type="integer", example=1),
     *                 @OA\Property(property="request_type_id", type="integer", example=1),
     *                 @OA\Property(property="request_type", type="string", example="Certificato Medico"),
     *                 @OA\Property(property="therapeutic_plan_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="therapy_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="status_label", type="string", example="In Attesa"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-25T10:30:00Z"),
     *                 @OA\Property(property="estimated_completion", type="string", format="date-time", example="2025-01-28T18:00:00Z"),
     *                 @OA\Property(property="reason", type="string", nullable=true, example="Certificato per assenza lavorativa dal 15/01 al 20/01"),
     *                 @OA\Property(property="notes", type="string", nullable=true, example="Note aggiuntive opzionali"),
     *                 @OA\Property(property="date_from", type="string", format="date", nullable=true, example="2025-01-15"),
     *                 @OA\Property(property="date_to", type="string", format="date", nullable=true, example="2025-01-20"),
     *                 @OA\Property(
     *                     property="created_by",
     *                     type="object",
     *                     description="Dati dell'account che ha creato la richiesta",
     *                     @OA\Property(property="id", type="integer", example=789, description="ID dell'AccountPatient"),
     *                     @OA\Property(property="user_id", type="integer", example=456, description="ID dell'utente"),
     *                     @OA\Property(property="first_name", type="string", example="Mario", description="Nome dell'utente"),
     *                     @OA\Property(property="last_name", type="string", example="Rossi", description="Cognome dell'utente"),
     *                     @OA\Property(property="relationship_type", type="string", enum={"self", "parent", "tutor", "other"}, example="parent", description="Tipo di relazione con il paziente")
     *                 ),
     *                 @OA\Property(property="can_be_cancelled", type="boolean", example=true)
     *             ),
     *             @OA\Property(property="message", type="string", example="Richiesta creata con successo! Riceverai una notifica quando sarà pronta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Errore di validazione",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Errori di validazione dei campi obbligatori"),
     *             @OA\Property(property="code", type="string", example="MISSING_REQUIRED_FIELD"),
     *             @OA\Property(
     *                 property="details",
     *                 type="object",
     *                 @OA\Property(property="patient_id", type="string", example="Il campo patient_id è obbligatorio"),
     *                 @OA\Property(property="request_type_id", type="string", example="Il campo request_type_id è obbligatorio"),
     *                 @OA\Property(property="therapeutic_plan_id", type="string", example="Il piano terapeutico è obbligatorio per questa tipologia"),
     *                 @OA\Property(property="therapy_id", type="string", example="L'assegnazione terapia è obbligatoria per questa tipologia"),
     *                 @OA\Property(property="reason", type="string", example="Il motivo è obbligatorio per questa tipologia"),
     *                 @OA\Property(property="notes", type="string", example="Le note sono obbligatorie per questa tipologia"),
     *                 @OA\Property(property="date_from", type="string", example="La data di inizio è obbligatoria")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accesso negato al paziente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Non hai i permessi per fare richieste per questo paziente"),
     *             @OA\Property(property="code", type="string", example="ACCESS_DENIED")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tipologia di richiesta non trovata",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Tipologia di richiesta non valida o non attiva"),
     *             @OA\Property(property="code", type="string", example="INVALID_REQUEST_TYPE"),
     *             @OA\Property(
     *                 property="details",
     *                 type="object",
     *                 @OA\Property(property="request_type_id", type="string", example="Tipologia con ID 999 non trovata")
     *             )
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
     *     )
     * )
     *
     * Crea una nuova richiesta documento
     * POST /requests
     * 
     * Questo endpoint permette ai pazienti autenticati di creare nuove richieste
     * di documenti. La validazione è dinamica basata sui requisiti della tipologia.
     * 
     * Headers richiesti:
     * - Authorization: Bearer {jwt_token}
     * - Content-Type: application/json
     * 
     * Validazione dinamica:
     * - therapeutic_plan_id: obbligatorio se therapeutic_plan_rule = PLAN_REQUIRED
     * - therapy_id: obbligatorio se require_therapy_assignment = true
     * - reason: obbligatorio solo se requires_reason = true
     * - notes: obbligatorio solo se require_notes = true
     * - date_from/date_to: obbligatorie solo se requires_date_range = true
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
                'message' => 'Complaint created successfully'
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