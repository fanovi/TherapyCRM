<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use common\models\Holiday;

/** @var yii\web\View $this */
/** @var frontend\models\HolidaySearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Giorni Festivi';
$this->params['breadcrumbs'][] = $this->title;

$weekdayShort = [1 => 'lun', 2 => 'mar', 3 => 'mer', 4 => 'gio', 5 => 'ven', 6 => 'sab', 7 => 'dom'];
$kindBadges = [
    Holiday::KIND_RECURRING => 'bg-brand-50 text-brand-500',
    Holiday::KIND_ONE_OFF => 'bg-warning-50 text-warning-700',
    Holiday::KIND_COMPUTED => 'bg-blue-light-50 text-blue-light-500',
    Holiday::KIND_WEEKDAY => 'bg-gray-100 text-gray-700',
];
$filterInputClass = 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white';
?>

<style>
    .holiday-row-inactive td { opacity: .55; }
    .holiday-badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 9999px; font-size: .75rem; font-weight: 500; white-space: nowrap; }
</style>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>

            <?php if (Yii::$app->user->can('create_holiday')): ?>
            <div>
                <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Nuova Festività',
                    ['create'], [
                    'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600'
                ]) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Regole -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white px-5 py-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <h3 class="text-sm font-medium text-gray-800 dark:text-white/90">Come funzionano i giorni di chiusura</h3>
        <ul class="mt-2 space-y-1 text-sm text-gray-500 dark:text-gray-400">
            <li>• Nei giorni di chiusura non si possono creare né spostare appuntamenti: vale per ogni tipo di appuntamento e per ogni utente.</li>
            <li>• Una chiusura non può essere inserita, spostata o riattivata su un giorno che ha appuntamenti programmati: vanno prima spostati o annullati dal calendario.</li>
            <li>• Gli appuntamenti già presenti in un giorno diventato chiuso restano visibili e gestibili.</li>
            <li>• Per sospendere una chiusura senza perderla, disattivala dalla modifica invece di eliminarla.</li>
        </ul>
    </div>

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Elenco giorni di chiusura
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Festività ricorrenti, chiusure una tantum, festività calcolate e chiusure settimanali della struttura.
            </p>
        </div>

        <!-- Filter Controls -->
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 flex justify-between items-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <?= 'Trovati ' . $dataProvider->totalCount . ' giorni di chiusura' ?>
            </div>
            <div class="flex gap-2">
                <?= Html::a('Reset Filtri', ['index'], [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 dark:hover:bg-white/5'
                ]) ?>
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'holiday-grid-pjax']); ?>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'options' => ['class' => 'min-w-full'],
                'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                'rowOptions' => function ($model) {
                    return ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50' . ($model->is_active ? '' : ' holiday-row-inactive')];
                },
                'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                'emptyText' => 'Nessun giorno di chiusura trovato.',
                'columns' => [
                    [
                        'attribute' => 'name',
                        'label' => 'Nome',
                        'format' => 'raw',
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4 font-medium text-gray-900 dark:text-white'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => $filterInputClass, 'placeholder' => 'Filtra nome...'],
                        'value' => function (Holiday $model) {
                            $html = Html::encode($model->name);
                            if ($model->is_national) {
                                $html .= ' <span class="holiday-badge bg-success-50 text-success-700" title="Riga del seed nazionale: modificabile ed eliminabile come le altre">Nazionale</span>';
                            }
                            if ($model->notes) {
                                $html .= '<div class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-400">' . Html::encode($model->notes) . '</div>';
                            }
                            return $html;
                        },
                    ],
                    [
                        'attribute' => 'kind',
                        'label' => 'Tipo',
                        'format' => 'raw',
                        'enableSorting' => false,
                        'filter' => Holiday::getKindOptions(),
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => $filterInputClass, 'prompt' => 'Tutti'],
                        'value' => function (Holiday $model) use ($kindBadges) {
                            $kind = $model->getKind();
                            return '<span class="holiday-badge ' . $kindBadges[$kind] . '">' . Html::encode(Holiday::getKindOptions()[$kind]) . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'month',
                        'label' => 'Ricorrenza',
                        'enableSorting' => false,
                        'filter' => Holiday::getMonthOptions(),
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => $filterInputClass, 'prompt' => 'Tutti i mesi'],
                        'value' => function (Holiday $model) {
                            return $model->getOccurrenceLabel();
                        },
                    ],
                    [
                        'attribute' => 'year',
                        'label' => 'Anno',
                        'enableSorting' => false,
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => $filterInputClass, 'placeholder' => 'es. 2027'],
                        'value' => function (Holiday $model) {
                            return (int)$model->year === 0 ? 'Ogni anno' : (string)$model->year;
                        },
                    ],
                    [
                        'label' => 'Prossima occorrenza',
                        'format' => 'raw',
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'value' => function (Holiday $model) use ($weekdayShort) {
                            if ($model->getClosedWeekday() !== null) {
                                return Html::encode($model->getOccurrenceLabel());
                            }
                            $next = $model->getNextOccurrence();
                            if ($next === null) {
                                return '<span class="text-gray-400">Passata</span>';
                            }
                            $dt = new \DateTimeImmutable($next);
                            return $weekdayShort[(int)$dt->format('N')] . ' ' . $dt->format('d/m/Y');
                        },
                    ],
                    [
                        'attribute' => 'is_active',
                        'label' => 'Stato',
                        'format' => 'raw',
                        'enableSorting' => false,
                        'filter' => [1 => 'Attivo', 0 => 'Disattivo'],
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => $filterInputClass, 'prompt' => 'Tutti'],
                        'value' => function (Holiday $model) {
                            return $model->is_active
                                ? '<span class="holiday-badge bg-success-50 text-success-700">Attivo</span>'
                                : '<span class="holiday-badge bg-gray-100 text-gray-600">Disattivo</span>';
                        },
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Azioni',
                        'headerOptions' => ['class' => 'px-4 py-3'],
                        'contentOptions' => ['class' => 'px-4 py-4', 'style' => 'white-space: nowrap'],
                        'template' => '{update} {delete}',
                        'buttons' => [
                            'update' => function ($url, Holiday $model) {
                                if (!Yii::$app->user->can('update_holiday')) {
                                    return '';
                                }
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                                    $url, [
                                    'title' => 'Modifica',
                                    'class' => 'text-warning-600 mr-2 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-white/5',
                                    'data-pjax' => '0',
                                ]);
                            },
                            'delete' => function ($url, Holiday $model) {
                                if (!Yii::$app->user->can('delete_holiday')) {
                                    return '';
                                }
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                                    $url, [
                                    'title' => 'Elimina',
                                    'class' => 'text-error-600 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-white/5',
                                    'data' => [
                                        'confirm' => 'Eliminare il giorno di chiusura "' . $model->name . '"? Da quel momento il giorno tornerà disponibile per nuovi appuntamenti. Per sospenderlo senza perderlo, disattivalo dalla modifica.',
                                        'method' => 'post',
                                    ],
                                    'data-pjax' => '0',
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
