<?php
use yii\helpers\Html;

$this->title = 'Impostazioni Calendario';
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900"><?= Html::encode($this->title) ?></h1>
        <p class="mt-1 text-sm text-gray-500">Configura il range temporale visibile nel calendario dei pazienti nell'app mobile.</p>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="rounded-md bg-green-50 p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-green-400 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="ml-3 text-sm font-medium text-green-800"><?= Yii::$app->session->getFlash('success') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?= Html::beginForm(['system-setting/update-calendar'], 'post') ?>

    <!-- Range Passato -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Range passato</h2>
            <p class="text-sm text-gray-500 mt-1">Quanto indietro nel tempo i pazienti possono navigare nel calendario. Valore 0 = solo oggi.</p>
        </div>
        <div class="px-6 py-4">
            <div class="flex items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantit&agrave;</label>
                    <input type="number" name="patient_past_range_value"
                           value="<?= Html::encode($settings['patient_past_range_value'] ?? 3) ?>"
                           min="0" max="24"
                           class="mt-1 block w-32 rounded-md border border-gray-300 shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit&agrave;</label>
                    <select name="patient_past_range_unit"
                            class="mt-1 block w-40 rounded-md border border-gray-300 shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="months" <?= ($settings['patient_past_range_unit'] ?? 'months') === 'months' ? 'selected' : '' ?>>Mesi</option>
                        <option value="days" <?= ($settings['patient_past_range_unit'] ?? 'months') === 'days' ? 'selected' : '' ?>>Giorni</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Range Futuro -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Range futuro</h2>
            <p class="text-sm text-gray-500 mt-1">Quanto avanti nel tempo i pazienti possono navigare nel calendario. Valore 0 = solo oggi.</p>
        </div>
        <div class="px-6 py-4">
            <div class="flex items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantit&agrave;</label>
                    <input type="number" name="patient_future_range_value"
                           value="<?= Html::encode($settings['patient_future_range_value'] ?? 3) ?>"
                           min="0" max="24"
                           class="mt-1 block w-32 rounded-md border border-gray-300 shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Unit&agrave;</label>
                    <select name="patient_future_range_unit"
                            class="mt-1 block w-40 rounded-md border border-gray-300 shadow-sm px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="months" <?= ($settings['patient_future_range_unit'] ?? 'months') === 'months' ? 'selected' : '' ?>>Mesi</option>
                        <option value="days" <?= ($settings['patient_future_range_unit'] ?? 'months') === 'days' ? 'selected' : '' ?>>Giorni</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Anteprima -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Anteprima</h2>
            <p class="text-sm text-gray-500 mt-1">Date calcolate con le impostazioni correnti (basate sulla data di oggi).</p>
        </div>
        <div class="px-6 py-4">
            <div class="flex items-center gap-8">
                <div>
                    <span class="block text-sm font-medium text-gray-500">Data minima</span>
                    <span class="text-lg font-semibold text-gray-900"><?= Html::encode($dateLimits['minDate']) ?></span>
                </div>
                <div class="text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>
                <div>
                    <span class="block text-sm font-medium text-gray-500">Data massima</span>
                    <span class="text-lg font-semibold text-gray-900"><?= Html::encode($dateLimits['maxDate']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <?= Html::submitButton('Salva impostazioni', [
            'class' => 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
        ]) ?>
    </div>

    <?= Html::endForm() ?>
</div>
