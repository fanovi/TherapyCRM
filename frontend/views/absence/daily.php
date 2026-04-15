<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\Therapist[] $therapists */
/** @var common\models\Absence[] $absences */
/** @var string $date */
/** @var string|null $groupName */

$this->title = 'Presenze Giornaliere';
$this->params['breadcrumbs'][] = ['label' => 'Assenze', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$totalCount = count($therapists);
$absentCount = count($absences);
$presentCount = $totalCount - $absentCount;

$formattedDate = Yii::$app->formatter->asDate($date, 'long');
$isToday = $date === date('Y-m-d');
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                <?= Html::encode($this->title) ?>
            </h2>
            <?php if ($groupName): ?>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Gruppo: <strong><?= Html::encode($groupName) ?></strong>
                </p>
            <?php endif; ?>
        </div>

        <!-- Date picker -->
        <form method="get" action="<?= Url::to(['absence/daily']) ?>">
            <div class="flex items-center gap-3">
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
            </div>
        </form>
    </div>
    <!-- Breadcrumb End -->

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 dark:bg-green-900">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white/90"><?= $presentCount ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Presenti</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 dark:bg-red-900">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white/90"><?= $absentCount ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Assenti</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white/90"><?= $totalCount ?></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Totale Terapisti</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Card -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Riepilogo <?= Html::encode($formattedDate) ?>
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Stato presenze dei terapisti<?= $groupName ? ' del gruppo ' . Html::encode($groupName) : '' ?>.
                </p>
            </div>
            <?php if ($totalCount > 0): ?>
                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-green-500"></span> Presente
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500"></span> Assente
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800">
            <?php if (empty($therapists)): ?>
                <div class="p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Nessun terapista trovato nel tuo gruppo.</p>
                </div>
            <?php else: ?>
                <div class="p-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($therapists as $therapist): ?>
                            <?php
                                $absence = isset($absences[$therapist->id]) ? $absences[$therapist->id] : null;
                                $isAbsent = $absence !== null;
                                $profile = $therapist->user->profile;
                                $initials = '';
                                if ($profile) {
                                    $initials = mb_strtoupper(mb_substr($profile->first_name, 0, 1) . mb_substr($profile->last_name, 0, 1));
                                }
                                $calendarColor = $therapist->calendar_color ?: '#6B7280';
                            ?>
                            <div class="rounded-xl border <?= $isAbsent ? 'border-red-200 dark:border-red-800' : 'border-gray-200 dark:border-gray-700' ?> bg-white p-4 shadow-sm dark:bg-gray-800/50 hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-3">
                                    <!-- Avatar -->
                                    <div class="flex-shrink-0 flex items-center justify-center h-11 w-11 rounded-full text-white text-sm font-bold" style="background-color: <?= Html::encode($calendarColor) ?>;">
                                        <?= Html::encode($initials) ?>
                                    </div>
                                    <!-- Info -->
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            <?= Html::encode($profile->last_name . ' ' . $profile->first_name) ?>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            <?= Html::encode($therapist->specialization->name ?? 'N/D') ?>
                                        </p>
                                    </div>
                                    <!-- Badge -->
                                    <?php if ($isAbsent): ?>
                                        <span class="flex-shrink-0 inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-900/50 dark:text-red-300">
                                            Assente
                                        </span>
                                    <?php else: ?>
                                        <span class="flex-shrink-0 inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/50 dark:text-green-300">
                                            Presente
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($isAbsent): ?>
                                    <div class="mt-3 flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 dark:bg-red-900/30">
                                        <svg class="w-3.5 h-3.5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-red-700 dark:text-red-300">
                                                <?= Html::encode($absence->getTypeLabel()) ?>
                                            </p>
                                            <p class="text-xs text-red-500 dark:text-red-400">
                                                <?= Yii::$app->formatter->asDate($absence->start_date, 'short') ?>
                                                &ndash;
                                                <?= Yii::$app->formatter->asDate($absence->end_date, 'short') ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
