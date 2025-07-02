<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\ActivityLog;

/* @var $this yii\web\View */
/* @var $logs ActivityLog[] */
/* @var $widget common\widgets\ActivityLogWidget */
?>

<div <?= Html::renderTagAttributes($widget->options) ?>>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="widget-title mb-0"><?= Html::encode($widget->title) ?></h5>
        <?php if ($widget->showViewAllLink): ?>
            <small>
                <?= Html::a('Vedi tutti &raquo;', $widget->getViewAllUrl(), [
                    'class' => 'text-primary',
                    'title' => 'Visualizza tutti i log per questa entità'
                ]) ?>
            </small>
        <?php endif; ?>
    </div>

    <div class="activity-log-list">
        <?php foreach ($logs as $log): ?>
            <div class="activity-log-item mb-3 pb-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="activity-details flex-grow-1">
                        <div class="activity-header mb-1">
                            <?= $widget->formatAction($log) ?>
                            <?php if ($widget->showUser): ?>
                                <span class="text-muted">da</span>
                                <strong><?= Html::encode($log->getUserName()) ?></strong>
                            <?php endif; ?>
                            <small class="text-muted ml-2">
                                <i class="fas fa-clock"></i>
                                <?= Yii::$app->formatter->asRelativeTime($log->created_at) ?>
                            </small>
                            <?php if ($widget->showIp && !empty($log->ip_address)): ?>
                                <small class="text-muted ml-2">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= Html::encode($log->ip_address) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <?php $changes = $widget->formatChanges($log); ?>
                        <?php if (!empty($changes)): ?>
                            <div class="activity-changes mt-1">
                                <?= $changes ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($log->action === ActivityLog::ACTION_DELETE && !empty($log->getOldValuesArray())): ?>
                    <div class="deleted-data mt-2">
                        <small class="text-danger">
                            <i class="fas fa-trash"></i>
                            Dati eliminati disponibili nei dettagli
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.activity-log-widget {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
}

.activity-log-widget .widget-title {
    color: #495057;
    font-weight: 600;
}

.activity-log-item:last-child {
    border-bottom: none !important;
    margin-bottom: 0 !important;
    padding-bottom: 0 !important;
}

.activity-header {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.activity-changes {
    margin-left: 1rem;
    padding-left: 1rem;
    border-left: 3px solid #e9ecef;
}

.badge {
    font-size: 0.75em;
    padding: 0.375em 0.5em;
}

.deleted-data {
    padding: 0.5rem;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 0.25rem;
}

@media (max-width: 576px) {
    .activity-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .activity-changes {
        margin-left: 0;
        padding-left: 0.5rem;
        border-left-width: 2px;
    }
}
</style> 