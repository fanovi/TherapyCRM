<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\data\ArrayDataProvider $dataProvider */
/** @var int $totalCount */
/** @var int $absentCount */
/** @var int $presentCount */
/** @var string $date */
/** @var string|null $groupName */

$this->title = 'Riepilogo Giornaliero Terapisti';
$this->params['breadcrumbs'][] = ['label' => 'Assenze', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$formattedDate = Yii::$app->formatter->asDate($date, 'long');
$isToday = $date === date('Y-m-d');
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Header -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Stato presenze terapisti del <?= Html::encode($formattedDate) ?><?= $groupName ? ' &mdash; gruppo <strong>' . Html::encode($groupName) . '</strong>' : '' ?>.
                </p>
            </div>

            <!-- Date picker -->
            <form method="get" action="<?= Url::to(['absence/daily']) ?>" class="flex items-center gap-2">
                <?php if (!$isToday): ?>
                    <?= Html::a('Oggi', ['absence/daily'], [
                        'class' => 'inline-flex items-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                    ]) ?>
                <?php endif; ?>
                <input
                    type="date"
                    id="date"
                    name="date"
                    value="<?= Html::encode($date) ?>"
                    onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-brand-300 focus:ring-brand-500/10 focus:ring-3 focus:outline-hidden"
                />
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 mb-6 md:gap-6">
        <!-- Presenti -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-success-50 dark:bg-success-500/15">
                <svg class="text-success-600 dark:text-success-400" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12l2 2 4-4"/>
                    <circle cx="12" cy="12" r="10"/>
                </svg>
            </div>
            <div class="mt-5 flex items-end justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Presenti</span>
                    <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?= $presentCount ?>
                    </h4>
                </div>
                <?php if ($totalCount > 0): ?>
                <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                    <?= round(($presentCount / $totalCount) * 100) ?>%
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assenti -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-error-50 dark:bg-error-500/15">
                <svg class="text-error-600 dark:text-error-400" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M15 9l-6 6"/>
                    <path d="M9 9l6 6"/>
                </svg>
            </div>
            <div class="mt-5 flex items-end justify-between">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Assenti</span>
                    <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?= $absentCount ?>
                    </h4>
                </div>
                <?php if ($totalCount > 0): ?>
                <span class="flex items-center gap-1 rounded-full bg-error-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">
                    <?= round(($absentCount / $totalCount) * 100) ?>%
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Totale -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                </svg>
            </div>
            <div class="mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Totale Terapisti</span>
                    <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">
                        <?= $totalCount ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Card -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Elenco Terapisti
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Stato presenze del <?= Html::encode($formattedDate) ?>.
            </p>
        </div>

        <!-- Toolbar -->
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 flex justify-between items-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <?= 'Trovati ' . $totalCount . ' terapisti' ?>
            </div>
            <div class="flex gap-2">
                <?= Html::button('Aggiorna', [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-brand-600 border border-transparent rounded-md shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                    'onclick' => '$.pjax.reload({container:"#daily-presence-pjax"});'
                ]) ?>
            </div>
        </div>

        <!-- GridView -->
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'daily-presence-pjax']); ?>

            <?php if ($totalCount === 0): ?>
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nessun terapista trovato.</p>
                </div>
            <?php else: ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'options' => ['class' => 'min-w-full'],
                    'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                    'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 sticky top-0'],
                    'rowOptions' => function ($row) {
                        $base = 'border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600';
                        $bg = $row['status'] === 'absent'
                            ? 'bg-red-50/50 dark:bg-red-900/10'
                            : 'bg-white dark:bg-gray-800';
                        return ['class' => $bg . ' ' . $base];
                    },
                    'columns' => [
                        [
                            'attribute' => 'full_name',
                            'label' => 'Terapista',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[240px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'format' => 'raw',
                            'value' => function ($row) {
                                $initials = mb_strtoupper(
                                    mb_substr($row['first_name'], 0, 1) . mb_substr($row['last_name'], 0, 1)
                                );
                                $color = Html::encode($row['calendar_color']);
                                $name = Html::encode($row['last_name'] . ' ' . $row['first_name']);
                                return '<div class="flex items-center gap-3">'
                                    . '<div class="flex-shrink-0 flex items-center justify-center h-9 w-9 rounded-full text-white text-xs font-bold" style="background-color: ' . $color . ';">'
                                    . Html::encode($initials)
                                    . '</div>'
                                    . '<span class="font-medium text-gray-900 dark:text-white">' . $name . '</span>'
                                    . '</div>';
                            },
                        ],
                        [
                            'attribute' => 'specialization',
                            'label' => 'Specializzazione',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[180px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300'],
                        ],
                        [
                            'attribute' => 'status',
                            'label' => 'Stato',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'format' => 'raw',
                            'value' => function ($row) {
                                if ($row['status'] === 'absent') {
                                    return '<span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-900/50 dark:text-red-300">'
                                        . '<span class="inline-block w-1.5 h-1.5 rounded-full bg-red-500"></span>Assente</span>';
                                }
                                return '<span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/50 dark:text-green-300">'
                                    . '<span class="inline-block w-1.5 h-1.5 rounded-full bg-green-500"></span>Presente</span>';
                            },
                        ],
                        [
                            'label' => 'Tipo Assenza',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[160px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'format' => 'raw',
                            'value' => function ($row) {
                                if ($row['status'] !== 'absent') {
                                    return '<span class="text-xs text-gray-400">&mdash;</span>';
                                }
                                return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-200 dark:text-amber-900">'
                                    . Html::encode($row['absence_type'])
                                    . '</span>';
                            },
                        ],
                        [
                            'label' => 'Periodo Assenza',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[180px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300'],
                            'format' => 'raw',
                            'value' => function ($row) {
                                if ($row['status'] !== 'absent' || !$row['absence_start']) {
                                    return '<span class="text-xs text-gray-400">&mdash;</span>';
                                }
                                $start = Yii::$app->formatter->asDate($row['absence_start'], 'php:d/m/Y');
                                $end = Yii::$app->formatter->asDate($row['absence_end'], 'php:d/m/Y');
                                return Html::encode($start) . ' &rarr; ' . Html::encode($end);
                            },
                        ],
                        [
                            'label' => 'Azioni',
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                            'format' => 'raw',
                            'value' => function ($row) {
                                if ($row['status'] !== 'absent' || !$row['absence_id']) {
                                    return '<span class="text-xs text-gray-400">&mdash;</span>';
                                }
                                if (!Yii::$app->user->can('view_absence')) {
                                    return '<span class="text-xs text-gray-400">&mdash;</span>';
                                }
                                return Html::a(
                                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                                    ['view', 'id' => $row['absence_id']],
                                    [
                                        'title' => 'Visualizza assenza',
                                        'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20',
                                    ]
                                );
                            },
                        ],
                    ],
                ]); ?>
            <?php endif; ?>

            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
