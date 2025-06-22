<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */
/** @var common\models\Therapist $therapist */
/** @var array $specializations */

$this->title = 'Modifica Terapista: ' . $profile->first_name . ' ' . $profile->last_name;
$this->params['breadcrumbs'][] = ['label' => 'Terapisti', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $profile->first_name . ' ' . $profile->last_name, 'url' => ['view', 'id' => $therapist->id]];
$this->params['breadcrumbs'][] = 'Modifica';
?>

<div x-data="therapistUpdateForm()" class="mx-auto flex max-w-7xl flex-col lg:flex-row gap-6 p-4 md:p-6">
    <!-- Form Content -->
    <div class="flex-1">
        <!-- Breadcrumb Start -->
        <div x-data="{ pageName: 'Modifica Terapista'}">
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
                            <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/therapist/index']) ?>">
                                Terapisti
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

        <?php $form = ActiveForm::begin([
            'id' => 'therapist-update-form',
            'fieldConfig' => [
                'options' => ['class' => 'mb-4'],
                'errorOptions' => ['class' => 'text-error-600 text-sm mt-1'],
                'inputOptions' => ['class' => 'block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-brand-500 dark:focus:ring-brand-500 text-sm px-3 py-2'],
            ]
        ]); ?>

        <!-- Dati Personali -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Dati Personali
                </h3>
            </div>
            
            <div class="px-5 pb-5 sm:px-6 sm:pb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <?= $form->field($profile, 'first_name')->textInput([
                            'placeholder' => 'Nome'
                        ])->label('Nome <span class="text-red-500">*</span>', ['encode' => false]) ?>
                    </div>

                    <div>
                        <?= $form->field($profile, 'last_name')->textInput([
                            'placeholder' => 'Cognome'
                        ])->label('Cognome <span class="text-red-500">*</span>', ['encode' => false]) ?>
                    </div>

                    <div>
                        <?= $form->field($user, 'email')->textInput([
                            'placeholder' => 'email@example.com',
                            'type' => 'email'
                        ])->label('Email <span class="text-red-500">*</span>', ['encode' => false]) ?>
                    </div>

                    <div>
                        <?= $form->field($profile, 'phone')->textInput([
                            'placeholder' => '+39 123 456 7890'
                        ])->label('Telefono') ?>
                    </div>

                    <div>
                        <?= $form->field($profile, 'fiscal_code')->textInput([
                            'placeholder' => 'RSSMRA80A01H501X'
                        ])->label('Codice Fiscale') ?>
                    </div>

                    <div class="sm:col-span-2">
                        <?= $form->field($profile, 'address')->textInput([
                            'placeholder' => 'Via Roma 123, 00100 Roma (RM)'
                        ])->label('Indirizzo') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dati Professionali -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] mb-6">
            <div class="px-5 py-4 sm:px-6 sm:py-5">
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                    Dati Professionali
                </h3>
            </div>
            
            <div class="px-5 pb-5 sm:px-6 sm:pb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <?= $form->field($therapist, 'specialization_id')->dropDownList($specializations, [
                            'prompt' => 'Seleziona specializzazione...'
                        ])->label('Specializzazione <span class="text-red-500">*</span>', ['encode' => false]) ?>
                    </div>

                    <div>
                        <?= $form->field($therapist, 'weekly_hours_contract')->textInput([
                            'type' => 'number',
                            'min' => 1,
                            'max' => 40,
                            'placeholder' => '40'
                        ])->label('Ore Settimanali <span class="text-red-500">*</span>', ['encode' => false]) ?>
                    </div>

                    <div>
                        <?= $form->field($therapist, 'calendar_color')->textInput([
                            'type' => 'color',
                            'value' => $therapist->calendar_color ?: '#6B7280'
                        ])->label('Colore Calendario') ?>
                    </div>

                    <div class="flex items-center">
                        <div class="mt-6">
                            <label class="flex items-center">
                                <input type="hidden" name="Therapist[is_active]" value="0">
                                <input type="checkbox" 
                                       name="Therapist[is_active]" 
                                       value="1" 
                                       <?= $therapist->is_active ? 'checked' : '' ?>
                                       class="rounded border-gray-300 text-brand-600 shadow-sm focus:border-brand-300 focus:ring focus:ring-brand-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Terapista Attivo</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<!-- Fixed bottom action bar -->
<div class="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white/95 backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/95">
    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6">
        <div class="flex items-center justify-end gap-3">
            <?= Html::a('Annulla', ['view', 'id' => $therapist->id], [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2'
            ]) ?>
            
            <?= Html::submitButton('Salva Modifiche', [
                'class' => 'inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2',
                'form' => 'therapist-update-form'
            ]) ?>
        </div>
    </div>
</div>

<script>
function therapistUpdateForm() {
    return {
        init() {
            // Gestione classi di errore sui campi
            this.handleFieldErrors();
        },
        
        handleFieldErrors() {
            // Trova tutti i campi con errori
            document.querySelectorAll('.field-error').forEach(field => {
                const input = field.querySelector('input, select, textarea');
                if (input) {
                    input.classList.add('border-error-500', 'focus:border-error-500', 'focus:ring-error-500/10');
                    input.classList.remove('border-gray-300', 'focus:border-brand-500', 'focus:ring-brand-500');
                }
            });
            
            // Trova tutti i campi con messaggi di errore
            document.querySelectorAll('.help-block-error').forEach(errorBlock => {
                errorBlock.classList.add('text-error-600', 'text-sm', 'mt-1');
            });
        }
    }
}

// Esegui al caricamento della pagina
document.addEventListener('DOMContentLoaded', function() {
    // Aggiungi classe di errore ai campi che hanno errori
    document.querySelectorAll('.has-error').forEach(function(fieldContainer) {
        fieldContainer.classList.add('field-error');
        
        const input = fieldContainer.querySelector('input, select, textarea');
        if (input) {
            input.classList.add('border-error-500', 'focus:border-error-500', 'focus:ring-error-500/10');
            input.classList.remove('border-gray-300', 'focus:border-brand-500', 'focus:ring-brand-500');
        }
    });
    
    // Stilizza i messaggi di errore
    document.querySelectorAll('.help-block-error').forEach(function(errorMsg) {
        errorMsg.classList.add('text-error-600', 'text-sm', 'mt-1', 'font-medium');
    });
});
</script> 