<?php

namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;
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
            $requestTypes = [
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
} 