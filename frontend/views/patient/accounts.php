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

<?php if ($canCreateAccount): ?>
<!-- Modale "Nuovo Account": ricerca paziente -->
<div
    id="new-account-modal"
    class="fixed inset-0 z-[1000] hidden items-center justify-center"
    aria-modal="true"
    role="dialog"
    aria-labelledby="new-account-modal-title">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="window.closeNewAccountModal && window.closeNewAccountModal()"></div>

    <div class="relative z-10 w-full max-w-2xl mx-4 rounded-2xl bg-white shadow-xl dark:bg-gray-900 dark:border dark:border-gray-800">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </span>
                <div>
                    <h3 id="new-account-modal-title" class="text-base font-semibold text-gray-900 dark:text-white">Nuovo Account</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Seleziona il paziente per cui creare le credenziali.</p>
                </div>
            </div>
            <button type="button"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    onclick="window.closeNewAccountModal && window.closeNewAccountModal()"
                    title="Chiudi">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="px-6 py-5">
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
                        style="display: none; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: 0; padding: 6px; cursor: pointer; color: #9ca3af; line-height: 0;"
                        title="Pulisci ricerca">
                    <svg style="width: 16px; height: 16px; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <p id="new-account-search-hint" class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Digita almeno 2 caratteri per iniziare la ricerca.
            </p>

            <div id="new-account-search-results" class="mt-3 max-h-80 overflow-y-auto rounded-xl border border-gray-100 dark:border-gray-800 hidden"></div>
        </div>

        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-800">
            <button type="button"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700"
                    onclick="window.closeNewAccountModal && window.closeNewAccountModal()">
                Annulla
            </button>
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
        div.className = 'px-4 py-6 text-center text-sm ' + (isError ? 'text-red-600' : 'text-gray-500 dark:text-gray-400');
        div.textContent = text;
        resultsEl.appendChild(div);
        resultsEl.classList.remove('hidden');
    }

    function buildResultRow(p) {
        var li = document.createElement('li');
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'w-full flex items-start justify-between gap-3 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-800/60';
        btn.setAttribute('data-create-url', p.create_url || '');

        var leftCol = document.createElement('div');
        leftCol.className = 'min-w-0';

        var nameRow = document.createElement('div');
        nameRow.className = 'flex items-center text-sm font-medium text-gray-900 dark:text-white';

        var nameSpan = document.createElement('span');
        nameSpan.className = 'truncate';
        nameSpan.textContent = p.full_name || '';
        nameRow.appendChild(nameSpan);

        if (p.accounts_count && p.accounts_count > 0) {
            var badge = document.createElement('span');
            badge.className = 'ml-2 inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
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
            sub.className = 'mt-0.5 text-xs text-gray-500 dark:text-gray-400 truncate';
            sub.textContent = subParts.join(' · ');
            leftCol.appendChild(sub);
        }

        var rightCol = document.createElement('span');
        rightCol.className = 'shrink-0 inline-flex items-center text-brand-600 dark:text-brand-400 text-xs font-medium';
        var rightText = document.createTextNode('Crea credenziali');
        rightCol.appendChild(rightText);
        var arrow = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        arrow.setAttribute('class', 'ml-1 w-4 h-4');
        arrow.setAttribute('fill', 'none');
        arrow.setAttribute('stroke', 'currentColor');
        arrow.setAttribute('viewBox', '0 0 24 24');
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
        resultsEl.classList.remove('hidden');
        if (!items || items.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400';
            empty.textContent = 'Nessun paziente trovato per questa ricerca.';
            resultsEl.appendChild(empty);
            return;
        }
        var ul = document.createElement('ul');
        ul.className = 'divide-y divide-gray-100 dark:divide-gray-800';
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
            resultsEl.classList.add('hidden');
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
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        input.value = '';
        clearBtn.style.display = 'none';
        resultsEl.classList.add('hidden');
        clearChildren(resultsEl);
        setHint('Digita almeno 2 caratteri per iniziare la ricerca.');
        setTimeout(function () { input.focus(); }, 30);
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        if (currentRequest && typeof currentRequest.abort === 'function') {
            try { currentRequest.abort(); } catch (e) {}
        }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    window.openNewAccountModal = openModal;
    window.closeNewAccountModal = closeModal;
})();
JS);
?>
<?php endif; ?>
