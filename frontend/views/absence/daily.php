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
?>

<div class="absence-daily">
    <!-- Header -->
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <?= Html::encode($this->title) ?>
            </h2>
            <?php if ($groupName): ?>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Gruppo: <?= Html::encode($groupName) ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Date picker -->
        <form method="get" action="<?= Url::to(['absence/daily']) ?>">
            <div class="flex items-center gap-2">
                <label for="date" class="text-sm font-medium text-gray-700 dark:text-gray-300">Data:</label>
                <input
                    type="date"
                    id="date"
                    name="date"
                    value="<?= Html::encode($date) ?>"
                    onchange="this.form.submit()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                />
            </div>
        </form>
    </div>

    <!-- Summary stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-center dark:border-green-800 dark:bg-green-900">
            <p class="text-2xl font-bold text-green-700 dark:text-green-300"><?= $presentCount ?></p>
            <p class="text-sm text-green-600 dark:text-green-400">Presenti</p>
        </div>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-center dark:border-red-800 dark:bg-red-900">
            <p class="text-2xl font-bold text-red-700 dark:text-red-300"><?= $absentCount ?></p>
            <p class="text-sm text-red-600 dark:text-red-400">Assenti</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-center dark:border-gray-700 dark:bg-gray-800">
            <p class="text-2xl font-bold text-gray-700 dark:text-gray-300"><?= $totalCount ?></p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Totale</p>
        </div>
    </div>

    <!-- Date display -->
    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        Riepilogo per il giorno: <strong><?= Html::encode($formattedDate) ?></strong>
    </p>

    <?php if (empty($therapists)): ?>
        <div class="rounded-lg border border-gray-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800">
            <p class="text-gray-500 dark:text-gray-400">Nessun terapista trovato.</p>
        </div>
    <?php else: ?>
        <!-- Therapist cards grid -->
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
                <div class="relative rounded-lg border <?= $isAbsent ? 'border-red-200' : 'border-gray-300' ?> bg-white px-6 py-5 shadow-sm dark:border-gray-600 dark:bg-gray-800">
                    <div class="flex items-center space-x-3">
                        <!-- Colored circle with initials -->
                        <div class="flex items-center justify-center h-10 w-10 rounded-full text-white text-sm font-bold" style="background-color: <?= Html::encode($calendarColor) ?>;">
                            <?= Html::encode($initials) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?= Html::encode($profile->last_name . ' ' . $profile->first_name) ?>
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= Html::encode($therapist->specialization->name ?? 'N/D') ?>
                            </p>
                        </div>
                        <!-- Status badge -->
                        <?php if ($isAbsent): ?>
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                Assente
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                Presente
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($isAbsent): ?>
                        <div class="mt-3 rounded bg-red-50 px-3 py-2 dark:bg-red-900">
                            <p class="text-xs font-medium text-red-700 dark:text-red-300">
                                <?= Html::encode($absence->getTypeLabel()) ?>
                            </p>
                            <p class="text-xs text-red-600 dark:text-red-400">
                                <?= Yii::$app->formatter->asDate($absence->start_date, 'short') ?>
                                &ndash;
                                <?= Yii::$app->formatter->asDate($absence->end_date, 'short') ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
