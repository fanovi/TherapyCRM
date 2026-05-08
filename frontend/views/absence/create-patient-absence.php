<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

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

    <div class="rounded-2xl border border-gray-200 bg-white">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-base font-semibold text-gray-800">Seleziona paziente</h3>
            <p class="text-xs text-gray-500 mt-1">Clicca su un paziente per gestire le sue assenze.</p>
        </div>
        <div class="overflow-x-auto">
            <?php Pjax::begin(['id' => 'patients-absence-pjax', 'enablePushState' => false]); ?>
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500'],
                'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50'],
                'rowOptions' => function ($model) {
                    return [
                        'class' => 'bg-white border-b hover:bg-blue-50 cursor-pointer patient-row',
                        'data-id' => $model->id,
                        'data-name' => trim($model->first_name . ' ' . $model->last_name),
                        'data-fc' => $model->fiscal_code ?? '',
                        'data-bd' => $model->birth_date ?? '',
                    ];
                },
                'filterRowOptions' => ['class' => 'bg-gray-100 border-b border-gray-200'],
                'columns' => [
                    [
                        'attribute' => 'last_name',
                        'label' => 'Cognome',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-3 font-medium text-gray-900'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded', 'placeholder' => 'Cognome...'],
                    ],
                    [
                        'attribute' => 'first_name',
                        'label' => 'Nome',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-3'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded', 'placeholder' => 'Nome...'],
                    ],
                    [
                        'attribute' => 'fiscal_code',
                        'label' => 'Codice fiscale',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[150px]'],
                        'contentOptions' => ['class' => 'px-4 py-3 text-xs'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded', 'placeholder' => 'CF...'],
                    ],
                    [
                        'attribute' => 'birth_date',
                        'label' => 'Data nascita',
                        'headerOptions' => ['class' => 'px-4 py-3 min-w-[120px]'],
                        'contentOptions' => ['class' => 'px-4 py-3 text-xs'],
                        'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded', 'type' => 'date'],
                        'value' => function ($m) {
                            return $m->birth_date
                                ? Yii::$app->formatter->asDate($m->birth_date, 'php:d/m/Y')
                                : '-';
                        },
                    ],
                    [
                        'header' => 'Azione',
                        'headerOptions' => ['class' => 'px-4 py-3 w-32'],
                        'contentOptions' => ['class' => 'px-4 py-3'],
                        'format' => 'raw',
                        'value' => function ($m) {
                            return '<button type="button" class="open-patient-modal rounded-lg bg-amber-600 px-3 py-1 text-xs font-medium text-white hover:bg-amber-700">Gestisci assenze</button>';
                        },
                    ],
                ],
            ]); ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>

<!-- Modale gestione assenze paziente -->
<div id="patient-absence-modal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-start justify-center p-4 pt-10 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-5 border-b border-gray-200">
            <div>
                <h3 class="text-lg font-semibold text-gray-800" id="modal-patient-name">-</h3>
                <p class="text-xs text-gray-500" id="modal-patient-meta">-</p>
            </div>
            <button type="button" id="modal-close" class="text-gray-400 hover:text-gray-600 p-1">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M6 6l12 12M6 18L18 6"/></svg>
            </button>
        </div>

        <div class="p-5 border-b border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Da</label>
                <input type="date" id="filter-from" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">A</label>
                <input type="date" id="filter-to" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <div class="flex items-end gap-2">
                <button type="button" id="btn-filter" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Filtra</button>
                <button type="button" id="btn-filter-reset" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">Reset</button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <div id="appts-loading" class="hidden p-8 text-center text-sm text-gray-500">Caricamento appuntamenti...</div>
            <div id="appts-empty" class="hidden p-8 text-center text-sm text-gray-500">Nessun appuntamento trovato.</div>
            <div id="appts-table-wrap" class="hidden overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700 sticky top-0">
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
        </div>

        <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
            <p class="text-xs text-gray-600">
                <span id="appts-count">0</span> appuntamenti — <span id="selected-count">0</span> selezionati
                <span class="ml-2 text-gray-500">Suggerimento: clicca sulla data per (de)selezionare tutti del giorno</span>
            </p>
            <button type="button" id="btn-bulk-mark" disabled
                    class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Marca selezionati come assenti
            </button>
        </div>
    </div>
</div>

<?php
$js = <<<JS
(function(){
  const APPTS_URL = '$apptsUrl';
  const MARK_URL = '$markUrl';
  const CSRF_TOKEN = '$csrfToken';

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

  let selectedPatient = null;
  let appointments = [];
  const selectedIds = new Set();

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

  // --- click su row paziente apre modale ---
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
    openModal();
  });

  function openModal(){
    \$('modal-patient-name').textContent = selectedPatient.name || '-';
    const metaParts = [];
    if (selectedPatient.fiscalCode) metaParts.push(selectedPatient.fiscalCode);
    if (selectedPatient.birthDate) metaParts.push(fmtDate(selectedPatient.birthDate));
    \$('modal-patient-meta').textContent = metaParts.join(' • ') || '-';
    \$('filter-from').value = '';
    \$('filter-to').value = '';
    \$('patient-absence-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    selectedIds.clear();
    loadAppointments();
  }

  function closeModal(){
    \$('patient-absence-modal').classList.add('hidden');
    document.body.style.overflow = '';
    selectedPatient = null;
    appointments = [];
    selectedIds.clear();
  }

  \$('modal-close').addEventListener('click', closeModal);

  // --- carica appuntamenti ---
  async function loadAppointments(){
    if (!selectedPatient) return;
    \$('appts-loading').classList.remove('hidden');
    \$('appts-empty').classList.add('hidden');
    \$('appts-table-wrap').classList.add('hidden');
    selectedIds.clear();
    updateBulkBtn();
    const from = \$('filter-from').value || '';
    const to = \$('filter-to').value || '';
    try {
      const r = await fetch(APPTS_URL+'?patientId='+selectedPatient.id+'&from='+from+'&to='+to);
      const data = await r.json();
      appointments = data.items || [];
      renderAppointments();
    } catch(e){ console.error(e); }
    finally { \$('appts-loading').classList.add('hidden'); }
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

      const tdTime = el('td', { cls: 'px-4 py-2', text: fmtTime(a.datetime) + ' (' + a.duration + 'min)' });
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

  function buildSwalContent(defaultType){
    const root = document.createElement('div');
    const lblT = el('label', { text: 'Tipo' });
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
    if (typeof Swal === 'undefined') { alert('Componente Swal non disponibile'); return; }
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
