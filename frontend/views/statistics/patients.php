<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\grid\ActionColumn;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\PatientStatisticsSearch */
/* @var $demographics array */
/* @var $byTreatment array */
/* @var $byRegime array */
/* @var $multiTreatmentStats array */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $treatmentOptions array */
/* @var $regimeOptions array */
/* @var $districtOptions array */

$this->title = 'Statistiche Pazienti';
$this->params['breadcrumbs'][] = ['label' => 'Statistiche', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Registra Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/statistics.css');
// Inizializza variabili se non definite
$demographics = $demographics ?? [];
$byTreatment = $byTreatment ?? [];
$byRegime = $byRegime ?? [];
$multiTreatmentStats = $multiTreatmentStats ?? ['patients' => [], 'stats' => []];

// Funzione helper per verificare se ci sono filtri attivi
$hasActiveFilters = !empty($searchModel->gender) || !empty($searchModel->ageFrom) ||
                    !empty($searchModel->ageTo) || !empty($searchModel->status) ||
                    !empty($searchModel->dateFrom) || !empty($searchModel->dateTo) ||
                    !empty($searchModel->treatmentTypeIds) || !empty($searchModel->districtId) ||
                    !$searchModel->activePlanOnly;

// Calcola il totale pazienti
$totalPatients = $demographics['age_stats']['total_patients'] ?? 0;

// Helper per verificare se ci sono dati
$hasData = $totalPatients > 0 || !empty($byTreatment) || !empty($byRegime) || !empty($multiTreatmentStats['patients']);

// Helper per calcolo percentuali
function calculatePercentage($part, $total, $decimals = 1) {
    return $total > 0 ? round($part / $total * 100, $decimals) : 0;
}
?>

<div class="mx-auto max-w-4xl p-4 md:p-6 statistics-patients">
    <!-- Header con titolo -->
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <p class="period-text">Analisi demografica e distribuzione pazienti</p>
    </div>

    <!-- Filtri di ricerca -->
    <div class="filter-card">
        <div class="filter-header">
            <h3>Filtri di ricerca</h3>
            <?php if ($hasActiveFilters): ?>
                <span class="active-filters-badge">
                    <i class="fas fa-filter"></i> Filtri attivi
                </span>
            <?php endif; ?>
        </div>

        <?php $form = ActiveForm::begin([
            'method' => 'get',
            'action' => Url::to(['patients']),
            'options' => ['class' => 'filter-form']
        ]); ?>

        <!-- Filtri demografici -->
        <div class="filter-section">
            <h4>Filtri demografici</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'gender')->dropDownList([
                        '' => 'Tutti i generi',
                        'M' => 'Maschio',
                        'F' => 'Femmina',
                        'N' => 'Non specificato'
                    ], ['class' => 'form-control'])->label('Genere') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'status')->dropDownList([
                        '' => 'Tutti gli stati',
                        'active' => 'Con piano attivo',
                        'inactive' => 'Senza piano attivo',
                        'dismissed' => 'Dimessi'
                    ], ['class' => 'form-control'])->label('Stato paziente') ?>
                </div>
            </div>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'ageFrom')->textInput([
                        'type' => 'number',
                        'min' => 0,
                        'max' => 120,
                        'class' => 'form-control'
                    ])->label('Età minima') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'ageTo')->textInput([
                        'type' => 'number',
                        'min' => 0,
                        'max' => 120,
                        'class' => 'form-control'
                    ])->label('Età massima') ?>
                </div>
            </div>
        </div>

        <!-- Filtri per trattamento e distretto -->
        <div class="filter-section">
            <h4>Filtri specifici</h4>
            <div class="filter-row">
                <div class="filter-col">
                    <?= $form->field($searchModel, 'treatmentTypeIds')->dropDownList(
                        $treatmentOptions,
                        [
                            'class' => 'form-control',
                            'multiple' => true,
                            'prompt' => 'Tutti i trattamenti'
                        ]
                    )->label('Tipo trattamento') ?>
                </div>
                <div class="filter-col">
                    <?= $form->field($searchModel, 'districtId')->dropDownList(
                        ['' => 'Tutti i distretti'] + $districtOptions,
                        ['class' => 'form-control']
                    )->label('Distretto') ?>
                </div>
            </div>
        </div>

        <!-- Filtri temporali -->
        <div class="filter-section">
            <h4>Periodo registrazione</h4>
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

        <!-- Filtro piano attivo -->
        <div class="filter-section" style="padding-top: 0;">
            <div class="filter-row">
                <div class="filter-col">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <?= Html::activeCheckbox($searchModel, 'activePlanOnly', [
                            'label' => false,
                            'class' => 'h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500',
                        ]) ?>
                        <span class="text-sm font-medium text-gray-700">Mostra solo pazienti con piano terapeutico attivo</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Pulsanti azione -->
        <div class="filter-actions">
            <?= Html::submitButton('<i class="fas fa-search"></i> Applica filtri', [
                'class' => 'btn btn-primary'
            ]) ?>
            <?= Html::a('<i class="fas fa-undo"></i> Rimuovi filtri', ['patients'], [
                'class' => 'btn btn-secondary'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <?php if (!$hasData): ?>
        <!-- Messaggio quando non ci sono dati -->
        <div class="no-data-message">
            <i class="fas fa-info-circle"></i>
            <h3>Nessun paziente trovato</h3>
            <p>
                Non sono presenti pazienti per i criteri selezionati.<br>
                Prova a modificare i filtri di ricerca per visualizzare i dati.
            </p>
        </div>
    <?php else: ?>

        <!-- 1. Riepilogo demografico -->
        <div class="summary-card">
            <h3>Riepilogo Demografico</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value blue"><?= $totalPatients ?></div>
                    <div class="stat-label">Totale Pazienti</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value green"><?= round($demographics['age_stats']['avg_age'] ?? 0, 1) ?></div>
                    <div class="stat-label">Età Media</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value gray"><?= count($multiTreatmentStats['patients'] ?? []) ?></div>
                    <div class="stat-label">Multi-trattamento</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value orange"><?= round($multiTreatmentStats['stats']['avg_treatments'] ?? 0, 1) ?></div>
                    <div class="stat-label">Media Trattamenti</div>
                </div>
            </div>
        </div>

        <!-- 2. Distribuzione demografica -->
        <div class="section-title">
            <h3>Distribuzione Demografica</h3>
        </div>
        <div class="charts-row">
            <div class="chart-card">
                <h4>Distribuzione per Età</h4>
                <div class="chart-container">
                    <canvas id="age-chart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h4>Distribuzione per Genere</h4>
                <div class="chart-container">
                    <canvas id="gender-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- 3. Analisi per trattamento -->
        <?php if (!empty($byTreatment)): ?>
        <div class="full-width-card">
            <h3>Pazienti per Trattamento</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Trattamento</th>
                        <th>Codice</th>
                        <th class="text-center">Pazienti</th>
                        <th class="text-center">% del Totale</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($byTreatment, 0, 15) as $treatment): ?>
                        <tr>
                            <td class="font-bold"><?= Html::encode($treatment['name']) ?></td>
                            <td><?= Html::encode($treatment['code']) ?></td>
                            <td class="text-center">
                                <span class="badge badge-gray"><?= $treatment['patient_count'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php 
                                $percentage = calculatePercentage($treatment['patient_count'], $totalPatients);
                                $badgeClass = $percentage >= 20 ? 'badge-green' : ($percentage >= 10 ? 'badge-orange' : 'badge-gray');
                                ?>
                                <span class="badge-small <?= $badgeClass ?>">
                                    <?= $percentage ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($byTreatment) > 15): ?>
            <p class="text-center mt-4 text-sm text-gray-600">
                Mostrati i primi 15 di <?= count($byTreatment) ?> trattamenti
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 4. Pazienti con trattamenti multipli -->
        <?php if (!empty($multiTreatmentStats['patients'])): ?>
        <div class="full-width-card">
            <h3>Pazienti con Trattamenti Multipli (escluso ABA)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Paziente</th>
                        <th class="text-center">N° Trattamenti</th>
                        <th>Trattamenti</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($multiTreatmentStats['patients'], 0, 20) as $patient): ?>
                        <tr>
                            <td class="font-bold"><?= Html::encode($patient['patient_name']) ?></td>
                            <td class="text-center">
                                <?php 
                                $count = $patient['treatment_count'];
                                $badgeClass = $count >= 4 ? 'badge-red' : ($count >= 3 ? 'badge-orange' : 'badge-green');
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $count ?></span>
                            </td>
                            <td class="text-sm"><?= Html::encode($patient['treatments']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($multiTreatmentStats['patients']) > 20): ?>
            <p class="text-center mt-4 text-sm text-gray-600">
                Mostrati i primi 20 di <?= count($multiTreatmentStats['patients']) ?> pazienti con trattamenti multipli
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 5. Distribuzione per regime -->
        <?php if (!empty($byRegime)): ?>
        <div class="section-title">
            <h3>Distribuzione per Regime Sanitario</h3>
        </div>
        <div class="regime-grid">
            <?php foreach ($byRegime as $regime): ?>
            <div class="regime-card">
                <h4><?= Html::encode($regime['regime_name']) ?></h4>
                <div class="regime-count">
                    <?= $regime['patient_count'] ?> pazienti
                </div>
                <?php if (!empty($regime['avg_duration']) && $regime['avg_duration'] > 0): ?>
                <div class="regime-duration">
                    Durata media: <?= round($regime['avg_duration']) ?> giorni
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- 6. Lista Pazienti -->
        <div class="full-width-card">
            <h3>Lista Pazienti</h3>
            <p class="text-sm text-gray-600 mb-4">
                Elenco completo dei pazienti filtrato secondo i criteri selezionati
            </p>
            
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-striped table-bordered'],
                'layout' => "{items}\n{summary}\n{pager}",
                'columns' => [
                    [
                        'attribute' => 'id',
                        'label' => 'ID',
                        'options' => ['width' => '80px'],
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                    [
                        'attribute' => 'first_name',
                        'label' => 'Nome',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $fullName = Html::encode($model['first_name'] . ' ' . $model['last_name']);
                            return Html::a($fullName, ['patient/view', 'id' => $model['id']], [
                                'class' => 'text-blue-600 hover:text-blue-800 font-medium'
                            ]);
                        },
                    ],
                    [
                        'attribute' => 'age',
                        'label' => 'Età',
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center'],
                        'options' => ['width' => '80px'],
                    ],
                    [
                        'attribute' => 'gender',
                        'label' => 'Genere',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $genderLabels = ['M' => 'Maschio', 'F' => 'Femmina', 'N' => 'N/A'];
                            $label = $genderLabels[$model['gender']] ?? 'N/A';
                            $badgeClass = $model['gender'] === 'M' ? 'badge-blue' : ($model['gender'] === 'F' ? 'badge-pink' : 'badge-gray');
                            return '<span class="badge ' . $badgeClass . '">' . Html::encode($label) . '</span>';
                        },
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center'],
                        'options' => ['width' => '100px'],
                    ],
                    [
                        'attribute' => 'piano_terapeutico_attivo',
                        'label' => 'Piano Attivo',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $isActive = $model['piano_terapeutico_attivo'] === 'SI';
                            $badgeClass = $isActive ? 'badge-green' : 'badge-gray';
                            $label = $isActive ? 'Attivo' : 'Non Attivo';
                            return '<span class="badge ' . $badgeClass . '">' . $label . '</span>';
                        },
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center'],
                        'options' => ['width' => '120px'],
                    ],
                    [
                        'attribute' => 'trattamenti_count_no_aba',
                        'label' => 'N° Trattamenti',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $count = (int)$model['trattamenti_count_no_aba'];
                            if ($count === 0) {
                                return '<span class="badge badge-gray">0</span>';
                            } elseif ($count === 1) {
                                return '<span class="badge badge-blue">1</span>';
                            } else {
                                return '<span class="badge badge-orange">' . $count . '</span>';
                            }
                        },
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center'],
                        'options' => ['width' => '120px'],
                    ],
                    [
                        'attribute' => 'district_name',
                        'label' => 'Distretto',
                        'value' => function ($model) {
                            return $model['district_name'] ?: 'N/A';
                        },
                        'options' => ['width' => '150px'],
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Data Registrazione',
                        'format' => 'date',
                        'headerOptions' => ['class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center'],
                        'options' => ['width' => '150px'],
                    ],
                ],
                'pager' => [
                    'class' => 'yii\widgets\LinkPager',
                    'options' => ['class' => 'pagination justify-center mt-4'],
                    'linkOptions' => ['class' => 'page-link'],
                    'activePageCssClass' => 'active',
                    'disabledPageCssClass' => 'disabled',
                    'prevPageLabel' => '‹ Precedente',
                    'nextPageLabel' => 'Successivo ›',
                ],
            ]) ?>
        </div>

        <!-- 7. Azioni export -->
        <div class="export-section">
            <div class="info-text">
                <i class="fas fa-info-circle"></i>
                I dati mostrati sono filtrati secondo i criteri selezionati
            </div>
            <?= Html::a(
                '<i class="fas fa-file-excel"></i> Esporta Report Excel',
                ['export', 'type' => 'patients'] + Yii::$app->request->queryParams,
                ['class' => 'btn btn-success']
            ) ?>
        </div>

    <?php endif; ?>
</div>



<!-- CSS aggiuntivo per questa view -->
<style>
/* Stili specifici per i regimi */
.regime-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.regime-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #3182ce;
}

.regime-card h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a202c;
    margin-bottom: 12px;
}

.regime-count {
    font-size: 1.5rem;
    font-weight: 700;
    color: #3182ce;
    margin-bottom: 8px;
}

.regime-duration {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Colore arancione per stat-value */
.stat-value.orange {
    color: #f97316;
}

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

/* Stili per i badge del GridView */
.badge-blue {
    background-color: #dbeafe;
    color: #1e40af;
}

.badge-pink {
    background-color: #fce7f3;
    color: #be185d;
}

.badge-green {
    background-color: #dcfce7;
    color: #166534;
}

.badge-orange {
    background-color: #fed7aa;
    color: #c2410c;
}

.badge-gray {
    background-color: #f3f4f6;
    color: #374151;
}

/* Stili per il GridView */
.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
}

.table th,
.table td {
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    text-align: left;
    vertical-align: middle;
}

.table th {
    background-color: #f9fafb;
    font-weight: 600;
    color: #374151;
}

.table-striped tbody tr:nth-child(odd) {
    background-color: #f9fafb;
}

.table-bordered {
    border: 1px solid #e5e7eb;
}

/* Stili per la paginazione */
.pagination {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 1rem 0;
    gap: 0.25rem;
}

.pagination li {
    display: inline-block;
}

.pagination .page-link {
    display: block;
    padding: 0.5rem 0.75rem;
    color: #3b82f6;
    text-decoration: none;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
}

.pagination .page-link:hover {
    background-color: #f3f4f6;
    color: #1d4ed8;
}

.pagination .active .page-link {
    background-color: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.pagination .disabled .page-link {
    color: #6b7280;
    background-color: #f9fafb;
    cursor: not-allowed;
}

.justify-center {
    justify-content: center;
}

/* Link sui nomi dei pazienti */
.text-blue-600 {
    color: #2563eb;
}

.text-blue-800 {
    color: #1e40af;
}

.hover\:text-blue-800:hover {
    color: #1e40af;
}

.font-medium {
    font-weight: 500;
}

</style>

<?php if ($hasData): ?>
<?php
// Javascript per i grafici
$this->registerJs("
// Configurazione globale per Chart.js
Chart.defaults.font.size = 12;
Chart.defaults.maintainAspectRatio = false;

// Variabili per memorizzare i grafici
let ageChart = null;
let genderChart = null;

// Funzione per distruggere un grafico se esiste
function destroyChart(chart) {
    if (chart) {
        chart.destroy();
    }
}

// Funzione per inizializzare i grafici
function initializeCharts() {
    
    // Grafico età
    loadAgeChart();
    
    // Grafico genere
    loadGenderChart();
}

// Carica grafico età
function loadAgeChart() {
    
    // Prepara i dati dalle statistiche demografiche
    var ageGroups = " . json_encode($demographics['age_groups'] ?? []) . ";
    
    if (ageGroups && ageGroups.length > 0) {
        destroyChart(ageChart);
        var ctx = document.getElementById('age-chart');
        if (ctx) {
            ageChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ageGroups.map(function(g) { return g.age_group; }),
                    datasets: [{
                        label: 'Numero pazienti',
                        data: ageGroups.map(function(g) { return g.count; }),
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
                                    return 'Pazienti: ' + context.parsed.y;
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
                                text: 'Numero pazienti'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Fascia di età'
                            }
                        }
                    }
                }
            });
        }
    }
}

// Carica grafico genere
function loadGenderChart() {
    
    // Prepara i dati dalle statistiche demografiche
    var genderData = " . json_encode($demographics['gender_distribution'] ?? []) . ";
    
    if (genderData && genderData.length > 0) {
        destroyChart(genderChart);
        var ctx = document.getElementById('gender-chart');
        if (ctx) {
            genderChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: genderData.map(function(g) { return g.gender_label; }),
                    datasets: [{
                        label: 'Pazienti',
                        data: genderData.map(function(g) { return g.count; }),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(201, 203, 207, 0.8)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(201, 203, 207, 1)'
                        ],
                        borderWidth: 1
                    }]
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
                                    var label = context.label || '';
                                    var value = context.parsed;
                                    var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                    var percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    }
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
<?php endif; ?>