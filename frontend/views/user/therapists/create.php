<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserProfile $profile */
/** @var common\models\Therapist $therapist */
/** @var array $specializations */

$this->title = 'Nuovo Terapista';
$this->params['breadcrumbs'][] = ['label' => 'Terapisti', 'url' => ['therapists']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="mx-auto max-w-4xl p-4 md:p-6 min-h-screen">
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
                        <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="<?= \yii\helpers\Url::to(['/user/therapists']) ?>">
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
        'id' => 'therapist-form',
        'options' => ['class' => 'space-y-6'],
    ]); ?>

    <!-- Dati Personali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Personali
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Inserisci i dati anagrafici del terapista.
            </p>
        </div>
        
        <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800 space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Nome -->
                <div>
                    <?= $form->field($profile, 'first_name')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Inserisci il nome'
                    ])->label('Nome', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <!-- Cognome -->
                <div>
                    <?= $form->field($profile, 'last_name')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Inserisci il cognome'
                    ])->label('Cognome', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Email -->
                <div>
                    <?= $form->field($user, 'email')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'terapista@esempio.com',
                        'type' => 'email'
                    ])->label('Email', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>

                <!-- Telefono -->
                <div>
                    <?= $form->field($profile, 'phone')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => '+39 123 456 7890',
                        'type' => 'tel'
                    ])->label('Telefono', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
            </div>

            <!-- Codice Fiscale -->
            <div>
                <?= $form->field($profile, 'fiscal_code')->textInput([
                    'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                    'placeholder' => 'RSSMRO80A01H501X'
                ])->label('Codice Fiscale', [
                    'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                ]) ?>
            </div>

            <!-- Indirizzo -->
            <div>
                <?= $form->field($profile, 'address')->textarea([
                    'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                    'placeholder' => 'Via Roma, 123, 00100 Roma',
                    'rows' => 3
                ])->label('Indirizzo', [
                    'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                ]) ?>
            </div>
        </div>
    </div>

    <!-- Dati Professionali -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Dati Professionali
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Configura i dettagli professionali del terapista.
            </p>
        </div>
        
        <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800 space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Specializzazione -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5">
                        Specializzazione
                    </label>
                    <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                        <?= $form->field($therapist, 'specialization_id')->dropDownList($specializations, [
                            'prompt' => 'Seleziona specializzazione',
                            'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                            'onchange' => 'isOptionSelected = true'
                        ])->label(false) ?>
                        <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                            <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <!-- Ore settimanali contratto -->
                <div>
                    <?= $form->field($therapist, 'weekly_hours_contract')->textInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => '40',
                        'type' => 'number',
                        'min' => '1',
                        'max' => '60'
                    ])->label('Ore Settimanali Contratto', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                </div>
            </div>

            <!-- Colore Calendario -->
            <div>
                <?= $form->field($therapist, 'calendar_color')->textInput([
                    'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-20 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                    'type' => 'color',
                    'value' => '#4ECDC4'
                ])->label('Colore Calendario', [
                    'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                ]) ?>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Seleziona un colore per identificare il terapista nel calendario.
                </p>
            </div>

            <!-- Stato attivo -->
            <div x-data="{ checkboxToggle: true }">
                <label for="is_active_checkbox" class="flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                    <div class="relative">
                        <?= $form->field($therapist, 'is_active')->hiddenInput(['value' => '0'])->label(false) ?>
                        <input type="checkbox" 
                               name="Therapist[is_active]" 
                               id="is_active_checkbox" 
                               value="1" 
                               checked 
                               class="sr-only" 
                               @change="checkboxToggle = !checkboxToggle" />
                        <div :class="checkboxToggle ? 'border-brand-500 bg-brand-500' : 'bg-transparent border-gray-300 dark:border-gray-700'" class="hover:border-brand-500 dark:hover:border-brand-500 mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]">
                            <span :class="checkboxToggle ? '' : 'opacity-0'">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11.6666 3.5L5.24992 9.91667L2.33325 7" stroke="white" stroke-width="1.94437" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    Terapista Attivo
                </label>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Il terapista sarà visibile e disponibile per gli appuntamenti.
                </p>
            </div>
        </div>
    </div>

    <!-- Credenziali Accesso -->
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Credenziali di Accesso
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Imposta le credenziali per l'accesso al sistema.
            </p>
        </div>
        
        <div class="border-t border-gray-100 p-5 sm:p-6 dark:border-gray-800 space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <!-- Password -->
                <div x-data="{ showPassword: false }">
                    <?= $form->field($user, 'password')->passwordInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Inserisci password',
                        'x-bind:type' => "showPassword ? 'text' : 'password'"
                    ])->label('Password', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                    <span @click="showPassword = !showPassword" class="absolute top-1/2 right-4 z-30 -translate-y-1/2 cursor-pointer mt-6">
                        <svg x-show="!showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z"/>
                        </svg>
                        <svg x-show="showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z"/>
                        </svg>
                    </span>
                </div>

                <!-- Conferma Password -->
                <div x-data="{ showPassword: false }">
                    <?= $form->field($user, 'password_repeat')->passwordInput([
                        'class' => 'shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                        'placeholder' => 'Ripeti password',
                        'x-bind:type' => "showPassword ? 'text' : 'password'"
                    ])->label('Conferma Password', [
                        'class' => 'block text-sm font-medium text-gray-700 dark:text-gray-400 mb-1.5'
                    ]) ?>
                    <span @click="showPassword = !showPassword" class="absolute top-1/2 right-4 z-30 -translate-y-1/2 cursor-pointer mt-6">
                        <svg x-show="!showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z"/>
                        </svg>
                        <svg x-show="showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z"/>
                        </svg>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

    <!-- Fixed Bottom Action Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 p-4 z-50">
        <div class="mx-auto max-w-4xl flex items-center justify-between gap-3">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                * Tutti i campi sono obbligatori
            </div>
            
            <div class="flex items-center gap-3">
                <?= Html::a('Annulla', ['therapists'], [
                    'class' => 'px-6 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-700'
                ]) ?>
                
                <?= Html::submitButton('Crea Terapista', [
                    'class' => 'px-6 py-2.5 text-sm font-medium text-white bg-brand-500 border border-transparent rounded-lg hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
                    'form' => 'therapist-form'
                ]) ?>
            </div>
        </div>
    </div>

    <!-- Bottom padding to avoid overlap with fixed bar -->
    <div class="h-20"></div>
</div>