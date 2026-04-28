<?php

use common\models\AccountPatient;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\AccountSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Account Pazienti';
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$relationshipLabels = AccountPatient::getRelationshipLabels();
$canCreateAccount = Yii::$app->user->can('create_patient') || Yii::$app->user->can('view_own_group_patients');
$searchPatientsUrl = Url::to(['patient/search-patients-for-account']);
?>

<div class="mx-auto max-w-7xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            <?php if ($canCreateAccount): ?>
            <button
                type="button"
                onclick="window.openNewAccountModal && window.openNewAccountModal()"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuovo Account
            </button>
            <?php endif; ?>
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
                                    return $model->profile->last_name . ' ' . $model->profile->first_name;
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
                            'headerOptions' => ['class' => 'px-4 py-3 min-w-[140px] text-center'],
                            'contentOptions' => ['class' => 'px-4 py-4 whitespace-nowrap text-center'],
                            'template' => '<div class="flex items-center justify-center gap-1">{view} {update} {regenerate}</div>',
                            'buttons' => [
                                'view' => function ($url, $model) {
                                    return Html::a(
                                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                                        ['view-account', 'id' => $model->id],
                                        [
                                            'class' => 'inline-flex items-center justify-center w-8 h-8 text-white bg-brand-500 rounded-lg hover:bg-brand-600 transition-colors',
                                            'title' => 'Visualizza dettaglio account',
                                            'data-pjax' => '0',
                                        ]
                                    );
                                },
                                'update' => function ($url, $model) {
                                    return Html::a(
                                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
                                        ['view-account', 'id' => $model->id],
                                        [
                                            'class' => 'inline-flex items-center justify-center w-8 h-8 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors',
                                            'title' => 'Modifica dati account',
                                            'data-pjax' => '0',
                                        ]
                                    );
                                },
                                'regenerate' => function ($url, $model) {
                                    return Html::button(
                                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>',
                                        [
                                            'type' => 'button',
                                            'class' => 'regenerate-credentials-btn inline-flex items-center justify-center w-8 h-8 text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 transition-colors',
                                            'data-user-id' => $model->id,
                                            'data-user-email' => $model->email,
                                            'title' => 'Rigenera le credenziali e scarica il PDF',
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

<?php if ($canCreateAccount): ?>
<!-- Modale "Nuovo Account": ricerca paziente -->
<div id="new-account-modal"
     aria-modal="true"
     role="dialog"
     aria-labelledby="new-account-modal-title"
     style="display: none; position: fixed; inset: 0; z-index: 9999;">
    <!-- Backdrop -->
    <div onclick="window.closeNewAccountModal && window.closeNewAccountModal()"
         style="position: absolute; inset: 0; background-color: rgba(17, 24, 39, 0.6); backdrop-filter: blur(2px);"></div>

    <!-- Dialog wrapper (centrato) -->
    <div style="position: relative; z-index: 10; min-height: 100%; display: flex; align-items: center; justify-content: center; padding: 16px;">
        <div style="position: relative; width: 100%; max-width: 640px; background: #ffffff; border-radius: 16px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden;">
            <!-- Header -->
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid #f3f4f6;">
                <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 9999px; background: #eff6ff; color: #2563eb; flex-shrink: 0;">
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </span>
                    <div style="min-width: 0;">
                        <h3 id="new-account-modal-title" style="margin: 0; font-size: 16px; font-weight: 600; color: #111827;">Nuovo Account</h3>
                        <p style="margin: 2px 0 0 0; font-size: 12px; color: #6b7280;">Seleziona il paziente per cui creare le credenziali.</p>
                    </div>
                </div>
                <button type="button"
                        onclick="window.closeNewAccountModal && window.closeNewAccountModal()"
                        title="Chiudi"
                        style="background: transparent; border: 0; padding: 6px; cursor: pointer; color: #9ca3af; line-height: 0; flex-shrink: 0;">
                    <svg style="width: 20px; height: 20px; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div style="padding: 20px 24px;">
                <div style="position: relative;">
                    <span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); pointer-events: none; line-height: 0;">
                        <svg style="width: 18px; height: 18px; color: #9ca3af; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z"></path>
                        </svg>
                    </span>
                    <input type="text"
                           id="new-account-search-input"
                           autocomplete="off"
                           placeholder="Cerca per nome, cognome o codice fiscale (almeno 2 caratteri)..."
                           style="width: 100%; padding: 12px 44px 12px 44px; font-size: 14px; line-height: 1.4; border: 1px solid #e5e7eb; border-radius: 10px; background: #ffffff; color: #111827; outline: none; box-sizing: border-box;"
                           onfocus="this.style.borderColor='#3b82f6';this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.15)'"
                           onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none'">
                    <button type="button"
                            id="new-account-search-clear"
                            title="Pulisci ricerca"
                            style="display: none; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: 0; padding: 6px; cursor: pointer; color: #9ca3af; line-height: 0;">
                        <svg style="width: 16px; height: 16px; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <p id="new-account-search-hint"
                   style="margin: 12px 0 0 0; font-size: 12px; color: #6b7280;">
                    Digita almeno 2 caratteri per iniziare la ricerca.
                </p>

                <div id="new-account-search-results"
                     style="display: none; margin-top: 12px; max-height: 320px; overflow-y: auto; border: 1px solid #f3f4f6; border-radius: 12px; background: #ffffff;"></div>
            </div>

            <!-- Footer -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding: 16px 24px; border-top: 1px solid #f3f4f6; background: #fafafa;">
                <button type="button"
                        onclick="window.closeNewAccountModal && window.closeNewAccountModal()"
                        style="padding: 8px 16px; font-size: 14px; font-weight: 500; color: #374151; background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;">
                    Annulla
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canCreateAccount): ?>
<?php
$jsSearchUrl = json_encode($searchPatientsUrl);
$this->registerJs(<<<JS
(function () {
    var modal = document.getElementById('new-account-modal');
    var input = document.getElementById('new-account-search-input');
    var clearBtn = document.getElementById('new-account-search-clear');
    var resultsEl = document.getElementById('new-account-search-results');
    var hintEl = document.getElementById('new-account-search-hint');
    if (!modal || !input || !resultsEl) { return; }

    var searchUrl = {$jsSearchUrl};
    var debounceTimer = null;
    var lastTerm = '';
    var currentRequest = null;

    function clearChildren(el) {
        while (el.firstChild) { el.removeChild(el.firstChild); }
    }

    function setHint(text) {
        if (!hintEl) { return; }
        hintEl.textContent = text || '';
        hintEl.style.display = text ? '' : 'none';
    }

    function showMessage(text, isError) {
        clearChildren(resultsEl);
        var div = document.createElement('div');
        div.style.padding = '24px 16px';
        div.style.textAlign = 'center';
        div.style.fontSize = '14px';
        div.style.color = isError ? '#dc2626' : '#6b7280';
        div.textContent = text;
        resultsEl.appendChild(div);
        resultsEl.style.display = 'block';
    }

    function buildResultRow(p) {
        var li = document.createElement('li');
        li.style.borderBottom = '1px solid #f3f4f6';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.style.cssText = 'width:100%;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:12px 16px;text-align:left;background:transparent;border:0;cursor:pointer;';
        btn.addEventListener('mouseover', function () { btn.style.background = '#f9fafb'; });
        btn.addEventListener('mouseout', function () { btn.style.background = 'transparent'; });
        btn.setAttribute('data-create-url', p.create_url || '');

        var leftCol = document.createElement('div');
        leftCol.style.cssText = 'min-width:0;flex:1 1 auto;';

        var nameRow = document.createElement('div');
        nameRow.style.cssText = 'display:flex;align-items:center;font-size:14px;font-weight:500;color:#111827;';

        var nameSpan = document.createElement('span');
        nameSpan.style.cssText = 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
        nameSpan.textContent = p.full_name || '';
        nameRow.appendChild(nameSpan);

        if (p.accounts_count && p.accounts_count > 0) {
            var badge = document.createElement('span');
            badge.style.cssText = 'margin-left:8px;display:inline-flex;align-items:center;padding:2px 8px;font-size:11px;font-weight:500;border-radius:9999px;background:#fef3c7;color:#92400e;';
            badge.title = 'Account già esistenti';
            badge.textContent = p.accounts_count + ' account';
            nameRow.appendChild(badge);
        }

        leftCol.appendChild(nameRow);

        var subParts = [];
        if (p.fiscal_code) { subParts.push('CF: ' + p.fiscal_code); }
        if (p.birth_date) { subParts.push('Nato il ' + p.birth_date); }
        if (subParts.length) {
            var sub = document.createElement('div');
            sub.style.cssText = 'margin-top:2px;font-size:12px;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
            sub.textContent = subParts.join(' · ');
            leftCol.appendChild(sub);
        }

        var rightCol = document.createElement('span');
        rightCol.style.cssText = 'flex-shrink:0;display:inline-flex;align-items:center;color:#2563eb;font-size:12px;font-weight:500;';
        var rightText = document.createTextNode('Crea credenziali');
        rightCol.appendChild(rightText);
        var arrow = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        arrow.setAttribute('fill', 'none');
        arrow.setAttribute('stroke', 'currentColor');
        arrow.setAttribute('viewBox', '0 0 24 24');
        arrow.style.cssText = 'width:16px;height:16px;margin-left:4px;';
        var arrowPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        arrowPath.setAttribute('stroke-linecap', 'round');
        arrowPath.setAttribute('stroke-linejoin', 'round');
        arrowPath.setAttribute('stroke-width', '2');
        arrowPath.setAttribute('d', 'M9 5l7 7-7 7');
        arrow.appendChild(arrowPath);
        rightCol.appendChild(arrow);

        btn.appendChild(leftCol);
        btn.appendChild(rightCol);

        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-create-url');
            if (url) {
                btn.disabled = true;
                btn.style.opacity = '0.6';
                window.location.href = url;
            }
        });

        li.appendChild(btn);
        return li;
    }

    function showResults(items) {
        clearChildren(resultsEl);
        resultsEl.style.display = 'block';
        if (!items || items.length === 0) {
            var empty = document.createElement('div');
            empty.style.cssText = 'padding:24px 16px;text-align:center;font-size:14px;color:#6b7280;';
            empty.textContent = 'Nessun paziente trovato per questa ricerca.';
            resultsEl.appendChild(empty);
            return;
        }
        var ul = document.createElement('ul');
        ul.style.cssText = 'list-style:none;margin:0;padding:0;';
        items.forEach(function (p) { ul.appendChild(buildResultRow(p)); });
        resultsEl.appendChild(ul);
    }

    function performSearch(term) {
        if (currentRequest && typeof currentRequest.abort === 'function') {
            try { currentRequest.abort(); } catch (e) {}
        }
        showMessage('Ricerca in corso...', false);
        currentRequest = jQuery.ajax({
            url: searchUrl,
            type: 'GET',
            dataType: 'json',
            data: { term: term },
            success: function (resp) {
                if (term !== lastTerm) { return; }
                showResults(resp && resp.results ? resp.results : []);
            },
            error: function (xhr, status) {
                if (status === 'abort') { return; }
                showMessage('Errore durante la ricerca. Riprova.', true);
            }
        });
    }

    function onInput() {
        var term = (input.value || '').trim();
        clearBtn.style.display = term.length ? 'block' : 'none';
        lastTerm = term;
        if (debounceTimer) { clearTimeout(debounceTimer); }
        if (term.length < 2) {
            resultsEl.style.display = 'none';
            clearChildren(resultsEl);
            setHint('Digita almeno 2 caratteri per iniziare la ricerca.');
            return;
        }
        setHint('');
        debounceTimer = setTimeout(function () { performSearch(term); }, 250);
    }

    input.addEventListener('input', onInput);
    clearBtn.addEventListener('click', function () {
        input.value = '';
        onInput();
        input.focus();
    });

    function openModal() {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        input.value = '';
        clearBtn.style.display = 'none';
        resultsEl.style.display = 'none';
        clearChildren(resultsEl);
        setHint('Digita almeno 2 caratteri per iniziare la ricerca.');
        setTimeout(function () { input.focus(); }, 30);
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        if (currentRequest && typeof currentRequest.abort === 'function') {
            try { currentRequest.abort(); } catch (e) {}
        }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display !== 'none') {
            closeModal();
        }
    });

    window.openNewAccountModal = openModal;
    window.closeNewAccountModal = closeModal;
})();
JS);
?>
<?php endif; ?>

<?php
// Handler "Rigenera credenziali" sui bottoni della grid. Conferma con
// SweetAlert2 (gia caricato globalmente via AppAsset), poi POST a
// /patient/reset-password e apertura del PDF in una nuova tab.
$resetUrl = \yii\helpers\Url::to(['patient/reset-password']);
$pdfUrl = \yii\helpers\Url::to(['patient/download-credentials-pdf']);
$jsResetUrl = json_encode($resetUrl);
$jsPdfUrl = json_encode($pdfUrl);
$this->registerJs(<<<JS
(function () {
    var resetUrl = $jsResetUrl;
    var pdfUrl = $jsPdfUrl;

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function setBtnLoading(btn, loading) {
        btn.disabled = !!loading;
        btn.style.opacity = loading ? '0.6' : '1';
        btn.style.cursor = loading ? 'wait' : '';
    }

    function doReset(btn, userId, email) {
        setBtnLoading(btn, true);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Rigenerazione in corso...',
                didOpen: function () { Swal.showLoading(); },
                allowOutsideClick: false, allowEscapeKey: false, allowEnterKey: false,
                showConfirmButton: false
            });
        }
        jQuery.ajax({
            url: resetUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                _csrf: jQuery('meta[name=csrf-token]').attr('content'),
                userId: userId
            }
        }).done(function (resp) {
            var ok = resp && (resp.status === 'success' || resp.success === true);
            if (ok) {
                window.open(pdfUrl, '_blank');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Credenziali rigenerate',
                        text: 'Il PDF e stato aperto in una nuova scheda. Le sessioni attive sono state revocate.',
                        confirmButtonColor: '#2563eb'
                    });
                }
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Errore',
                    text: (resp && (resp.error || resp.message)) || 'Impossibile rigenerare le credenziali.',
                    confirmButtonColor: '#2563eb'
                });
            }
        }).fail(function (xhr) {
            if (typeof Swal !== 'undefined') {
                var msg = (xhr && xhr.status === 403)
                    ? 'Non hai i permessi per rigenerare le credenziali.'
                    : 'Impossibile contattare il server. Riprova.';
                Swal.fire({
                    icon: 'error',
                    title: 'Errore',
                    text: msg,
                    confirmButtonColor: '#2563eb'
                });
            }
        }).always(function () {
            setBtnLoading(btn, false);
        });
    }

    // Delegation: la grid puo' essere ridisegnata da Pjax dopo filtri/sort,
    // quindi un singolo listener su document copre anche i bottoni rigenerati.
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest && ev.target.closest('.regenerate-credentials-btn');
        if (!btn) { return; }
        ev.preventDefault();

        var userId = btn.getAttribute('data-user-id');
        var email = btn.getAttribute('data-user-email') || '';
        if (!userId) { return; }

        if (typeof Swal === 'undefined') {
            if (!window.confirm('Rigenerare le credenziali per ' + email + '?')) { return; }
            doReset(btn, userId, email);
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Rigenerare le credenziali?',
            html: 'Verra generata una nuova password per <strong>' + escapeHtml(email)
                + '</strong> e prodotto un nuovo PDF da consegnare al paziente.<br><br>'
                + 'Tutte le sessioni attive verranno revocate.',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Si, rigenera e scarica PDF',
            cancelButtonText: 'Annulla',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                doReset(btn, userId, email);
            }
        });
    });
})();
JS);
?>
