<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use common\models\ActivityLog;
use common\helpers\ActivityLogHelper;
use yii\widgets\ActiveForm;

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

    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6"><?= Html::encode($this->title) ?></h1>

    <div class="flex flex-wrap gap-4 mb-6">
        <div class="flex-1">
            <?= Html::a('Pulizia Log', ['cleanup'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500',
                'data' => [
                    'confirm' => 'Eliminare i log più vecchi di 90 giorni?',
                    'method' => 'post',
                ],
            ]) ?>
            
            <?= Html::a('Esporta Excel', ['export', 'ActivityLogSearch' => Yii::$app->request->queryParams['ActivityLogSearch'] ?? []], [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 ml-2',
                'target' => '_blank',
            ]) ?>
            
            <?= Html::a('Statistiche', ['stats'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ml-2',
            ]) ?>
        </div>
        <div class="flex gap-2">
            <?= Html::a('Oggi', ['index', 'ActivityLogSearch[date_from]' => date('Y-m-d'), 'ActivityLogSearch[date_to]' => date('Y-m-d')], [
                'class' => 'inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
            ]) ?>
            <?= Html::a('Questa Settimana', ['index', 'ActivityLogSearch[date_from]' => date('Y-m-d', strtotime('-7 days')), 'ActivityLogSearch[date_to]' => date('Y-m-d')], [
                'class' => 'inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
            ]) ?>
            <?= Html::a('Questo Mese', ['index', 'ActivityLogSearch[date_from]' => date('Y-m-01'), 'ActivityLogSearch[date_to]' => date('Y-m-d')], [
                'class' => 'inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
            ]) ?>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
        <?php $form = ActiveForm::begin(['method' => 'get']); ?>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <?= Html::dropDownList('user_id', $filters['user_id'], $users, [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                    'prompt' => 'Tutti gli utenti'
                ]) ?>
            </div>
            <div>
                <?= Html::dropDownList('action', $filters['action'], $actions, [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                    'prompt' => 'Tutte le azioni'
                ]) ?>
            </div>
            <div>
                <?= Html::dropDownList('entity_name', $filters['entity_name'], $entities, [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                    'prompt' => 'Tutte le entità'
                ]) ?>
            </div>
            <div>
                <?= Html::textInput('search', $filters['search'], [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                    'placeholder' => 'Cerca...'
                ]) ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <div>
                <?= Html::input('date', 'date_from', $filters['date_from'], [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                    'placeholder' => 'Data da'
                ]) ?>
            </div>
            <div>
                <?= Html::input('date', 'date_to', $filters['date_to'], [
                    'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                    'placeholder' => 'Data a'
                ]) ?>
            </div>
            <div class="flex items-center">
                <label class="inline-flex items-center">
                    <?= Html::checkbox('parent_only', $filters['parent_only'], [
                        'class' => 'rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500',
                        'id' => 'parent-only'
                    ]) ?>
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Solo log principali</span>
                </label>
            </div>
            <div class="flex items-center gap-2">
                <?= Html::submitButton('Filtra', ['class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']) ?>
                <?= Html::a('Reset', ['index'], ['class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>

    <?php Pjax::begin(['id' => 'pjax-grid-activity-log']); ?>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700'],
            'headerRowOptions' => ['class' => 'bg-gray-50 dark:bg-gray-700'],
            'rowOptions' => ['class' => 'hover:bg-gray-50 dark:hover:bg-gray-600'],
            'pager' => [
                'options' => ['class' => 'flex items-center justify-center space-x-1 py-4'],
                'linkOptions' => ['class' => 'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'],
                'activePageCssClass' => 'active-page bg-brand-500 text-white border-brand-500 hover:bg-brand-600',
                'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                'prevPageLabel' => '&laquo;',
                'nextPageLabel' => '&raquo;',
            ],
            'columns' => [
                [
                    'attribute' => 'created_at',
                    'format' => ['datetime', 'php:d/m/Y H:i:s'],
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'],
                ],
                [
                    'attribute' => 'user_id',
                    'value' => 'user.username',
                    'label' => 'Utente',
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'],
                ],
                [
                    'attribute' => 'action',
                    'value' => function ($model) use ($actions) {
                        return $actions[$model->action] ?? $model->action;
                    },
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'],
                ],
                [
                    'attribute' => 'entity_name',
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'],
                ],
                [
                    'attribute' => 'entity_id',
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'],
                ],
                [
                    'attribute' => 'parent_log_id',
                    'value' => function ($model) {
                        return $model->parent_log_id ? 
                            Html::a('Log Padre #' . $model->parent_log_id, ['view', 'id' => $model->parent_log_id], ['class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300']) : 
                            null;
                    },
                    'format' => 'raw',
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return Html::a('<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>', $url, [
                                'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300',
                                'title' => 'Visualizza',
                            ]);
                        },
                    ],
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'],
                ],
            ],
        ]); ?>
    </div>

    <?php Pjax::end(); ?>

</div>

<style>
/* Evidenziazione pagina attiva nel paginatore */
/* Selettore principale usando la classe active-page */
.active-page {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
    border-color: #3b82f6 !important;
    font-weight: 600 !important;
    box-shadow: 0 2px 4px 0 rgba(59, 130, 246, 0.3), 0 1px 2px 0 rgba(59, 130, 246, 0.2) !important;
    transform: scale(1.05);
    transition: all 0.2s ease-in-out;
}

a.active-page,
span.active-page,
a.active-page.bg-brand-500,
span.active-page.bg-brand-500 {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
    border-color: #3b82f6 !important;
    font-weight: 600 !important;
    box-shadow: 0 2px 4px 0 rgba(59, 130, 246, 0.3), 0 1px 2px 0 rgba(59, 130, 246, 0.2) !important;
    transform: scale(1.05);
    transition: all 0.2s ease-in-out;
}

a.active-page:hover,
span.active-page:hover,
a.active-page.bg-brand-500:hover,
span.active-page.bg-brand-500:hover {
    background-color: #2563eb !important;
    border-color: #2563eb !important;
    box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4), 0 2px 4px -1px rgba(59, 130, 246, 0.3) !important;
    transform: scale(1.08);
}

/* Fallback per selettori con bg-brand-500 */
a.bg-brand-500,
span.bg-brand-500 {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
    border-color: #3b82f6 !important;
    font-weight: 600 !important;
    box-shadow: 0 2px 4px 0 rgba(59, 130, 246, 0.3), 0 1px 2px 0 rgba(59, 130, 246, 0.2) !important;
}

.dark a.active-page,
.dark span.active-page,
.dark a.bg-brand-500,
.dark span.bg-brand-500 {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
    border-color: #3b82f6 !important;
}

.dark a.active-page:hover,
.dark span.active-page:hover,
.dark a.bg-brand-500:hover,
.dark span.bg-brand-500:hover {
    background-color: #2563eb !important;
    border-color: #2563eb !important;
}
</style>

<script>
$(document).ready(function() {
    // Funzione per evidenziare la pagina attiva
    function highlightActivePage() {
        // Cerca link con classe active-page o bg-brand-500
        $('a.active-page, span.active-page, a.bg-brand-500, span.bg-brand-500').each(function() {
            $(this).css({
                'background-color': '#3b82f6',
                'color': '#ffffff',
                'border-color': '#3b82f6',
                'font-weight': '600',
                'box-shadow': '0 2px 4px 0 rgba(59, 130, 246, 0.3), 0 1px 2px 0 rgba(59, 130, 246, 0.2)',
                'transform': 'scale(1.05)'
            });
        });
    }
    
    // Applica subito
    highlightActivePage();
    
    // Applica dopo ogni reload Pjax
    $(document).on('pjax:complete', function() {
        highlightActivePage();
    });
    
    // Auto-refresh ogni 30 secondi
    setInterval(function() {
        $.pjax.reload({container: '#pjax-grid-activity-log'});
    }, 30000);
});
</script> 