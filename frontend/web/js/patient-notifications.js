/**
 * Sistema Notifiche Pazienti - Versione Semplificata
 */

$(document).ready(function() {
    let selectedPatients = [];
    
    // Inizializzazione
    init();
    
    function init() {
        bindEvents();
        updateUI();
    }
    
    function bindEvents() {
        // Checkbox "Seleziona tutto"
        $(document).on('change', '#select-all-patients', function() {
            const isChecked = $(this).is(':checked');
            $('.patient-checkbox').prop('checked', isChecked);
            updateSelectedPatients();
        });
        
        // Checkbox singoli pazienti
        $(document).on('change', '.patient-checkbox', function() {
            updateSelectedPatients();
            updateSelectAllState();
        });
        
        // Bottone "Invia Notifica"
        $(document).on('click', '#send-notifications-btn', function(e) {
            e.preventDefault();
            if (selectedPatients.length === 0) {
                showAlert('Seleziona almeno un paziente per inviare le notifiche.', 'warning');
                return;
            }
            openModal();
        });
    }
    
    function updateSelectedPatients() {
        selectedPatients = [];
        $('.patient-checkbox:checked').each(function() {
            selectedPatients.push(parseInt($(this).val()));
        });
        updateUI();
    }
    
    function updateSelectAllState() {
        const total = $('.patient-checkbox').length;
        const checked = $('.patient-checkbox:checked').length;
        
        $('#select-all-patients').prop('indeterminate', checked > 0 && checked < total);
        $('#select-all-patients').prop('checked', checked === total && total > 0);
    }
    
    function updateUI() {
        const count = selectedPatients.length;
        $('#selected-patients-count').text(count);
        
        if (count > 0) {
            $('#notification-actions-bar').removeClass('hidden').addClass('flex');
        } else {
            $('#notification-actions-bar').removeClass('flex').addClass('hidden');
        }
    }
    
    function openModal() {
        // Aggiorna il contatore nel modal
        const modalData = Alpine.$data(document.querySelector('[x-data*="showModal"]'));
        if (modalData) {
            modalData.selectedCount = selectedPatients.length;
            modalData.showModal = true;
            modalData.errors = '';
            modalData.success = '';
            modalData.title = '';
            modalData.message = '';
        }
    }
    
    // Funzione globale per inviare le notifiche (chiamata dal modal Alpine)
    window.sendPatientNotifications = async function() {
        const modalData = Alpine.$data(document.querySelector('[x-data*="showModal"]'));
        
        if (!modalData) return;
        
        const title = modalData.title?.trim();
        const message = modalData.message?.trim();
        
        if (!title || !message) {
            modalData.errors = 'Inserisci sia il titolo che il messaggio della notifica.';
            return;
        }
        
        modalData.isLoading = true;
        modalData.errors = '';
        modalData.success = '';
        
        try {
            // URL generato da Yii2
            const response = await $.ajax({
                url: window.sendNotificationUrl || '/patient/send-notification',
                type: 'POST',
                data: {
                    patient_ids: selectedPatients,
                    title: title,
                    message: message,
                    _csrf: $('meta[name=csrf-token]').attr('content')
                },
                dataType: 'json'
            });
            
            if (response.success) {
                modalData.success = response.message || 'Notifiche inviate con successo!';
                
                // Chiudi modal e resetta selezioni dopo 2 secondi
                setTimeout(() => {
                    modalData.showModal = false;
                    clearAllSelections();
                }, 2000);
                
            } else {
                modalData.errors = response.error || 'Errore durante l\'invio delle notifiche.';
            }
            
        } catch (error) {
            console.error('Errore AJAX:', error);
            
            let errorMessage = 'Errore di comunicazione con il server.';
            if (error.responseJSON?.error) {
                errorMessage = error.responseJSON.error;
            } else if (error.status) {
                errorMessage = `Errore ${error.status}: ${error.statusText}`;
            }
            
            modalData.errors = errorMessage;
        } finally {
            modalData.isLoading = false;
        }
    };
    
    function clearAllSelections() {
        selectedPatients = [];
        $('.patient-checkbox').prop('checked', false);
        $('#select-all-patients').prop('checked', false);
        updateUI();
    }
    
    function showAlert(message, type = 'info') {
        const alertTypes = {
            'success': 'bg-green-50 border-green-200 text-green-800',
            'error': 'bg-red-50 border-red-200 text-red-800',
            'warning': 'bg-yellow-50 border-yellow-200 text-yellow-800',
            'info': 'bg-blue-50 border-blue-200 text-blue-800'
        };
        
        const alertClass = alertTypes[type] || alertTypes.info;
        
        const $alert = $(`
            <div class="border rounded-lg p-4 mb-4 ${alertClass}" role="alert">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-3">
                        <button type="button" class="inline-flex text-gray-400 hover:text-gray-600" onclick="$(this).closest('[role=alert]').fadeOut()">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `);
        
        $('.mx-auto.max-w-7xl').first().prepend($alert);
        
        setTimeout(() => {
            $alert.fadeOut(() => $alert.remove());
        }, 5000);
    }
});
