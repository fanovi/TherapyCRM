<?php

namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\helpers\Html;
use yii\web\Request;
use common\models\ActivityLog;

/**
 * Behavior per il logging automatico delle attività CRUD
 * 
 * Esempio di utilizzo in un model:
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => ActivityLogBehavior::class,
 *             'excludedAttributes' => ['updated_at', 'password_hash'],
 *             'parentLogId' => function($model) {
 *                 return $model->parent_log_id;
 *             },
 *         ],
 *     ];
 * }
 * ```
 */
class ActivityLogBehavior extends Behavior
{
    /**
     * @var array Attributi da escludere dal logging
     */
    public $excludedAttributes = ['created_at', 'updated_at'];

    /**
     * @var bool Se true, logga anche i campi non modificati nelle update
     */
    public $logUnchangedAttributes = false;

    /**
     * @var callable|null Callback per personalizzare il nome dell'entità
     */
    public $entityNameCallback;

    /**
     * @var callable|null Callback per ottenere l'ID del log parent
     */
    public $parentLogId;

    /**
     * @var array Valori precedenti del record per il confronto
     */
    private $_oldAttributes = [];

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'logInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_AFTER_UPDATE => 'logUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            ActiveRecord::EVENT_AFTER_DELETE => 'logDelete',
        ];
    }

    /**
     * Salva i valori precedenti prima dell'update
     * @param \yii\base\Event $event
     */
    public function beforeUpdate($event)
    {
        $this->_oldAttributes = $this->owner->getOldAttributes();
    }

    /**
     * Salva i valori precedenti prima della delete
     * @param \yii\base\Event $event
     */
    public function beforeDelete($event)
    {
        $this->_oldAttributes = $this->owner->getAttributes();
    }

    /**
     * Logga l'inserimento di un nuovo record
     * @param \yii\base\Event $event
     */
    public function logInsert($event)
    {
        $newValues = $this->filterAttributes($this->owner->getAttributes());
        
        $this->saveLog(
            ActivityLog::ACTION_CREATE,
            null,
            $newValues
        );
    }

    /**
     * Logga l'aggiornamento di un record
     * @param \yii\base\Event $event
     */
    public function logUpdate($event)
    {
        $oldValues = $this->filterAttributes($this->_oldAttributes);
        $newValues = $this->filterAttributes($this->owner->getAttributes());
        
        // Confronta solo i campi modificati
        $changedOldValues = [];
        $changedNewValues = [];
        
        foreach ($newValues as $attribute => $newValue) {
            $oldValue = isset($oldValues[$attribute]) ? $oldValues[$attribute] : null;
            
            // Confronto che gestisce anche valori null
            if ($this->isDifferent($oldValue, $newValue)) {
                $changedOldValues[$attribute] = $oldValue;
                $changedNewValues[$attribute] = $newValue;
            } elseif ($this->logUnchangedAttributes) {
                $changedOldValues[$attribute] = $oldValue;
                $changedNewValues[$attribute] = $newValue;
            }
        }
        
        // Logga solo se ci sono stati cambiamenti
        if (!empty($changedOldValues) || !empty($changedNewValues)) {
            $this->saveLog(
                ActivityLog::ACTION_UPDATE,
                $changedOldValues,
                $changedNewValues
            );
        }
    }

    /**
     * Logga l'eliminazione di un record
     * @param \yii\base\Event $event
     */
    public function logDelete($event)
    {
        $oldValues = $this->filterAttributes($this->_oldAttributes);
        
        $this->saveLog(
            ActivityLog::ACTION_DELETE,
            $oldValues,
            null
        );
    }

    /**
     * Salva il log nel database
     * @param string $action
     * @param array|null $oldValues
     * @param array|null $newValues
     * @return ActivityLog|null Il log creato o null in caso di errore
     */
    protected function saveLog($action, $oldValues = null, $newValues = null)
    {
        try {
            // Ottieni l'ID utente corrente
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                Yii::info('ActivityLog: Impossibile determinare user_id per il logging', __METHOD__);
                return null;
            }

            // Determina il nome dell'entità
            $entityName = $this->getEntityName();
            
            // Ottieni l'ID del record
            $entityId = $this->owner->getPrimaryKey();
            if (is_array($entityId)) {
                $entityId = implode('-', $entityId);
            }

            // Ottieni l'ID del log parent se configurato
            $parentId = null;
            if ($this->parentLogId instanceof \Closure) {
                $parentId = call_user_func($this->parentLogId, $this->owner);
            }

            // Crea il record di log
            $log = new ActivityLog([
                'user_id' => $userId,
                'action' => $action,
                'entity_name' => $entityName,
                'entity_id' => $entityId,
                'old_values' => $oldValues ? Json::encode($oldValues) : null,
                'new_values' => $newValues ? Json::encode($newValues) : null,
                'ip_address' => $this->getClientIpAddress(),
                'user_agent' => $this->getUserAgent(),
                'parent_log_id' => $parentId,
            ]);

            if (!$log->save()) {
                Yii::error('ActivityLog: Errore nel salvataggio del log: ' . Json::encode($log->getErrors()), __METHOD__);
                return null;
            }

            // Invia notifiche per azioni critiche
            $this->sendCriticalActionNotification($log);

            return $log;

        } catch (\Exception $e) {
            Yii::error('ActivityLog: Eccezione durante il salvataggio: ' . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Filtra gli attributi escludendo quelli specificati
     * @param array $attributes
     * @return array
     */
    protected function filterAttributes($attributes)
    {
        $filtered = [];
        
        foreach ($attributes as $name => $value) {
            if (!in_array($name, $this->excludedAttributes)) {
                $filtered[$name] = $value;
            }
        }
        
        return $filtered;
    }

    /**
     * Determina se due valori sono diversi
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return bool
     */
    protected function isDifferent($oldValue, $newValue)
    {
        // Gestisce confronto con null
        if ($oldValue === null && $newValue === null) {
            return false;
        }
        
        if ($oldValue === null || $newValue === null) {
            return true;
        }
        
        // Confronto normale
        return $oldValue != $newValue;
    }

    /**
     * Ottieni l'ID dell'utente corrente
     * @return int|null
     */
    protected function getCurrentUserId()
    {
        // Prova prima con Yii::$app->user->id (web)
        if (Yii::$app->has('user') && !Yii::$app->user->isGuest) {
            return Yii::$app->user->id;
        }
        
        // Prova con identity diretta (API)
        if (Yii::$app->has('user') && Yii::$app->user->identity) {
            return Yii::$app->user->identity->getId();
        }
        
        // Fallback per console commands
        if (php_sapi_name() === 'cli') {
            return 1; // User ID di sistema per console
        }
        
        return null;
    }

    /**
     * Ottieni il nome dell'entità
     * @return string
     */
    protected function getEntityName()
    {
        if ($this->entityNameCallback !== null) {
            return call_user_func($this->entityNameCallback, $this->owner);
        }
        
        // Usa il nome della classe senza namespace
        return (new \ReflectionClass($this->owner))->getShortName();
    }

    /**
     * Ottieni l'indirizzo IP del client
     * @return string|null
     */
    protected function getClientIpAddress()
    {
        if (!Yii::$app->has('request')) {
            return null;
        }

        $request = Yii::$app->request;
        if ($request instanceof Request) {
            return $request->getUserIP();
        }
        
        return null;
    }

    /**
     * Ottieni lo User Agent del browser
     * @return string|null
     */
    protected function getUserAgent()
    {
        if (!Yii::$app->has('request')) {
            return null;
        }

        $request = Yii::$app->request;
        if ($request instanceof Request) {
            return $request->getUserAgent();
        }
        
        return null;
    }

    /**
     * Configura attributi da escludere
     * @param array $attributes
     */
    public function setExcludedAttributes($attributes)
    {
        $this->excludedAttributes = array_merge($this->excludedAttributes, $attributes);
    }

    /**
     * Aggiungi un attributo da escludere
     * @param string $attribute
     */
    public function excludeAttribute($attribute)
    {
        if (!in_array($attribute, $this->excludedAttributes)) {
            $this->excludedAttributes[] = $attribute;
        }
    }

    /**
     * Invia notifica email per azioni critiche
     * @param ActivityLog $log
     */
    protected function sendCriticalActionNotification($log)
    {
        try {
            // Controlla se le notifiche sono abilitate
            $criticalEntities = Yii::$app->params['activityLog']['criticalEntities'] ?? [];
            $criticalActions = Yii::$app->params['activityLog']['criticalActions'] ?? [];
            $notificationEmails = Yii::$app->params['activityLog']['notificationEmails'] ?? [];
            //TODO: rimuovere true
            if (true ||empty($notificationEmails)) {
                return;
            }

            // Verifica se è un'azione critica
            $isCritical = false;
            
            if (in_array($log->entity_name, $criticalEntities) || in_array($log->action, $criticalActions)) {
                $isCritical = true;
            }

            // Verifica azioni di eliminazione (sempre critiche)
            if ($log->action === ActivityLog::ACTION_DELETE) {
                $isCritical = true;
            }

            if (!$isCritical) {
                return;
            }

            // Invio diretto della notifica
            $this->sendNotificationEmail($log, $notificationEmails);

        } catch (\Exception $e) {
            Yii::error('Errore invio notifica activity log: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Invia l'email di notifica
     * @param ActivityLog $log  
     * @param array $emails
     */
    protected function sendNotificationEmail($log, $emails)
    {
        $subject = sprintf(
            '[TherapyCRM] Azione Critica: %s %s #%d',
            $log->getActionDescription(),
            $this->getEntityName(),
            $log->entity_id
        );

        $body = $this->buildNotificationEmailBody($log);

        foreach ($emails as $email) {
            Yii::$app->mailer->compose()
                ->setTo($email)
                ->setSubject($subject)
                ->setHtmlBody($body)
                ->send();
        }
    }

    /**
     * Costruisce il corpo dell'email di notifica
     * @param ActivityLog $log
     * @return string
     */
    protected function buildNotificationEmailBody($log)
    {
        $html = '<h3>Azione Critica Rilevata</h3>';
        $html .= '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
        $html .= '<tr><td><strong>Data/Ora:</strong></td><td>' . $log->created_at . '</td></tr>';
        $html .= '<tr><td><strong>Utente:</strong></td><td>' . Html::encode($log->getUserName()) . '</td></tr>';
        $html .= '<tr><td><strong>Azione:</strong></td><td>' . $log->getActionDescription() . '</td></tr>';
        $html .= '<tr><td><strong>Entità:</strong></td><td>' . $this->getEntityName() . ' #' . $log->entity_id . '</td></tr>';
        $html .= '<tr><td><strong>IP Address:</strong></td><td>' . Html::encode($log->ip_address) . '</td></tr>';

        if ($log->action === ActivityLog::ACTION_UPDATE) {
            $oldValues = $log->getOldValuesArray();
            $newValues = $log->getNewValuesArray();
            
            if (!empty($oldValues) || !empty($newValues)) {
                $html .= '<tr><td><strong>Modifiche:</strong></td><td>';
                
                foreach ($newValues as $field => $newValue) {
                    $oldValue = $oldValues[$field] ?? null;
                    if ($oldValue != $newValue) {
                        $html .= "<strong>{$field}:</strong> " . Html::encode($oldValue) . ' → ' . Html::encode($newValue) . '<br>';
                    }
                }
                
                $html .= '</td></tr>';
            }
        }

        $html .= '</table>';
        $html .= '<p><em>Questo è un messaggio automatico dal sistema TherapyCRM.</em></p>';

        return $html;
    }
} 