<?php

namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\UnauthorizedHttpException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

/**
 * Controller per la gestione degli errori API nel formato standardizzato
 */
class ErrorController extends Controller
{
    /**
     * Gestisce tutti gli errori dell'API e li converte nel formato standard
     */
    public function actionIndex()
    {
        $exception = Yii::$app->errorHandler->exception;
        
        if ($exception !== null) {
            return $this->formatException($exception);
        }
        
        return $this->formatErrorResponse('INTERNAL_ERROR', 'Errore sconosciuto', [], 500);
    }
    
    /**
     * Converte un'eccezione nel formato errore standardizzato
     */
    private function formatException($exception)
    {
        // Determina il codice errore e il messaggio basandosi sul tipo di eccezione
        if ($exception instanceof UnauthorizedHttpException) {
            return $this->formatErrorResponse(
                'UNAUTHORIZED', 
                $exception->getMessage() ?: 'Accesso non autorizzato',
                [],
                401
            );
        }
        
        if ($exception instanceof ForbiddenHttpException) {
            return $this->formatErrorResponse(
                'ACCESS_DENIED', 
                $exception->getMessage() ?: 'Accesso negato',
                [],
                403
            );
        }
        
        if ($exception instanceof NotFoundHttpException) {
            return $this->formatErrorResponse(
                'NOT_FOUND', 
                $exception->getMessage() ?: 'Risorsa non trovata',
                [],
                404
            );
        }
        
        if ($exception instanceof BadRequestHttpException) {
            return $this->formatErrorResponse(
                'BAD_REQUEST', 
                $exception->getMessage() ?: 'Richiesta non valida',
                [],
                400
            );
        }
        
        if ($exception instanceof HttpException) {
            return $this->formatErrorResponse(
                'HTTP_ERROR', 
                $exception->getMessage() ?: 'Errore HTTP',
                [],
                $exception->statusCode
            );
        }
        
        // Per tutte le altre eccezioni, restituisci errore interno
        Yii::error("Unhandled exception: " . $exception->getMessage(), __METHOD__);
        Yii::error("Stack trace: " . $exception->getTraceAsString(), __METHOD__);
        
        return $this->formatErrorResponse(
            'INTERNAL_ERROR', 
            YII_DEBUG ? $exception->getMessage() : 'Errore interno del server',
            YII_DEBUG ? ['trace' => $exception->getTraceAsString()] : [],
            500
        );
    }
    
    /**
     * Formatta una risposta di errore secondo lo standard API
     */
    private function formatErrorResponse($errorCode, $message, $details = [], $statusCode = 500)
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