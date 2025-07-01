<?php

namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;
use yii\web\BadRequestHttpException;
use common\models\User;
use common\models\RequestType;
use common\models\DocumentRequest;
use common\models\DocumentRequestStatusHistory;
use common\models\AccountPatient;
use common\models\TherapeuticPlan;
use common\models\PlanTherapy;

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
class RequestsController extends Controller
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
     * @OA\Get(
     *     path="/requests/types",
     *     summary="Recupera tipologie di richieste",
     *     description="Restituisce l'elenco delle tipologie di richieste attive dal database per i pazienti autenticati",
     *     operationId="getRequestTypes",
     *     tags={"Richieste"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Token di accesso Bearer",
     *         @OA\Schema(
     *             type="string",
     *             example="Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tipologie di richieste recuperate con successo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Certificato Medico"),
     *                     @OA\Property(property="description", type="string", example="Richiesta certificato medico per assenza lavorativa"),
     *                     @OA\Property(property="category", type="string", enum={"medical", "therapy", "fitness", "appointment"}, example="medical"),
     *                     @OA\Property(property="estimated_days", type="integer", example=3),
     *                     @OA\Property(property="requires_reason", type="boolean", example=true),
     *                     @OA\Property(property="requires_date_range", type="boolean", example=true),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorizzato - Token mancante o non valido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Il token di autenticazione non è stato fornito."),
     *             @OA\Property(property="code", type="string", example="UNAUTHORIZED")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Errore interno del server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Errore interno del server"),
     *             @OA\Property(property="code", type="string", example="INTERNAL_ERROR")
     *         )
     *     )
     * )
     *
     * Recupera le tipologie di richieste disponibili per i pazienti
     * GET /requests/types
     * 
     * Questo endpoint restituisce l'elenco completo delle tipologie di richieste
     * che un paziente può fare al proprio centro terapeutico.
     * 
     * Headers richiesti:
     * - Authorization: Bearer {jwt_token}
     * 
     * Risposta:
     * - Lista di tipologie con metadati per la UI
     * - Informazioni su tempistiche e requisiti
     * - Categorizzazione per organizzazione UI
     */
    public function actionTypes()
    {
        try {
            // Verifica che l'utente sia autenticato (già gestito da JwtAuthBehavior)
            $currentUser = Yii::$app->user->identity;
            
            if (!$currentUser) {
                throw new UnauthorizedHttpException('Utente non autenticato');
            }
            
            // Registra l'accesso per audit
            Yii::info("Request types accessed by user ID: {$currentUser->id}", __METHOD__);
            
            // Recupera le tipologie di richieste dal database (una sola query)
            $requestTypes = RequestType::getForApi();
            
            // Se non ci sono tipologie, restituisci array vuoto
            if (empty($requestTypes)) {
                Yii::warning("No request types found in database", __METHOD__);
                return [
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'total' => 0,
                        'active_count' => 0,
                        'rules' => RequestType::getTherapeuticPlanRuleOptions()
                    ]
                ];
            }
            
            // Calcola statistiche utili dai dati recuperati
            $activeCount = 0;
            $ruleDistribution = [];
            
            foreach ($requestTypes as $type) {
                if ($type['is_active']) {
                    $activeCount++;
                }
                
                $rule = $type['therapeutic_plan_rule'];
                if (!isset($ruleDistribution[$rule])) {
                    $ruleDistribution[$rule] = 0;
                }
                $ruleDistribution[$rule]++;
            }
            
            // Risposta con dati dal database e meta informazioni utili
            return [
                'success' => true,
                'data' => $requestTypes,
                'meta' => [
                    'total' => count($requestTypes),
                    'active_count' => $activeCount,
                    'rule_distribution' => $ruleDistribution,
                    'rules' => RequestType::getTherapeuticPlanRuleOptions()
                ]
            ];
            
        } catch (UnauthorizedHttpException $e) {
            return $this->formatErrorResponse('UNAUTHORIZED', $e->getMessage(), [], 401);
            
        } catch (\Exception $e) {
            // Log dell'errore per debugging
            Yii::error("Error in RequestsController::actionTypes: " . $e->getMessage(), __METHOD__);
            Yii::error("Stack trace: " . $e->getTraceAsString(), __METHOD__);
            
            return $this->formatErrorResponse('INTERNAL_ERROR', 'Errore interno del server', [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/requests",
     *     summary="Recupera richieste del paziente",
     *     description="Restituisce l'elenco paginato delle richieste associate al paziente specificato con possibilità di filtrare per status",
     *     operationId="getPatientRequests",
     *     tags={"Richieste"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="patient_id",
     *         in="query",
     *         required=true,
     *         description="ID del paziente di cui recuperare le richieste",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Numero di pagina (default: 1)",
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Numero di elementi per pagina (default: 20, max: 100)",
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filtro per status della richiesta",
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "accepted", "processing", "ready", "delivered", "cancelled", "rejected"},
     *             example="pending"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Richieste recuperate con successo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="patient_id", type="integer", example=1),
     *                     @OA\Property(property="request_type", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Certificato Medico"),
     *                         @OA\Property(property="category", type="string", example="medical")
     *                     ),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="status_label", type="string", example="In Attesa"),
     *                     @OA\Property(property="reason", type="string", example="Certificato per assenza lavorativa"),
     *                     @OA\Property(property="notes", type="string", example="Note aggiuntive"),
     *                     @OA\Property(property="date_from", type="string", format="date", example="2025-01-15"),
     *                     @OA\Property(property="date_to", type="string", format="date", example="2025-01-20"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-25T10:30:00Z"),
     *                     @OA\Property(property="estimated_completion", type="string", format="date-time", example="2025-01-28T18:00:00Z"),
     *                     @OA\Property(property="created_by", type="object",
     *                         @OA\Property(property="id", type="integer", example=789),
     *                         @OA\Property(property="first_name", type="string", example="Mario"),
     *                         @OA\Property(property="last_name", type="string", example="Rossi"),
     *                         @OA\Property(property="relationship_type", type="string", example="parent")
     *                     ),
     *                     @OA\Property(property="can_be_cancelled", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="page", type="integer", example=1),
     *                 @OA\Property(property="limit", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=45),
     *                 @OA\Property(property="total_pages", type="integer", example=3),
     *                 @OA\Property(property="has_next_page", type="boolean", example=true),
     *                 @OA\Property(property="has_prev_page", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Errore di validazione parametri",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Parametri di richiesta non validi"),
     *             @OA\Property(property="code", type="string", example="MISSING_REQUIRED_FIELD"),
     *             @OA\Property(property="details", type="object",
     *                 @OA\Property(property="patient_id", type="string", example="Il parametro patient_id è obbligatorio")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accesso negato al paziente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Non hai i permessi per accedere alle richieste di questo paziente"),
     *             @OA\Property(property="code", type="string", example="ACCESS_DENIED")
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
     * Recupera le richieste associate al paziente specificato
     * GET /requests?patient_id=1&page=1&limit=20&status=pending
     * 
     * Questo endpoint restituisce l'elenco paginato delle richieste di documenti
     * associate al paziente specificato. L'utente deve avere accesso al paziente
     * tramite la relazione AccountPatient.
     * 
     * Parametri query:
     * - patient_id (obbligatorio): ID del paziente
     * - page (opzionale): Numero di pagina (default: 1)
     * - limit (opzionale): Elementi per pagina (default: 20, max: 100)
     * - status (opzionale): Filtro per status della richiesta
     * 
     * Headers richiesti:
     * - Authorization: Bearer {jwt_token}
     * 
     * Risposta:
     * - Lista paginata di richieste con dettagli completi
     * - Metadati di paginazione
     * - Informazioni su tipo di richiesta e chi l'ha creata
     */
    public function actionIndex()
    {
        try {
            // Verifica autenticazione
            $currentUser = Yii::$app->user->identity;
            if (!$currentUser) {
                throw new UnauthorizedHttpException('Utente non autenticato');
            }

            // Ottieni parametri query
            $request = Yii::$app->request;
            $patientId = $request->get('patient_id');
            $page = max(1, (int) $request->get('page', 1));
            $limit = max(1, min(100, (int) $request->get('limit', 20))); // Max 100 per performance
            $status = $request->get('status');

            // Validazione parametri
            $validationErrors = $this->validateIndexParameters($patientId, $status);
            if (!empty($validationErrors)) {
                $details = $this->formatValidationErrors($validationErrors);
                return $this->formatErrorResponse(
                    'MISSING_REQUIRED_FIELD', 
                    'Parametri di richiesta non validi', 
                    $details, 
                    400
                );
            }

            // Verifica accesso al paziente
            $accessCheck = $this->validatePatientAccess((int) $patientId, $currentUser);
            if (!$accessCheck['hasAccess']) {
                return $this->formatErrorResponse(
                    'ACCESS_DENIED', 
                    $accessCheck['message'], 
                    [], 
                    403
                );
            }

            // Log per audit
            Yii::info("Patient requests accessed by user {$currentUser->id} for patient {$patientId}, page {$page}, limit {$limit}, status: " . ($status ?: 'all'), __METHOD__);

            // Costruisci query per le richieste
            $query = DocumentRequest::find()
                ->with(['requestType', 'requestedByAccountPatient.user.profile'])
                ->where(['patient_id' => $patientId])
                ->orderBy(['created_at' => SORT_DESC]); // Più recenti prima

            // Applica filtro status se specificato
            if ($status) {
                $convertedStatus = $this->convertStatusValue($status);
                $query->andWhere(['status' => $convertedStatus]);
            }

            // Conta totale per paginazione
            $total = $query->count();

            // Applica paginazione
            $offset = ($page - 1) * $limit;
            $requests = $query->offset($offset)->limit($limit)->all();

            // Formatta i dati per la response
            $data = [];
            foreach ($requests as $request) {
                $data[] = $this->formatRequestForApi($request);
            }

            // Calcola metadati paginazione
            $totalPages = ceil($total / $limit);
            $hasNextPage = $page < $totalPages;
            $hasPrevPage = $page > 1;

            return [
                'success' => true,
                'data' => $data,
                'meta' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                    'has_next_page' => $hasNextPage,
                    'has_prev_page' => $hasPrevPage,
                    'status_filter' => $status,
                    'patient_id' => (int) $patientId
                ]
            ];

        } catch (UnauthorizedHttpException $e) {
            return $this->formatErrorResponse('UNAUTHORIZED', $e->getMessage(), [], 401);

        } catch (\Exception $e) {
            // Log dell'errore per debugging
            Yii::error("Error in RequestsController::actionIndex: " . $e->getMessage(), __METHOD__);
            Yii::error("Stack trace: " . $e->getTraceAsString(), __METHOD__);

            return $this->formatErrorResponse('INTERNAL_ERROR', 'Errore interno del server', [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/requests/{id}",
     *     summary="Recupera una singola richiesta",
     *     description="Recupera i dettagli completi di una richiesta specifica. L'utente deve avere accesso al paziente associato alla richiesta.",
     *     operationId="getRequest",
     *     tags={"Richieste"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID della richiesta da recuperare",
     *         @OA\Schema(type="integer", minimum=1, example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Richiesta recuperata con successo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="request_type", type="string", example="Certificato Medico"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-20T10:30:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-23T16:45:00Z"),
     *                 @OA\Property(property="estimated_completion", type="string", format="date-time", example="2025-01-25T18:00:00Z"),
     *                 @OA\Property(property="completed_at", type="string", format="date-time", example="2025-01-23T16:45:00Z"),
     *                 @OA\Property(property="reason", type="string", example="Certificato per assenza lavorativa dal 15/01 al 20/01"),
     *                 @OA\Property(property="notes", type="string", example="Richiesta urgente per datore di lavoro"),
     *                 @OA\Property(property="date_from", type="string", format="date", example="2025-01-15"),
     *                 @OA\Property(property="date_to", type="string", format="date", example="2025-01-20"),
     *                 @OA\Property(
     *                     property="type_info",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Certificato Medico"),
     *                     @OA\Property(property="category", type="string", example="medical"),
     *                     @OA\Property(property="estimated_days", type="integer", example=3)
     *                 ),
     *                 @OA\Property(
     *                     property="created_by",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=4),
     *                     @OA\Property(property="first_name", type="string", example="Anna"),
     *                     @OA\Property(property="last_name", type="string", example="Bianchi"),
     *                     @OA\Property(property="relationship_type", type="string", example="parent")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Richiesta non trovata",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Richiesta non trovata"),
     *             @OA\Property(property="code", type="string", example="NOT_FOUND")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accesso negato",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Non hai i permessi per accedere a questa richiesta"),
     *             @OA\Property(property="code", type="string", example="ACCESS_DENIED")
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
     * Recupera una singola richiesta con dettagli completi
     * GET /requests/{id}
     * 
     * Questo endpoint restituisce i dettagli completi di una richiesta specifica
     * includendo informazioni sul tipo di richiesta e chi l'ha creata.
     * L'utente deve avere accesso al paziente associato alla richiesta.
     * 
     * Parametri path:
     * - id (obbligatorio): ID della richiesta da recuperare
     * 
     * Headers richiesti:
     * - Authorization: Bearer {jwt_token}
     * 
     * Risposta:
     * - Dettagli completi della richiesta
     * - Informazioni sul tipo di richiesta (type_info)
     * - Informazioni su chi ha creato la richiesta (created_by)
     * - Tutti i timestamp in formato UTC ISO8601
     */
    public function actionView($id)
    {
        try {
            // Verifica autenticazione
            $currentUser = Yii::$app->user->identity;
            if (!$currentUser) {
                throw new UnauthorizedHttpException('Utente non autenticato');
            }

            // Validazione ID
            if (!is_numeric($id) || (int)$id <= 0) {
                return $this->formatErrorResponse(
                    'MISSING_REQUIRED_FIELD', 
                    'ID richiesta non valido', 
                    ['id' => 'L\'ID deve essere un numero intero positivo'], 
                    400
                );
            }

            // Trova la richiesta con relazioni
            $request = DocumentRequest::find()
                ->with(['requestType', 'requestedByAccountPatient.user.profile', 'patient'])
                ->where(['id' => $id])
                ->one();

            if (!$request) {
                return $this->formatErrorResponse(
                    'NOT_FOUND', 
                    'Richiesta non trovata', 
                    [], 
                    404
                );
            }

            // Verifica accesso al paziente associato alla richiesta
            $accessCheck = $this->validatePatientAccess($request->patient_id, $currentUser);
            if (!$accessCheck['hasAccess']) {
                return $this->formatErrorResponse(
                    'ACCESS_DENIED', 
                    'Non hai i permessi per accedere a questa richiesta. ' . $accessCheck['message'], 
                    [], 
                    403
                );
            }

            // Log per audit
            Yii::info("Request {$id} accessed by user {$currentUser->id} for patient {$request->patient_id}", __METHOD__);

            // Formatta la risposta secondo le specifiche
            $data = $this->formatSingleRequestForApi($request);

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (UnauthorizedHttpException $e) {
            return $this->formatErrorResponse('UNAUTHORIZED', $e->getMessage(), [], 401);

        } catch (\Exception $e) {
            // Log dell'errore per debugging
            Yii::error("Error in RequestsController::actionView: " . $e->getMessage(), __METHOD__);
            Yii::error("Stack trace: " . $e->getTraceAsString(), __METHOD__);
            
            return $this->formatErrorResponse(
                'INTERNAL_ERROR', 
                'Errore interno del server', 
                [], 
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/requests",
     *     summary="Crea una nuova richiesta documento",
     *     description="Crea una nuova richiesta di documento per il paziente autenticato con validazione dinamica basata sui requisiti del tipo di richiesta",
     *     operationId="createRequest",
     *     tags={"Richieste"},
     *     security={{"BearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dati della richiesta documento",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"request_type_id"},
     *                 @OA\Property(
     *                     property="request_type_id",
     *                     type="integer",
     *                     description="ID della tipologia di richiesta",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="reason",
     *                     type="string",
     *                     description="Motivo della richiesta (obbligatorio se requires_reason = true)",
     *                     example="Certificato per assenza lavorativa dal 15/01 al 20/01"
     *                 ),
     *                 @OA\Property(
     *                     property="notes",
     *                     type="string",
     *                     description="Note aggiuntive opzionali",
     *                     example="Note aggiuntive opzionali"
     *                 ),
     *                 @OA\Property(
     *                     property="date_from",
     *                     type="string",
     *                     format="date",
     *                     description="Data di inizio (obbligatoria se requires_date_range = true)",
     *                     example="2025-01-15"
     *                 ),
     *                 @OA\Property(
     *                     property="date_to",
     *                     type="string",
     *                     format="date",
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
     *                 @OA\Property(property="request_type", type="string", example="Certificato Medico"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-25T10:30:00Z"),
     *                 @OA\Property(property="estimated_completion", type="string", format="date-time", example="2025-01-28T18:00:00Z"),
     *                 @OA\Property(property="reason", type="string", example="Certificato per assenza lavorativa dal 15/01 al 20/01"),
     *                 @OA\Property(property="notes", type="string", example="Note aggiuntive opzionali"),
     *                 @OA\Property(property="date_from", type="string", format="date", example="2025-01-15"),
     *                 @OA\Property(property="date_to", type="string", format="date", example="2025-01-20"),
     *                 @OA\Property(
     *                     property="created_by",
     *                     type="object",
     *                     description="Dati dell'account che ha creato la richiesta",
     *                     @OA\Property(property="id", type="integer", example=789, description="ID dell'AccountPatient"),
     *                     @OA\Property(property="user_id", type="integer", example=456, description="ID dell'utente"),
     *                     @OA\Property(property="first_name", type="string", example="Mario", description="Nome dell'utente"),
     *                     @OA\Property(property="last_name", type="string", example="Rossi", description="Cognome dell'utente"),
     *                     @OA\Property(property="relationship_type", type="string", enum={"self", "parent", "tutor", "other"}, example="parent", description="Tipo di relazione con il paziente")
     *                 )
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
     *                 @OA\Property(property="reason", type="string", example="Il motivo è obbligatorio per questa tipologia"),
     *                 @OA\Property(property="date_from", type="string", example="La data di inizio è obbligatoria")
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
     *         response=500,
     *         description="Errore interno del server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Errore interno del server"),
     *             @OA\Property(property="code", type="string", example="INTERNAL_ERROR")
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
     * - reason: obbligatorio solo se requires_reason = true
     * - date_from/date_to: obbligatorie solo se requires_date_range = true
     * - notes: sempre opzionale
     */
    public function actionCreate()
    {
        try {
            // Verifica che l'utente sia autenticato
            $currentUser = Yii::$app->user->identity;
            if (!$currentUser) {
                throw new UnauthorizedHttpException('Utente non autenticato');
            }

            // Ottieni i dati della richiesta (JSON automaticamente parsato dal main.php)
            $request = Yii::$app->request;
            $data = $request->getBodyParams();

            // Registra la richiesta per audit
            Yii::info("Create request attempt by user ID: {$currentUser->id}", __METHOD__);
            Yii::info("Request data: " . json_encode($data), __METHOD__);

            // Validazione input base
            $validationErrors = $this->validateRequestData($data);
            if (!empty($validationErrors)) {
                $details = $this->formatValidationErrors($validationErrors);
                return $this->formatErrorResponse('MISSING_REQUIRED_FIELD', 'Errori di validazione dei campi obbligatori', $details, 400);
            }

            // Trova la tipologia di richiesta
            $requestType = $this->findRequestTypeById($data['request_type_id']);
            if (!$requestType) {
                return $this->formatErrorResponse(
                    'INVALID_REQUEST_TYPE', 
                    'Tipologia di richiesta non valida o non attiva', 
                    ['request_type_id' => "Tipologia con ID {$data['request_type_id']} non trovata"], 
                    404
                );
            }

            // Validazione dinamica basata sui requisiti del tipo
            $dynamicErrors = $this->validateRequestTypeRequirements($data, $requestType);
            if (!empty($dynamicErrors)) {
                $details = $this->formatValidationErrors($dynamicErrors);
                return $this->formatErrorResponse(
                    'MISSING_REQUIRED_FIELD', 
                    "Requisiti obbligatori per '{$requestType['name']}' non soddisfatti", 
                    $details, 
                    400
                );
            }

            // Salva la richiesta nel database (o restituisce quella esistente)
            $requestResult = $this->saveDocumentRequest($data, $requestType, $currentUser);

            // Determina status code e messaggio in base al risultato
            if (isset($requestResult['is_duplicate']) && $requestResult['is_duplicate']) {
                // Richiesta duplicata - restituisce quella esistente
                Yii::$app->response->statusCode = 200; // OK invece di 201 Created
                $message = $requestResult['duplicate_message'];
                Yii::info("Duplicate request returned for patient {$requestResult['patient_id']}, existing ID: {$requestResult['id']}", __METHOD__);
            } else {
                // Nuova richiesta creata
                Yii::$app->response->statusCode = 201; // Created
                $message = 'Richiesta creata con successo! Riceverai una notifica quando sarà pronta.';
                Yii::info("New request created successfully with ID: {$requestResult['id']}", __METHOD__);
            }

            return [
                'success' => true,
                'data' => $requestResult,
                'message' => $message
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
     * Trova una tipologia di richiesta per ID dal database
     */
    private function findRequestTypeById($typeId)
    {
        $requestType = RequestType::findActiveById($typeId);
        
        if (!$requestType) {
            return null;
        }
        
        // Converte il modello in array con i nuovi campi
        return [
            'id' => $requestType->id,
            'name' => $requestType->name,
            'therapeutic_plan_rule' => $requestType->therapeutic_plan_rule,
            'allow_multiple_requests' => (bool) $requestType->allow_multiple_requests,
            'require_therapy_assignment' => (bool) $requestType->require_therapy_assignment,
            'require_notes' => (bool) $requestType->require_notes,
            'is_active' => (bool) $requestType->is_active,
        ];
    }

    /**
     * Validazione input base per i nuovi campi
     */
    private function validateRequestData($data)
    {
        $errors = [];

        // request_type_id è obbligatorio
        if (empty($data['request_type_id'])) {
            $errors['request_type_id'][] = 'Il campo request_type_id è obbligatorio';
        } elseif (!is_numeric($data['request_type_id']) || (int)$data['request_type_id'] <= 0) {
            $errors['request_type_id'][] = 'Il campo request_type_id deve essere un numero intero positivo';
        }

        // patient_id è obbligatorio
        if (empty($data['patient_id'])) {
            $errors['patient_id'][] = 'Il campo patient_id è obbligatorio';
        } elseif (!is_numeric($data['patient_id']) || (int)$data['patient_id'] <= 0) {
            $errors['patient_id'][] = 'Il campo patient_id deve essere un numero intero positivo';
        }

        // Validazione therapeutic_plan_id se presente
        if (!empty($data['therapeutic_plan_id'])) {
            if (!is_numeric($data['therapeutic_plan_id']) || (int)$data['therapeutic_plan_id'] <= 0) {
                $errors['therapeutic_plan_id'][] = 'Il campo therapeutic_plan_id deve essere un numero intero positivo';
            }
        }

        // Validazione therapy_id se presente
        if (!empty($data['therapy_id'])) {
            if (!is_numeric($data['therapy_id']) || (int)$data['therapy_id'] <= 0) {
                $errors['therapy_id'][] = 'Il campo therapy_id deve essere un numero intero positivo';
            }
        }

        // Validazione lunghezza note
        if (!empty($data['notes']) && strlen($data['notes']) > 2000) {
            $errors['notes'][] = 'Le note non possono superare i 2000 caratteri';
        }

        return $errors;
    }

    /**
     * Validazione dinamica basata sui requisiti del tipo di richiesta
     */
    private function validateRequestTypeRequirements($data, $requestType)
    {
        $errors = [];

        // Verifica campo notes se richiesto
        if ($requestType['require_notes'] && empty($data['notes'])) {
            $errors['notes'][] = "Le note sono obbligatorie per la tipologia '{$requestType['name']}'";
        }

        // Verifica therapeutic_plan_id secondo la regola del tipo
        switch ($requestType['therapeutic_plan_rule']) {
            case RequestType::PLAN_REQUIRED:
                if (empty($data['therapeutic_plan_id'])) {
                    $errors['therapeutic_plan_id'][] = "Il piano terapeutico è obbligatorio per la tipologia '{$requestType['name']}'";
                }
                break;
                
            case RequestType::PLAN_NOT_ALLOWED:
                if (!empty($data['therapeutic_plan_id'])) {
                    $errors['therapeutic_plan_id'][] = "Il piano terapeutico non può essere associato per la tipologia '{$requestType['name']}'";
                }
                break;
                
            case RequestType::PLAN_OPTIONAL:
                // Nessuna validazione specifica - può essere presente o meno
                break;
        }

        // Verifica therapy_id se richiesto
        if ($requestType['require_therapy_assignment'] && empty($data['therapy_id'])) {
            $errors['therapy_id'][] = "L'assegnazione terapia è obbligatoria per la tipologia '{$requestType['name']}'";
        }

        // Se therapeutic_plan_id è specificato, verifica che esista e appartenga al paziente
        if (!empty($data['therapeutic_plan_id'])) {
            $plan = TherapeuticPlan::find()
                ->where(['id' => $data['therapeutic_plan_id'], 'patient_id' => $data['patient_id']])
                ->one();
                
            if (!$plan) {
                $errors['therapeutic_plan_id'][] = "Piano terapeutico non trovato o non appartiene al paziente specificato";
            }
        }

        // Se therapy_id è specificato, verifica che esista e sia del piano corretto
        if (!empty($data['therapy_id'])) {
            $therapyQuery = PlanTherapy::find()->where(['id' => $data['therapy_id']]);
            
            // Se abbiamo un therapeutic_plan_id, verifica che la terapia appartenga a quel piano
            if (!empty($data['therapeutic_plan_id'])) {
                $therapyQuery->andWhere(['therapeutic_plan_id' => $data['therapeutic_plan_id']]);
            }
            
            $therapy = $therapyQuery->one();
            
            if (!$therapy) {
                $errors['therapy_id'][] = "Terapia non trovata" . 
                    (!empty($data['therapeutic_plan_id']) ? " o non appartiene al piano terapeutico specificato" : "");
            }
        }

        return $errors;
    }

    /**
     * Salva la richiesta nel database utilizzando il modello DocumentRequest
     */
    private function saveDocumentRequest($data, $requestType, $currentUser)
    {
        // Determina il paziente per cui fare la richiesta
        $patientSelection = $this->determinePatientForRequest($data, $currentUser);
        $accountPatient = $patientSelection['accountPatient'];
        $patient = $patientSelection['patient'];

        // Controlla se esiste già una richiesta attiva per questo paziente e tipo
        $existingRequest = $this->checkDuplicateRequest($patient->id, $data['request_type_id'], $requestType);
        if ($existingRequest) {
            // Restituisce la richiesta esistente invece di crearne una nuova
            return $this->formatExistingRequestResponse($existingRequest, $requestType);
        }

        // Crea il nuovo DocumentRequest
        $documentRequest = new DocumentRequest();
        $documentRequest->patient_id = $patient->id;
        $documentRequest->request_type_id = $data['request_type_id'];
        $documentRequest->account_patient_id = $accountPatient->id;
        $documentRequest->therapeutic_plan_id = $data['therapeutic_plan_id'] ?? null;
        $documentRequest->therapy_id = $data['therapy_id'] ?? null;
        $documentRequest->notes = $data['notes'] ?? null;
        $documentRequest->status = DocumentRequest::STATUS_INVIATA;

        // Salva nel database
        if (!$documentRequest->save()) {
            $errors = $documentRequest->getFirstErrors();
            Yii::error("Error saving DocumentRequest: " . json_encode($errors), __METHOD__);
            throw new \Exception("Errore nel salvataggio della richiesta: " . implode(', ', $errors));
        }

        // Crea record iniziale nello storico stati
        DocumentRequestStatusHistory::createInitialEntry(
            $documentRequest->id,
            DocumentRequest::STATUS_INVIATA,
            $currentUser->id
        );

        // Log per audit
        Yii::info("DocumentRequest created successfully with ID: {$documentRequest->id}", __METHOD__);

        // Recupera i dati dell'account per la response
        $createdByData = $this->getCreatedByData($currentUser);

        // Restituisce i dati formattati per l'API
        return [
            'id' => $documentRequest->id,
            'patient_id' => $documentRequest->patient_id,
            'request_type_id' => $documentRequest->request_type_id,
            'request_type' => $requestType['name'],
            'therapeutic_plan_id' => $documentRequest->therapeutic_plan_id,
            'therapy_id' => $documentRequest->therapy_id,
            'status' => $documentRequest->status,
            'status_label' => $documentRequest->getStatusLabel(),
            'notes' => $documentRequest->notes,
            'created_at' => (new \DateTime($documentRequest->created_at, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            'created_by' => $createdByData,
            'can_be_cancelled' => $documentRequest->canBeCancelled(),
        ];
    }

    /**
     * Determina il paziente per cui fare la richiesta e verifica i permessi
     *
     * @param array $data
     * @param User $currentUser
     * @return array ['accountPatient' => AccountPatient, 'patient' => Patient]
     * @throws \Exception
     */
    private function determinePatientForRequest($data, $currentUser)
    {
        // patient_id è sempre obbligatorio (validato prima)
        $requestedPatientId = (int) $data['patient_id'];

        // Trova tutti gli AccountPatient per l'utente corrente
        $accountPatients = AccountPatient::find()
            ->with(['patient']) // Carica anche i pazienti associati
            ->where(['user_id' => $currentUser->id])
            ->all();

        if (empty($accountPatients)) {
            throw new \Exception("Non hai accesso a nessun paziente. Contatta l'amministratore.");
        }

        // Verifica che l'utente abbia accesso al paziente specificato
        $validAccountPatient = null;

        foreach ($accountPatients as $accountPatient) {
            if ($accountPatient->patient && $accountPatient->patient->id === $requestedPatientId) {
                $validAccountPatient = $accountPatient;
                break;
            }
        }

        if (!$validAccountPatient) {
            // Crea lista pazienti accessibili per messaggio informativo
            $accessiblePatients = [];
            foreach ($accountPatients as $ap) {
                if ($ap->patient) {
                    $accessiblePatients[] = "ID {$ap->patient->id}: {$ap->patient->first_name} {$ap->patient->last_name}";
                }
            }

            // Log di sicurezza per tentativo di accesso non autorizzato
            Yii::warning("User {$currentUser->id} attempted to access patient {$requestedPatientId} without permission. Accessible patients: " . implode(', ', $accessiblePatients), __METHOD__);
            
            throw new \Exception("Non hai i permessi per fare richieste per il paziente ID: {$requestedPatientId}. Pazienti accessibili: " . implode(', ', $accessiblePatients));
        }

        Yii::info("Patient access validated - user {$currentUser->id} accessing patient ID: {$requestedPatientId}", __METHOD__);

        return [
            'accountPatient' => $validAccountPatient,
            'patient' => $validAccountPatient->patient
        ];
    }

    /**
     * Controlla se esiste già una richiesta attiva per il paziente e tipo specificato
     * Usa la logica allow_multiple_requests del RequestType
     *
     * @param int $patientId
     * @param int $requestTypeId
     * @param array $requestType
     * @return DocumentRequest|null
     */
    private function checkDuplicateRequest($patientId, $requestTypeId, $requestType)
    {
        // Se il tipo permette richieste multiple, non c'è duplicato
        if ($requestType['allow_multiple_requests']) {
            return null;
        }
        
        return DocumentRequest::find()
            ->with(['accountPatient.user.profile']) // Carica relazioni per created_by
            ->where([
                'patient_id' => $patientId,
                'request_type_id' => $requestTypeId
            ])
            ->andWhere(['in', 'status', [
                DocumentRequest::STATUS_INVIATA,
                DocumentRequest::STATUS_PRESA_IN_CARICO,
                DocumentRequest::STATUS_STAMPATO
            ]]) // Solo richieste attive (non consegnate)
            ->orderBy(['created_at' => SORT_DESC]) // La più recente
            ->one();
    }

    /**
     * Formatta la response per una richiesta esistente (duplicata)
     *
     * @param DocumentRequest $existingRequest
     * @param array $requestType
     * @return array
     */
    private function formatExistingRequestResponse($existingRequest, $requestType)
    {
        // Recupera i dati di chi ha fatto la richiesta originale
        $originalRequester = [
            'id' => $existingRequest->accountPatient->id,
            'user_id' => $existingRequest->accountPatient->user_id,
            'first_name' => $existingRequest->accountPatient->user->profile ? 
                           $existingRequest->accountPatient->user->profile->first_name : 'N/A',
            'last_name' => $existingRequest->accountPatient->user->profile ? 
                          $existingRequest->accountPatient->user->profile->last_name : 'N/A',
            'relationship_type' => $existingRequest->accountPatient->relationship_type
        ];

        // Log per audit
        Yii::info("Duplicate request detected for patient {$existingRequest->patient_id}, returning existing request ID: {$existingRequest->id}", __METHOD__);

        return [
            'id' => $existingRequest->id,
            'patient_id' => $existingRequest->patient_id,
            'request_type_id' => $existingRequest->request_type_id,
            'request_type' => $requestType['name'],
            'therapeutic_plan_id' => $existingRequest->therapeutic_plan_id,
            'therapy_id' => $existingRequest->therapy_id,
            'status' => $existingRequest->status,
            'status_label' => $existingRequest->getStatusLabel(),
            'notes' => $existingRequest->notes,
            'created_at' => (new \DateTime($existingRequest->created_at, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            'created_by' => $originalRequester,
            'can_be_cancelled' => $existingRequest->canBeCancelled(),
            'is_duplicate' => true, // Flag per indicare che è una richiesta esistente
            'duplicate_message' => "Esiste già una richiesta di questo tipo per il paziente, creata da {$originalRequester['first_name']} {$originalRequester['last_name']} il " . 
                                  (new \DateTime($existingRequest->created_at, new \DateTimeZone('UTC')))->format('d/m/Y \a\l\l\e H:i')
        ];
    }

    /**
     * Recupera i dati dell'account che ha creato la richiesta
     * Gli account che fanno richieste sono sempre utenti della tabella account_patients
     */
    private function getCreatedByData($currentUser)
    {
        try {
            // Recupera l'AccountPatient per l'utente corrente
            // Include le relazioni necessarie: user.profile per nome/cognome
            $accountPatient = AccountPatient::find()
                ->with(['user.profile'])
                ->where(['user_id' => $currentUser->id])
                ->one();

            if (!$accountPatient) {
                // Fallback: se non troviamo AccountPatient, usa dati base dell'utente
                Yii::warning("AccountPatient not found for user ID: {$currentUser->id}", __METHOD__);
                
                return [
                    'id' => $currentUser->id,
                    'first_name' => $currentUser->profile ? $currentUser->profile->first_name : 'N/A',
                    'last_name' => $currentUser->profile ? $currentUser->profile->last_name : 'N/A',
                    'relationship_type' => 'unknown'
                ];
            }

            // Costruisce l'oggetto created_by con i dati dell'account
            $createdBy = [
                'id' => $accountPatient->id,
                'user_id' => $accountPatient->user_id,
                'first_name' => $accountPatient->user->profile ? $accountPatient->user->profile->first_name : 'N/A',
                'last_name' => $accountPatient->user->profile ? $accountPatient->user->profile->last_name : 'N/A',
                'relationship_type' => $accountPatient->relationship_type
            ];

            // Log per debugging
            Yii::info("Created by data: " . json_encode($createdBy), __METHOD__);

            return $createdBy;

        } catch (\Exception $e) {
            // In caso di errore, log e restituisci dati minimi
            Yii::error("Error retrieving created_by data for user {$currentUser->id}: " . $e->getMessage(), __METHOD__);
            
            return [
                'id' => $currentUser->id,
                'first_name' => 'N/A',
                'last_name' => 'N/A',
                'relationship_type' => 'error'
            ];
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

    /**
     * Converte errori di validazione nel formato standard
     */
    private function formatValidationErrors($validationErrors)
    {
        $details = [];
        
        foreach ($validationErrors as $field => $fieldErrors) {
            // Prendi il primo errore per ogni campo
            $details[$field] = is_array($fieldErrors) ? $fieldErrors[0] : $fieldErrors;
        }
        
        return $details;
    }

    /**
     * Validazione parametri per actionIndex
     */
    private function validateIndexParameters($patientId, $status)
    {
        $errors = [];

        // patient_id è obbligatorio
        if (empty($patientId)) {
            $errors['patient_id'][] = 'Il parametro patient_id è obbligatorio';
        } elseif (!is_numeric($patientId) || (int)$patientId <= 0) {
            $errors['patient_id'][] = 'Il parametro patient_id deve essere un numero intero positivo';
        }

        // Validazione status se fornito
        if (!empty($status)) {
            $validStatuses = [
                DocumentRequest::STATUS_INVIATA,         // 1
                DocumentRequest::STATUS_PRESA_IN_CARICO, // 2
                DocumentRequest::STATUS_STAMPATO,        // 3
                DocumentRequest::STATUS_CONSEGNATO       // 4
            ];

            // Usa il metodo helper per la conversione
            $statusValue = $this->convertStatusValue($status);

            if (!in_array($statusValue, $validStatuses)) {
                $errors['status'][] = 'Status non valido. Valori ammessi: ' . implode(', ', $validStatuses) . ' oppure: pending, in_progress, printed, completed';
            }
        }

        return $errors;
    }

    /**
     * Verifica accesso dell'utente al paziente specificato
     */
    private function validatePatientAccess($patientId, $currentUser)
    {
        // Trova tutti gli AccountPatient per l'utente corrente
        $accountPatients = AccountPatient::find()
            ->with(['patient'])
            ->where(['user_id' => $currentUser->id])
            ->all();

        if (empty($accountPatients)) {
            return [
                'hasAccess' => false,
                'message' => 'Non hai accesso a nessun paziente. Contatta l\'amministratore.'
            ];
        }

        // Verifica che l'utente abbia accesso al paziente specificato
        $hasAccess = false;
        $accessiblePatients = [];

        foreach ($accountPatients as $accountPatient) {
            if ($accountPatient->patient) {
                $accessiblePatients[] = "ID {$accountPatient->patient->id}: {$accountPatient->patient->first_name} {$accountPatient->patient->last_name}";
                
                if ($accountPatient->patient->id === $patientId) {
                    $hasAccess = true;
                }
            }
        }

        if (!$hasAccess) {
            // Log di sicurezza per tentativo di accesso non autorizzato
            Yii::warning("User {$currentUser->id} attempted to access requests for patient {$patientId} without permission. Accessible patients: " . implode(', ', $accessiblePatients), __METHOD__);
            
            return [
                'hasAccess' => false,
                'message' => "Non hai i permessi per accedere alle richieste del paziente ID: {$patientId}. Pazienti accessibili: " . implode(', ', $accessiblePatients)
            ];
        }

        return [
            'hasAccess' => true,
            'message' => 'Accesso autorizzato'
        ];
    }

    /**
     * Formatta una richiesta per la response API secondo il formato specificato
     */
    private function formatRequestForApi($request)
    {
        // Usa il metodo toApiArray del modello che gestisce solo campi esistenti
        $data = $request->toApiArray();
        
        // Aggiungi solo i campi legacy necessari per compatibilità API esistente
        $data['request_type'] = $request->requestType ? $request->requestType->name : 'N/A';
        $data['updated_at'] = $data['created_at']; // Usa created_at come fallback

        return $data;
    }

    /**
     * Formatta una singola richiesta per la response API del dettaglio
     * Secondo le specifiche per actionView con type_info e created_by
     */
    private function formatSingleRequestForApi($request)
    {
        // Usa il metodo toApiArray del modello che gestisce solo campi esistenti
        $data = $request->toApiArray();
        
        // Aggiungi solo i campi legacy necessari per compatibilità API esistente
        $data['request_type'] = $request->requestType ? $request->requestType->name : 'N/A';
        $data['updated_at'] = $data['created_at']; // Usa created_at come fallback

        // Aggiungi informazioni dettagliate sul tipo di richiesta come oggetto (solo campi esistenti)
        if ($request->requestType) {
            $data['type_info'] = [
                'id' => $request->requestType->id,
                'name' => $request->requestType->name,
                'therapeutic_plan_rule' => $request->requestType->therapeutic_plan_rule,
                'allow_multiple_requests' => (bool) $request->requestType->allow_multiple_requests,
                'require_therapy_assignment' => (bool) $request->requestType->require_therapy_assignment,
                'require_notes' => (bool) $request->requestType->require_notes,
                'is_active' => (bool) $request->requestType->is_active
            ];
        } else {
            $data['type_info'] = null;
        }

        return $data;
    }

    /**
     * Converte il valore status da stringa a valore numerico
     */
    private function convertStatusValue($status)
    {
        if (empty($status)) {
            return null;
        }

        // Mappa stringhe descrittive ai valori numerici
        $statusMap = [
            'pending' => DocumentRequest::STATUS_INVIATA,          // 1 - "Inviata" 
            'inviata' => DocumentRequest::STATUS_INVIATA,          // 1
            'in_progress' => DocumentRequest::STATUS_PRESA_IN_CARICO, // 2 - "Presa in carico"
            'presa_in_carico' => DocumentRequest::STATUS_PRESA_IN_CARICO, // 2
            'printed' => DocumentRequest::STATUS_STAMPATO,         // 3 - "Stampato"
            'stampato' => DocumentRequest::STATUS_STAMPATO,        // 3
            'completed' => DocumentRequest::STATUS_CONSEGNATO,     // 4 - "Consegnato"
            'consegnato' => DocumentRequest::STATUS_CONSEGNATO,    // 4
            'delivered' => DocumentRequest::STATUS_CONSEGNATO      // 4
        ];

        // Converti status
        if (is_string($status)) {
            if (is_numeric($status)) {
                // Stringa numerica -> converti in intero
                return (int) $status;
            } elseif (isset($statusMap[strtolower($status)])) {
                // Stringa descrittiva -> mappa al valore numerico
                return $statusMap[strtolower($status)];
            }
        }

        return $status; // Restituisci il valore originale se non è convertibile
    }
} 