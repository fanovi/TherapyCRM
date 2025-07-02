<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use common\models\ActivityLog;
use common\helpers\ActivityLogHelper;

/* @var $this yii\web\View */
/* @var $searchModel backend\controllers\ActivityLogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $users array */
/* @var $entities array */
/* @var $actions array */

$this->title = 'Log delle Attività';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="activity-log-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row mb-3">
        <div class="col-md-6">
            <p>
                <?= Html::a('Pulizia Log', ['cleanup'], [
                    'class' => 'btn btn-warning',
                    'data' => [
                        'confirm' => 'Eliminare i log più vecchi di 90 giorni?',
                        'method' => 'post',
                    ],
                ]) ?>
                
                <?= Html::a('Esporta Excel', ['export', 'ActivityLogSearch' => Yii::$app->request->queryParams['ActivityLogSearch'] ?? []], [
                    'class' => 'btn btn-success',
                    'target' => '_blank',
                ]) ?>
                
                <?= Html::a('Statistiche', ['stats'], [
                    'class' => 'btn btn-info',
                ]) ?>
            </p>
        </div>
        <div class="col-md-6 text-right">
            <div class="btn-group">
                <?= Html::a('Oggi', ['index', 'ActivityLogSearch[date_from]' => date('Y-m-d'), 'ActivityLogSearch[date_to]' => date('Y-m-d')], ['class' => 'btn btn-outline-primary btn-sm']) ?>
                <?= Html::a('Questa Settimana', ['index', 'ActivityLogSearch[date_from]' => date('Y-m-d', strtotime('-7 days')), 'ActivityLogSearch[date_to]' => date('Y-m-d')], ['class' => 'btn btn-outline-primary btn-sm']) ?>
                <?= Html::a('Questo Mese', ['index', 'ActivityLogSearch[date_from]' => date('Y-m-01'), 'ActivityLogSearch[date_to]' => date('Y-m-d')], ['class' => 'btn btn-outline-primary btn-sm']) ?>
            </div>
        </div>
    </div>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'attribute' => 'id',
                'options' => ['width' => '80'],
            ],
            [
                'attribute' => 'created_at',
                'label' => 'Data/Ora',
                'format' => 'datetime',
                'options' => ['width' => '160'],
                'filter' => false,
            ],
            [
                'attribute' => 'user_id',
                'label' => 'Utente',
                'value' => function ($model) {
                    return $model->getUserName();
                },
                'filter' => Html::activeDropDownList($searchModel, 'user_id', ['' => 'Tutti'] + $users, ['class' => 'form-control']),
                'options' => ['width' => '150'],
            ],
            [
                'attribute' => 'action',
                'label' => 'Azione',
                'value' => function ($model) {
                    $class = '';
                    switch ($model->action) {
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
                    return Html::tag('span', $model->getActionDescription(), ['class' => $class]);
                },
                'format' => 'raw',
                'filter' => Html::activeDropDownList($searchModel, 'action', ['' => 'Tutte'] + $actions, ['class' => 'form-control']),
                'options' => ['width' => '100'],
            ],
            [
                'attribute' => 'entity_name',
                'label' => 'Entità',
                'value' => function ($model) {
                    return ActivityLogHelper::getEntityLabel($model->entity_name);
                },
                'filter' => Html::activeDropDownList($searchModel, 'entity_name', ['' => 'Tutte'] + array_combine($entities, $entities), ['class' => 'form-control']),
                'options' => ['width' => '120'],
            ],
            [
                'attribute' => 'entity_id',
                'label' => 'ID',
                'options' => ['width' => '80'],
            ],
            [
                'label' => 'Modifiche',
                'value' => function ($model) {
                    if ($model->action === ActivityLog::ACTION_UPDATE) {
                        $changes = ActivityLogHelper::formatChanges(
                            $model->getOldValuesArray(),
                            $model->getNewValuesArray()
                        );
                        return $changes ? Html::tag('small', implode('<br>', array_slice($changes, 0, 3)), ['class' => 'text-muted']) : '-';
                    } elseif ($model->action === ActivityLog::ACTION_CREATE) {
                        $newValues = $model->getNewValuesArray();
                        if (empty($newValues)) {
                            return '-';
                        }
                        $summary = [];
                        foreach (array_slice($newValues, 0, 2) as $key => $value) {
                            $summary[] = "<strong>{$key}:</strong> " . Html::encode(is_array($value) ? json_encode($value) : $value);
                        }
                        return Html::tag('small', implode('<br>', $summary), ['class' => 'text-muted']);
                    } else {
                        return '-';
                    }
                },
                'format' => 'raw',
                'options' => ['width' => '300'],
            ],
            [
                'attribute' => 'ip_address',
                'label' => 'IP',
                'options' => ['width' => '120'],
            ],
            [
                'attribute' => 'date_from',
                'label' => 'Da',
                'filter' => Html::activeInput('date', $searchModel, 'date_from', ['class' => 'form-control']),
                'format' => 'raw',
                'value' => '',
                'visible' => false,
            ],
            [
                'attribute' => 'date_to',
                'label' => 'A',
                'filter' => Html::activeInput('date', $searchModel, 'date_to', ['class' => 'form-control']),
                'format' => 'raw',
                'value' => '',
                'visible' => false,
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {entity} {delete}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'title' => 'Visualizza',
                            'class' => 'btn btn-sm btn-outline-primary',
                        ]);
                    },
                    'entity' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-list"></i>', ['entity', 'entity' => $model->entity_name, 'id' => $model->entity_id], [
                            'title' => 'Vedi tutti i log per questa entità',
                            'class' => 'btn btn-sm btn-outline-info',
                        ]);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'title' => 'Elimina',
                            'class' => 'btn btn-sm btn-outline-danger',
                            'data' => [
                                'confirm' => 'Eliminare questo log?',
                                'method' => 'post',
                            ],
                        ]);
                    },
                ],
                'options' => ['width' => '120'],
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

    <!-- Filtri date in modal -->
    <div class="modal fade" id="dateFilterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtro Date</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?= Html::beginForm(['index'], 'get', ['id' => 'date-filter-form']) ?>
                    <div class="row">
                        <div class="col-md-6">
                            <label>Data Inizio</label>
                            <?= Html::input('date', 'ActivityLogSearch[date_from]', $searchModel->date_from, ['class' => 'form-control']) ?>
                        </div>
                        <div class="col-md-6">
                            <label>Data Fine</label>
                            <?= Html::input('date', 'ActivityLogSearch[date_to]', $searchModel->date_to, ['class' => 'form-control']) ?>
                        </div>
                    </div>
                    <?= Html::endForm() ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
                    <button type="button" class="btn btn-primary" onclick="$('#date-filter-form').submit()">Applica Filtro</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
$(document).ready(function() {
    // Auto-refresh ogni 30 secondi
    setInterval(function() {
        $.pjax.reload({container: '#pjax-grid-activity-log'});
    }, 30000);
    
    // Tooltip per i campi troncati
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<style>
.badge {
    font-size: 0.8em;
}

.activity-log-index .grid-view {
    overflow-x: auto;
}

.activity-log-index .grid-view th,
.activity-log-index .grid-view td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.activity-log-index .grid-view td:nth-child(7) {
    white-space: normal;
    word-wrap: break-word;
}

.btn-group .btn {
    margin-right: 5px;
}
</style> 