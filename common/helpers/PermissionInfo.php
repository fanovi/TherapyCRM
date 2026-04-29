<?php

namespace common\helpers;

/**
 * Descrizioni estese dei permessi RBAC, pensate per essere mostrate
 * all'utente cliccando sull'icona info accanto al nome del permesso.
 *
 * La mappa qui sotto contiene SOLO i permessi per cui esiste una
 * descrizione "umana" piu' ricca dell'auth_item.description. Per gli
 * altri il fallback ritorna null e l'icona info non viene resa.
 *
 * Convenzione: title corto come la voce in elenco; description lunga
 * con esempi di cosa l'utente puo' fare e i contesti tipici.
 */
class PermissionInfo
{
    /**
     * Mappa permission_name => ['title' => ..., 'description' => ...]
     */
    public static function map(): array
    {
        return [
            // ==== Accesso ====
            'platform_login' => [
                'title' => 'Accesso alla piattaforma web',
                'description' => "Consente di accedere al gestionale via browser (questa applicazione).\n\n"
                    . "Senza questo permesso l'utente non puo' loggarsi al sito."
            ],
            'app_login' => [
                'title' => 'Accesso all\'app mobile',
                'description' => "Consente di loggarsi all'app mobile dei terapisti/pazienti.\n\n"
                    . "Solo i ruoli therapist e patient_family possono averlo. Gli altri ruoli non passano il login mobile anche se assegnato."
            ],
            'manage_system' => [
                'title' => 'Configurazione di sistema',
                'description' => "Accesso a impostazioni avanzate del sistema (parametri globali, configurazioni tecniche)."
            ],
            'manage_permissions' => [
                'title' => 'Gestire permessi utenti',
                'description' => "Permette di assegnare o rimuovere permessi extra ai singoli utenti dalla loro scheda di modifica."
            ],
            'assign_role_permissions' => [
                'title' => 'Assegnare permessi ai ruoli',
                'description' => "Permette di abilitare o disabilitare i permessi di un intero ruolo da /permission/view-role.\n\n"
                    . "Ha effetto su TUTTI gli utenti con quel ruolo: usare con cautela."
            ],

            // ==== Coordinatori ====
            'create_coordinator' => [
                'title' => 'Creare coordinatori',
                'description' => "Permette di creare un nuovo utente con ruolo coordinator dalla sezione Coordinatori."
            ],
            'view_coordinator' => [
                'title' => 'Visualizzare coordinatori',
                'description' => "Permette di vedere la lista dei coordinatori e le loro schede di dettaglio."
            ],
            'update_coordinator' => [
                'title' => 'Modificare coordinatori',
                'description' => "Permette di aggiornare i dati anagrafici e i permessi extra dei coordinatori."
            ],
            'delete_coordinator' => [
                'title' => 'Eliminare coordinatori',
                'description' => "Permette di eliminare un coordinatore dal sistema. Operazione irreversibile."
            ],
            'view_coordinator_group' => [
                'title' => 'Visualizzare gruppi coordinatori',
                'description' => "Accesso in lettura ai gruppi di coordinamento (lista terapisti che fanno capo a un coordinator)."
            ],
            'create_coordinator_group' => [
                'title' => 'Creare gruppi coordinatori',
                'description' => "Permette di creare un nuovo gruppo di coordinamento e assegnargli terapisti."
            ],
            'manage_coordinator_groups' => [
                'title' => 'Gestire gruppi di coordinamento',
                'description' => "Gestione completa dei gruppi: creazione, modifica, eliminazione, riassegnazione terapisti."
            ],

            // ==== Terapisti ====
            'create_therapist' => [
                'title' => 'Creare terapisti',
                'description' => "Permette di registrare un nuovo terapista nel sistema."
            ],
            'view_therapist' => [
                'title' => 'Visualizzare terapisti',
                'description' => "Permette di vedere la lista dei terapisti e accedere alle loro schede."
            ],
            'update_therapist' => [
                'title' => 'Modificare terapisti',
                'description' => "Permette di aggiornare i dati di un terapista (specializzazione, ore contratto, etc)."
            ],
            'delete_therapist' => [
                'title' => 'Eliminare terapisti',
                'description' => "Permette di rimuovere un terapista dal sistema. Verifica prima eventuali appuntamenti collegati."
            ],
            'view_own_group_therapists' => [
                'title' => 'Visualizzare i terapisti del proprio gruppo',
                'description' => "Vista limitata: il coordinator vede solo i terapisti che appartengono al suo gruppo di coordinamento.\n\n"
                    . "Pagina /therapist/my-group e search globale filtrate."
            ],

            // ==== Pazienti ====
            'create_patient' => [
                'title' => 'Creare pazienti',
                'description' => "Permette di registrare un nuovo paziente."
            ],
            'view_patient' => [
                'title' => 'Visualizzare pazienti',
                'description' => "Accesso alla scheda dei pazienti e alla lista globale."
            ],
            'update_patient' => [
                'title' => 'Modificare pazienti',
                'description' => "Permette di modificare i dati anagrafici e clinici di un paziente."
            ],
            'delete_patient' => [
                'title' => 'Eliminare pazienti',
                'description' => "Eliminazione paziente. Operazione delicata: rimuove anche relazioni associate."
            ],
            'manage_patient_accounts' => [
                'title' => 'Gestire account dei pazienti',
                'description' => "Crea/modifica gli account utente collegati ai pazienti (familiari, tutori, paziente stesso) e le loro credenziali."
            ],
            'view_own_group_patients' => [
                'title' => 'Visualizzare i pazienti del proprio gruppo',
                'description' => "Il coordinator vede solo i pazienti curati dai terapisti del suo gruppo (pagina /patient/my-group + search globale)."
            ],

            // ==== Piani Terapeutici ====
            'create_therapeutic_plan' => [
                'title' => 'Creare piani terapeutici',
                'description' => "Permette di creare un nuovo piano terapeutico per un paziente con regime, durata, terapie."
            ],
            'view_therapeutic_plan' => [
                'title' => 'Visualizzare piani terapeutici',
                'description' => "Accesso ai piani terapeutici dei pazienti."
            ],
            'update_therapeutic_plan' => [
                'title' => 'Modificare piani terapeutici',
                'description' => "Modifica un piano esistente: durata, terapie, frequenza settimanale."
            ],
            'delete_therapeutic_plan' => [
                'title' => 'Eliminare piani terapeutici',
                'description' => "Elimina un piano. Gli appuntamenti collegati restano ma orfani."
            ],

            // ==== Appuntamenti / Calendario ====
            'view_calendar' => [
                'title' => 'Visualizzare calendario',
                'description' => "Permette di accedere alla pagina /calendar in sola visualizzazione (drag e creazione disabilitati)."
            ],
            'manage_calendar' => [
                'title' => 'Gestire calendario completo',
                'description' => "Pieno controllo del calendario: creazione, modifica, spostamento e cancellazione di appuntamenti.\n\n"
                    . "Senza questo permesso il calendario rimane in sola lettura anche se l'utente puo' aprirlo."
            ],
            'view_appointment' => [
                'title' => 'Visualizzare appuntamenti',
                'description' => "Accesso in lettura ai dettagli degli appuntamenti."
            ],
            'view_own_appointments' => [
                'title' => 'Visualizzare i propri appuntamenti',
                'description' => "Vista limitata ai soli appuntamenti dell'utente corrente (terapista o paziente)."
            ],

            // ==== Assenze ====
            'create_absence' => [
                'title' => 'Registrare assenze',
                'description' => "Permette di registrare un'assenza paziente o terapista."
            ],
            'view_absence' => [
                'title' => 'Visualizzare assenze',
                'description' => "Accesso alla lista delle assenze e ai loro dettagli."
            ],
            'update_absence' => [
                'title' => 'Modificare assenze',
                'description' => "Permette di modificare un'assenza gia' registrata (date, tipo, motivo)."
            ],
            'delete_absence' => [
                'title' => 'Eliminare assenze',
                'description' => "Rimuove un'assenza dal sistema, ripristinando l'appuntamento associato se previsto."
            ],
            'view_patient_absence' => [
                'title' => 'Visualizzare assenze pazienti',
                'description' => "Vista delle assenze relative ai pazienti, accessibile anche da chi non gestisce calendario completo."
            ],
            'manage_absence_recovery' => [
                'title' => 'Gestire recuperi assenze',
                'description' => "Permette di programmare e gestire i recuperi delle assenze giustificate."
            ],

            // ==== Documenti ====
            'view_documents' => [
                'title' => 'Visualizzare documenti',
                'description' => "Accesso in lettura alle richieste documenti e ai documenti caricati."
            ],
            'manage_documents' => [
                'title' => 'Gestire documenti',
                'description' => "Gestione completa: visualizza, modifica stato, carica, rimuovi documenti."
            ],
            'download_documents' => [
                'title' => 'Scaricare documenti',
                'description' => "Permette di scaricare file documento dal sistema."
            ],
            'change_document_request_status' => [
                'title' => 'Cambiare stato richiesta documento (libero)',
                'description' => "Permette di muovere una richiesta documento tra gli stati di lavorazione interna:\n"
                    . "Inviata, Presa in carico, Stampato.\n\n"
                    . "Non include la marcatura come Consegnato (gestita dal permesso dedicato).\n\n"
                    . "Tipico per chi gestisce il flusso interno della pratica (es. admin)."
            ],
            'mark_document_request_delivered' => [
                'title' => 'Marcare richiesta come Consegnato e ripristinare',
                'description' => "Permette due transizioni:\n"
                    . "1) Marcare una richiesta come Consegnato (chiusura).\n"
                    . "2) Riportare una richiesta gia' Consegnata allo stato precedente in caso di errore.\n\n"
                    . "Tipico per chi consegna materialmente il documento al paziente (es. operatore allo sportello, manager)."
            ],

            // ==== Notifiche / Comunicazioni ====
            'view_notification' => [
                'title' => 'Visualizzare notifiche',
                'description' => "Accesso alla pagina notifiche personali."
            ],
            'create_notification' => [
                'title' => 'Creare notifiche',
                'description' => "Permette di inviare notifiche manuali a uno o piu' utenti."
            ],
            'manage_notification_templates' => [
                'title' => 'Gestire template notifiche',
                'description' => "Crea/modifica i template usati per le notifiche automatiche di sistema."
            ],
            'send_notifications' => [
                'title' => 'Inviare notifiche',
                'description' => "Permette di inviare notifiche broadcast o programmate."
            ],
            'view_messages' => [
                'title' => 'Visualizzare messaggi',
                'description' => "Accesso alla messaggistica interna tra utenti."
            ],
            'send_messages' => [
                'title' => 'Inviare messaggi',
                'description' => "Permette di inviare messaggi all'interno della piattaforma."
            ],
            'manage_communications' => [
                'title' => 'Gestire comunicazioni',
                'description' => "Gestione completa delle comunicazioni: invio, modifica, archiviazione."
            ],

            // ==== Dati personali ====
            'view_own_data' => [
                'title' => 'Visualizzare i propri dati',
                'description' => "Permette all'utente di vedere il proprio profilo e le informazioni personali."
            ],
            'update_own_data' => [
                'title' => 'Modificare i propri dati',
                'description' => "Permette di aggiornare le proprie informazioni anagrafiche, telefono, indirizzo."
            ],

            // ==== Statistiche / Report ====
            'view_statistics' => [
                'title' => 'Visualizzare statistiche e dashboard',
                'description' => "Accesso alla dashboard analitica e ai grafici sintetici."
            ],
            'view_reports' => [
                'title' => 'Visualizzare rapporti',
                'description' => "Permette di vedere i report di attivita' (es. report attivita' terapista)."
            ],
            'generate_reports' => [
                'title' => 'Generare rapporti',
                'description' => "Genera nuovi report parametrizzati (per periodo, terapista, paziente)."
            ],
            'export_data' => [
                'title' => 'Esportare dati',
                'description' => "Permette l'esportazione dei dati in formato CSV/Excel."
            ],
        ];
    }

    /**
     * Restituisce title + description per un permesso, o null se non
     * abbiamo una descrizione estesa per quel permesso.
     *
     * @return array{title:string, description:string}|null
     */
    public static function get(string $permissionName): ?array
    {
        $map = self::map();
        return $map[$permissionName] ?? null;
    }

    /**
     * Renderizza un'icona "info" cliccabile accanto al nome del permesso.
     * Ritorna stringa vuota se non esiste descrizione estesa per il permesso
     * (cosi' la UI non mostra icona orfana).
     */
    public static function renderInfoIcon(string $permissionName): string
    {
        $info = self::get($permissionName);
        if (!$info) {
            return '';
        }
        $title = htmlspecialchars($info['title'], ENT_QUOTES);
        $description = htmlspecialchars($info['description'], ENT_QUOTES);
        return '<button type="button" '
            . 'class="permission-info-btn inline-flex items-center justify-center w-4 h-4 ml-1 rounded-full text-blue-500 hover:text-blue-700 hover:bg-blue-50 transition-colors" '
            . 'data-perm-title="' . $title . '" '
            . 'data-perm-description="' . $description . '" '
            . 'title="Maggiori informazioni" '
            . 'onclick="event.preventDefault(); event.stopPropagation(); window.showPermissionInfo && window.showPermissionInfo(this.getAttribute(\'data-perm-title\'), this.getAttribute(\'data-perm-description\'));">'
            . '<svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">'
            . '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
            . '</svg>'
            . '</button>';
    }

    /**
     * Snippet JS per registrare il dialog Swal globale. Registralo una sola
     * volta per pagina (es. via Yii::$app->view->registerJs).
     */
    public static function getJsHandler(): string
    {
        return <<<JS
window.showPermissionInfo = function (title, description) {
    if (typeof Swal === 'undefined') {
        alert(title + "\\n\\n" + description);
        return;
    }
    var html = '<pre style="white-space:pre-wrap;word-wrap:break-word;text-align:left;font-family:inherit;font-size:14px;color:#374151;margin:0;background:#f9fafb;padding:12px;border-radius:8px;border:1px solid #e5e7eb;">'
        + (description || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        + '</pre>';
    Swal.fire({
        title: title,
        html: html,
        icon: 'info',
        confirmButtonText: 'Chiudi',
        confirmButtonColor: '#2563eb',
        width: 560
    });
};
JS;
    }
}
