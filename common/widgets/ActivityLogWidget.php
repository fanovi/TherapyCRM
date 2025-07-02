<?php

namespace common\widgets;

use Yii;
use yii\base\Widget;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Url;
use common\models\ActivityLog;
use common\helpers\ActivityLogHelper;

/**
 * Widget per visualizzare gli ultimi log di attività per una specifica entità
 * 
 * Esempio di utilizzo:
 * ```php
 * echo ActivityLogWidget::widget([
 *     'entityName' => 'Patient',
 *     'entityId' => $model->id,
 *     'limit' => 5,
 *     'showUser' => true,
 *     'showChanges' => true,
 * ]);
 * ```
 */
class ActivityLogWidget extends Widget
{
    /**
     * @var string Nome dell'entità
     */
    public $entityName;
    
    /**
     * @var int ID dell'entità
     */
    public $entityId;
    
    /**
     * @var int Numero massimo di log da mostrare
     */
    public $limit = 10;
    
    /**
     * @var bool Se mostrare il nome utente
     */
    public $showUser = true;
    
    /**
     * @var bool Se mostrare i dettagli delle modifiche
     */
    public $showChanges = true;
    
    /**
     * @var bool Se mostrare l'indirizzo IP
     */
    public $showIp = false;
    
    /**
     * @var string Titolo del widget
     */
    public $title = 'Cronologia Attività';
    
    /**
     * @var bool Se mostrare il link per vedere tutti i log
     */
    public $showViewAllLink = true;
    
    /**
     * @var array Opzioni HTML per il container
     */
    public $options = ['class' => 'activity-log-widget'];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        
        if (empty($this->entityName)) {
            throw new InvalidConfigException('La proprietà "entityName" deve essere specificata.');
        }
        
        if (empty($this->entityId)) {
            throw new InvalidConfigException('La proprietà "entityId" deve essere specificata.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $logs = ActivityLog::getRecentByEntity($this->entityName, $this->entityId, $this->limit);
        
        if (empty($logs)) {
            return $this->renderEmpty();
        }
        
        return $this->render('activity-log-widget', [
            'logs' => $logs,
            'widget' => $this,
        ]);
    }
    
    /**
     * Renderizza il messaggio quando non ci sono log
     * @return string
     */
    protected function renderEmpty()
    {
        return Html::beginTag('div', $this->options) .
               Html::tag('h5', $this->title, ['class' => 'widget-title']) .
               Html::tag('div', 'Nessuna attività registrata.', ['class' => 'alert alert-info']) .
               Html::endTag('div');
    }
    
    /**
     * Formatta l'azione con badge colorato
     * @param ActivityLog $log
     * @return string
     */
    public function formatAction($log)
    {
        $class = '';
        switch ($log->action) {
            case ActivityLog::ACTION_CREATE:
                $class = 'badge badge-success';
                break;
            case ActivityLog::ACTION_UPDATE:
                $class = 'badge badge-warning';
                break;
            case ActivityLog::ACTION_DELETE:
                $class = 'badge badge-danger';
                break;
        }
        
        return Html::tag('span', $log->getActionDescription(), ['class' => $class]);
    }
    
    /**
     * Formatta le modifiche in modo leggibile
     * @param ActivityLog $log
     * @return string
     */
    public function formatChanges($log)
    {
        if (!$this->showChanges) {
            return '';
        }
        
        if ($log->action === ActivityLog::ACTION_UPDATE) {
            $changes = ActivityLogHelper::formatChanges(
                $log->getOldValuesArray(),
                $log->getNewValuesArray()
            );
            
            if (!empty($changes)) {
                $formatted = [];
                foreach (array_slice($changes, 0, 3) as $change) {
                    $formatted[] = Html::tag('small', $change, ['class' => 'text-muted']);
                }
                return implode('<br>', $formatted);
            }
        } elseif ($log->action === ActivityLog::ACTION_CREATE) {
            $newValues = $log->getNewValuesArray();
            if (!empty($newValues)) {
                $key = array_keys($newValues)[0];
                $value = array_values($newValues)[0];
                return Html::tag('small', "Creato con {$key}: " . Html::encode($value), ['class' => 'text-muted']);
            }
        }
        
        return '';
    }
    
    /**
     * Ritorna il link per vedere tutti i log
     * @return string
     */
    public function getViewAllUrl()
    {
        return Url::to(['/activity-log/entity', 'entity' => $this->entityName, 'id' => $this->entityId]);
    }
} 