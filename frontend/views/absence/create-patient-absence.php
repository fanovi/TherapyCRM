<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;
use common\helpers\GridViewHelper;

/** @var yii\web\View $this */
/** @var frontend\models\PatientSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Crea Assenze Paziente';
$this->params['breadcrumbs'][] = ['label' => 'Assenze Pazienti', 'url' => ['patients']];
$this->params['breadcrumbs'][] = $this->title;

$apptsUrl = Url::to(['absence/patient-appointments-absence']);
$markUrl = Url::to(['absence/mark-patients-absent']);
$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            <?= Html::encode($this->title) ?>
        </h2>
        <a href="<?= Url::to(['patients']) ?>"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            ← Torna alla lista assenze
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Seleziona paziente
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Clicca su un paziente per gestire le sue assenze.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800">
            <div class="overflow-x-auto">
                <?php Pjax::begin(['id' => 'patients-absence-pjax', 'enablePushState' => false]); ?>
                <?= GridView::widget(array_merge([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                    'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                    'rowOptions' => function ($model) {
                        return [
                            'class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-600 cursor-pointer patient-row',
                            'data-id' => $model->id,
                            'data-name' => trim($model->first_name . ' ' . $model->last_name),
                            'data-fc' => $model->fiscal_code ?? '',
                            'data-bd' => $model->birth_date ?? '',
                        ];
                    },
                    'filterRowOptions' => ['class' => 'bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700'],
                    'columns' => [
                        [
                            'attribute' => 'last_name',
                            'label' => 'Cognome',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Cognome...'],
                        ],
                        [
                            'attribute' => 'first_name',
                            'label' => 'Nome',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Nome...'],
                        ],
                        [
                            'attribute' => 'fiscal_code',
                            'label' => 'Codice fiscale',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[150px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 text-xs whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'CF...'],
                        ],
                        [
                            'attribute' => 'birth_date',
                            'label' => 'Data nascita',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 text-xs whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'type' => 'date'],
                            'value' => function ($m) {
                                return $m->birth_date
                                    ? Yii::$app->formatter->asDate($m->birth_date, 'php:d/m/Y')
                                    : '-';
                            },
                        ],
                        [
                            'header' => 'Azione',
                            'headerOptions' => ['class' => 'px-6 py-3 w-32'],
                            'contentOptions' => ['class' => 'px-6 py-4'],
                            'format' => 'raw',
                            'value' => function ($m) {
                                return '<button type="button" class="open-patient-modal" style="background:#2563eb;color:#fff;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:500;border:none;cursor:pointer;">Gestisci assenze</button>';
                            },
                        ],
                    ],
                ], GridViewHelper::getGridViewConfig('pazienti'))); ?>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
(function(){
  const APPTS_URL = '$apptsUrl';
  const MARK_URL = '$markUrl';
  const CSRF_TOKEN = '$csrfToken';

  let selectedPatient = null;
  let appointments = [];
  const selectedIds = new Set();

  const el = (tag, opts) => {
    const e = document.createElement(tag);
    if (!opts) return e;
    if (opts.cls) e.className = opts.cls;
    if (opts.text != null) e.textContent = String(opts.text);
    if (opts.attrs) for (const k in opts.attrs) e.setAttribute(k, opts.attrs[k]);
    if (opts.dataset) for (const k in opts.dataset) e.dataset[k] = opts.dataset[k];
    if (opts.style) e.style.cssText = opts.style;
    return e;
  };

  function fmtDate(iso){ if(!iso) return '-'; const m = String(iso).match(/^(\\d{4})-(\\d{2})-(\\d{2})/); return m ? m[3]+'/'+m[2]+'/'+m[1] : iso; }
  function fmtTime(iso){ const m = String(iso).match(/(\\d{2}:\\d{2})/); return m ? m[1] : '-'; }

  const STATUS_MAP = {
    'scheduled': ['Programmato','#dcfce7','#15803d'],
    'completed': ['Completato','#e0e7ff','#4338ca'],
    'absent_justified': ['Assente giustificato','#ffedd5','#c2410c'],
    'absent_not_justified': ['Assente non giust.','#fee2e2','#b91c1c'],
    'therapist_absent': ['Terapista assente','#ede9fe','#6d28d9'],
    'cancelled': ['Cancellato','#f3f4f6','#4b5563'],
  };

  function statusBadge(s, isAdmin){
    const wrap = el('span');
    const m = STATUS_MAP[s] || [s, '#f3f4f6', '#374151'];
    wrap.appendChild(el('span', {
      text: m[0],
      style: 'padding:2px 8px;border-radius:6px;font-size:11px;font-weight:500;background:' + m[1] + ';color:' + m[2] + ';'
    }));
    if (isAdmin && (s==='absent_justified' || s==='absent_not_justified')) {
      wrap.appendChild(el('span', { text: ' [gestionale]', style: 'font-size:10px;color:#92400e;margin-left:4px;' }));
    }
    return wrap;
  }

  // --- click su row paziente apre modale swal ---
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.open-patient-modal');
    const row = e.target.closest('.patient-row');
    if (!btn && !row) return;
    if (e.target.closest('input')) return;
    const tr = row || (btn && btn.closest('.patient-row'));
    if (!tr || !tr.dataset.id) return;
    selectedPatient = {
      id: parseInt(tr.dataset.id),
      name: tr.dataset.name || '',
      fiscalCode: tr.dataset.fc || '',
      birthDate: tr.dataset.bd || '',
    };
    openSwalModal();
  });

  function buildModalContent(){
    const root = document.createElement('div');
    root.style.textAlign = 'left';

    // Filtri
    const filters = el('div', { style: 'display:grid;grid-template-columns:1fr 1fr auto auto;gap:10px;margin-bottom:12px;align-items:end;' });
    const fromBox = el('div');
    fromBox.appendChild(el('label', { text: 'Da', style: 'display:block;font-size:12px;font-weight:600;margin-bottom:4px;' }));
    const inpFrom = el('input', { attrs: { type: 'date', id: 'pa-filter-from' }, style: 'width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;' });
    fromBox.appendChild(inpFrom);
    const toBox = el('div');
    toBox.appendChild(el('label', { text: 'A', style: 'display:block;font-size:12px;font-weight:600;margin-bottom:4px;' }));
    const inpTo = el('input', { attrs: { type: 'date', id: 'pa-filter-to' }, style: 'width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;' });
    toBox.appendChild(inpTo);
    const btnFilter = el('button', { text: 'Filtra', attrs: { type: 'button', id: 'pa-btn-filter' }, style: 'padding:6px 14px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer;' });
    const btnReset = el('button', { text: 'Reset', attrs: { type: 'button', id: 'pa-btn-reset' }, style: 'padding:6px 12px;background:#fff;color:#374151;border:1px solid #d1d5db;border-radius:6px;font-size:13px;cursor:pointer;' });
    filters.appendChild(fromBox); filters.appendChild(toBox); filters.appendChild(btnFilter); filters.appendChild(btnReset);
    root.appendChild(filters);

    // Stato
    const status = el('div', { attrs: { id: 'pa-status' }, text: 'Caricamento...', style: 'padding:14px;text-align:center;color:#6b7280;font-size:13px;' });
    root.appendChild(status);

    // Wrapper tabella
    const tableWrap = el('div', { attrs: { id: 'pa-table-wrap' }, style: 'display:none;border:1px solid #e5e7eb;border-radius:8px;overflow:auto;max-height:50vh;' });
    const table = el('table', { style: 'width:100%;font-size:13px;border-collapse:collapse;' });
    const thead = el('thead', { style: 'background:#f9fafb;position:sticky;top:0;z-index:1;' });
    const trh = el('tr');
    const headers = ['', 'Data', 'Orario', 'Terapista', 'Trattamento', 'Tipo', 'Stato', 'Azioni'];
    headers.forEach((h, i) => {
      const th = el('th', { style: 'padding:8px 10px;text-align:left;font-size:11px;font-weight:600;color:#374151;text-transform:uppercase;' });
      if (i === 0) {
        const ck = el('input', { attrs: { type: 'checkbox', id: 'pa-select-all' } });
        th.appendChild(ck);
      } else {
        th.textContent = h;
      }
      trh.appendChild(th);
    });
    thead.appendChild(trh);
    table.appendChild(thead);
    table.appendChild(el('tbody', { attrs: { id: 'pa-tbody' } }));
    tableWrap.appendChild(table);
    root.appendChild(tableWrap);

    // Footer
    const footer = el('div', { style: 'display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb;' });
    const counters = el('div', { attrs: { id: 'pa-counters' }, style: 'font-size:12px;color:#6b7280;' });
    counters.textContent = '0 appuntamenti — 0 selezionati';
    const bulkBtn = el('button', { text: 'Marca selezionati come assenti', attrs: { type: 'button', id: 'pa-btn-bulk', disabled: 'disabled' }, style: 'padding:8px 16px;background:#d97706;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:500;cursor:not-allowed;opacity:0.5;' });
    footer.appendChild(counters);
    footer.appendChild(bulkBtn);
    root.appendChild(footer);

    return root;
  }

  function openSwalModal(){
    if (typeof Swal === 'undefined') { alert('Swal non disponibile'); return; }
    const meta = [];
    if (selectedPatient.fiscalCode) meta.push(selectedPatient.fiscalCode);
    if (selectedPatient.birthDate) meta.push(fmtDate(selectedPatient.birthDate));
    const titleHtml = '<div style="text-align:left;"><div style="font-size:16px;font-weight:600;color:#1f2937;">' + escapeHtml(selectedPatient.name) + '</div><div style="font-size:12px;color:#6b7280;font-weight:400;">' + escapeHtml(meta.join(' • ') || '-') + '</div></div>';

    Swal.fire({
      title: titleHtml,
      html: buildModalContent(),
      showCloseButton: true,
      showConfirmButton: false,
      showCancelButton: false,
      width: '90%',
      padding: '1.5rem',
      didOpen: () => {
        wireModalHandlers();
        loadAppointments();
      },
      willClose: () => {
        selectedPatient = null;
        appointments = [];
        selectedIds.clear();
      }
    });
  }

  function escapeHtml(s){
    const d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
  }

  function wireModalHandlers(){
    document.getElementById('pa-btn-filter').addEventListener('click', loadAppointments);
    document.getElementById('pa-btn-reset').addEventListener('click', () => {
      document.getElementById('pa-filter-from').value = '';
      document.getElementById('pa-filter-to').value = '';
      loadAppointments();
    });
    document.getElementById('pa-btn-bulk').addEventListener('click', () => {
      if (selectedIds.size === 0) return;
      openMarkDialog(Array.from(selectedIds), null);
    });
    document.getElementById('pa-select-all').addEventListener('change', (e) => {
      appointments.forEach(a => { if (e.target.checked) selectedIds.add(a.id); else selectedIds.delete(a.id); });
      document.querySelectorAll('.pa-row-cb').forEach(cb => cb.checked = e.target.checked);
      updateBulkBtn();
    });
    document.getElementById('pa-tbody').addEventListener('change', (e) => {
      const cb = e.target.closest('.pa-row-cb');
      if (!cb) return;
      const id = parseInt(cb.dataset.id);
      if (cb.checked) selectedIds.add(id); else selectedIds.delete(id);
      updateBulkBtn();
    });
    document.getElementById('pa-tbody').addEventListener('click', (e) => {
      const dayCell = e.target.closest('[data-day]');
      if (dayCell) {
        const day = dayCell.dataset.day;
        const ids = appointments.filter(a => String(a.datetime).startsWith(day)).map(a=>a.id);
        const allSelected = ids.every(id => selectedIds.has(id));
        ids.forEach(id => allSelected ? selectedIds.delete(id) : selectedIds.add(id));
        document.querySelectorAll('.pa-row-cb').forEach(cb => {
          cb.checked = selectedIds.has(parseInt(cb.dataset.id));
        });
        updateBulkBtn();
        return;
      }
      const qm = e.target.closest('.pa-quick-mark');
      if (qm) {
        const id = parseInt(qm.dataset.id);
        const type = qm.dataset.type;
        openMarkDialog([id], type);
      }
    });
  }

  async function loadAppointments(){
    if (!selectedPatient) return;
    const status = document.getElementById('pa-status');
    const tableWrap = document.getElementById('pa-table-wrap');
    status.style.display = 'block';
    status.textContent = 'Caricamento appuntamenti...';
    tableWrap.style.display = 'none';
    selectedIds.clear();
    updateBulkBtn();
    const from = document.getElementById('pa-filter-from').value || '';
    const to = document.getElementById('pa-filter-to').value || '';
    try {
      const r = await fetch(APPTS_URL+'?patientId='+selectedPatient.id+'&from='+from+'&to='+to);
      const data = await r.json();
      appointments = data.items || [];
      renderAppointments();
    } catch(e) {
      status.textContent = 'Errore caricamento: ' + e.message;
    }
  }

  function renderAppointments(){
    const status = document.getElementById('pa-status');
    const tableWrap = document.getElementById('pa-table-wrap');
    const tbody = document.getElementById('pa-tbody');
    tbody.replaceChildren();

    if (!appointments.length){
      status.style.display = 'block';
      status.textContent = 'Nessun appuntamento trovato.';
      tableWrap.style.display = 'none';
      updateCounters();
      return;
    }
    status.style.display = 'none';
    tableWrap.style.display = 'block';

    appointments.forEach(a => {
      const tr = el('tr', { style: 'border-top:1px solid #f3f4f6;' });
      const date = String(a.datetime || '').split(' ')[0];

      const tdCheck = el('td', { style: 'padding:6px 10px;' });
      const cb = el('input', { cls: 'pa-row-cb' });
      cb.type = 'checkbox';
      cb.dataset.id = String(a.id);
      cb.checked = selectedIds.has(a.id);
      tdCheck.appendChild(cb);

      const tdDate = el('td', { text: fmtDate(a.datetime), style: 'padding:6px 10px;cursor:pointer;color:#2563eb;text-decoration:underline;', attrs: { title: 'Clic per (de)selezionare tutti del giorno' } });
      tdDate.dataset.day = date;

      const tdTime = el('td', { text: fmtTime(a.datetime) + ' (' + a.duration + 'min)', style: 'padding:6px 10px;' });
      const tdTher = el('td', { text: a.therapist || '-', style: 'padding:6px 10px;' });
      const tdTreat = el('td', { text: a.treatmentType || '-', style: 'padding:6px 10px;' });
      const tdAt = el('td', { text: a.appointmentType || '-', style: 'padding:6px 10px;font-size:11px;' });

      const tdStatus = el('td', { style: 'padding:6px 10px;' });
      tdStatus.appendChild(statusBadge(a.status, a.isAdminAbsence));

      const tdActions = el('td', { style: 'padding:6px 10px;font-size:11px;' });
      const bJ = el('button', { text: 'Giust.', cls: 'pa-quick-mark', attrs: { type: 'button' }, dataset: { id: String(a.id), type: 'justified' }, style: 'background:none;border:none;color:#c2410c;cursor:pointer;text-decoration:underline;margin-right:8px;font-size:11px;' });
      const bN = el('button', { text: 'Non giust.', cls: 'pa-quick-mark', attrs: { type: 'button' }, dataset: { id: String(a.id), type: 'not_justified' }, style: 'background:none;border:none;color:#b91c1c;cursor:pointer;text-decoration:underline;font-size:11px;' });
      tdActions.appendChild(bJ);
      tdActions.appendChild(bN);

      tr.appendChild(tdCheck);
      tr.appendChild(tdDate);
      tr.appendChild(tdTime);
      tr.appendChild(tdTher);
      tr.appendChild(tdTreat);
      tr.appendChild(tdAt);
      tr.appendChild(tdStatus);
      tr.appendChild(tdActions);
      tbody.appendChild(tr);
    });

    document.getElementById('pa-select-all').checked = false;
    updateCounters();
    updateBulkBtn();
  }

  function updateCounters(){
    const el = document.getElementById('pa-counters');
    if (el) el.textContent = appointments.length + ' appuntamenti — ' + selectedIds.size + ' selezionati';
  }

  function updateBulkBtn(){
    updateCounters();
    const btn = document.getElementById('pa-btn-bulk');
    if (!btn) return;
    if (selectedIds.size === 0) {
      btn.disabled = true;
      btn.style.opacity = '0.5';
      btn.style.cursor = 'not-allowed';
    } else {
      btn.disabled = false;
      btn.style.opacity = '1';
      btn.style.cursor = 'pointer';
    }
  }

  // ---- swal nested per scelta tipo+motivo ----
  function buildMarkContent(defaultType){
    const root = document.createElement('div');
    const lblT = el('label', { text: 'Tipo', style: 'display:block;text-align:left;font-size:12px;font-weight:600;margin-bottom:4px;' });
    const sel = document.createElement('select');
    sel.id = 'swal-type';
    sel.className = 'swal2-select';
    sel.style.cssText = 'display:block;width:100%;margin:0 0 10px 0;';
    [['justified','Giustificata'],['not_justified','Non giustificata']].forEach(o => {
      const op = document.createElement('option');
      op.value = o[0]; op.textContent = o[1];
      if (defaultType === o[0]) op.selected = true;
      sel.appendChild(op);
    });
    const lblR = el('label', { text: 'Motivo *', style: 'display:block;text-align:left;font-size:12px;font-weight:600;margin:8px 0 4px 0;' });
    const inpR = document.createElement('input');
    inpR.id = 'swal-reason'; inpR.className = 'swal2-input';
    inpR.placeholder = "Motivo dell'assenza";
    inpR.style.cssText = 'width:100%;max-width:none;display:block;margin:0 0 10px 0;';
    const lblN = el('label', { text: 'Note (opzionale)', style: 'display:block;text-align:left;font-size:12px;font-weight:600;margin:8px 0 4px 0;' });
    const txtN = document.createElement('textarea');
    txtN.id = 'swal-notes'; txtN.className = 'swal2-textarea'; txtN.rows = 2;
    txtN.placeholder = 'Note aggiuntive';
    txtN.style.cssText = 'width:100%;max-width:none;display:block;margin:0;';
    const warn = el('div', { text: 'Le assenze inserite da gestionale NON sono revocabili dal paziente tramite app.', style: 'margin-top:10px;font-size:11px;color:#92400e;text-align:left;' });
    root.appendChild(lblT); root.appendChild(sel);
    root.appendChild(lblR); root.appendChild(inpR);
    root.appendChild(lblN); root.appendChild(txtN);
    root.appendChild(warn);
    return root;
  }

  function openMarkDialog(ids, defaultType){
    Swal.fire({
      title: 'Segna ' + ids.length + ' appuntamento/i come assente',
      html: buildMarkContent(defaultType),
      focusConfirm: false,
      showCancelButton: true,
      cancelButtonText: 'Annulla',
      confirmButtonText: 'Conferma',
      confirmButtonColor: '#d97706',
      preConfirm: () => {
        const type = document.getElementById('swal-type').value;
        const reason = document.getElementById('swal-reason').value.trim();
        const notes = document.getElementById('swal-notes').value.trim();
        if (!reason) { Swal.showValidationMessage('Motivo obbligatorio'); return false; }
        return { type, reason, notes };
      }
    }).then(async (res) => {
      if (!res.isConfirmed || !res.value) return;
      try {
        const r = await fetch(MARK_URL, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN,
          },
          body: JSON.stringify({
            appointmentIds: ids,
            absenceType: res.value.type,
            reason: res.value.reason,
            notes: res.value.notes,
          })
        });
        const data = await r.json();
        if (data.success) {
          Swal.fire({ icon:'success', title:'Aggiornati', text: data.updated+' appuntamento/i. '+(data.skipped && data.skipped.length ? data.skipped.length+' saltati.' : ''), timer: 2000, showConfirmButton:false }).then(() => {
            selectedIds.clear();
            loadAppointments();
          });
        } else {
          Swal.fire({ icon:'error', title:'Errore', text: data.error || 'Operazione fallita' });
        }
      } catch(e) {
        Swal.fire({ icon:'error', title:'Errore', text: e.message || 'Errore di rete' });
      }
    });
  }
})();
JS;
$this->registerJs($js);

$this->registerCss(<<<CSS
div:where(.swal2-container) { z-index: 2147483647 !important; }
CSS);
?>
