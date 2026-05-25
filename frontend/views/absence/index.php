<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var common\models\AbsenceSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array|null $therapistsList */

$this->title = 'Gestione assenze terapista';
$this->params['breadcrumbs'][] = $this->title;

// Lista terapisti per il select della modale. Coordinator => solo terapisti
// del proprio gruppo, altrimenti tutti.
$modalTherapists = $therapistsList ?? \common\models\AbsenceSearch::getTherapistsList();
$absenceTypes = \common\models\Absence::getTypeLabels();
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>

            <!-- Action Button -->
            <?php if (Yii::$app->user->can('create_absence')): ?>
            <div>
                <button type="button"
                        id="btn-new-absence"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nuova Assenza Terapista
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Content Start -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Lista Assenze Terapisti
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestisci le assenze dei terapisti.
            </p>
        </div>

        <!-- Filter Controls -->
        <div class="border-t border-gray-100 dark:border-gray-800 px-5 py-3 flex justify-between items-center">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <?= 'Trovate ' . $dataProvider->totalCount . ' assenze' ?>
            </div>
            <div class="flex gap-2">
                <?= Html::a('Reset Filtri', ['index'], [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'
                ]) ?>
                <?= Html::button('Aggiorna', [
                    'class' => 'inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-brand-600 border border-transparent rounded-md shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                    'onclick' => '$.pjax.reload({container:"#absence-grid-pjax"});'
                ]) ?>
            </div>
        </div>

        <!-- Scrollable Table Container -->
        <div class="border-t border-gray-100 dark:border-gray-800 overflow-x-auto">
            <?php Pjax::begin(['id' => 'absence-grid-pjax']); ?>

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
                        'attribute' => 'therapist_name',
                        'label' => 'Terapista',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'therapist_id',
                            isset($therapistsList) ? $therapistsList : \common\models\AbsenceSearch::getTherapistsList(),
                            [
                                'prompt' => 'Tutti',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'value' => function($model) {
                            return $model->therapist && $model->therapist->user && $model->therapist->user->profile
                                ? $model->therapist->user->profile->last_name . ' ' . $model->therapist->user->profile->first_name
                                : '';
                        }
                    ],
                    [
                        'attribute' => 'start_date',
                        'label' => 'Data Inizio',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'type' => 'date'],
                        'format' => ['date', 'php:d/m/Y'],
                    ],
                    [
                        'attribute' => 'end_date',
                        'label' => 'Data Fine',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'type' => 'date'],
                        'format' => ['date', 'php:d/m/Y'],
                    ],
                    [
                        'label' => 'Orario',
                        'format' => 'raw',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[110px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-sm'],
                        'value' => function ($model) {
                            if (!$model->isHourly()) {
                                return '<span class="text-gray-400">Tutto il giorno</span>';
                            }
                            return '<span class="font-medium">'
                                . substr($model->start_time, 0, 5)
                                . ' - ' . substr($model->end_time, 0, 5)
                                . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'type',
                        'label' => 'Tipo',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'type',
                            \common\models\AbsenceSearch::getTypesList(),
                            [
                                'prompt' => 'Tutti',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'value' => function($model) {
                            return $model->getTypeLabel();
                        }
                    ],
                    [
                        'label' => 'Durata',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[100px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-center'],
                        'format' => 'raw',
                        'content' => function($model) {
                            $days = $model->getDurationDays();
                            $badgeClass = $days > 7 ? 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900' : 'bg-blue-100 text-blue-800 dark:bg-blue-200 dark:text-blue-900';
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $badgeClass . '">' . $days . ' giorni</span>';
                        }
                    ],
                    [
                        'attribute' => 'reason',
                        'label' => 'Motivo',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[200px]'],
                        'contentOptions' => ['class' => 'px-4 py-4'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Filtra motivo...'],
                        'value' => function($model) {
                            return $model->reason ? \yii\helpers\StringHelper::truncate($model->reason, 50) : '-';
                        }
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Stato',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'filter' => \yii\helpers\Html::activeDropDownList($searchModel, 'status',
                            \common\models\AbsenceSearch::getStatusList(),
                            [
                                'prompt' => 'Tutti',
                                'class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                            ]
                        ),
                        'format' => 'raw',
                        'content' => function($model) {
                            $statusClass = $model->isApproved() ? 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-200 dark:text-yellow-900';
                            return '<span class="inline-flex px-2 py-1 text-xs font-medium rounded-full ' . $statusClass . '">' . $model->getStatusLabel() . '</span>';
                        }
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Azioni',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap'],
                        'filterOptions' => ['class' => 'px-2 py-2'],
                        'template' => '{view} {update} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                                    $url, [
                                    'title' => 'Visualizza assenza',
                                    'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20',
                                    'data-bs-toggle' => 'tooltip',
                                    'data-bs-placement' => 'top'
                                ]);
                            },
                            'update' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('update_absence')) return '';
                                return Html::button('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', [
                                    'type' => 'button',
                                    'title' => 'Modifica assenza',
                                    'class' => 'btn-edit-absence text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-3 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20',
                                    'data-id' => $model->id,
                                ]);
                            },
                            'delete' => function ($url, $model, $key) {
                                if (!Yii::$app->user->can('delete_absence')) return '';
                                return Html::a('<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                                    $url, [
                                    'title' => 'Elimina assenza',
                                    'class' => 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20',
                                    'data-bs-toggle' => 'tooltip',
                                    'data-bs-placement' => 'top',
                                    'data' => [
                                        'confirm' => 'Sei sicuro di voler eliminare questa assenza?',
                                        'method' => 'post',
                                    ],
                                ]);
                            },
                        ],
                    ],
                ],
            ]); ?>

            <?php Pjax::end(); ?>
        </div>
    </div>
</div>

<?php
$therapistsJson = Json::encode($modalTherapists);
$typesJson = Json::encode($absenceTypes);
$saveUrl = Url::to(['ajax-save']);
$getUrl = Url::to(['ajax-get']);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$js = <<<JS
(function() {
    const therapists = {$therapistsJson};
    const types = {$typesJson};
    const SAVE_URL = '{$saveUrl}';
    const GET_URL = '{$getUrl}';
    const CSRF_PARAM = '{$csrfParam}';
    const CSRF_TOKEN = '{$csrfToken}';

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = String(s ?? '');
        return d.innerHTML;
    }

    function buildModalHtml(model) {
        const isUpdate = !!model.id;
        const isHourly = !!(model.start_time && model.end_time);
        const today = new Date().toISOString().slice(0, 10);

        const therapistOpts = Object.entries(therapists).map(([id, name]) => {
            const sel = String(model.therapist_id || '') === String(id) ? 'selected' : '';
            return '<option value="' + escapeHtml(id) + '" ' + sel + '>' + escapeHtml(name) + '</option>';
        }).join('');

        const typeOpts = Object.entries(types).map(([k, v]) => {
            const sel = String(model.type || '') === String(k) ? 'selected' : '';
            return '<option value="' + escapeHtml(k) + '" ' + sel + '>' + escapeHtml(v) + '</option>';
        }).join('');

        return '' +
        '<div style="text-align:left;font-size:13px;">' +
            '<div style="margin-bottom:10px;">' +
                '<label style="display:block;font-weight:600;color:#374151;margin-bottom:4px;">Terapista *</label>' +
                '<select id="ab-therapist" ' + (isUpdate ? 'disabled' : '') + ' style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;background:#fff;">' +
                    '<option value="">— Seleziona terapista —</option>' +
                    therapistOpts +
                '</select>' +
            '</div>' +
            '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">' +
                '<div>' +
                    '<label style="display:block;font-weight:600;color:#374151;margin-bottom:4px;">Data inizio *</label>' +
                    '<input type="date" id="ab-start-date" value="' + escapeHtml(model.start_date || today) + '" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;">' +
                '</div>' +
                '<div>' +
                    '<label style="display:block;font-weight:600;color:#374151;margin-bottom:4px;">Data fine *</label>' +
                    '<input type="date" id="ab-end-date" value="' + escapeHtml(model.end_date || today) + '" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;">' +
                '</div>' +
            '</div>' +
            '<div style="margin-bottom:10px;">' +
                '<label style="display:inline-flex;align-items:center;cursor:pointer;">' +
                    '<input type="checkbox" id="ab-all-day" ' + (isHourly ? '' : 'checked') + ' style="margin-right:6px;">' +
                    '<span style="font-weight:600;color:#374151;">Tutto il giorno</span>' +
                '</label>' +
                '<p style="margin:3px 0 0 22px;font-size:11px;color:#6b7280;">Disattivare per range orario (singolo giorno).</p>' +
            '</div>' +
            '<div id="ab-hourly-row" style="display:' + (isHourly ? 'grid' : 'none') + ';grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">' +
                '<div>' +
                    '<label style="display:block;font-weight:600;color:#374151;margin-bottom:4px;">Ora inizio *</label>' +
                    '<input type="time" id="ab-start-time" value="' + escapeHtml(model.start_time || '') + '" step="300" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;">' +
                '</div>' +
                '<div>' +
                    '<label style="display:block;font-weight:600;color:#374151;margin-bottom:4px;">Ora fine *</label>' +
                    '<input type="time" id="ab-end-time" value="' + escapeHtml(model.end_time || '') + '" step="300" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;">' +
                '</div>' +
            '</div>' +
            '<div style="margin-bottom:10px;">' +
                '<label style="display:block;font-weight:600;color:#374151;margin-bottom:4px;">Tipo *</label>' +
                '<select id="ab-type" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;background:#fff;">' +
                    '<option value="">— Seleziona tipo —</option>' +
                    typeOpts +
                '</select>' +
            '</div>' +
            '<div>' +
                '<label style="display:block;font-weight:600;color:#374151;margin-bottom:4px;">Note</label>' +
                '<textarea id="ab-notes" rows="3" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;resize:vertical;">' + escapeHtml(model.notes || '') + '</textarea>' +
            '</div>' +
        '</div>';
    }

    function wireHandlers() {
        const allDay = document.getElementById('ab-all-day');
        const hourlyRow = document.getElementById('ab-hourly-row');
        const startDate = document.getElementById('ab-start-date');
        const endDate = document.getElementById('ab-end-date');
        const startTime = document.getElementById('ab-start-time');
        const endTime = document.getElementById('ab-end-time');

        allDay.addEventListener('change', () => {
            if (allDay.checked) {
                hourlyRow.style.display = 'none';
                startTime.value = '';
                endTime.value = '';
            } else {
                hourlyRow.style.display = 'grid';
                // Forza single-day: end = start
                endDate.value = startDate.value;
            }
        });
        startDate.addEventListener('change', () => {
            if (!allDay.checked) endDate.value = startDate.value;
        });
    }

    function readForm() {
        return {
            id: document.getElementById('ab-id') ? document.getElementById('ab-id').value : null,
            therapist_id: document.getElementById('ab-therapist').value,
            start_date: document.getElementById('ab-start-date').value,
            end_date: document.getElementById('ab-end-date').value,
            start_time: document.getElementById('ab-all-day').checked ? '' : document.getElementById('ab-start-time').value,
            end_time: document.getElementById('ab-all-day').checked ? '' : document.getElementById('ab-end-time').value,
            type: document.getElementById('ab-type').value,
            notes: document.getElementById('ab-notes').value,
        };
    }

    function validateForm(f) {
        if (!f.therapist_id) return 'Seleziona un terapista.';
        if (!f.start_date || !f.end_date) return 'Specifica le date.';
        if (f.start_date > f.end_date) return 'La data di fine deve essere uguale o successiva a quella di inizio.';
        if (!f.type) return 'Seleziona il tipo di assenza.';
        if (f.start_time || f.end_time) {
            if (!f.start_time || !f.end_time) return 'Specifica entrambi gli orari.';
            if (f.start_date !== f.end_date) return 'Un\\'assenza oraria deve essere su un singolo giorno.';
            if (f.start_time >= f.end_time) return 'L\\'orario di fine deve essere successivo a quello di inizio.';
        }
        return null;
    }

    function submitForm(absenceId) {
        const f = readForm();
        const err = validateForm(f);
        if (err) {
            Swal.showValidationMessage(err);
            return false;
        }
        const body = new FormData();
        body.append(CSRF_PARAM, CSRF_TOKEN);
        if (absenceId) body.append('id', absenceId);
        body.append('Absence[therapist_id]', f.therapist_id);
        body.append('Absence[start_date]', f.start_date);
        body.append('Absence[end_date]', f.end_date);
        body.append('Absence[start_time]', f.start_time ? f.start_time + ':00' : '');
        body.append('Absence[end_time]', f.end_time ? f.end_time + ':00' : '');
        body.append('Absence[type]', f.type);
        body.append('Absence[notes]', f.notes);

        return fetch(SAVE_URL, {
            method: 'POST',
            credentials: 'include',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body,
        }).then(r => r.json()).then(data => {
            if (!data.success) {
                let msg = data.error || 'Errore salvataggio';
                if (data.errors) {
                    const flat = Object.values(data.errors).flat();
                    if (flat.length) msg = flat.join(' ');
                }
                Swal.showValidationMessage(msg);
                return false;
            }
            return data;
        }).catch(() => {
            Swal.showValidationMessage('Errore di rete');
            return false;
        });
    }

    function openModal(model) {
        const isUpdate = !!model.id;
        Swal.fire({
            title: isUpdate ? 'Modifica assenza' : 'Nuova assenza terapista',
            html: buildModalHtml(model),
            showCancelButton: true,
            confirmButtonText: isUpdate ? 'Salva modifiche' : 'Crea assenza',
            cancelButtonText: 'Annulla',
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
            focusConfirm: false,
            width: 560,
            didOpen: wireHandlers,
            preConfirm: () => submitForm(model.id),
        }).then(res => {
            if (res.isConfirmed && res.value && res.value.success) {
                Swal.fire({
                    icon: 'success',
                    title: res.value.message || 'Salvato',
                    timer: 1400,
                    showConfirmButton: false,
                });
                $.pjax.reload({ container: '#absence-grid-pjax' });
            }
        });
    }

    // Nuova
    const btnNew = document.getElementById('btn-new-absence');
    if (btnNew) btnNew.addEventListener('click', () => openModal({}));

    // Modifica (delegato perche' la grid e' dentro pjax e si rigenera)
    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit-absence');
        if (!btn) return;
        const id = btn.dataset.id;
        if (!id) return;

        fetch(GET_URL + '?id=' + encodeURIComponent(id), {
            credentials: 'include',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).then(r => r.json()).then(data => {
            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'Errore', text: data.error || 'Impossibile caricare l\\'assenza' });
                return;
            }
            openModal(data.data);
        }).catch(() => {
            Swal.fire({ icon: 'error', title: 'Errore', text: 'Errore di rete' });
        });
    });
})();
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>
