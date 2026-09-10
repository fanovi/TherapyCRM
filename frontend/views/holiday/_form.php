<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use common\models\Holiday;

/** @var yii\web\View $this */
/** @var common\models\Holiday $model */
/** @var common\models\Appointment[] $conflictAppointments */

$inputClass = 'block w-full rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2';
$kindHints = [
    Holiday::KIND_RECURRING => 'Stesso giorno ogni anno, es. 25 dicembre.',
    Holiday::KIND_ONE_OFF => 'Una sola data, es. un ponte o una chiusura straordinaria.',
    Holiday::KIND_COMPUTED => 'Data diversa ogni anno, calcolata dal sistema.',
    Holiday::KIND_WEEKDAY => 'Un giorno della settimana chiuso sempre, es. la domenica.',
];
$days = array_combine(range(1, 31), range(1, 31));
$totalConflicts = array_sum($model->appointmentConflicts);
$canViewCalendar = Yii::$app->user->can('view_calendar');
// Le sezioni non pertinenti partono nascoste lato server: x-show le gestisce
// dopo l'avvio di Alpine, senza dipendere da x-cloak.
$sectionStyle = fn(string $kind) => $model->kind === $kind ? null : 'display: none';
?>

<style>
    .holiday-kind-option { display: flex; gap: .75rem; align-items: flex-start; padding: .875rem 1rem; border: 1px solid #e4e7ec; border-radius: .75rem; cursor: pointer; }
    .holiday-kind-option.is-selected { border-color: #465fff; background: #ecf3ff; }
    .dark .holiday-kind-option { border-color: #344054; }
    .dark .holiday-kind-option.is-selected { background: rgba(70, 95, 255, .12); }
    .holiday-kind-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .75rem; }
    .has-error input, .has-error select, .has-error textarea { border-color: #f04438 !important; }
    .help-block-error { color: #d92d20; font-size: .875rem; margin-top: .25rem; font-weight: 500; }
    .holiday-conflicts td, .holiday-conflicts th { padding: .5rem .75rem; }
</style>

<?= Html::beginTag('div', ['x-data' => '{ kind: ' . Json::encode($model->kind) . ' }']) ?>

    <?php $form = ActiveForm::begin([
        'id' => 'holiday-form',
        'options' => ['class' => 'space-y-6'],
        'fieldConfig' => [
            'options' => ['class' => 'mb-4'],
            'labelOptions' => ['class' => 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400'],
            'errorOptions' => ['class' => 'help-block-error'],
            'inputOptions' => ['class' => $inputClass],
        ],
    ]); ?>

    <?php if ($model->hasErrors('rule')): ?>
    <div class="rounded-xl border border-error-300 bg-error-50 px-5 py-4">
        <?php foreach ($model->getErrors('rule') as $error): ?>
            <p class="text-sm font-medium text-error-700"><?= Html::encode($error) ?></p>
        <?php endforeach; ?>

        <?php if (!empty($conflictAppointments)): ?>
        <div class="mt-3 overflow-x-auto">
            <table class="holiday-conflicts min-w-full text-sm text-left text-gray-700">
                <thead class="text-xs uppercase text-gray-500">
                    <tr>
                        <th>Data</th>
                        <th>Ora</th>
                        <th>Terapista</th>
                        <th>Paziente</th>
                        <?php if ($canViewCalendar): ?><th></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($conflictAppointments as $appointment): ?>
                    <?php
                    $dt = new \DateTimeImmutable($appointment->appointment_datetime);
                    $profile = $appointment->therapist->user->profile ?? null;
                    $patient = $appointment->getActualPatient();
                    ?>
                    <tr class="border-t border-error-300">
                        <td><?= $dt->format('d/m/Y') ?></td>
                        <td><?= $dt->format('H:i') ?></td>
                        <td><?= Html::encode($profile ? $profile->getFullName() : 'N/D') ?></td>
                        <td><?= Html::encode($patient ? $patient->getFullName() : ($appointment->group_session_id ? 'Gruppo' : 'N/D')) ?></td>
                        <?php if ($canViewCalendar): ?>
                        <td>
                            <?= Html::a('Apri calendario', Url::to(['/calendar/index', 'id_therapist' => $appointment->therapist_id]), [
                                'class' => 'text-brand-500 font-medium',
                                'target' => '_blank',
                            ]) ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($totalConflicts > count($conflictAppointments)): ?>
                <p class="mt-2 text-xs text-gray-600">Mostrati i primi <?= count($conflictAppointments) ?> appuntamenti su <?= $totalConflicts ?>.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Dati generali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">Giorno di chiusura</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Nei giorni di chiusura non è possibile creare né spostare appuntamenti.
            </p>
        </div>

        <div class="px-5 pb-5 sm:px-6">
            <?= $form->field($model, 'name')->textInput([
                'maxlength' => true,
                'placeholder' => 'es. Santo Patrono, Ponte del 24 aprile',
            ]) ?>

            <?php if ($model->is_national): ?>
                <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                    Festività nazionale inserita all'installazione del modulo: può essere modificata, disattivata o eliminata come le altre.
                </p>
            <?php endif; ?>

            <!-- Tipo -->
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tipo</label>
                <div class="holiday-kind-grid">
                    <?php foreach (Holiday::getKindOptions() as $value => $label): ?>
                    <label class="holiday-kind-option" :class="kind === '<?= $value ?>' ? 'is-selected' : ''">
                        <input type="radio"
                               name="<?= Html::getInputName($model, 'kind') ?>"
                               value="<?= $value ?>"
                               x-model="kind"
                               <?= $model->kind === $value ? 'checked' : '' ?>>
                        <span>
                            <span class="block text-sm font-medium text-gray-800 dark:text-white/90"><?= Html::encode($label) ?></span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400"><?= Html::encode($kindHints[$value]) ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?= Html::error($model, 'kind', ['class' => 'help-block-error']) ?>
            </div>

            <!-- Ricorrente ogni anno -->
            <div x-show="kind === '<?= Holiday::KIND_RECURRING ?>'" style="<?= $sectionStyle(Holiday::KIND_RECURRING) ?>">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?= $form->field($model, 'day')->dropDownList($days, ['prompt' => 'Giorno...'])->label('Giorno') ?>
                    <?= $form->field($model, 'month')->dropDownList(Holiday::getMonthOptions(), ['prompt' => 'Mese...'])->label('Mese') ?>
                </div>
            </div>

            <!-- Una tantum -->
            <div x-show="kind === '<?= Holiday::KIND_ONE_OFF ?>'" style="<?= $sectionStyle(Holiday::KIND_ONE_OFF) ?>">
                <?= $form->field($model, 'date')->input('date') ?>
            </div>

            <!-- Calcolata -->
            <div x-show="kind === '<?= Holiday::KIND_COMPUTED ?>'" style="<?= $sectionStyle(Holiday::KIND_COMPUTED) ?>">
                <?= $form->field($model, 'rule')
                    ->dropDownList(Holiday::getComputedRuleOptions(), ['prompt' => 'Seleziona...'])
                    ->label('Festività calcolata')
                    ->hint('La data viene ricalcolata dal sistema per ogni anno.', ['class' => 'mt-1 text-xs text-gray-500 dark:text-gray-400'])
                    ->error(false) ?>
            </div>

            <!-- Chiusura settimanale -->
            <div x-show="kind === '<?= Holiday::KIND_WEEKDAY ?>'" style="<?= $sectionStyle(Holiday::KIND_WEEKDAY) ?>">
                <?= $form->field($model, 'weekday')
                    ->dropDownList(Holiday::getWeekdayOptions(), ['prompt' => 'Seleziona...'])
                    ->hint('Il giorno scelto sarà chiuso tutte le settimane dell\'anno.', ['class' => 'mt-1 text-xs text-gray-500 dark:text-gray-400']) ?>
            </div>

            <?= $form->field($model, 'notes')->textarea(['rows' => 3, 'placeholder' => 'Note facoltative']) ?>

            <?= $form->field($model, 'is_active', [
                'options' => ['class' => 'mb-0'],
            ])->checkbox([
                'class' => 'h-4 w-4 rounded border-gray-300',
                'labelOptions' => ['class' => 'inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-400'],
            ]) ?>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Una chiusura disattivata resta in elenco ma non blocca gli appuntamenti.</p>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center justify-between gap-4 pt-4">
        <?= Html::a('Annulla', ['index'], [
            'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50',
        ]) ?>
        <?= Html::submitButton($model->isNewRecord ? 'Crea giorno di chiusura' : 'Salva modifiche', [
            'class' => 'inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

<?= Html::endTag('div') ?>
