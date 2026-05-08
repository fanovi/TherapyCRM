<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */

$this->title = 'Crea Assenze Paziente';
$this->params['breadcrumbs'][] = ['label' => 'Assenze Pazienti', 'url' => ['patients']];
$this->params['breadcrumbs'][] = $this->title;

$searchUrl = Url::to(['absence/search-patients-absence']);
$apptsUrl = Url::to(['absence/patient-appointments-absence']);
$markUrl = Url::to(['absence/mark-patients-absent']);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="mx-auto max-w-full p-4 md:p-6" id="patient-absence-app">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            <?= Html::encode($this->title) ?>
        </h2>
        <a href="<?= Url::to(['patients']) ?>"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <span>← Torna alla lista assenze</span>
        </a>
    </div>

    <!-- Step 1: Ricerca paziente -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 mb-4">
        <h3 class="text-base font-semibold text-gray-800 mb-3">1. Seleziona paziente</h3>
        <div id="patient-selected-box" class="hidden mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-center justify-between">
            <div>
                <div class="font-medium text-blue-900" id="patient-selected-name"></div>
                <div class="text-xs text-blue-700" id="patient-selected-meta"></div>
            </div>
            <button type="button" id="btn-change-patient"
                    class="text-sm text-blue-700 hover:underline">Cambia</button>
        </div>

        <div id="patient-search-box" class="relative">
            <input type="text" id="patient-search-input"
                   placeholder="Cerca paziente per nome, cognome, codice fiscale o data di nascita..."
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                   autocomplete="off" />
            <div id="patient-search-spinner" class="hidden absolute right-3 top-2.5">
                <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>
            <ul id="patient-search-results"
                class="hidden absolute z-20 mt-1 w-full max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg divide-y"></ul>
        </div>
    </div>

    <!-- Step 2: Filtri data + tabella appuntamenti -->
    <div id="appointments-section" class="hidden">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 mb-4">
            <h3 class="text-base font-semibold text-gray-800 mb-3">2. Filtra appuntamenti</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Da</label>
                    <input type="date" id="filter-from"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">A</label>
                    <input type="date" id="filter-to"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                </div>
                <div class="flex items-end gap-2">
                    <button type="button" id="btn-filter"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Filtra
                    </button>
                    <button type="button" id="btn-filter-reset"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">3. Appuntamenti</h3>
                    <p class="text-xs text-gray-500">
                        <span id="appts-count">0</span> appuntamenti — <span id="selected-count">0</span> selezionati
                    </p>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="btn-bulk-mark" disabled
                            class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Marca selezionati come assenti
                    </button>
                </div>
            </div>

            <div id="appts-loading" class="hidden p-8 text-center text-sm text-gray-500">
                Caricamento appuntamenti...
            </div>
            <div id="appts-empty" class="hidden p-8 text-center text-sm text-gray-500">
                Nessun appuntamento trovato per i filtri selezionati.
            </div>

            <div id="appts-table-wrap" class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left w-10">
                                <input type="checkbox" id="select-all" class="h-4 w-4 cursor-pointer" />
                            </th>
                            <th class="px-4 py-2 text-left">Data</th>
                            <th class="px-4 py-2 text-left">Orario</th>
                            <th class="px-4 py-2 text-left">Terapista</th>
                            <th class="px-4 py-2 text-left">Trattamento</th>
                            <th class="px-4 py-2 text-left">Tipo</th>
                            <th class="px-4 py-2 text-left">Stato</th>
                            <th class="px-4 py-2 text-left w-32">Azioni rapide</th>
                        </tr>
                    </thead>
                    <tbody id="appts-tbody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 text-xs text-gray-600">
                Suggerimento: clicca sulla data per selezionare/deselezionare tutti gli appuntamenti del giorno.
            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
(function(){
  const SEARCH_URL = '$searchUrl';
  const APPTS_URL = '$apptsUrl';
  const MARK_URL = '$markUrl';
  const CSRF_TOKEN = '$csrfToken';

  let selectedPatient = null;
  let appointments = [];
  const selectedIds = new Set();

  const \$ = id => document.getElementById(id);
  const el = (tag, opts) => {
    const e = document.createElement(tag);
    if (!opts) return e;
    if (opts.cls) e.className = opts.cls;
    if (opts.text != null) e.textContent = String(opts.text);
    if (opts.attrs) for (const k in opts.attrs) e.setAttribute(k, opts.attrs[k]);
    if (opts.dataset) for (const k in opts.dataset) e.dataset[k] = opts.dataset[k];
    return e;
  };

  function debounce(fn, ms){ let t; return function(){ clearTimeout(t); const a=arguments; t=setTimeout(()=>fn.apply(null,a),ms); }; }

  function fmtDate(iso){ if(!iso) return '-'; const m = String(iso).match(/^(\\d{4})-(\\d{2})-(\\d{2})/); return m ? m[3]+'/'+m[2]+'/'+m[1] : iso; }
  function fmtTime(iso){ const m = String(iso).match(/(\\d{2}:\\d{2})/); return m ? m[1] : '-'; }

  const STATUS_MAP = {
    'scheduled': ['Programmato','bg-green-100 text-green-700'],
    'completed': ['Completato','bg-indigo-100 text-indigo-700'],
    'absent_justified': ['Assente giustificato','bg-orange-100 text-orange-700'],
    'absent_not_justified': ['Assente non giust.','bg-red-100 text-red-700'],
    'therapist_absent': ['Terapista assente','bg-purple-100 text-purple-700'],
    'cancelled': ['Cancellato','bg-gray-100 text-gray-600'],
  };

  function statusBadge(s, isAdmin){
    const wrap = el('span');
    const m = STATUS_MAP[s] || [s, 'bg-gray-100 text-gray-700'];
    wrap.appendChild(el('span', { cls: 'px-2 py-0.5 rounded text-xs font-medium '+m[1], text: m[0] }));
    if (isAdmin && (s==='absent_justified' || s==='absent_not_justified')) {
      wrap.appendChild(el('span', { cls: 'text-[10px] text-amber-700 ml-1', text: '[gestionale]' }));
    }
    return wrap;
  }

  // ---- search paziente ----
  const searchInput = \$('patient-search-input');
  const searchResults = \$('patient-search-results');
  const searchSpinner = \$('patient-search-spinner');

  async function fetchPatients(q){
    searchSpinner.classList.remove('hidden');
    try{
      const r = await fetch(SEARCH_URL+'?q='+encodeURIComponent(q));
      const data = await r.json();
      renderResults(data.items || []);
    }catch(e){ console.error(e); }
    finally{ searchSpinner.classList.add('hidden'); }
  }

  function renderResults(items){
    searchResults.replaceChildren();
    if (!items.length) { searchResults.classList.add('hidden'); return; }
    items.forEach(p => {
      const li = el('li', { cls: 'px-3 py-2 hover:bg-blue-50 cursor-pointer' });
      li.dataset.id = p.id;
      li.dataset.name = p.name || '';
      li.dataset.fc = p.fiscalCode || '';
      li.dataset.bd = p.birthDate || '';
      li.appendChild(el('div', { cls: 'font-medium', text: p.name }));
      const meta = (p.fiscalCode || '-') + (p.birthDate ? (' • ' + fmtDate(p.birthDate)) : '');
      li.appendChild(el('div', { cls: 'text-xs text-gray-500', text: meta }));
      searchResults.appendChild(li);
    });
    searchResults.classList.remove('hidden');
  }

  searchInput.addEventListener('input', debounce(()=>{ fetchPatients(searchInput.value.trim()); }, 350));
  searchInput.addEventListener('focus', ()=>{ if (searchInput.value.trim()) fetchPatients(searchInput.value.trim()); });

  searchResults.addEventListener('click', (e)=>{
    const li = e.target.closest('li[data-id]');
    if (!li) return;
    selectedPatient = {
      id: parseInt(li.dataset.id),
      name: li.dataset.name,
      fiscalCode: li.dataset.fc,
      birthDate: li.dataset.bd,
    };
    \$('patient-selected-box').classList.remove('hidden');
    \$('patient-search-box').classList.add('hidden');
    \$('patient-selected-name').textContent = selectedPatient.name;
    \$('patient-selected-meta').textContent = (selectedPatient.fiscalCode||'-') + (selectedPatient.birthDate?(' • '+fmtDate(selectedPatient.birthDate)):'');
    searchResults.classList.add('hidden');
    \$('appointments-section').classList.remove('hidden');
    loadAppointments();
  });

  document.addEventListener('click', (e)=>{
    if (!searchResults.contains(e.target) && e.target !== searchInput) searchResults.classList.add('hidden');
  });

  \$('btn-change-patient').addEventListener('click', ()=>{
    selectedPatient = null;
    appointments = [];
    selectedIds.clear();
    \$('patient-selected-box').classList.add('hidden');
    \$('patient-search-box').classList.remove('hidden');
    \$('appointments-section').classList.add('hidden');
    searchInput.value = '';
    searchInput.focus();
  });

  // ---- appointment loading ----
  async function loadAppointments(){
    if (!selectedPatient) return;
    \$('appts-loading').classList.remove('hidden');
    \$('appts-empty').classList.add('hidden');
    \$('appts-table-wrap').classList.add('hidden');
    selectedIds.clear();
    updateBulkBtn();
    const from = \$('filter-from').value || '';
    const to = \$('filter-to').value || '';
    try{
      const r = await fetch(APPTS_URL+'?patientId='+selectedPatient.id+'&from='+from+'&to='+to);
      const data = await r.json();
      appointments = data.items || [];
      renderAppointments();
    }catch(e){ console.error(e); }
    finally{ \$('appts-loading').classList.add('hidden'); }
  }

  function renderAppointments(){
    \$('appts-count').textContent = String(appointments.length);
    const tbody = \$('appts-tbody');
    tbody.replaceChildren();
    if (!appointments.length){
      \$('appts-empty').classList.remove('hidden');
      \$('appts-table-wrap').classList.add('hidden');
      return;
    }
    \$('appts-table-wrap').classList.remove('hidden');
    appointments.forEach(a => {
      const tr = el('tr', { cls: 'hover:bg-gray-50' });
      const date = String(a.datetime || '').split(' ')[0];

      const tdCheck = el('td', { cls: 'px-4 py-2' });
      const cb = el('input', { cls: 'h-4 w-4 cursor-pointer row-checkbox' });
      cb.type = 'checkbox';
      cb.dataset.id = String(a.id);
      cb.checked = selectedIds.has(a.id);
      tdCheck.appendChild(cb);

      const tdDate = el('td', { cls: 'px-4 py-2 cursor-pointer underline-on-hover', text: fmtDate(a.datetime), attrs: { title: 'Clic per (de)selezionare tutti del giorno' } });
      tdDate.dataset.day = date;

      const tdTime = el('td', { cls: 'px-4 py-2', text: fmtTime(a.datetime)+' ('+a.duration+'min)' });
      const tdTher = el('td', { cls: 'px-4 py-2', text: a.therapist || '-' });
      const tdTreat = el('td', { cls: 'px-4 py-2', text: a.treatmentType || '-' });
      const tdAt = el('td', { cls: 'px-4 py-2 text-xs', text: a.appointmentType || '-' });

      const tdStatus = el('td', { cls: 'px-4 py-2' });
      tdStatus.appendChild(statusBadge(a.status, a.isAdminAbsence));

      const tdActions = el('td', { cls: 'px-4 py-2 text-xs' });
      const bJ = el('button', { cls: 'text-orange-700 hover:underline mr-2 quick-mark', text: 'Giust.', attrs: { type: 'button' }, dataset: { id: String(a.id), type: 'justified' } });
      const bN = el('button', { cls: 'text-red-700 hover:underline quick-mark', text: 'Non giust.', attrs: { type: 'button' }, dataset: { id: String(a.id), type: 'not_justified' } });
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
    \$('select-all').checked = false;
    updateBulkBtn();
  }

  \$('appts-tbody').addEventListener('change', (e)=>{
    const cb = e.target.closest('.row-checkbox');
    if (!cb) return;
    const id = parseInt(cb.dataset.id);
    if (cb.checked) selectedIds.add(id); else selectedIds.delete(id);
    updateBulkBtn();
  });

  \$('select-all').addEventListener('change', (e)=>{
    appointments.forEach(a => { if (e.target.checked) selectedIds.add(a.id); else selectedIds.delete(a.id); });
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = e.target.checked);
    updateBulkBtn();
  });

  \$('appts-tbody').addEventListener('click', (e)=>{
    const dayCell = e.target.closest('[data-day]');
    if (dayCell) {
      const day = dayCell.dataset.day;
      const ids = appointments.filter(a => String(a.datetime).startsWith(day)).map(a=>a.id);
      const allSelected = ids.every(id => selectedIds.has(id));
      ids.forEach(id => allSelected ? selectedIds.delete(id) : selectedIds.add(id));
      document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = selectedIds.has(parseInt(cb.dataset.id));
      });
      updateBulkBtn();
      return;
    }
    const qm = e.target.closest('.quick-mark');
    if (qm) {
      const id = parseInt(qm.dataset.id);
      const type = qm.dataset.type;
      openMarkDialog([id], type);
    }
  });

  function updateBulkBtn(){
    \$('selected-count').textContent = String(selectedIds.size);
    \$('btn-bulk-mark').disabled = selectedIds.size === 0;
  }

  \$('btn-bulk-mark').addEventListener('click', ()=>{
    if (selectedIds.size === 0) return;
    openMarkDialog(Array.from(selectedIds), null);
  });

  \$('btn-filter').addEventListener('click', loadAppointments);
  \$('btn-filter-reset').addEventListener('click', ()=>{
    \$('filter-from').value = '';
    \$('filter-to').value = '';
    loadAppointments();
  });

  // ---- swal dialog mark absent ----
  function buildSwalContent(defaultType){
    const root = document.createElement('div');

    const lblT = el('label', { cls: 'swal-l', text: 'Tipo' });
    lblT.style.cssText = 'display:block;text-align:left;font-size:12px;font-weight:600;margin-bottom:4px;';
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

    const lblR = el('label', { text: 'Motivo *' });
    lblR.style.cssText = 'display:block;text-align:left;font-size:12px;font-weight:600;margin:8px 0 4px 0;';
    const inpR = document.createElement('input');
    inpR.id = 'swal-reason'; inpR.className = 'swal2-input';
    inpR.placeholder = "Motivo dell'assenza";
    inpR.style.cssText = 'width:100%;max-width:none;display:block;margin:0 0 10px 0;';

    const lblN = el('label', { text: 'Note (opzionale)' });
    lblN.style.cssText = 'display:block;text-align:left;font-size:12px;font-weight:600;margin:8px 0 4px 0;';
    const txtN = document.createElement('textarea');
    txtN.id = 'swal-notes'; txtN.className = 'swal2-textarea'; txtN.rows = 2;
    txtN.placeholder = 'Note aggiuntive';
    txtN.style.cssText = 'width:100%;max-width:none;display:block;margin:0;';

    const warn = el('div', { text: 'Le assenze inserite da gestionale NON sono revocabili dal paziente tramite app.' });
    warn.style.cssText = 'margin-top:10px;font-size:11px;color:#92400e;text-align:left;';

    root.appendChild(lblT); root.appendChild(sel);
    root.appendChild(lblR); root.appendChild(inpR);
    root.appendChild(lblN); root.appendChild(txtN);
    root.appendChild(warn);
    return root;
  }

  function openMarkDialog(ids, defaultType){
    if (typeof Swal === 'undefined') {
      alert('Componente Swal non disponibile');
      return;
    }
    Swal.fire({
      title: 'Segna ' + ids.length + ' appuntamento/i come assente',
      html: buildSwalContent(defaultType),
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
          Swal.fire({ icon:'success', title:'Aggiornati', text: data.updated+' appuntamento/i. '+(data.skipped && data.skipped.length ? data.skipped.length+' saltati.' : ''), timer: 2200, showConfirmButton:false });
          selectedIds.clear();
          loadAppointments();
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
.underline-on-hover:hover { text-decoration: underline; color: #2563eb; }
CSS);
?>
