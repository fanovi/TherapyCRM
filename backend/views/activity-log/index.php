<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use common\models\ActivityLog;
use common\helpers\ActivityLogHelper;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $searchModel backend\controllers\ActivityLogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $users array */
/* @var $entities array */
/* @var $actions array */
/* @var $filters array */

$this->title = 'Log Attività';
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

    <div class="card mb-4">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['method' => 'get']); ?>
            <div class="row">
                <div class="col-md-3">
                    <?= Html::dropDownList('user_id', $filters['user_id'], $users, [
                        'class' => 'form-control',
                        'prompt' => 'Tutti gli utenti'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= Html::dropDownList('action', $filters['action'], $actions, [
                        'class' => 'form-control',
                        'prompt' => 'Tutte le azioni'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= Html::dropDownList('entity_name', $filters['entity_name'], $entities, [
                        'class' => 'form-control',
                        'prompt' => 'Tutte le entità'
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= Html::textInput('search', $filters['search'], [
                        'class' => 'form-control',
                        'placeholder' => 'Cerca...'
                    ]) ?>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-3">
                    <?= DatePicker::widget([
                        'name' => 'date_from',
                        'value' => $filters['date_from'],
                        'options' => ['placeholder' => 'Data da'],
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'yyyy-mm-dd'
                        ]
                    ]); ?>
                </div>
                <div class="col-md-3">
                    <?= DatePicker::widget([
                        'name' => 'date_to',
                        'value' => $filters['date_to'],
                        'options' => ['placeholder' => 'Data a'],
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'yyyy-mm-dd'
                        ]
                    ]); ?>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <?= Html::checkbox('parent_only', $filters['parent_only'], [
                            'class' => 'form-check-input',
                            'id' => 'parent-only'
                        ]) ?>
                        <?= Html::label('Solo log principali', 'parent-only', ['class' => 'form-check-label']) ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <?= Html::submitButton('Filtra', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('Reset', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:d/m/Y H:i:s'],
                'headerOptions' => ['style' => 'width: 150px;'],
            ],
            [
                'attribute' => 'user_id',
                'value' => 'user.username',
                'label' => 'Utente',
                'headerOptions' => ['style' => 'width: 150px;'],
            ],
            [
                'attribute' => 'action',
                'value' => function ($model) use ($actions) {
                    return $actions[$model->action] ?? $model->action;
                },
                'headerOptions' => ['style' => 'width: 100px;'],
            ],
            'entity_name',
            [
                'attribute' => 'entity_id',
                'headerOptions' => ['style' => 'width: 80px;'],
            ],
            [
                'attribute' => 'parent_log_id',
                'value' => function ($model) {
                    return $model->parent_log_id ? 
                        Html::a('Log Padre #' . $model->parent_log_id, ['view', 'id' => $model->parent_log_id]) : 
                        null;
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 120px;'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view}',
                'headerOptions' => ['style' => 'width: 50px;'],
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

.activity-log-index .card { background-color: #f8f9fa; }
.activity-log-index .form-control { margin-bottom: 10px; }
.activity-log-index .btn { margin-right: 5px; }
</style> 