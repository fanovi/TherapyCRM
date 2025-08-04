<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Alert;
use yii\helpers\Html;

$this->title = 'Reset Password - ' . Yii::$app->name;
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900">
                Reset Password
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Crea una nuova password per il tuo account
            </p>
        </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            
            <?php if (Yii::$app->session->hasFlash('error')): ?>
                <div class="mb-4">
                    <?= Alert::widget([
                        'body' => Yii::$app->session->getFlash('error'),
                        'options' => ['class' => 'alert-danger'],
                    ]) ?>
                </div>
            <?php endif; ?>

            <?php if (Yii::$app->session->hasFlash('success')): ?>
                <div class="mb-4">
                    <?= Alert::widget([
                        'body' => Yii::$app->session->getFlash('success'),
                        'options' => ['class' => 'alert-success'],
                    ]) ?>
                </div>
            <?php endif; ?>

            <!-- User Info -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold">
                                <?= strtoupper(substr($user->email, 0, 1)) ?>
                            </span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">
                            <?= Html::encode($user->email) ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            Account attivo
                        </p>
                    </div>
                </div>
            </div>

            <?php $form = ActiveForm::begin([
                'id' => 'reset-password-form',
                'options' => ['class' => 'space-y-6'],
                'fieldConfig' => [
                    'options' => ['class' => 'form-group'],
                    'template' => "{label}\n{input}\n{error}",
                    'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700'],
                    'inputOptions' => ['class' => 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm'],
                    'errorOptions' => ['class' => 'mt-1 text-sm text-red-600'],
                ],
            ]); ?>

            <div>
                <?= $form->field($model, 'password')->passwordInput([
                    'placeholder' => 'Inserisci la nuova password',
                    'autocomplete' => 'new-password'
                ]) ?>
            </div>

            <div>
                <?= $form->field($model, 'password_repeat')->passwordInput([
                    'placeholder' => 'Conferma la nuova password',
                    'autocomplete' => 'new-password'
                ]) ?>
            </div>

            <!-- Password Requirements -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Requisiti password:</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li class="flex items-center">
                        <span class="text-green-500 mr-2">✓</span>
                        Almeno 8 caratteri
                    </li>
                    <li class="flex items-center">
                        <span class="text-green-500 mr-2">✓</span>
                        Almeno una lettera maiuscola
                    </li>
                    <li class="flex items-center">
                        <span class="text-green-500 mr-2">✓</span>
                        Almeno una lettera minuscola
                    </li>
                    <li class="flex items-center">
                        <span class="text-green-500 mr-2">✓</span>
                        Almeno un numero
                    </li>
                </ul>
            </div>

            <div>
                <?= Html::submitButton('Reset Password', [
                    'class' => 'w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
                    'name' => 'reset-button'
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            Oppure
                        </span>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">
                                Hai l'app mobile?
                            </span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">
                                        Token per app mobile
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p class="font-mono bg-yellow-100 p-2 rounded text-xs break-all">
                                            <?= Html::encode($token) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    <a href="<?= Yii::$app->urlManager->createUrl(['site/login']) ?>" class="font-medium text-blue-600 hover:text-blue-500">
                        Torna al login
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for better mobile experience */
@media (max-width: 640px) {
    .min-h-screen {
        min-height: 100vh;
    }
    
    .sm\:mx-auto {
        margin-left: auto;
        margin-right: auto;
    }
    
    .sm\:w-full {
        width: 100%;
    }
    
    .sm\:max-w-md {
        max-width: 28rem;
    }
    
    .sm\:px-10 {
        padding-left: 2.5rem;
        padding-right: 2.5rem;
    }
}

/* Ensure form elements are properly styled */
.form-group {
    margin-bottom: 1rem;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    color: #212529;
    background-color: #fff;
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.btn {
    display: inline-block;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    text-align: center;
    text-decoration: none;
    vertical-align: middle;
    cursor: pointer;
    user-select: none;
    background-color: transparent;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    border-radius: 0.375rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.btn-primary {
    color: #fff;
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.btn-primary:hover {
    color: #fff;
    background-color: #0b5ed7;
    border-color: #0a58ca;
}
</style>
