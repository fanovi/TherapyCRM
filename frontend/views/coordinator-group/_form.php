<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\Therapist;
use common\models\GroupTherapist;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var common\models\CoordinatorGroup $model */
/** @var yii\widgets\ActiveForm $form */
/** @var array $coordinators */
/** @var array $selectedTherapists */
/** @var array $therapistRoles */

// Ottieni tutti i terapisti attivi
$allTherapists = Therapist::find()
    ->with(['user.profile', 'specialization'])
    ->joinWith(['user.profile'])
    ->where(['therapists.is_active' => true])
    ->orderBy('user_profiles.last_name, user_profiles.first_name')
    ->all();

?>

<div class="mx-auto max-w-4xl p-4 md:p-6">

    <?php $form = ActiveForm::begin([
        'id' => 'coordinator-group-form',
        'enableClientValidation' => true,
        'validateOnSubmit' => true,
        'validateOnChange' => true,
        'validateOnBlur' => true,
        'options' => ['class' => 'space-y-6'],
        'fieldConfig' => [
            'options' => ['class' => 'mb-4'],
            'errorOptions' => [
                'class' => 'text-red-600 text-sm mt-1 font-medium help-block-error'
            ],
            'inputOptions' => ['class' => 'block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2'],
        ],
    ]); ?>

    <style>
    /* Stili per i campi con errori */
    .has-error input,
    .has-error select,
    .has-error textarea {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 1px #dc2626 !important;
    }
    
    .has-error input:focus,
    .has-error select:focus,
    .has-error textarea:focus {
        border-color: #dc2626 !important;
        box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.1) !important;
    }
    
    /* Assicuriamoci che i messaggi di errore siano rossi */
    .help-block-error {
        color: #dc2626 !important;
        font-weight: 500 !important;
        margin-top: 0.25rem !important;
        font-size: 0.875rem !important;
    }
    
    /* Stili per i messaggi di errore di Yii2 */
    .error-summary ul {
        color: #dc2626;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .error-summary li {
        color: #dc2626 !important;
        font-weight: 500;
    }
    </style>

    <!-- Informazioni Gruppo -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Informazioni Gruppo
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Configura le informazioni base del gruppo coordinatori.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <?= $form->field($model, 'name')->textInput([
                        'maxlength' => true,
                        'placeholder' => 'es. Gruppo Terapisti Nord'
                    ])->label('Nome Gruppo') ?>
                </div>

                <div>
                    <?= $form->field($model, 'coordinator_user_id')->dropDownList($coordinators, [
                        'prompt' => 'Seleziona un coordinatore...'
                    ])->label('Coordinatore') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Selezione Terapisti -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Terapisti del Gruppo
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Seleziona i terapisti che faranno parte di questo gruppo.
            </p>
        </div>
        
        <div class="px-5 pb-5 sm:px-6 sm:pb-6">

        <!-- Controlli Selezione -->
        <div class="flex gap-2 mb-4">
            <button type="button" class="select-all-btn px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-300 rounded-md hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-900/30">
                Seleziona Tutti
            </button>
            <button type="button" class="select-none-btn px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 border border-gray-300 rounded-md hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                Deseleziona Tutti
            </button>
        </div>

        <!-- Lista Terapisti -->
        <div class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <?php foreach ($allTherapists as $therapist): ?>
                <?php 
                $profile = $therapist->user->profile;
                $fullName = $profile ? $profile->first_name . ' ' . $profile->last_name : $therapist->user->email;
                $isSelected = isset($selectedTherapists) && in_array($therapist->id, $selectedTherapists);
                ?>
                
                <div class="therapist-card flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <!-- Checkbox -->
                    <div class="flex-shrink-0">
                        <input type="checkbox" 
                               name="therapists[]" 
                               value="<?= $therapist->id ?>" 
                               class="therapist-checkbox h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300 rounded"
                               <?= $isSelected ? 'checked' : '' ?>>
                    </div>

                    <!-- Avatar/Colore -->
                    <div class="ml-3 flex-shrink-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium"
                             style="background-color: <?= Html::encode($therapist->calendar_color ?: '#6B7280') ?>">
                            <?= strtoupper(substr($fullName, 0, 1)) ?>
                        </div>
                    </div>

                    <!-- Info Terapista -->
                    <div class="ml-3 flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            <?= Html::encode($fullName) ?>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <?= Html::encode($therapist->user->email) ?>
                            <?php if ($therapist->specialization): ?>
                                • <?= Html::encode($therapist->specialization->name) ?>
                            <?php endif; ?>
                        </div>
                    </div>


                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($allTherapists)): ?>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                <p class="mt-2">Nessun terapista attivo trovato</p>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center justify-between gap-4 pt-6">
        <div>
            <?= Html::a('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>Annulla', 
                ['index'], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
            ]) ?>
        </div>
        
        <div class="flex space-x-3">
            <?= Html::submitButton('<svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' . ($model->isNewRecord ? 'Crea Gruppo' : 'Aggiorna Gruppo'), [
                'class' => 'inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-950 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                'id' => 'submit-btn'
            ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
// JavaScript per gestire l'interazione
$this->registerJs("
    // Gestione selezione tutti/nessuno
    $('.select-all-btn').on('click', function(e) {
        e.preventDefault();
        $('.therapist-checkbox').prop('checked', true).trigger('change');
    });
    
    $('.select-none-btn').on('click', function(e) {
        e.preventDefault();
        $('.therapist-checkbox').prop('checked', false).trigger('change');
    });
    

    
    // Validazione form - almeno un terapista deve essere selezionato
    $('#submit-btn').on('click', function(e) {
        var selectedTherapists = $('.therapist-checkbox:checked').length;
        if (selectedTherapists === 0) {
            e.preventDefault();
            alert('Seleziona almeno un terapista per il gruppo.');
            return false;
        }
    });
");
?>

<!-- Info Box -->
<div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                Informazioni sui Gruppi Coordinatori
            </h3>
            <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                <ul class="list-disc list-inside space-y-1">
                    <li>Ogni gruppo deve avere un nome univoco nel sistema</li>
                    <li>Il coordinatore selezionato gestirà i terapisti assegnati al gruppo</li>
                    <li>Dopo aver creato il gruppo, potrai assegnare i terapisti utilizzando l'apposita funzione</li>
                    <li>I coordinatori potranno visualizzare solo i terapisti del proprio gruppo</li>
                </ul>
            </div>
        </div>
    </div>
</div> 