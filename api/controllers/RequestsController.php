<?php

namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;
use yii\web\BadRequestHttpException;
use common\models\User;

/**
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
     *     description="Restituisce l'elenco delle tipologie di richieste disponibili per i pazienti autenticati",
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
     *             @OA\Property(property="message", type="string", example="Il token di autenticazione non è stato fornito."),
     *             @OA\Property(property="error_code", type="string", example="UNAUTHORIZED")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accesso negato - Utente non autorizzato per questa risorsa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accesso negato per questo tipo di utente"),
     *             @OA\Property(property="error_code", type="string", example="ACCESS_DENIED")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Errore interno del server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Errore interno del server"),
     *             @OA\Property(property="error_code", type="string", example="INTERNAL_ERROR")
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
            
            // Dati statici delle tipologie di richieste
            // TODO: Sostituire con query al database quando il modello RequestType sarà implementato
            $requestTypes = $this->getRequestTypesData();
            
            // Filtra solo le tipologie attive
            $activeRequestTypes = array_filter($requestTypes, function($type) {
                return $type['is_active'] === true;
            });
            
            // Ordina per categoria e poi per nome
            usort($activeRequestTypes, function($a, $b) {
                if ($a['category'] === $b['category']) {
                    return strcmp($a['name'], $b['name']);
                }
                return strcmp($a['category'], $b['category']);
            });
            
            // Risposta semplificata (formato JSON e status già gestiti globalmente)
            return [
                'success' => true,
                'data' => array_values($activeRequestTypes),
                'meta' => [
                    'total' => count($activeRequestTypes),
                    'categories' => array_unique(array_column($activeRequestTypes, 'category'))
                ]
            ];
            
        } catch (UnauthorizedHttpException $e) {
            // Status code e formato già gestiti automaticamente da Yii2
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'UNAUTHORIZED'
            ];
            
        } catch (\Exception $e) {
            // Log dell'errore per debugging
            Yii::error("Error in RequestsController::actionTypes: " . $e->getMessage(), __METHOD__);
            Yii::error("Stack trace: " . $e->getTraceAsString(), __METHOD__);
            
            return [
                'success' => false,
                'message' => 'Errore interno del server',
                'error_code' => 'INTERNAL_ERROR'
            ];
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
     *                 required={"type_id"},
     *                 @OA\Property(
     *                     property="type_id",
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
     *                 @OA\Property(property="date_to", type="string", format="date", example="2025-01-20")
     *             ),
     *             @OA\Property(property="message", type="string", example="Richiesta creata con successo! Riceverai una notifica quando sarà pronta.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Errore di validazione",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Il campo 'reason' è obbligatorio per questa tipologia di richiesta"),
     *             @OA\Property(property="error_code", type="string", example="VALIDATION_ERROR"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="reason", type="array", @OA\Items(type="string", example="Il motivo è obbligatorio per questa tipologia")),
     *                 @OA\Property(property="date_from", type="array", @OA\Items(type="string", example="La data di inizio è obbligatoria"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorizzato",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token non valido"),
     *             @OA\Property(property="error_code", type="string", example="UNAUTHORIZED")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tipologia di richiesta non trovata",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Tipologia di richiesta non trovata o non attiva"),
     *             @OA\Property(property="error_code", type="string", example="TYPE_NOT_FOUND")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Errore interno del server",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Errore interno del server"),
     *             @OA\Property(property="error_code", type="string", example="INTERNAL_ERROR")
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
                Yii::$app->response->statusCode = 400;
                return [
                    'success' => false,
                    'message' => 'Errori di validazione',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $validationErrors
                ];
            }

            // Trova la tipologia di richiesta
            $requestType = $this->findRequestTypeById($data['type_id']);
            if (!$requestType) {
                Yii::$app->response->statusCode = 404;
                return [
                    'success' => false,
                    'message' => 'Tipologia di richiesta non trovata o non attiva',
                    'error_code' => 'TYPE_NOT_FOUND'
                ];
            }

            // Validazione dinamica basata sui requisiti del tipo
            $dynamicErrors = $this->validateRequestTypeRequirements($data, $requestType);
            if (!empty($dynamicErrors)) {
                Yii::$app->response->statusCode = 400;
                return [
                    'success' => false,
                    'message' => 'Requisiti della tipologia non soddisfatti',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $dynamicErrors
                ];
            }

            // Simula il salvataggio nel database
            $createdRequest = $this->simulateRequestSaving($data, $requestType, $currentUser);

            // Risposta di successo
            Yii::$app->response->statusCode = 201;
            Yii::info("Request created successfully with ID: {$createdRequest['id']}", __METHOD__);

            return [
                'success' => true,
                'data' => $createdRequest,
                'message' => 'Richiesta creata con successo! Riceverai una notifica quando sarà pronta.'
            ];

        } catch (UnauthorizedHttpException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'UNAUTHORIZED'
            ];

        } catch (\Exception $e) {
            // Log dell'errore per debugging
            Yii::error("Error in RequestsController::actionCreate: " . $e->getMessage(), __METHOD__);
            Yii::error("Stack trace: " . $e->getTraceAsString(), __METHOD__);

            Yii::$app->response->statusCode = 500;
            return [
                'success' => false,
                'message' => 'Errore interno del server',
                'error_code' => 'INTERNAL_ERROR'
            ];
        }
    }

    /**
     * Restituisce i dati statici delle tipologie di richieste
     * TODO: Sostituire con query al database quando il modello RequestType sarà implementato
     */
    private function getRequestTypesData()
    {
        return [
            [
                'id' => 1,
                'name' => 'Certificato Medico',
                'description' => 'Richiesta certificato medico per assenza lavorativa',
                'category' => 'medical',
                'estimated_days' => 3,
                'requires_reason' => true,
                'requires_date_range' => true,
                'is_active' => true
            ],
            [
                'id' => 2,
                'name' => 'Relazione Terapeutica',
                'description' => 'Richiesta relazione dettagliata sui progressi terapeutici',
                'category' => 'therapy',
                'estimated_days' => 5,
                'requires_reason' => true,
                'requires_date_range' => false,
                'is_active' => true
            ],
            [
                'id' => 3,
                'name' => 'Programma Riabilitativo',
                'description' => 'Richiesta piano riabilitativo personalizzato per il paziente',
                'category' => 'therapy',
                'estimated_days' => 7,
                'requires_reason' => true,
                'requires_date_range' => false,
                'is_active' => true
            ],
            [
                'id' => 4,
                'name' => 'Certificato Idoneità Fisica',
                'description' => 'Certificato per attività fisica e sportiva',
                'category' => 'fitness',
                'estimated_days' => 2,
                'requires_reason' => false,
                'requires_date_range' => false,
                'is_active' => true
            ],
            [
                'id' => 5,
                'name' => 'Richiesta Appuntamento Urgente',
                'description' => 'Richiesta di appuntamento con priorità alta',
                'category' => 'appointment',
                'estimated_days' => 1,
                'requires_reason' => true,
                'requires_date_range' => true,
                'is_active' => true
            ],
            [
                'id' => 6,
                'name' => 'Copia Cartella Clinica',
                'description' => 'Richiesta copia della cartella clinica del paziente',
                'category' => 'medical',
                'estimated_days' => 4,
                'requires_reason' => true,
                'requires_date_range' => false,
                'is_active' => true
            ],
            [
                'id' => 7,
                'name' => 'Prescrizione Esercizi Domiciliari',
                'description' => 'Richiesta programma di esercizi da svolgere a casa',
                'category' => 'therapy',
                'estimated_days' => 3,
                'requires_reason' => false,
                'requires_date_range' => false,
                'is_active' => true
            ],
            [
                'id' => 8,
                'name' => 'Rivalutazione Funzionale',
                'description' => 'Richiesta rivalutazione completa delle capacità funzionali',
                'category' => 'medical',
                'estimated_days' => 6,
                'requires_reason' => true,
                'requires_date_range' => false,
                'is_active' => true
            ]
        ];
    }

    /**
     * Trova una tipologia di richiesta per ID
     */
    private function findRequestTypeById($typeId)
    {
        $requestTypes = $this->getRequestTypesData();
        
        foreach ($requestTypes as $type) {
            if ($type['id'] == $typeId && $type['is_active']) {
                return $type;
            }
        }
        
        return null;
    }

    /**
     * Validazione input base
     */
    private function validateRequestData($data)
    {
        $errors = [];

        // type_id è obbligatorio
        if (empty($data['type_id'])) {
            $errors['type_id'][] = 'Il campo type_id è obbligatorio';
        } elseif (!is_numeric($data['type_id']) || (int)$data['type_id'] <= 0) {
            $errors['type_id'][] = 'Il campo type_id deve essere un numero intero positivo';
        }

        // Validazione formato date se presenti
        if (!empty($data['date_from']) && !$this->isValidDate($data['date_from'])) {
            $errors['date_from'][] = 'Il formato della data di inizio non è valido (usa YYYY-MM-DD)';
        }

        if (!empty($data['date_to']) && !$this->isValidDate($data['date_to'])) {
            $errors['date_to'][] = 'Il formato della data di fine non è valido (usa YYYY-MM-DD)';
        }

        // Se entrambe le date sono presenti, verifica che date_from <= date_to
        if (!empty($data['date_from']) && !empty($data['date_to'])) {
            if (strtotime($data['date_from']) > strtotime($data['date_to'])) {
                $errors['date_to'][] = 'La data di fine deve essere successiva o uguale alla data di inizio';
            }
        }

        // Validazione lunghezza campi di testo
        if (!empty($data['reason']) && strlen($data['reason']) > 1000) {
            $errors['reason'][] = 'Il motivo non può superare i 1000 caratteri';
        }

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

        // Verifica campo reason se richiesto
        if ($requestType['requires_reason'] && empty($data['reason'])) {
            $errors['reason'][] = "Il motivo è obbligatorio per la tipologia '{$requestType['name']}'";
        }

        // Verifica campi date se richiesti
        if ($requestType['requires_date_range']) {
            if (empty($data['date_from'])) {
                $errors['date_from'][] = "La data di inizio è obbligatoria per la tipologia '{$requestType['name']}'";
            }
            if (empty($data['date_to'])) {
                $errors['date_to'][] = "La data di fine è obbligatoria per la tipologia '{$requestType['name']}'";
            }
        }

        return $errors;
    }

    /**
     * Simula il salvataggio della richiesta nel database
     * TODO: Sostituire con salvataggio reale quando il modello DocumentRequest sarà implementato
     */
    private function simulateRequestSaving($data, $requestType, $currentUser)
    {
        // Simula un ID incrementale
        $requestId = mt_rand(100, 9999);

        // Data e ora correnti
        $now = new \DateTime();
        $createdAt = $now->format('Y-m-d\TH:i:s\Z');

        // Calcola estimated_completion aggiungendo i giorni lavorativi
        $estimatedCompletion = $this->calculateEstimatedCompletion($requestType['estimated_days']);

        // Costruisce il record della richiesta
        $request = [
            'id' => $requestId,
            'user_id' => $currentUser->id,
            'type_id' => $data['type_id'],
            'request_type' => $requestType['name'],
            'status' => 'pending',
            'created_at' => $createdAt,
            'estimated_completion' => $estimatedCompletion,
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'date_from' => $data['date_from'] ?? null,
            'date_to' => $data['date_to'] ?? null
        ];

        // Log per debugging
        Yii::info("Simulated request saving: " . json_encode($request), __METHOD__);

        // In un sistema reale qui si farebbe:
        // $documentRequest = new DocumentRequest();
        // $documentRequest->attributes = $request;
        // $documentRequest->save();

        return $request;
    }

    /**
     * Calcola la data stimata di completamento aggiungendo giorni lavorativi
     */
    private function calculateEstimatedCompletion($estimatedDays)
    {
        $date = new \DateTime();
        $addedDays = 0;

        while ($addedDays < $estimatedDays) {
            $date->add(new \DateInterval('P1D')); // Aggiungi un giorno
            
            // Se è un giorno lavorativo (lunedì-venerdì), conta
            if ($date->format('N') <= 5) {
                $addedDays++;
            }
        }

        // Imposta l'ora a fine giornata lavorativa (18:00)
        $date->setTime(18, 0, 0);

        return $date->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Verifica se una stringa è una data valida nel formato YYYY-MM-DD
     */
    private function isValidDate($dateString)
    {
        if (!$dateString) return false;
        
        $date = \DateTime::createFromFormat('Y-m-d', $dateString);
        return $date && $date->format('Y-m-d') === $dateString;
    }
} 