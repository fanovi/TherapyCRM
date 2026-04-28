<?php

use common\models\AccountPatient;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $accountPatients common\models\AccountPatient[] */

$profileName = $model->profile ? $model->profile->last_name . ' ' . $model->profile->first_name : $model->email;
$this->title = 'Account: ' . $profileName;
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Account', 'url' => ['accounts']];
$this->params['breadcrumbs'][] = $profileName;

$relationshipLabels = AccountPatient::getRelationshipLabels();

// Register URLs for JS
$this->registerJsVar('linkPatientUrl', Url::to(['patient/link-patient']));
$this->registerJsVar('unlinkPatientUrl', Url::to(['patient/unlink-patient']));
$this->registerJsVar('searchPatientsUrl', Url::to(['patient/search-patients']));
$this->registerJsVar('currentUserId', $model->id);
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($profileName) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                Dettaglio Account
            </h2>

            <nav>
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/site/index']) ?>">
                            Home
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= Url::to(['/patient/accounts']) ?>">
                            Account
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li class="text-sm text-gray-800 dark:text-white/90" x-text="pageName"></li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Breadcrumb End -->

    <!-- Dati Account -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Informazioni Account
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Dati dell'account utente.
                    </p>
                </div>
                <?php if (Yii::$app->user->can('create_patient')): ?>
                    <?= Html::a(
                        '<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>Modifica',
                        ['edit-account', 'id' => $model->id],
                        [
                            'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600',
                            'data-pjax' => '0',
                        ]
                    ) ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    [
                        'label' => 'Nome',
                        'value' => function ($model) {
                            return $model->profile ? $model->profile->first_name : '-';
                        }
                    ],
                    [
                        'label' => 'Cognome',
                        'value' => function ($model) {
                            return $model->profile ? $model->profile->last_name : '-';
                        }
                    ],
                    [
                        'attribute' => 'email',
                        'label' => 'Email',
                    ],
                    [
                        'label' => 'Codice Fiscale',
                        'value' => function ($model) {
                            return $model->profile && $model->profile->fiscal_code ? $model->profile->fiscal_code : '-';
                        }
                    ],
                    [
                        'label' => 'Telefono',
                        'value' => function ($model) {
                            return $model->profile && $model->profile->phone ? $model->profile->phone : '-';
                        }
                    ],
                    [
                        'label' => 'Indirizzo',
                        'value' => function ($model) {
                            return $model->profile && $model->profile->address ? $model->profile->address : '-';
                        }
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Stato',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->status === 'active') {
                                return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Attivo</span>';
                            }
                            return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inattivo</span>';
                        }
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Creato il',
                        'value' => function ($model) {
                            return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at, 'php:d/m/Y H:i') : '-';
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Pazienti Collegati -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        Pazienti Collegati
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Pazienti associati a questo account per l'accesso all'app mobile.
                    </p>
                </div>
                <?php if (Yii::$app->user->can('create_patient')): ?>
                    <button type="button" id="addPatientBtn"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Aggiungi Paziente
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800" id="patientsListContainer">
            <?php if (empty($accountPatients)): ?>
                <div class="px-5 py-8 sm:px-6 text-center" id="emptyState">
                    <div class="text-gray-400 dark:text-gray-500 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-800 dark:text-white/90 mb-2">Nessun paziente collegato</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Non ci sono pazienti associati a questo account.
                    </p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-100 dark:divide-gray-800" id="patientsList">
                    <?php foreach ($accountPatients as $accountPatient): ?>
                        <?php if ($accountPatient->patient): ?>
                        <div class="px-5 py-4 sm:px-6 flex items-center justify-between patient-item" data-patient-id="<?= $accountPatient->patient_id ?>">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-brand-100 dark:bg-brand-900 flex items-center justify-center">
                                            <svg class="h-5 w-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                            <?= Html::a(
                                                Html::encode($accountPatient->patient->last_name . ' ' . $accountPatient->patient->first_name),
                                                ['view', 'id' => $accountPatient->patient_id],
                                                ['class' => 'hover:text-brand-600 hover:underline']
                                            ) ?>
                                        </p>
                                        <div class="flex items-center space-x-4 mt-1">
                                            <?php if ($accountPatient->patient->fiscal_code): ?>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                                                <?= Html::encode($accountPatient->patient->fiscal_code) ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center space-x-2 mt-2">
                                            <?php
                                            $badgeClass = match($accountPatient->relationship_type) {
                                                'self' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                'parent' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'tutor' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                            };
                                            ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                                <?= Html::encode($accountPatient->getRelationshipLabel()) ?>
                                            </span>
                                            <?php if ($accountPatient->hasParentalAuthority()): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                                    Autorità Genitoriale
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (Yii::$app->user->can('create_patient')): ?>
                                <div class="flex-shrink-0">
                                    <button type="button"
                                            class="unlink-patient-btn inline-flex items-center px-3 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50"
                                            data-user-id="<?= $model->id ?>"
                                            data-patient-id="<?= $accountPatient->patient_id ?>"
                                            data-patient-name="<?= Html::encode($accountPatient->patient->last_name . ' ' . $accountPatient->patient->first_name) ?>"
                                            title="Rimuovi collegamento">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Rimuovi
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Aggiungi Paziente -->
<div id="addPatientModal" class="fixed inset-0 z-99999 hidden overflow-y-auto">
    <!-- Background overlay -->
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" id="modalOverlay"></div>

    <!-- Modal panel centered -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-md transform rounded-2xl bg-white p-6 shadow-xl transition-all dark:bg-gray-800">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Aggiungi Paziente all'Account
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" id="closeModalBtn">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="linkPatientForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Cerca Paziente
                    </label>
                    <input type="text"
                           id="patientSearch"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                           placeholder="Digita nome, cognome o codice fiscale..."
                           autocomplete="off">
                    <div id="patientSearchResults" class="mt-2 border border-gray-200 rounded-lg hidden max-h-48 overflow-y-auto dark:border-gray-600"></div>
                    <input type="hidden" name="patient_id" id="selectedPatientId">
                    <div id="selectedPatientDisplay" class="mt-2 hidden">
                        <span class="inline-flex items-center gap-2 px-3 py-2 bg-brand-100 text-brand-800 rounded-lg dark:bg-brand-900 dark:text-brand-300">
                            <span id="selectedPatientName"></span>
                            <button type="button" id="clearPatientSelection" class="text-brand-600 hover:text-brand-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tipo di Relazione
                    </label>
                    <select name="relationship_type" id="relationshipType"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <?php foreach ($relationshipLabels as $value => $label): ?>
                            <option value="<?= $value ?>"><?= Html::encode($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="has_parental_authority" id="hasParentalAuthority" value="1"
                               class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Ha autorità genitoriale</span>
                    </label>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelModalBtn"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Annulla
                    </button>
                    <button type="submit" id="submitLinkBtn"
                            class="px-4 py-2 text-sm font-medium text-white bg-brand-500 rounded-lg hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                        Collega Paziente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$js = <<<JS
// Modal handling
const modal = document.getElementById('addPatientModal');
const modalOverlay = document.getElementById('modalOverlay');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelModalBtn = document.getElementById('cancelModalBtn');
const addPatientBtn = document.getElementById('addPatientBtn');

function openModal() {
    modal.classList.remove('hidden');
    document.getElementById('patientSearch').value = '';
    document.getElementById('selectedPatientId').value = '';
    document.getElementById('selectedPatientDisplay').classList.add('hidden');
    document.getElementById('patientSearchResults').classList.add('hidden');
    document.getElementById('submitLinkBtn').disabled = true;
    document.getElementById('patientSearch').focus();
}

function closeModal() {
    modal.classList.add('hidden');
}

if (addPatientBtn) {
    addPatientBtn.addEventListener('click', openModal);
}
closeModalBtn.addEventListener('click', closeModal);
cancelModalBtn.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', closeModal);

// Patient search
let searchTimeout;
const patientSearch = document.getElementById('patientSearch');
const searchResults = document.getElementById('patientSearchResults');
const selectedPatientId = document.getElementById('selectedPatientId');
const selectedPatientDisplay = document.getElementById('selectedPatientDisplay');
const selectedPatientName = document.getElementById('selectedPatientName');
const submitBtn = document.getElementById('submitLinkBtn');

patientSearch.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const term = this.value.trim();

    if (term.length < 2) {
        searchResults.classList.add('hidden');
        return;
    }

    searchTimeout = setTimeout(function() {
        fetch(searchPatientsUrl + '?term=' + encodeURIComponent(term) + '&user_id=' + currentUserId)
            .then(response => response.json())
            .then(data => {
                if (data.results.length > 0) {
                    let html = '';
                    data.results.forEach(function(patient) {
                        html += '<div class="px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 patient-result" data-id="' + patient.id + '" data-text="' + patient.text + '">' + patient.text + '</div>';
                    });
                    searchResults.innerHTML = html;
                    searchResults.classList.remove('hidden');
                } else {
                    searchResults.innerHTML = '<div class="px-3 py-2 text-gray-500">Nessun paziente trovato</div>';
                    searchResults.classList.remove('hidden');
                }
            });
    }, 300);
});

searchResults.addEventListener('click', function(e) {
    const result = e.target.closest('.patient-result');
    if (result) {
        selectedPatientId.value = result.dataset.id;
        selectedPatientName.textContent = result.dataset.text;
        selectedPatientDisplay.classList.remove('hidden');
        patientSearch.value = '';
        searchResults.classList.add('hidden');
        submitBtn.disabled = false;
    }
});

document.getElementById('clearPatientSelection').addEventListener('click', function() {
    selectedPatientId.value = '';
    selectedPatientDisplay.classList.add('hidden');
    submitBtn.disabled = true;
});

// Form submission
document.getElementById('linkPatientForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData();
    formData.append('user_id', currentUserId);
    formData.append('patient_id', selectedPatientId.value);
    formData.append('relationship_type', document.getElementById('relationshipType').value);
    formData.append('has_parental_authority', document.getElementById('hasParentalAuthority').checked ? 1 : 0);
    formData.append(yii.getCsrfParam(), yii.getCsrfToken());

    fetch(linkPatientUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            // Reload page to show new patient
            location.reload();
        } else {
            alert(data.error || 'Errore nel collegamento');
        }
    });
});

// Unlink patient
document.addEventListener('click', function(e) {
    if (e.target.closest('.unlink-patient-btn')) {
        const btn = e.target.closest('.unlink-patient-btn');
        const patientName = btn.dataset.patientName;

        if (confirm('Sei sicuro di voler rimuovere il collegamento con ' + patientName + '?')) {
            const formData = new FormData();
            formData.append('user_id', btn.dataset.userId);
            formData.append('patient_id', btn.dataset.patientId);
            formData.append(yii.getCsrfParam(), yii.getCsrfToken());

            fetch(unlinkPatientUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to reflect changes
                    location.reload();
                } else {
                    alert(data.error || 'Errore nella rimozione');
                }
            });
        }
    }
});
JS;
$this->registerJs($js);
?>
