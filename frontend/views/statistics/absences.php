<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\AbsenceStatisticsSearch */
/* @var $monthlyRate array */
/* @var $byReason array */
/* @var $byGenerator array */
/* @var $byTreatmentType array */
/* @var $bySetting array */
/* @var $topAbsentees array */
/* @var $therapistOptions array */
/* @var $patientOptions array */
/* @var $treatmentOptions array */
/* @var $settingOptions array */

$this->title = 'Analisi assenze';
$this->params['breadcrumbs'][] = ['label' => 'Statistiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/statistics.css');

$monthlyRate = $monthlyRate ?? [];
$byReason = $byReason ?? [];
$byGenerator = $byGenerator ?? [];
$byTreatmentType = $byTreatmentType ?? [];
$bySetting = $bySetting ?? [];
$topAbsentees = $topAbsentees ?? ['therapists' => [], 'patients' => []];

$defaultFrom = date('Y-m-01');
$defaultTo = date('Y-m-d');
$isDefaultPeriod = $searchModel->dateFrom === $defaultFrom && $searchModel->dateTo === $defaultTo;
$customPeriod = (!$isDefaultPeriod) && (!empty($searchModel->dateFrom) || !empty($searchModel->dateTo));

$hasActiveFilters = $customPeriod ||
                    !empty($searchModel->absenceSource) ||
                    ($searchModel->isJustified !== null && $searchModel->isJustified !== '') ||
                    !empty($searchModel->absenceTypeFlag) || !empty($searchModel->therapistId) ||
                    !empty($searchModel->patientId) || !empty($searchModel->treatmentTypeId) ||
                    !empty($searchModel->settingId);

$formatDate = function ($value) {
    return $value ? Yii::$app->formatter->asDate($value, 'php:d/m/Y') : '';
};

if (!empty($searchModel->dateFrom) && !empty($searchModel->dateTo)) {
    $range = $formatDate($searchModel->dateFrom) . ' – ' . $formatDate($searchModel->dateTo);
    $periodText = $isDefaultPeriod
        ? 'Mese in corso · ' . $range
        : 'Periodo selezionato · ' . $range;
} elseif (!empty($searchModel->dateFrom)) {
    $periodText = 'Dal ' . $formatDate($searchModel->dateFrom);
} elseif (!empty($searchModel->dateTo)) {
    $periodText = 'Fino al ' . $formatDate($searchModel->dateTo);
} else {
    $periodText = 'Mese in corso';
}

$totalAbsences = (int) ($monthlyRate['total_absences'] ?? 0);
$justifiedAbsences = (int) ($monthlyRate['justified_absences'] ?? 0);
$unjustifiedAbsences = (int) ($monthlyRate['unjustified_absences'] ?? max(0, $totalAbsences - $justifiedAbsences));
$therapistAbsences = (int) ($monthlyRate['therapist_absences'] ?? 0);
$patientAbsences = (int) ($monthlyRate['patient_absences'] ?? 0);
$withRecovery = (int) ($monthlyRate['with_recovery'] ?? 0);
$withoutRecovery = (int) ($monthlyRate['without_recovery'] ?? max(0, $totalAbsences - $withRecovery));
$totalAppointments = (int) ($monthlyRate['total_appointments'] ?? 0);
$absenceRate = (float) ($monthlyRate['absence_rate'] ?? 0);
$lostHours = (float) ($monthlyRate['lost_hours'] ?? 0);
$unrecoveredHours = (float) ($monthlyRate['unrecovered_hours'] ?? 0);
$plannedHours = (float) ($monthlyRate['planned_hours'] ?? 0);
$hoursRate = (float) ($monthlyRate['hours_rate'] ?? 0);
$recoveredHours = max(0, round($lostHours - $unrecoveredHours, 1));

$hasAbsences = $totalAbsences > 0 ||
               !empty($byReason) ||
               !empty($byGenerator) ||
               !empty($byTreatmentType) ||
               !empty($bySetting) ||
               !empty($topAbsentees['therapists']) ||
               !empty($topAbsentees['patients']);
$hasData = $hasAbsences || $totalAppointments > 0 || $plannedHours > 0;

$fmtHours = function ($hours) {
    return number_format((float) $hours, 1, ',', '.');
};
$fmtPct = function ($value) {
    return number_format((float) $value, 1, ',', '.');
};
$canExport = Yii::$app->user->can('export_data');
?>

<div class="mx-auto max-w-4xl p-4 md:p-6 statistics-absences">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="period-text"><?= Html::encode($periodText) ?></p>
        <p class="section-intro">Quante sedute saltano, dove si concentrano e quanta capacità oraria si perde nel periodo.</p>
    </div>

    <!-- Filtri di ricerca - Riorganizzati logicamente -->
    <div class="filter-card">
        <div class="filter-header">
            <h3>Periodo e criteri</h3>
            <?php if ($hasActiveFilters): ?>
                <span class="active-filters-badge">
                    <i class="fas fa-filter"></i> Filtri attivi
                </span>
            <?php endif; ?>
        </div>

        <?php $form = ActiveForm::begin([
            'method' => 'get',
            'action' => Url::to(['absences']),
            'options' => ['class' => 'filter-form']
        ]); ?>

        <!-- Filtri temporali -->
        <div class="filter-section">
            <h4>Periodo da analizzare</h4>
            <p class="section-intro">Di default è il mese in corso fino a oggi. Cambia le date per confrontare un altro intervallo.</p>
            <div class="filter-row">
                <div class="filter-col">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Data inizio</label>
                    <div class="relative">
                        <?= Html::activeTextInput($searchModel, 'dateFrom', [
                            'type' => 'date',
                            'placeholder' => 'Seleziona data',
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden',
                            'onclick' => 'this.showPicker()'
                        ]) ?>
                        <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-500">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z" fill=""/>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="filter-col">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Data fine</label>
                    <div class="relative">
                        <?= Html::activeTextInput($searchModel, 'dateTo', [
                            'type' => 'date',
                            'placeholder' => 'Seleziona data',
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden',
                            'onclick' => 'this.showPicker()'
                        ]) ?>
                        <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-gray-500">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66659 1.5415C7.0808 1.5415 7.41658 1.87729 7.41658 2.2915V2.99984H12.5833V2.2915C12.5833 1.87729 12.919 1.5415 13.3333 1.5415C13.7475 1.5415 14.0833 1.87729 14.0833 2.2915V2.99984L15.4166 2.99984C16.5212 2.99984 17.4166 3.89527 17.4166 4.99984V7.49984V15.8332C17.4166 16.9377 16.5212 17.8332 15.4166 17.8332H4.58325C3.47868 17.8332 2.58325 16.9377 2.58325 15.8332V7.49984V4.99984C2.58325 3.89527 3.47868 2.99984 4.58325 2.99984L5.91659 2.99984V2.2915C5.91659 1.87729 6.25237 1.5415 6.66659 1.5415ZM6.66659 4.49984H4.58325C4.30711 4.49984 4.08325 4.7237 4.08325 4.99984V6.74984H15.9166V4.99984C15.9166 4.7237 15.6927 4.49984 15.4166 4.49984H13.3333H6.66659ZM15.9166 8.24984H4.08325V15.8332C4.08325 16.1093 4.30711 16.3332 4.58325 16.3332H15.4166C15.6927 16.3332 15.9166 16.1093 15.9166 15.8332V8.24984Z" fill=""/>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtri per tipologia -->
        <div class="filter-section">
            <h4>Tipo di assenza</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'absenceSource')->dropDownList([
                        '' => 'Terapisti e pazienti',
                        'therapist' => 'Solo terapisti',
                        'patient' => 'Solo pazienti'
                    ], ['class' => 'form-control'])->label('Chi è assente') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'isJustified')->dropDownList([
                        '' => 'Tutte',
                        '1' => 'Solo giustificate',
                        '0' => 'Solo non giustificate'
                    ], ['class' => 'form-control'])->label('Giustificata') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'absenceTypeFlag')->dropDownList([
                        '' => 'Tutti i tipi',
                        'direct' => 'Terapista — diretta',
                        'substitution' => 'Terapista — sostituzione',
                        'patient' => 'Paziente'
                    ], ['class' => 'form-control'])->label('Tipo assenza') ?>
                </div>
            </div>
        </div>

        <!-- Filtri specifici -->
        <div class="filter-section">
            <h4>Ambito</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'therapistId')->dropDownList(
                        ['' => 'Tutti i terapisti'] + $therapistOptions,
                        ['class' => 'form-control']
                    )->label('Terapista') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'patientId')->dropDownList(
                        ['' => 'Tutti i pazienti'] + $patientOptions,
                        ['class' => 'form-control']
                    )->label('Paziente') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'treatmentTypeId')->dropDownList(
                        ['' => 'Tutti i trattamenti'] + $treatmentOptions,
                        ['class' => 'form-control']
                    )->label('Tipo trattamento') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'settingId')->dropDownList(
                        ['' => 'Tutti i setting'] + $settingOptions,
                        ['class' => 'form-control']
                    )->label('Setting') ?>
                </div>
            </div>
        </div>

        <!-- Pulsanti azione -->
        <div class="filter-actions">
            <?= Html::submitButton('<i class="fas fa-search"></i> Applica filtri', [
                'class' => 'btn btn-primary'
            ]) ?>
            <?= Html::a('<i class="fas fa-undo"></i> Rimuovi filtri', ['absences'], [
                'class' => 'btn btn-secondary'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <?php if (!$hasData): ?>
        <div class="no-data-message">
            <i class="fas fa-info-circle"></i>
            <h3>Nessun dato nel periodo</h3>
            <p>
                Non risultano appuntamenti né assenze per i criteri selezionati.<br>
                Allarga le date o rimuovi i filtri per analizzare un altro intervallo.
            </p>
        </div>
    <?php else: ?>

        <div class="summary-card">
            <h3>Volume assenze</h3>
            <p class="section-intro">Quanti eventi di assenza nel periodo, chi li genera e quanto pesano sugli slot previsti.</p>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value blue"><?= $totalAbsences ?></div>
                    <div class="stat-label">Eventi di assenza</div>
                    <div class="stat-period"><?= $therapistAbsences ?> terapista · <?= $patientAbsences ?> paziente</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value green"><?= $justifiedAbsences ?></div>
                    <div class="stat-label">Giustificate</div>
                    <div class="stat-period"><?= $unjustifiedAbsences ?> non giustificate</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value gray"><?= $totalAppointments ?></div>
                    <div class="stat-label">Slot previsti</div>
                    <div class="stat-period">Sedute nel periodo</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value red"><?= $fmtPct($absenceRate) ?>%</div>
                    <div class="stat-label">Tasso di assenza</div>
                    <div class="stat-period">Slot con assenza / slot previsti</div>
                </div>
            </div>
        </div>

        <div class="summary-card">
            <h3>Capacità e recupero</h3>
            <p class="section-intro">Ore di slot perse rispetto alle ore previste, e quanto è stato recuperato.</p>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value gray"><?= $fmtHours($plannedHours) ?></div>
                    <div class="stat-label">Ore previste</div>
                    <div class="stat-period">Durata degli slot nel periodo</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value orange"><?= $fmtHours($lostHours) ?></div>
                    <div class="stat-label">Ore perse</div>
                    <div class="stat-period"><?= $fmtPct($hoursRate) ?>% delle ore previste</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value red"><?= $fmtHours($unrecoveredHours) ?></div>
                    <div class="stat-label">Ore non recuperate</div>
                    <div class="stat-period"><?= $withoutRecovery ?> assenze senza recupero</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value green"><?= $fmtHours($recoveredHours) ?></div>
                    <div class="stat-label">Ore recuperate</div>
                    <div class="stat-period"><?= $withRecovery ?> assenze con recupero</div>
                </div>
            </div>
        </div>

        <?php if (!$hasAbsences): ?>
            <div class="no-data-message">
                <i class="fas fa-info-circle"></i>
                <h3>Nessuna assenza nel periodo</h3>
                <p>Gli slot previsti ci sono, ma non risultano assenze con i criteri selezionati.</p>
            </div>
        <?php else: ?>

        <div class="section-title">
            <h3>Quando si concentrano</h3>
            <p class="section-intro">Fasce orarie e giorni della settimana in cui saltano più sedute.</p>
        </div>
        <div class="charts-row">
            <div class="chart-card">
                <h4>Per fascia oraria</h4>
                <div class="chart-container">
                    <canvas id="hourly-chart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h4>Per giorno della settimana</h4>
                <div class="chart-container">
                    <canvas id="day-chart"></canvas>
                </div>
            </div>
        </div>

        <?php if (!empty($bySetting)): ?>
        <div class="full-width-card">
            <h3>Assenze per setting</h3>
            <p class="section-intro">Dove saltano le sedute: ambulatorio, domiciliare e altri setting, con le ore di slot perse.</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Setting</th>
                        <th class="text-center">Eventi</th>
                        <th class="text-center">Terapisti</th>
                        <th class="text-center">Pazienti</th>
                        <th class="text-center">Ore perse</th>
                        <th class="text-center">% eventi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bySetting as $setting): ?>
                        <?php
                        $settingShare = $totalAbsences > 0
                            ? round(((int) $setting['total_absences'] / $totalAbsences) * 100, 1)
                            : 0;
                        ?>
                        <tr>
                            <td class="font-bold"><?= Html::encode($setting['setting_name'] ?: 'Non indicato') ?></td>
                            <td class="text-center">
                                <span class="badge badge-gray"><?= (int) $setting['total_absences'] ?></span>
                            </td>
                            <td class="text-center"><?= (int) $setting['therapist_absences'] ?></td>
                            <td class="text-center"><?= (int) $setting['patient_absences'] ?></td>
                            <td class="text-center"><?= $fmtHours($setting['lost_hours'] ?? 0) ?></td>
                            <td class="text-center"><?= $fmtPct($settingShare) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="section-title">
            <h3>Chi genera l’assenza e perché</h3>
            <p class="section-intro">Origine dell’evento (diretta, sostituzione, paziente) e motivazioni più frequenti.</p>
        </div>
        <div class="analysis-row">
            <div class="table-card">
                <h4>Origine delle assenze</h4>
                <?php if (!empty($byGenerator)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Chi</th>
                                <th>Tipo</th>
                                <th class="text-right">Eventi</th>
                                <th class="text-right">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($byGenerator as $item): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?= $item['generator'] === 'therapist' ? 'badge-purple' : 'badge-orange' ?>">
                                            <?= $item['generator'] === 'therapist' ? 'Terapista' : 'Paziente' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($item['absence_type'] === 'direct') echo 'Assenza diretta';
                                        elseif ($item['absence_type'] === 'substitution') echo 'Sostituzione';
                                        else echo 'Assenza paziente';
                                        ?>
                                    </td>
                                    <td class="text-right font-bold"><?= $item['count'] ?></td>
                                    <td class="text-right"><?= $fmtPct($item['percentage'] ?? 0) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">Nessun dato disponibile</p>
                <?php endif; ?>
            </div>

            <div class="table-card">
                <h4>Motivazioni più frequenti</h4>
                <?php if (!empty($byReason)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Motivazione</th>
                                <th>Chi</th>
                                <th class="text-right">Eventi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $topReasons = array_slice($byReason, 0, 10);
                            foreach ($topReasons as $item):
                            ?>
                                <tr>
                                    <td><?= Html::encode($item['reason'] ?: 'Non specificata') ?></td>
                                    <td>
                                        <span class="badge-small <?= $item['source'] === 'therapist' ? 'badge-purple' : 'badge-orange' ?>">
                                            <?= $item['source'] === 'therapist' ? 'Terapista' : 'Paziente' ?>
                                        </span>
                                    </td>
                                    <td class="text-right font-bold"><?= $item['count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">Nessun dato disponibile</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($byTreatmentType)): ?>
        <div class="full-width-card">
            <h3>Assenze per trattamento</h3>
            <p class="section-intro">Su quali terapie si concentrano le assenze nel periodo.</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Trattamento</th>
                        <th>Codice</th>
                        <th class="text-center">Eventi</th>
                        <th class="text-center">Terapisti</th>
                        <th class="text-center">Pazienti</th>
                        <th class="text-center">% giustificate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byTreatmentType as $treatment): ?>
                        <tr>
                            <td class="font-bold">
                                <?= Html::encode($treatment['treatment_name'] ?: 'Trattamento non specificato') ?>
                            </td>
                            <td>
                                <?= Html::encode($treatment['treatment_code'] ?: 'N/A') ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-gray"><?= $treatment['total_absences'] ?></span>
                            </td>
                            <td class="text-center"><?= $treatment['therapist_absences'] ?></td>
                            <td class="text-center"><?= $treatment['patient_absences'] ?></td>
                            <td class="text-center">
                                <span class="badge-small <?= $treatment['justified_rate'] > 50 ? 'badge-green' : 'badge-red' ?>">
                                    <?= $fmtPct($treatment['justified_rate']) ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if (empty($searchModel->therapistId) && (!empty($topAbsentees['therapists']) || !empty($topAbsentees['patients']))): ?>
        <div class="section-title">
            <h3>Chi è assente più spesso</h3>
            <p class="section-intro">Concentrazione nel periodo, non una lista da gestire: serve a capire se il fenomeno è diffuso o legato a poche persone.</p>
        </div>
        <div class="ranking-row">
            <?php if (!empty($topAbsentees['therapists']) && $searchModel->absenceSource !== 'patient'): ?>
            <div class="ranking-card red">
                <h4>Terapisti con più assenze</h4>
                <div class="ranking-list">
                    <?php foreach ($topAbsentees['therapists'] as $index => $therapist): ?>
                        <div class="ranking-item <?= $index < 3 ? 'top-three' : '' ?>">
                            <span class="rank"><?= $index + 1 ?>.</span>
                            <span class="name"><?= Html::encode($therapist['name']) ?></span>
                            <span class="count"><?= $therapist['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($topAbsentees['patients']) && $searchModel->absenceSource !== 'therapist'): ?>
            <div class="ranking-card orange">
                <h4>Pazienti con più assenze</h4>
                <div class="ranking-list">
                    <?php foreach ($topAbsentees['patients'] as $index => $patient): ?>
                        <div class="ranking-item <?= $index < 3 ? 'top-three' : '' ?>">
                            <span class="rank"><?= $index + 1 ?>.</span>
                            <span class="name"><?= Html::encode($patient['name']) ?></span>
                            <span class="count"><?= $patient['count'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="full-width-card">
            <h3>Andamento nel periodo</h3>
            <p class="section-intro">Come evolvono le assenze nel tempo. Su intervalli brevi il grafico è per giorno, altrimenti per mese.</p>
            <div class="chart-container large">
                <canvas id="trend-chart"></canvas>
            </div>
        </div>

        <?php endif; ?>

        <?php if ($canExport && $hasAbsences): ?>
        <div class="export-section">
            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                Export del dettaglio eventi secondo i criteri del periodo.
            </div>
            <?= Html::a(
                '<i class="fas fa-file-excel"></i> Esporta report Excel',
                ['export', 'type' => 'absences'] + Yii::$app->request->queryParams,
                ['class' => 'btn btn-success']
            ) ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php if ($hasAbsences): ?>
<?php
// Javascript per i grafici
$this->registerJs("
// Configurazione globale per Chart.js
Chart.defaults.font.size = 12;
Chart.defaults.maintainAspectRatio = false;

// Variabili per memorizzare i grafici
let hourlyChart = null;
let dayChart = null;
let trendChart = null;

// Funzione per distruggere un grafico se esiste
function destroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
}

// Funzione per inizializzare i grafici
function initializeCharts() {
    
    // Grafico orario
    loadHourlyChart();
    
    // Grafico per giorno
    loadDayChart();
    
    // Grafico trend
    loadTrendChart();
}

// Carica grafico orario
function loadHourlyChart() {
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'absence-hourly']) . "',
        data: " . json_encode(array_merge(Yii::$app->request->queryParams, ['AbsenceStatisticsSearch' => $searchModel->attributes])) . ",
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                destroyChart(hourlyChart);
                var ctx = document.getElementById('hourly-chart');
                if (ctx) {
                    hourlyChart = new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: response.data.labels || [],
                            datasets: [{
                                label: 'Numero assenze',
                                data: response.data.values || [],
                                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 1,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return 'Assenze: ' + context.parsed.y;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    title: {
                                        display: true,
                                        text: 'Numero assenze'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Ora del giorno'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Errore caricamento grafico orario:', error);
        }
    });
}

// Carica grafico per giorno
function loadDayChart() {
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'absence-by-day']) . "',
        data: " . json_encode(array_merge(Yii::$app->request->queryParams, ['AbsenceStatisticsSearch' => $searchModel->attributes])) . ",
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                destroyChart(dayChart);
                var ctx = document.getElementById('day-chart');
                if (ctx) {
                    // Prepara i dati per i giorni della settimana
                    var labels = response.data.labels || ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
                    var datasets = response.data.datasets || [];
                    
                    // I dataset sono calcolati dal backend sui dati reali.
                    if (datasets[0]) {
                        datasets[0].borderRadius = 4;
                    }
                    if (datasets[1]) {
                        datasets[1].borderRadius = 4;
                    }
                    
                    dayChart = new Chart(ctx.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        usePointStyle: true
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y + ' assenze';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    title: {
                                        display: true,
                                        text: 'Numero assenze'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Giorno della settimana'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Errore caricamento grafico giornaliero:', error);
        }
    });
}

// Carica grafico trend
function loadTrendChart() {
    $.ajax({
        url: '" . Url::to(['chart-data', 'type' => 'absence-trend']) . "',
        data: " . json_encode(array_merge(Yii::$app->request->queryParams, ['AbsenceStatisticsSearch' => $searchModel->attributes])) . ",
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                destroyChart(trendChart);
                var ctx = document.getElementById('trend-chart');
                if (ctx) {
                    // Personalizza i colori per i dataset
                    if (response.data.datasets) {
                        response.data.datasets.forEach(function(dataset, index) {
                            if (index === 0) { // Totale
                                dataset.borderColor = 'rgba(59, 130, 246, 1)';
                                dataset.backgroundColor = 'rgba(59, 130, 246, 0.1)';
                            } else if (index === 1) { // Giustificate
                                dataset.borderColor = 'rgba(34, 197, 94, 1)';
                                dataset.backgroundColor = 'rgba(34, 197, 94, 0.1)';
                            }
                            dataset.tension = 0.3;
                            dataset.fill = true;
                        });
                    }
                    
                    trendChart = new Chart(ctx.getContext('2d'), {
                        type: 'line',
                        data: response.data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        padding: 15,
                                        usePointStyle: true
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y + ' assenze';
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    },
                                    title: {
                                        display: true,
                                        text: 'Numero assenze'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: response.data.xAxisTitle || 'Periodo'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Errore caricamento grafico trend:', error);
        }
    });
}

// Attendi che Chart.js sia caricato
if (typeof Chart !== 'undefined') {
    initializeCharts();
} else {
    // Riprova dopo un breve delay
    setTimeout(function() {
        if (typeof Chart !== 'undefined') {
            initializeCharts();
        } else {
            console.error('Chart.js non trovato!');
        }
    }, 1000);
}
", \yii\web\View::POS_READY);
?>

<!-- CSS per i date picker moderni -->
<style>
/* Stili per i date picker personalizzati */
.shadow-theme-xs {
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.focus\:border-brand-300:focus {
    border-color: #93c5fd;
}

.focus\:ring-brand-500\/10:focus {
    --tw-ring-color: rgba(59, 130, 246, 0.1);
    --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
    --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(3px + var(--tw-ring-offset-width)) var(--tw-ring-color);
    box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}

.focus\:ring-3:focus {
    --tw-ring-offset-width: 3px;
}

.focus\:outline-hidden:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
}

/* Posizionamento dell'icona calendario */
.filter-col .relative {
    position: relative;
}

.filter-col .pointer-events-none {
    pointer-events: none;
}

.filter-col .absolute {
    position: absolute;
}

.filter-col .top-1\/2 {
    top: 50%;
}

.filter-col .right-3 {
    right: 0.75rem;
}

.filter-col .-translate-y-1\/2 {
    transform: translateY(-50%);
}
</style>

<?php endif; ?>