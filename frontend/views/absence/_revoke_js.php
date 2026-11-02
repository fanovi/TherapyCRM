<?php

use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Modale di revoca assenza, usata dalla griglia e dal dettaglio.
 * Si attiva sui pulsanti .btn-revoke-absence con data-id.
 *
 * @var yii\web\View $this
 */

$config = Json::htmlEncode([
    'infoUrl' => Url::to(['revoke-info']),
    'revokeUrl' => Url::to(['revoke']),
    'csrfParam' => Yii::$app->request->csrfParam,
    'csrfToken' => Yii::$app->request->csrfToken,
]);

$js = <<<JS
(function() {
    const config = {$config};

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = String(s ?? '');
        return d.innerHTML;
    }

    function buildImpactWarning(info) {
        if (!info.therapist_absent_count && !info.substituted_count) return '';
        return '' +
        '<div style="margin-bottom:12px;padding:10px 12px;background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;color:#78350f;">' +
            '<div style="font-weight:700;margin-bottom:6px;">Attenzione: gli appuntamenti non verranno modificati automaticamente.</div>' +
            '<div>In questo periodo risultano:</div>' +
            '<ul style="margin:4px 0 6px 18px;list-style:disc;">' +
                '<li><strong>' + info.therapist_absent_count + '</strong> ' + (info.therapist_absent_count === 1 ? 'appuntamento' : 'appuntamenti') + ' in stato "Terapista assente"</li>' +
                '<li><strong>' + info.substituted_count + '</strong> ' + (info.substituted_count === 1 ? 'appuntamento assegnato' : 'appuntamenti assegnati') + ' a un sostituto</li>' +
            '</ul>' +
            '<div>Dopo la revoca resteranno invariati: se il terapista torna disponibile, vanno ripristinati manualmente dal calendario.</div>' +
        '</div>';
    }

    function buildHtml(info) {
        return '' +
        '<div style="text-align:left;font-size:13px;">' +
            '<p style="margin-bottom:12px;color:#374151;">Stai revocando l\'assenza (' + escapeHtml(info.type) + ') di <strong>' + escapeHtml(info.therapist) + '</strong> ' + escapeHtml(info.period) + '. Resterà visibile nell\'elenco con stato "Revocata".</p>' +
            buildImpactWarning(info) +
            '<label for="ab-revoke-reason" style="display:block;font-weight:600;color:#374151;margin-bottom:4px;">Motivo della revoca *</label>' +
            '<textarea id="ab-revoke-reason" rows="3" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;resize:vertical;"></textarea>' +
        '</div>';
    }

    function submitRevoke(id) {
        const reason = document.getElementById('ab-revoke-reason').value.trim();
        if (!reason) {
            Swal.showValidationMessage('Il motivo della revoca è obbligatorio.');
            return false;
        }
        const body = new FormData();
        body.append(config.csrfParam, config.csrfToken);
        body.append('reason', reason);

        return fetch(config.revokeUrl + '?id=' + encodeURIComponent(id), {
            method: 'POST',
            credentials: 'include',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body,
        }).then(r => r.json()).then(data => {
            if (!data.success) {
                Swal.showValidationMessage(data.error || 'Errore durante la revoca');
                return false;
            }
            return data;
        }).catch(() => {
            Swal.showValidationMessage('Errore di rete');
            return false;
        });
    }

    function refreshPage() {
        if (document.getElementById('absence-grid-pjax')) {
            $.pjax.reload({ container: '#absence-grid-pjax' });
        } else {
            window.location.reload();
        }
    }

    function afterRevoke(data) {
        if (data.notification_sent === false) {
            Swal.fire({
                icon: 'warning',
                title: 'Assenza revocata, ma notifica non inviata',
                text: 'La revoca è stata registrata, ma la notifica a direzione e coordinatori non è partita: avvisali direttamente.',
                confirmButtonColor: '#3b82f6',
            }).then(refreshPage);
            return;
        }
        Swal.fire({ icon: 'success', title: data.message || 'Assenza revocata', timer: 1400, showConfirmButton: false }).then(refreshPage);
    }

    // Delegato perche' la griglia e' dentro pjax e si rigenera.
    document.body.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-revoke-absence');
        if (!btn || !btn.dataset.id) return;
        const id = btn.dataset.id;

        fetch(config.infoUrl + '?id=' + encodeURIComponent(id), {
            credentials: 'include',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).then(r => r.json()).then(res => {
            if (!res.success) {
                Swal.fire({ icon: 'error', title: 'Revoca non disponibile', text: res.error || 'Impossibile revocare l\'assenza' });
                return;
            }
            Swal.fire({
                title: 'Revoca assenza',
                html: buildHtml(res.data),
                showCancelButton: true,
                confirmButtonText: 'Conferma revoca',
                cancelButtonText: 'Annulla',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
                focusConfirm: false,
                width: 560,
                preConfirm: () => submitRevoke(id),
            }).then(result => {
                if (result.isConfirmed && result.value && result.value.success) {
                    afterRevoke(result.value);
                }
            });
        }).catch(() => {
            Swal.fire({ icon: 'error', title: 'Errore', text: 'Errore di rete' });
        });
    });
})();
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
