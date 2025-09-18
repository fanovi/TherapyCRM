<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use common\models\District;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $search string */

$this->title = 'Gestione Distretti';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="districts-index">

    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6"><?= Html::encode($this->title) ?></h1>

    <div class="flex flex-wrap gap-4 mb-6">
        <div class="flex-1">
            <?= Html::a('Nuovo Distretto', ['create'], [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
            ]) ?>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
        <?= Html::beginForm(['index'], 'get', ['class' => 'flex gap-4 items-end']) ?>
        <div class="flex-1">
            <?= Html::textInput('search', $search, [
                'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300',
                'placeholder' => 'Cerca per codice, nome o riferimento ASL...'
            ]) ?>
        </div>
        <div class="flex items-center gap-2">
            <?= Html::submitButton('Cerca', ['class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']) ?>
            <?= Html::a('Reset', ['index'], ['class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']) ?>
        </div>
        <?= Html::endForm() ?>
    </div>

    <?php Pjax::begin(); ?>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700'],
            'headerRowOptions' => ['class' => 'bg-gray-50 dark:bg-gray-700'],
            'rowOptions' => ['class' => 'hover:bg-gray-50 dark:hover:bg-gray-600'],
            'columns' => [
                [
                    'attribute' => 'code',
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100'],
                ],
                [
                    'attribute' => 'name',
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'],
                ],
                [
                    'attribute' => 'asl_reference',
                    'label' => 'Riferimento ASL',
                    'value' => function ($model) {
                        return $model->asl_reference ?: '-';
                    },
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'],
                ],
                [
                    'label' => 'Pazienti',
                    'value' => function ($model) {
                        $count = $model->getPatients()->count();
                        return $count > 0 ? $count : '-';
                    },
                    'headerOptions' => ['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider'],
                    'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view} {update} {delete}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return Html::a('<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>', $url, [
                                'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-2',
                                'title' => 'Visualizza',
                            ]);
                        },
                        'update' => function ($url, $model) {
                            return Html::a('<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', $url, [
                                'class' => 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-2',
                                'title' => 'Modifica',
                            ]);
                        },
                        'delete' => function ($url, $model) {
                            return Html::a('<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>', $url, [
                                'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300',
                                'title' => 'Elimina',
                                'data' => [
                                    'confirm' => 'Sei sicuro di voler eliminare questo distretto?',
                                    'method' => 'post',
                                ],
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

<script>
$(document).ready(function() {
    // Gestione AJAX per eliminazione
    $(document).on('click', 'a[data-method="post"]', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var message = $link.data('confirm');
        
        if (message && !confirm(message)) {
            return;
        }
        
        $.ajax({
            url: $link.attr('href'),
            type: 'POST',
            data: {
                '_csrf': $('meta[name=csrf-token]').attr('content')
            },
            success: function(response) {
                if (response.status === 'success') {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        location.reload();
                    }
                } else {
                    alert(response.message || 'Errore durante l\'operazione');
                }
            },
            error: function() {
                alert('Errore durante l\'operazione');
            }
        });
    });
});
</script>
