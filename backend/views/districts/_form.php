<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\District;

/* @var $this yii\web\View */
/* @var $model District */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="districts-form">

    <?php $form = ActiveForm::begin([
        'id' => 'district-form',
        'options' => ['class' => 'space-y-6'],
        'fieldConfig' => [
            'template' => '<div class="form-field">{label}{input}{error}</div>',
            'labelOptions' => ['class' => 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2'],
            'inputOptions' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300'],
            'errorOptions' => ['class' => 'mt-1 text-sm text-red-600 dark:text-red-400'],
        ],
    ]); ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <?= $form->field($model, 'code')->textInput([
                'maxlength' => true,
                'placeholder' => 'Es. DST01',
                'style' => 'text-transform: uppercase;'
            ])->hint('Codice identificativo del distretto (solo lettere maiuscole e numeri)') ?>
        </div>

        <div>
            <?= $form->field($model, 'name')->textInput([
                'maxlength' => true,
                'placeholder' => 'Es. Distretto Centro'
            ]) ?>
        </div>
    </div>

    <div>
        <?= $form->field($model, 'asl_reference')->textInput([
            'maxlength' => true,
            'placeholder' => 'Es. ASL Napoli 1'
        ])->hint('Riferimento ASL di competenza (opzionale)') ?>
    </div>

    <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
        <div class="flex gap-3">
            <?= Html::submitButton($model->isNewRecord ? 'Crea Distretto' : 'Salva Modifiche', [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500',
                'id' => 'submit-btn'
            ]) ?>
            
            <?= Html::a('Annulla', $model->isNewRecord ? ['index'] : ['view', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600'
            ]) ?>
        </div>
        
        <?php if (!$model->isNewRecord): ?>
        <div>
            <?= Html::a('Elimina', ['delete', 'id' => $model->id], [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500',
                'data' => [
                    'confirm' => 'Sei sicuro di voler eliminare questo distretto?',
                    'method' => 'post',
                ],
            ]) ?>
        </div>
        <?php endif; ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
$(document).ready(function() {
    // Converti automaticamente il codice in maiuscolo
    $('#district-code').on('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Gestione submit via AJAX
    $('#district-form').on('beforeSubmit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $('#submit-btn');
        var originalText = $submitBtn.text();
        
        // Disabilita il pulsante e mostra loading
        $submitBtn.prop('disabled', true).text('Salvando...');
        
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    // Mostra messaggio di successo
                    showNotification(response.message, 'success');
                    
                    // Reindirizza dopo un breve delay
                    setTimeout(function() {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    }, 1000);
                } else {
                    // Mostra errori
                    showNotification(response.message, 'error');
                    
                    // Mostra errori di validazione
                    if (response.errors) {
                        $.each(response.errors, function(field, errors) {
                            var $field = $('#district-' + field);
                            var $errorContainer = $field.closest('.form-field').find('.field-error');
                            if ($errorContainer.length === 0) {
                                $errorContainer = $('<div class="field-error mt-1 text-sm text-red-600"></div>');
                                $field.after($errorContainer);
                            }
                            $errorContainer.text(errors[0]);
                        });
                    }
                }
            },
            error: function() {
                showNotification('Errore durante il salvataggio', 'error');
            },
            complete: function() {
                // Riabilita il pulsante
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
        
        return false;
    });
    
    // Rimuovi errori quando l'utente inizia a digitare
    $('input').on('input', function() {
        $(this).closest('.form-field').find('.field-error').remove();
    });
    
    // Funzione per mostrare notifiche
    function showNotification(message, type) {
        var bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        var $notification = $('<div class="fixed top-4 right-4 z-50 px-4 py-2 rounded-md text-white ' + bgColor + '">' + message + '</div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Gestione AJAX per eliminazione
    $(document).on('click', 'a[data-method="post"]', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var message = $link.data('confirm');
        
        if (message && !confirm(message)) {
            return;
        }
        
        $.ajax({
            url: $link.attr('href'),
            type: 'POST',
            data: {
                '_csrf': $('meta[name=csrf-token]').attr('content')
            },
            success: function(response) {
                if (response.status === 'success') {
                    showNotification(response.message, 'success');
                    setTimeout(function() {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    }, 1000);
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Errore durante l\'operazione', 'error');
            }
        });
    });
});
</script>
