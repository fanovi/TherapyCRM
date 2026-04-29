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
