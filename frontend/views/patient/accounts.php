<?php

use common\models\AccountPatient;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\AccountSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Account Pazienti';
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$relationshipLabels = AccountPatient::getRelationshipLabels();
?>

<div class="mx-auto max-w-7xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Lista Account Famiglie/Pazienti
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Gestisci gli account e i pazienti collegati a ciascuno.
                    </p>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800">
            <div class="overflow-x-auto">
                <?php Pjax::begin([
                    'id' => 'accounts-pjax',
                    'enablePushState' => false,
                ]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                    'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                    'rowOptions' => ['class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'],
                    'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                    'columns' => [
                        [
                            'attribute' => 'email',
                            'label' => 'Email Account',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[220px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap font-medium'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Cerca email...'],
                            'format' => 'raw',
                            'value' => function ($model) {
                                return Html::a(
                                    Html::encode($model->email),
                                    ['view-account', 'id' => $model->id],
                                    ['class' => 'text-brand-500 hover:text-brand-600 hover:underline']
                                );
                            },
                        ],
                        [
                            'attribute' => 'profile_name',
                            'label' => 'Nome Titolare',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[180px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Cerca nome...'],
                            'value' => function ($model) {
                                if ($model->profile) {
                                    return $model->profile->first_name . ' ' . $model->profile->last_name;
                                }
                                return '-';
                            },
                        ],
                        [
                            'attribute' => 'linked_patients',
                            'label' => 'Pazienti Collegati',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[300px]'],
                            'contentOptions' => ['class' => 'px-6 py-4'],
                            'filter' => false,
                            'format' => 'raw',
                            'value' => function ($model) use ($relationshipLabels) {
                                $accountPatients = $model->accountPatients;
                                if (empty($accountPatients)) {
                                    return '<span class="text-gray-400 italic">Nessun paziente collegato</span>';
                                }

                                $html = '<div class="flex flex-wrap gap-2" id="patients-list-' . $model->id . '">';
                                foreach ($accountPatients as $ap) {
                                    if ($ap->patient) {
                                        $relationLabel = $relationshipLabels[$ap->relationship_type] ?? $ap->relationship_type;
                                        $badgeClass = match($ap->relationship_type) {
                                            'self' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                            'parent' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'tutor' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                        };

                                        $html .= '<span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full ' . $badgeClass . '" data-patient-id="' . $ap->patient_id . '">';
                                        $html .= Html::a(
                                            Html::encode($ap->patient->last_name . ' ' . $ap->patient->first_name),
                                            ['view', 'id' => $ap->patient_id],
                                            ['class' => 'hover:underline', 'data-pjax' => '0']
                                        );
                                        $html .= ' <span class="opacity-60">(' . Html::encode($relationLabel) . ')</span>';
                                        $html .= '<button type="button" class="ml-1 text-red-500 hover:text-red-700 unlink-patient-btn"
                                                    data-user-id="' . $model->id . '"
                                                    data-patient-id="' . $ap->patient_id . '"
                                                    data-patient-name="' . Html::encode($ap->patient->last_name . ' ' . $ap->patient->first_name) . '"
                                                    title="Rimuovi collegamento">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>';
                                        $html .= '</span>';
                                    }
                                }
                                $html .= '</div>';
                                return $html;
                            },
                        ],
                        [
                            'label' => 'N. Pazienti',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[100px] text-center'],
                            'contentOptions' => ['class' => 'px-6 py-4 text-center'],
                            'filter' => false,
                            'value' => function ($model) {
                                $count = count($model->accountPatients);
                                return '<span class="inline-flex items-center justify-center w-8 h-8 text-sm font-semibold rounded-full bg-brand-100 text-brand-800 dark:bg-brand-900 dark:text-brand-300">' . $count . '</span>';
                            },
                            'format' => 'raw',
                        ],
                        [
                            'attribute' => 'created_at',
                            'label' => 'Creato il',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'filter' => false,
                            'value' => function ($model) {
                                return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at, 'php:d/m/Y') : '-';
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => 'Azioni',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[100px] text-center'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap text-center'],
                            'template' => '{view}',
                            'buttons' => [
                                'view' => function ($url, $model) {
                                    return Html::a(
                                        '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>Visualizza',
                                        ['view-account', 'id' => $model->id],
                                        [
                                            'class' => 'inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium text-white bg-brand-500 rounded-lg hover:bg-brand-600 transition-colors',
                                            'title' => 'Visualizza dettaglio account',
                                            'data-pjax' => '0',
                                        ]
                                    );
                                },
                            ],
                        ],
                    ],
                    'pager' => [
                        'options' => ['class' => 'flex items-center justify-center space-x-1 py-4'],
                        'linkOptions' => ['class' => 'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700'],
                        'activePageCssClass' => 'bg-brand-500 text-white border-brand-500 hover:bg-brand-600',
                        'disabledPageCssClass' => 'opacity-50 cursor-not-allowed',
                        'prevPageLabel' => '&laquo;',
                        'nextPageLabel' => '&raquo;',
                    ],
                    'summary' => '<div class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400">Mostrando {begin}-{end} di {totalCount} account</div>',
                    'emptyText' => '<div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">Nessun account paziente trovato.</div>',
                    'emptyTextOptions' => ['class' => ''],
                ]); ?>

                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
    <!-- Content End -->
</div>
