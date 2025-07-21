<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Accedi';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-brand-100 dark:bg-brand-900">
                <svg class="h-6 w-6 text-brand-600 dark:text-brand-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
                Accedi al tuo account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Inserisci le tue credenziali per accedere al sistema
            </p>
        </div>

        <!-- Login Form -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="p-8 space-y-6">
                <?php $form = ActiveForm::begin([
                    'id' => 'login-form',
                    'options' => ['class' => 'space-y-6'],
                ]); ?>

                    <!-- Username Field -->
                    <div>
                        <?= $form->field($model, 'username', [
                            'template' => '<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{label}</label>
                                         <div class="relative">
                                             <span class="absolute top-1/2 left-0 -translate-y-1/2 border-r border-gray-200 px-3.5 py-3 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                                 <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">

                                                     <path fill-rule="evenodd" clip-rule="evenodd" d="M10 11.5C7.51472 11.5 5.5 13.5147 5.5 16C5.5 16.2761 5.27614 16.5 5 16.5C4.72386 16.5 4.5 16.2761 4.5 16C4.5 12.9624 6.96243 10.5 10 10.5C13.0376 10.5 15.5 12.9624 15.5 16C15.5 16.2761 15.2761 16.5 15 16.5C14.7239 16.5 14.5 16.2761 14.5 16C14.5 13.5147 12.4853 11.5 10 11.5Z" fill="#667085"/>
                                                 </svg>
                                             </span>
                                             {input}
                                         </div>
                                         {error}',
                            'labelOptions' => [],
                        ])->textInput([
                            'placeholder' => 'inserisci il tuo username',
                            'autofocus' => true,
                            'class' => 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-[62px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30'
                        ]) ?>
                    </div>

                    <!-- Password Field -->
                    <!-- Password Field -->
                    <div x-data="{ showPassword: false }">
                        <?= $form->field($model, 'password', [
                            'template' => '<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{label}</label>
                                         <div class="relative">
                                             {input}
                                             <span @click="showPassword = !showPassword" class="absolute top-1/2 right-4 z-30 -translate-y-1/2 cursor-pointer">
                                                 <svg x-show="!showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                     <path fill-rule="evenodd" clip-rule="evenodd" d="M10.0002 13.8619C7.23361 13.8619 4.86803 12.1372 3.92328 9.70241C4.86804 7.26761 7.23361 5.54297 10.0002 5.54297C12.7667 5.54297 15.1323 7.26762 16.0771 9.70243C15.1323 12.1372 12.7667 13.8619 10.0002 13.8619ZM10.0002 4.04297C6.48191 4.04297 3.49489 6.30917 2.4155 9.4593C2.3615 9.61687 2.3615 9.78794 2.41549 9.94552C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C13.5184 15.3619 16.5055 13.0957 17.5849 9.94555C17.6389 9.78797 17.6389 9.6169 17.5849 9.45932C16.5055 6.30919 13.5184 4.04297 10.0002 4.04297ZM9.99151 7.84413C8.96527 7.84413 8.13333 8.67606 8.13333 9.70231C8.13333 10.7286 8.96527 11.5605 9.99151 11.5605H10.0064C11.0326 11.5605 11.8646 10.7286 11.8646 9.70231C11.8646 8.67606 11.0326 7.84413 10.0064 7.84413H9.99151Z"/>
                                                 </svg>
                                                 <svg x-show="showPassword" class="fill-gray-500 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                     <path fill-rule="evenodd" clip-rule="evenodd" d="M4.63803 3.57709C4.34513 3.2842 3.87026 3.2842 3.57737 3.57709C3.28447 3.86999 3.28447 4.34486 3.57737 4.63775L4.85323 5.91362C3.74609 6.84199 2.89363 8.06395 2.4155 9.45936C2.3615 9.61694 2.3615 9.78801 2.41549 9.94558C3.49488 13.0957 6.48191 15.3619 10.0002 15.3619C11.255 15.3619 12.4422 15.0737 13.4994 14.5598L15.3625 16.4229C15.6554 16.7158 16.1302 16.7158 16.4231 16.4229C16.716 16.13 16.716 15.6551 16.4231 15.3622L4.63803 3.57709ZM12.3608 13.4212L10.4475 11.5079C10.3061 11.5423 10.1584 11.5606 10.0064 11.5606H9.99151C8.96527 11.5606 8.13333 10.7286 8.13333 9.70237C8.13333 9.5461 8.15262 9.39434 8.18895 9.24933L5.91885 6.97923C5.03505 7.69015 4.34057 8.62704 3.92328 9.70247C4.86803 12.1373 7.23361 13.8619 10.0002 13.8619C10.8326 13.8619 11.6287 13.7058 12.3608 13.4212ZM16.0771 9.70249C15.7843 10.4569 15.3552 11.1432 14.8199 11.7311L15.8813 12.7925C16.6329 11.9813 17.2187 11.0143 17.5849 9.94561C17.6389 9.78803 17.6389 9.61696 17.5849 9.45938C16.5055 6.30925 13.5184 4.04303 10.0002 4.04303C9.13525 4.04303 8.30244 4.17999 7.52218 4.43338L8.75139 5.66259C9.1556 5.58413 9.57311 5.54303 10.0002 5.54303C12.7667 5.54303 15.1323 7.26768 16.0771 9.70249Z"/>
                                                 </svg>
                                             </span>
                                         </div>
                                         {error}',
                            'labelOptions' => [],
                        ])->textInput([
                            'placeholder' => 'inserisci la tua password',
                            'class' => 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 pl-4 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30',
                            'x-bind:type' => "showPassword ? 'text' : 'password'"
                        ]) ?>
                    </div>

   
                 

                    <!-- Submit Button -->
                    <div>
                        <?= Html::submitButton('Accedi', [
                            'class' => 'w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200',
                            'name' => 'login-button',
                            'style' => 'background-color: #2563eb !important; color: white !important;'
                        ]) ?>
                    </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>

        <!-- Footer Links -->
        <!-- <div class="text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Hai problemi ad accedere?
                <a href="#" class="font-medium text-brand-600 hover:text-brand-500 dark:text-brand-400 dark:hover:text-brand-300">
                    Contatta l'amministratore
                </a>
            </p>
        </div> -->
    </div>
</div>

<!-- Custom Styles for Validation Errors -->
<style>
.field-loginform-username.has-error .form-control,
.field-loginform-password.has-error .form-control {
    border-color: #fca5a5 !important;
}

.dark .field-loginform-username.has-error .form-control,
.dark .field-loginform-password.has-error .form-control {
    border-color: #b91c1c !important;
}

.field-loginform-username .help-block,
.field-loginform-password .help-block,
.field-loginform-rememberme .help-block {
    color: #ef4444 !important;
    font-size: 0.875rem !important;
    margin-top: 0.25rem !important;
}

/* Animazioni per il form */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.min-h-screen > div {
    animation: fadeIn 0.5s ease-out;
}

/* Loading spinner per il submit */
.btn-loading {
    color: transparent;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spinner 0.6s linear infinite;
}

@keyframes spinner {
    to {
        transform: rotate(360deg);
    }
}
</style>

<?php
// JavaScript per feedback visivo
$this->registerJs("
// Aggiungi classe loading al submit
$('#login-form').on('beforeSubmit', function() {
    const btn = $(this).find('button[type=\"submit\"]');
    btn.addClass('btn-loading relative');
    btn.prop('disabled', true);
    return true;
});

// Animazione shake per errori
$('#login-form').on('afterValidate', function(event, messages, errorAttributes) {
    if (errorAttributes.length > 0) {
        $('.rounded-2xl').addClass('animate-shake');
        setTimeout(() => {
            $('.rounded-2xl').removeClass('animate-shake');
        }, 500);
    }
});
");

// CSS per animazione shake
$this->registerCss("
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}


");
?>