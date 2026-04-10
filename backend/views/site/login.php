<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = 'Accedi - Backend';
?>

<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 mb-4">
            <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Backend Admin
        </h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Accedi con le tue credenziali amministratore
        </p>
    </div>

    <!-- Login Form -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <div class="p-8">
            <?php $form = ActiveForm::begin([
                'id' => 'login-form',
                'options' => ['class' => 'space-y-5'],
            ]); ?>

            <!-- Username Field -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Username</label>
                <?= $form->field($model, 'username', [
                    'template' => '{input}{error}',
                ])->textInput([
                    'placeholder' => 'Inserisci il tuo username',
                    'autofocus' => true,
                    'class' => 'w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 focus:outline-none transition dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400'
                ]) ?>
            </div>

            <!-- Password Field -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                <div class="relative">
                    <?= $form->field($model, 'password', [
                        'template' => '{input}{error}',
                    ])->passwordInput([
                        'id' => 'password-input',
                        'placeholder' => 'Inserisci la tua password',
                        'class' => 'w-full px-4 py-3 pr-12 rounded-lg border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 focus:outline-none transition dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400',
                    ]) ?>
                    <button type="button" id="toggle-password" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg id="icon-show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg id="icon-hide" class="w-5 h-5" style="display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <?= Html::submitButton('Accedi', [
                    'class' => 'w-full py-3 px-4 rounded-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-lg hover:shadow-xl',
                    'name' => 'login-button'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Footer -->
    <p class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
        San Luca Centro Medico &copy; <?= date('Y') ?>
    </p>
</div>

<style>
.has-error input {
    border-color: #ef4444 !important;
}
.help-block {
    color: #ef4444;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}
</style>

<script>
document.getElementById('toggle-password').addEventListener('click', function() {
    var input = document.getElementById('password-input');
    var iconShow = document.getElementById('icon-show');
    var iconHide = document.getElementById('icon-hide');
    if (input.type === 'password') {
        input.type = 'text';
        iconShow.style.display = 'none';
        iconHide.style.display = 'block';
    } else {
        input.type = 'password';
        iconShow.style.display = 'block';
        iconHide.style.display = 'none';
    }
});
</script>