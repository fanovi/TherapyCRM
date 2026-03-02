<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var frontend\models\CoordinatorGroupSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Gruppi Coordinatori';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <!-- Action Button -->
            <?php if (Yii::$app->user->can('create_coordinator_group')): ?>
            <div>
                <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Nuovo Gruppo', 
                    ['create'], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800'
                ]) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Lista Gruppi Coordinatori
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestisci i gruppi di terapisti assegnati ai coordinatori.
            </p>
        </div>
        
        <!-- Filter Controls -->
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 flex justify-between items-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <?= 'Trovati ' . $dataProvider->totalCount . ' gruppi coordinatori' ?>
            </div>
            <div class="flex gap-2">
                <?= Html::a('Reset Filtri', ['index'], [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
                <?= Html::button('Aggiorna', [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-brand-600 border border-transparent rounded-md shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                    'onclick' => '$.pjax.reload({container:"#coordinator-group-grid-pjax"});'
                ]) ?>
            </div>
        </div>
        
        <!-- Scrollable Table Container -->
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'coordinator-group-grid-pjax']); ?>
            
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'options' => ['class' => 'min-w-full'],
                'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0'],
                'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                'columns' => [
                    [
                        'attribute' => 'name',
                        'label' => 'Nome Gruppo',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 font-medium text-gray-900 dark:text-white'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Filtra nome...'],
                    ],
                    [
                        'attribute' => 'coordinator_name',
                        'label' => 'Coordinatore',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Filtra coordinatore...'],
                        'value' => function($model) {
                            return $model->coordinator && $model->coordinator->profile ? 
                                $model->coordinator->profile->last_name . ' ' . $model->coordinator->profile->first_name :
                                ($model->coordinator ? $model->coordinator->email : 'N/D');
                        }
                    ],
                    [
                        'label' => 'Terapisti',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 text-center'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'format' => 'raw',
                        'content' => function($model) {
                            $count = $model->getTherapistCount();
                            $badgeClass = $count > 0 ? 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900' : 'bg-gray-100 text-gray-800 dark:bg-gray-200 dark:text-gray-900';
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $badgeClass . '">' . $count . '</span>';
                        }
                    ],

                    [
                        'attribute' => 'created_at',
                        'label' => 'Creato il',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[130px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 text-sm'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'format' => ['date', 'php:d/m/Y H:i'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Azioni',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[180px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'template' => '{view} {update} {delete}',
                        'urlCreator' => function ($action, $model, $key, $index) {
                            return [$action, 'id' => $model->id];
                        },
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>', 
                                    $url, [
                                    'title' => 'Visualizza gruppo',
                                    'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-2 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20',
                                ]);
                            },

                            'update' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('update_coordinator_group')) return '';
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', 
                                    $url, [
                                    'title' => 'Modifica gruppo',
                                    'class' => 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-2 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20',
                                ]);
                            },
                            'delete' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('delete_coordinator_group')) return '';
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>', 
                                    'javascript:void(0)', [
                                    'title' => 'Elimina gruppo',
                                    'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 delete-group-btn',
                                    'data-group-id' => $model->id,
                                    'data-delete-url' => \yii\helpers\Url::to(['delete', 'id' => $model->id])
                                ]);
                            },
                        ],
                    ],
                ],
            ]); ?>
            
            <?php Pjax::end(); ?>
        </div>
    </div>
    <!-- Content End -->
</div>

<?php
$this->registerJs("
// Event delegation per gestire i click sui bottoni di eliminazione anche dopo Pjax reload
$(document).on('click', '.delete-group-btn', function(e) {
    e.preventDefault();
    
    var \$btn = $(this);
    var groupId = \$btn.data('group-id');
    var deleteUrl = \$btn.data('delete-url');
    
    if (!confirm('Sei sicuro di voler eliminare questo gruppo coordinatore?\\n\\nATTENZIONE: \\n- Tutti i terapisti assegnati verranno automaticamente rimossi dal gruppo\\n- Questa azione non può essere annullata')) {
        return;
    }
    
    // Disable button and show loading
    var originalHtml = \$btn.html();
    \$btn.prop('disabled', true);
    \$btn.html('<svg class=\"animate-spin w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\"><circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle><path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path></svg>');
    
    $.ajax({
        url: deleteUrl,
        type: 'POST',
        data: {
            _csrf: $('meta[name=csrf-token]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                // Show success message
                alert('Gruppo coordinatore eliminato con successo.');
                // Reload the grid
                $.pjax.reload({container:'#coordinator-group-grid-pjax'});
            } else {
                // Re-enable button
                \$btn.prop('disabled', false);
                \$btn.html(originalHtml);
                alert(response.message || 'Errore durante l\\'eliminazione del gruppo.');
            }
        },
        error: function(xhr, status, error) {
            // Re-enable button
            \$btn.prop('disabled', false);
            \$btn.html(originalHtml);
            
            var errorMessage = 'Errore durante l\\'eliminazione del gruppo.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // Keep default error message
                }
            }
            
            alert(errorMessage);
            console.error('Delete error:', error, xhr.responseText);
        }
    });
});
");
?> 