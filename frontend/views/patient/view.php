<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Patient */
/* @var $accountPatients common\models\AccountPatient[] */

// Reset Password functionality
$this->registerJs("
window.resetPasswordManual = function(userId) {
    if (!confirm('Sei sicuro di voler resettare la password per questo utente? Verrà generato un PDF con le nuove credenziali.')) {
        return false;
    }
    
    var actionUrl = '" . \yii\helpers\Url::to(['reset-password']) . "';
    
    // Use AJAX to reset password and generate PDF
    \$.ajax({
        url: actionUrl,
        type: 'POST',
        data: {
            'userId': userId,
            '" . Yii::$app->request->csrfParam . "': '" . Yii::$app->request->csrfToken . "'
        },
        success: function(response) {
            // Trigger PDF download
            window.open('" . \yii\helpers\Url::to(['download-credentials-pdf']) . "', '_blank');
            // Reload page to show success message
            location.reload();
        },
        error: function(xhr, status, error) {
            console.error('Errore nel reset password:', error);
            console.error('Response:', xhr.responseText);
            alert('Errore nel reset password: ' + (xhr.responseJSON ? xhr.responseJSON.message : error));
        }
    });
};

window.sendCredentialsEmail = function(userId, userName) {
    if (!confirm('Sei sicuro di voler inviare le credenziali esistenti via email a \"' + userName + '\"?')) {
        return false;
    }
    
    var actionUrl = '" . \yii\helpers\Url::to(['send-credentials']) . "';
    
    // Use AJAX to send credentials email
    \$.ajax({
        url: actionUrl,
        type: 'POST',
        data: {
            'userId': userId,
            '" . Yii::$app->request->csrfParam . "': '" . Yii::$app->request->csrfToken . "'
        },
        success: function(response) {
            if (response.status === 'success') {
                alert('✅ ' + response.message);
                // Reload page to show success flash message
                location.reload();
            } else {
                alert('❌ Errore nell\'invio delle credenziali');
            }
        },
        error: function(xhr, status, error) {
            console.error('Errore nell\'invio credenziali:', error);
            console.error('Response:', xhr.responseText);
            var errorMessage = 'Errore nell\'invio delle credenziali';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage += ': ' + xhr.responseJSON.message;
            }
            alert('❌ ' + errorMessage);
        }
    });
};
", \yii\web\View::POS_READY);

$this->title = $model->fullName;
$this->params['breadcrumbs'][] = ['label' => 'Pazienti', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Get active therapeutic plan for this patient
$activeTherapeuticPlan = $model->getActiveTherapeuticPlan();
?>

<div class="mx-auto max-w-4xl p-4 md:p-6">
    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: '<?= Html::encode($this->title) ?>'}">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90" x-text="pageName"></h2>
            
            <nav>
                <ol class="flex items-center gap-1.5">
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/site/index']) ?>">
                            Home
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/patient/index']) ?>">
                            Pazienti
                            <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
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

    <!-- Action Buttons -->
    <div class="mb-6 flex flex-wrap gap-3">
        <?php if (Yii::$app->user->can('update_patient')): ?>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>Modifica',
                    ['update', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
            ]) ?>
        <?php endif; ?>
        
        <?php if ($activeTherapeuticPlan && Yii::$app->user->can('view_therapeutic_plan')): ?>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Piano Terapeutico #' . $activeTherapeuticPlan->id,
                    ['/therapeutic-plan/view', 'id' => $activeTherapeuticPlan->id], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-300 rounded-lg hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                'title' => 'Visualizza Piano Terapeutico (Inizio: ' . Yii::$app->formatter->asDate($activeTherapeuticPlan->start_date) . ', Durata: ' . $activeTherapeuticPlan->duration_days . ' giorni)'
            ]) ?>
        <?php endif; ?>
        
        <?php if (Yii::$app->user->can('create_patient')): ?>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>Crea Credenziali',
                    ['create-credentials', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-green-600 bg-white border border-green-300 rounded-lg hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2'
            ]) ?>
        <?php endif; ?>
        
        <?php if (Yii::$app->user->can('delete_patient')): ?>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>Elimina',
                    ['delete', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
                'data' => [
                    'confirm' => 'Sei sicuro di voler eliminare questo paziente?',
                    'method' => 'post',
                ],
            ]) ?>
        <?php endif; ?>
    </div>

    <!-- Dati Personali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Personali
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Informazioni anagrafiche del paziente.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'first_name',
                        'label' => 'Nome',
                    ],
                    [
                        'attribute' => 'last_name',
                        'label' => 'Cognome',
                    ],
                    [
                        'attribute' => 'gender',
                        'label' => 'Sesso',
                        'value' => function ($model) {
                            $genderLabels = ['M' => 'Maschio', 'F' => 'Femmina', 'N' => 'Non specificato'];
                            return $genderLabels[$model->gender] ?? 'Non specificato';
                        }
                    ],
                    [
                        'attribute' => 'fiscal_code',
                        'label' => 'Codice Fiscale',
                        'value' => function ($model) {
                            return $model->fiscal_code ?: '-';
                        }
                    ],
                    [
                        'attribute' => 'birth_date',
                        'label' => 'Data di Nascita',
                        'format' => ['date', 'php:d/m/Y'],
                    ],
                    [
                        'label' => 'Luogo di Nascita',
                        'value' => function ($model) {
                            return $model->getBirthLocation();
                        }
                    ],
                    [
                        'label' => 'Età',
                        'value' => function ($model) {
                            return $model->age ? $model->age . ' anni' : '-';
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Residenza e Contatti -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Residenza e Contatti
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Informazioni di residenza e contatto del paziente.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'residence_address',
                        'label' => 'Indirizzo',
                        'value' => function ($model) {
                            return $model->residence_address ?: '-';
                        }
                    ],
                    [
                        'attribute' => 'residence_city',
                        'label' => 'Comune',
                        'value' => function ($model) {
                            return $model->residence_city ?: '-';
                        }
                    ],
                    [
                        'attribute' => 'residence_province_code',
                        'label' => 'Provincia',
                        'value' => function ($model) {
                            if ($model->residence_province_name && $model->residence_province_code) {
                                return $model->residence_province_name . ' (' . $model->residence_province_code . ')';
                            } elseif ($model->residence_province_code) {
                                return $model->residence_province_code;
                            }
                            return '-';
                        }
                    ],
                    [
                        'attribute' => 'residence_postal_code',
                        'label' => 'CAP',
                        'value' => function ($model) {
                            return $model->residence_postal_code ?: '-';
                        }
                    ],
                    [
                        'attribute' => 'phone_number',
                        'label' => 'Telefono',
                        'value' => function ($model) {
                            return $model->phone_number ?: '-';
                        }
                    ],
                    [
                        'label' => 'Indirizzo Completo',
                        'value' => function ($model) {
                            $fullAddress = $model->getFullResidenceAddress();
                            return $fullAddress ?: '-';
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Informazioni Aggiuntive -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni Aggiuntive
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dettagli supplementari del paziente.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'district.name',
                        'label' => 'Distretto',
                        'value' => function ($model) {
                            return $model->district ? $model->district->name : '-';
                        }
                    ],
                    [
                        'attribute' => 'notes',
                        'label' => 'Note',
                        'value' => function ($model) {
                            return $model->notes ?: '-';
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <?php if ($activeTherapeuticPlan): ?>
    <!-- Piano Terapeutico Attivo -->
    <div class="rounded-2xl border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20 mb-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-medium text-blue-800 dark:text-blue-200">
                        Piano Terapeutico Attivo
                    </h3>
                    <p class="mt-1 text-sm text-blue-600 dark:text-blue-400">
                        Dettagli del piano terapeutico corrente per questo paziente.
                    </p>
                </div>
                <?php if (Yii::$app->user->can('view_therapeutic_plan')): ?>
                    <?= Html::a('Visualizza Dettagli', ['/therapeutic-plan/view', 'id' => $activeTherapeuticPlan->id], [
                        'class' => 'inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-300 rounded-lg hover:bg-blue-50'
                    ]) ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="border-t border-blue-100 dark:border-blue-800">
            <?= DetailView::widget([
                'model' => $activeTherapeuticPlan,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-blue-100 dark:border-blue-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-blue-800 dark:text-blue-200">{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'id',
                        'label' => 'ID Piano',
                        'value' => '#' . $activeTherapeuticPlan->id
                    ],
                    [
                        'attribute' => 'start_date',
                        'label' => 'Data Inizio',
                        'format' => ['date', 'php:d/m/Y'],
                    ],
                    [
                        'attribute' => 'duration_days',
                        'label' => 'Durata',
                        'value' => $activeTherapeuticPlan->duration_days . ' giorni'
                    ],
                    [
                        'attribute' => 'end_date',
                        'label' => 'Data Fine',
                        'format' => ['date', 'php:d/m/Y'],
                    ],
                    [
                        'attribute' => 'regime.nome',
                        'label' => 'Regime',
                        'value' => function ($model) {
                            return $model->regime ? $model->regime->nome : '-';
                        }
                    ],
                    [
                        'label' => 'Progresso',
                        'value' => function ($model) {
                            $progress = $model->getProgressPercentage();
                            return round($progress, 1) . '%';
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Informazioni di Sistema -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni di Sistema
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Date di creazione e aggiornamento del paziente.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?= DetailView::widget([
                'model' => $model,
                'options' => ['class' => 'w-full'],
                'template' => '<tr class="border-b border-gray-100 dark:border-gray-800 last:border-b-0"><th class="px-5 py-4 sm:px-6 text-left text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 w-1/3">{label}</th><td class="px-5 py-4 sm:px-6 text-sm text-gray-800 dark:text-white/90">{value}</td></tr>',
                'attributes' => [
                    [
                        'attribute' => 'created_at',
                        'label' => 'Creato il',
                        'format' => ['date', 'php:d/m/Y H:i'],
                    ],
                    [
                        'attribute' => 'updated_at',
                        'label' => 'Aggiornato il',
                        'format' => ['date', 'php:d/m/Y H:i'],
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <!-- Account Collegati -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mt-6">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Account Collegati
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Account utente associati a questo paziente per l'accesso all'app mobile.
            </p>
        </div>
        
        <div class="border-t border-gray-100 dark:border-gray-800">
            <?php if (empty($accountPatients)): ?>
                <div class="px-5 py-8 sm:px-6 text-center">
                    <div class="text-gray-400 dark:text-gray-500 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-800 dark:text-white/90 mb-2">Nessun account collegato</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Non ci sono account utente associati a questo paziente.
                    </p>
                    <?php if (Yii::$app->user->can('create_patient')): ?>
                        <?= Html::a('Crea Credenziali', ['create-credentials', 'id' => $model->id], [
                            'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-600 border border-transparent rounded-lg hover:bg-brand-700'
                        ]) ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php foreach ($accountPatients as $accountPatient): ?>
                        <div class="px-5 py-4 sm:px-6 flex items-center justify-between">
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
                                            <?= Html::encode($accountPatient->user->profile
                                                    ? $accountPatient->user->profile->last_name . ' ' . $accountPatient->user->profile->first_name
                                                    : 'Utente Senza Profilo') ?>
                                        </p>
                                        <div class="flex items-center space-x-4 mt-1">
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                                <?= Html::encode($accountPatient->user->email) ?>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-4 mt-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                <?= Html::encode($accountPatient->getRelationshipLabel()) ?>
                                            </span>
                                            <?php if ($accountPatient->hasParentalAuthority()): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Autorità Genitoriale
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (Yii::$app->user->can('update_patient')): ?>
                                <div class="flex-shrink-0">
                                    <div class="flex items-center gap-1">
                                        <?= Html::a(
                                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>',
                                            ['view-account', 'id' => $accountPatient->user_id],
                                            [
                                                'title' => 'Visualizza dettaglio account',
                                                'class' => 'text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20',
                                                'data-pjax' => '0',
                                            ]
                                        ) ?>
                                        <?= Html::a(
                                            '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
                                            ['edit-account', 'id' => $accountPatient->user_id],
                                            [
                                                'title' => 'Modifica dati account',
                                                'class' => 'text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-yellow-50 dark:hover:bg-yellow-900/20',
                                                'data-pjax' => '0',
                                            ]
                                        ) ?>
                                        <button type="button"
                                                onclick="sendCredentialsEmail(<?= $accountPatient->user_id ?>, '<?= Html::encode($accountPatient->user->profile ? $accountPatient->user->profile->last_name . ' ' . $accountPatient->user->profile->first_name : $accountPatient->user->email) ?>')"
                                                class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                                                title="Invia credenziali via email">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                        <button type="button"
                                                onclick="resetPasswordManual(<?= $accountPatient->user_id ?>)"
                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20"
                                                title="Rigenera credenziali e genera PDF">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (Yii::$app->user->can('create_patient')): ?>
                    <div class="px-5 py-4 sm:px-6 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/20">
                        <?= Html::a('
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Aggiungi Nuovo Account
                        ', ['create-credentials', 'id' => $model->id], [
                            'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-brand-600 bg-white border border-brand-300 rounded-lg hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:bg-gray-800 dark:border-brand-600 dark:text-brand-400 dark:hover:bg-brand-900/20'
                        ]) ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Debug PDF Download (solo per test) -->
    <!-- <?php if (Yii::$app->user->can('update_patient') && !empty($accountPatients)): ?>
        <div class="rounded-2xl border border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20 mt-6">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <h3 class="text-base font-medium text-yellow-800 dark:text-yellow-200">
                    🔧 Test PDF Download
                </h3>
                <p class="mt-1 text-sm text-yellow-600 dark:text-yellow-400">
                    Link diretto per testare il download del PDF delle credenziali.
                </p>
                <div class="mt-3">
                    <a href="<?= \yii\helpers\Url::to(['download-credentials-pdf']) ?>" 
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-yellow-600 border border-transparent rounded-lg hover:bg-yellow-700">
                        📄 Test Download PDF Diretto
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?> -->
</div> 