<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;
use common\helpers\GridViewHelper;

/** @var yii\web\View $this */
/** @var frontend\models\TherapistSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Gestione assenze terapista';
$this->params['breadcrumbs'][] = $this->title;

$listUrl = Url::to(['absence/therapist-absences-list']);
$createUrl = Url::to(['absence/create-therapist-absence']);
$revokeUrl = Url::to(['absence/revoke-therapist-absence-day']);
$csrfToken = Yii::$app->request->csrfToken;
?>

<div class="mx-auto max-w-full p-4 md:p-6">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
            <?= Html::encode($this->title) ?>
        </h2>
        <a href="<?= Url::to(['legacy-index']) ?>"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Vista storica
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="px-5 py-4 sm:px-6 sm:py-5">
            <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                Seleziona terapista
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Clicca su un terapista per visualizzare e creare le sue assenze.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-800">
            <div class="overflow-x-auto">
                <?php Pjax::begin(['id' => 'therapists-absence-pjax', 'enablePushState' => false]); ?>
                <?= GridView::widget(array_merge([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'min-w-full text-sm text-left text-gray-500 dark:text-gray-400'],
                    'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'],
                    'rowOptions' => function ($model) {
                        $profile = $model->user && $model->user->profile ? $model->user->profile : null;
                        return [
                            'class' => 'bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-blue-50 dark:hover:bg-gray-600 cursor-pointer therapist-row',
                            'data-id' => $model->id,
                            'data-name' => $profile ? trim($profile->first_name . ' ' . $profile->last_name) : ('Terapista #' . $model->id),
                            'data-spec' => $model->specialization ? $model->specialization->name : '',
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
                            'value' => function ($m) {
                                return $m->user && $m->user->profile ? $m->user->profile->last_name : '-';
                            },
                        ],
                        [
                            'attribute' => 'first_name',
                            'label' => 'Nome',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[120px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Nome...'],
                            'value' => function ($m) {
                                return $m->user && $m->user->profile ? $m->user->profile->first_name : '-';
                            },
                        ],
                        [
                            'attribute' => 'specialization_name',
                            'label' => 'Specializzazione',
                            'headerOptions' => ['class' => 'px-6 py-3 min-w-[150px]'],
                            'contentOptions' => ['class' => 'px-6 py-4 text-xs whitespace-nowrap'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white', 'placeholder' => 'Spec...'],
                            'value' => function ($m) {
                                return $m->specialization ? $m->specialization->name : '-';
                            },
                        ],
                        [
                            'attribute' => 'is_active',
                            'label' => 'Attivo',
                            'headerOptions' => ['class' => 'px-6 py-3 w-24'],
                            'contentOptions' => ['class' => 'px-6 py-4 text-xs whitespace-nowrap'],
                            'filter' => ['1' => 'Sì', '0' => 'No'],
                            'filterOptions' => ['class' => 'px-2 py-2'],
                            'filterInputOptions' => ['class' => 'w-full px-2 py-1 text-xs border border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-white'],
                            'format' => 'raw',
                            'value' => function ($m) {
                                return $m->is_active
                                    ? '<span style="background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:6px;font-size:11px;">Sì</span>'
                                    : '<span style="background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:6px;font-size:11px;">No</span>';
                            },
                        ],
                        [
                            'header' => 'Azione',
                            'headerOptions' => ['class' => 'px-6 py-3 w-32'],
                            'contentOptions' => ['class' => 'px-6 py-4'],
                            'format' => 'raw',
                            'value' => function ($m) {
                                return '<button type="button" class="open-therapist-modal" style="background:#2563eb;color:#fff;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:500;border:none;cursor:pointer;">Gestisci assenze</button>';
                            },
                        ],
                    ],
                ], GridViewHelper::getGridViewConfig('terapisti'))); ?>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
(function(){
  const LIST_URL = '$listUrl';
  const CREATE_URL = '$createUrl';
  const REVOKE_URL = '$revokeUrl';
  const CSRF_TOKEN = '$csrfToken';

  let selectedTherapist = null;
  let absences = [];

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
  function pad(n){ return n < 10 ? '0' + n : '' + n; }
  function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }

  function escapeHtml(s){ const d = document.createElement('div'); d.textContent = String(s); return d.innerHTML; }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.open-therapist-modal');
    const row = e.target.closest('.therapist-row');
    if (!btn && !row) return;
    if (e.target.closest('input')) return;
    const tr = row || (btn && btn.closest('.therapist-row'));
    if (!tr || !tr.dataset.id) return;
    selectedTherapist = {
      id: parseInt(tr.dataset.id),
      name: tr.dataset.name || '',
      specialization: tr.dataset.spec || '',
    };
    openSwalModal();
  });

  function buildModalContent(){
    const root = document.createElement('div');
    root.style.textAlign = 'left';

    // Preset rapidi range
    const quick = el('div', { style: 'display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;' });
    [['Oggi','today'],['Domani','tomorrow'],['Questa settimana','this_week'],['Settimana prossima','next_week'],['Questo mese','this_month'],['Prossimo mese','next_month']].forEach(p => {
      const b = el('button', { text: p[0], attrs: { type: 'button' }, dataset: { preset: p[1] }, cls: 'ta-quick-preset', style: 'padding:5px 10px;background:#fff;border:1px solid #d1d5db;border-radius:9999px;font-size:12px;cursor:pointer;color:#374151;' });
      quick.appendChild(b);
    });
    root.appendChild(quick);

    // Form crea assenza
    const form = el('div', { style: 'border:1px solid #e5e7eb;border-radius:10px;padding:12px;margin-bottom:14px;background:#f9fafb;' });
    form.appendChild(el('div', { text: 'Crea nuova assenza', style: 'font-size:13px;font-weight:600;color:#1f2937;margin-bottom:8px;' }));
    const grid = el('div', { style: 'display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;' });
    const fromBox = el('div');
    fromBox.appendChild(el('label', { text: 'Data inizio *', style: 'display:block;font-size:11px;font-weight:600;margin-bottom:4px;' }));
    const inpFrom = el('input', { attrs: { type: 'date', id: 'ta-start' }, style: 'width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;cursor:pointer;' });
    inpFrom.addEventListener('click', () => { try { inpFrom.showPicker && inpFrom.showPicker(); } catch(e){} });
    fromBox.appendChild(inpFrom);
    const toBox = el('div');
    toBox.appendChild(el('label', { text: 'Data fine *', style: 'display:block;font-size:11px;font-weight:600;margin-bottom:4px;' }));
    const inpTo = el('input', { attrs: { type: 'date', id: 'ta-end' }, style: 'width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;cursor:pointer;' });
    inpTo.addEventListener('click', () => { try { inpTo.showPicker && inpTo.showPicker(); } catch(e){} });
    toBox.appendChild(inpTo);
    const typeBox = el('div');
    typeBox.appendChild(el('label', { text: 'Tipo *', style: 'display:block;font-size:11px;font-weight:600;margin-bottom:4px;' }));
    const sel = document.createElement('select');
    sel.id = 'ta-type';
    sel.style.cssText = 'width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;background:#fff;';
    [['vacation','Ferie'],['sick_leave','Congedo Malattia'],['personal','Personale'],['training','Formazione'],['other','Altro']].forEach(o => {
      const op = document.createElement('option');
      op.value = o[0]; op.textContent = o[1];
      sel.appendChild(op);
    });
    typeBox.appendChild(sel);
    grid.appendChild(fromBox); grid.appendChild(toBox); grid.appendChild(typeBox);
    form.appendChild(grid);

    const reasonBox = el('div', { style: 'margin-top:10px;' });
    reasonBox.appendChild(el('label', { text: 'Motivo (opzionale)', style: 'display:block;font-size:11px;font-weight:600;margin-bottom:4px;' }));
    const txtR = document.createElement('textarea');
    txtR.id = 'ta-reason'; txtR.rows = 2;
    txtR.style.cssText = 'width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;resize:vertical;';
    txtR.placeholder = 'Note aggiuntive...';
    reasonBox.appendChild(txtR);
    form.appendChild(reasonBox);

    const formActions = el('div', { style: 'display:flex;justify-content:flex-end;gap:8px;margin-top:10px;' });
    const btnCreate = el('button', { text: 'Crea assenza', attrs: { type: 'button', id: 'ta-btn-create' }, style: 'padding:7px 14px;background:#16a34a;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:500;cursor:pointer;' });
    formActions.appendChild(btnCreate);
    form.appendChild(formActions);

    root.appendChild(form);

    // Stato
    const status = el('div', { attrs: { id: 'ta-status' }, text: 'Caricamento...', style: 'padding:10px;text-align:center;color:#6b7280;font-size:13px;' });
    root.appendChild(status);

    // Tabella assenze esistenti
    const wrap = el('div', { attrs: { id: 'ta-table-wrap' }, style: 'display:none;border:1px solid #e5e7eb;border-radius:8px;overflow:auto;max-height:40vh;' });
    const table = el('table', { style: 'width:100%;font-size:13px;border-collapse:collapse;' });
    const thead = el('thead', { style: 'background:#f9fafb;position:sticky;top:0;z-index:1;' });
    const trh = el('tr');
    ['Data inizio','Data fine','Tipo','Motivo','Stato','Azioni'].forEach(h => {
      trh.appendChild(el('th', { text: h, style: 'padding:8px 10px;text-align:left;font-size:11px;font-weight:600;color:#374151;text-transform:uppercase;' }));
    });
    thead.appendChild(trh);
    table.appendChild(thead);
    table.appendChild(el('tbody', { attrs: { id: 'ta-tbody' } }));
    wrap.appendChild(table);
    root.appendChild(wrap);

    return root;
  }

  function applyPreset(name){
    const today = new Date(); today.setHours(0,0,0,0);
    let from = null, to = null;
    if (name === 'today') { from = today; to = today; }
    else if (name === 'tomorrow') { from = new Date(today); from.setDate(from.getDate()+1); to = new Date(from); }
    else if (name === 'this_week') {
      const dow = today.getDay() || 7;
      from = new Date(today); from.setDate(from.getDate() - (dow - 1));
      to = new Date(from); to.setDate(to.getDate() + 6);
    }
    else if (name === 'next_week') {
      const dow = today.getDay() || 7;
      from = new Date(today); from.setDate(from.getDate() - (dow - 1) + 7);
      to = new Date(from); to.setDate(to.getDate() + 6);
    }
    else if (name === 'this_month') {
      from = new Date(today.getFullYear(), today.getMonth(), 1);
      to = new Date(today.getFullYear(), today.getMonth()+1, 0);
    }
    else if (name === 'next_month') {
      from = new Date(today.getFullYear(), today.getMonth()+1, 1);
      to = new Date(today.getFullYear(), today.getMonth()+2, 0);
    }
    if (from) document.getElementById('ta-start').value = ymd(from);
    if (to) document.getElementById('ta-end').value = ymd(to);
  }

  function openSwalModal(){
    if (typeof Swal === 'undefined') { alert('Swal non disponibile'); return; }
    const titleHtml = '<div style="text-align:left;"><div style="font-size:16px;font-weight:600;color:#1f2937;">' + escapeHtml(selectedTherapist.name) + '</div><div style="font-size:12px;color:#6b7280;font-weight:400;">' + escapeHtml(selectedTherapist.specialization || '-') + '</div></div>';
    Swal.fire({
      title: titleHtml,
      html: buildModalContent(),
      showCloseButton: true,
      showConfirmButton: false,
      showCancelButton: false,
      width: '85%',
      padding: '1.5rem',
      didOpen: () => {
        wireModalHandlers();
        loadAbsences();
      },
      willClose: () => {
        selectedTherapist = null;
        absences = [];
      }
    });
  }

  function wireModalHandlers(){
    document.querySelectorAll('.ta-quick-preset').forEach(b => {
      b.addEventListener('click', () => applyPreset(b.dataset.preset));
    });
    document.getElementById('ta-btn-create').addEventListener('click', createAbsence);
  }

  async function loadAbsences(){
    if (!selectedTherapist) return;
    const status = document.getElementById('ta-status');
    const wrap = document.getElementById('ta-table-wrap');
    status.style.display = 'block';
    status.textContent = 'Caricamento assenze...';
    wrap.style.display = 'none';
    try {
      const r = await fetch(LIST_URL+'?therapistId='+selectedTherapist.id);
      const data = await r.json();
      absences = data.items || [];
      renderAbsences();
    } catch(e) {
      status.textContent = 'Errore: ' + e.message;
    }
  }

  function statusBadge(s){
    const map = {
      'approved': ['Approvata','#dcfce7','#15803d'],
      'pending': ['In attesa','#fef3c7','#92400e'],
      'rejected': ['Rifiutata','#fee2e2','#b91c1c'],
    };
    const m = map[s] || [s, '#f3f4f6', '#374151'];
    return el('span', { text: m[0], style: 'padding:2px 8px;border-radius:6px;font-size:11px;font-weight:500;background:' + m[1] + ';color:' + m[2] + ';' });
  }

  function renderAbsences(){
    const status = document.getElementById('ta-status');
    const wrap = document.getElementById('ta-table-wrap');
    const tbody = document.getElementById('ta-tbody');
    tbody.replaceChildren();
    if (!absences.length) {
      status.style.display = 'block';
      status.textContent = 'Nessuna assenza registrata.';
      wrap.style.display = 'none';
      return;
    }
    status.style.display = 'none';
    wrap.style.display = 'block';
    absences.forEach(a => {
      const tr = el('tr', { style: 'border-top:1px solid #f3f4f6;' });
      tr.appendChild(el('td', { text: fmtDate(a.startDate), style: 'padding:6px 10px;' }));
      tr.appendChild(el('td', { text: fmtDate(a.endDate), style: 'padding:6px 10px;' }));
      tr.appendChild(el('td', { text: a.typeLabel || a.type, style: 'padding:6px 10px;' }));
      tr.appendChild(el('td', { text: a.reason || '-', style: 'padding:6px 10px;font-size:12px;color:#4b5563;' }));
      const tdSt = el('td', { style: 'padding:6px 10px;' });
      tdSt.appendChild(statusBadge(a.status));
      tr.appendChild(tdSt);
      const tdActions = el('td', { style: 'padding:6px 10px;' });
      if (a.status === 'approved') {
        const btn = el('button', { text: 'Revoca giorno...', attrs: { type: 'button' }, dataset: { absenceId: String(a.id), startDate: a.startDate, endDate: a.endDate }, cls: 'ta-revoke-btn', style: 'background:#dc2626;color:#fff;border:none;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;' });
        tdActions.appendChild(btn);
      } else {
        tdActions.appendChild(el('span', { text: '-', style: 'color:#9ca3af;' }));
      }
      tr.appendChild(tdActions);
      tbody.appendChild(tr);
    });

    document.querySelectorAll('.ta-revoke-btn').forEach(b => {
      b.addEventListener('click', () => openRevokeDialog(parseInt(b.dataset.absenceId), b.dataset.startDate, b.dataset.endDate));
    });
  }

  function openRevokeDialog(absenceId, startDate, endDate){
    const root = document.createElement('div');
    root.style.textAlign = 'left';
    const grid = el('div', { style: 'display:grid;grid-template-columns:1fr 1fr;gap:10px;' });
    const fromBox = el('div');
    fromBox.appendChild(el('label', { text: 'Da *', style: 'display:block;font-size:12px;font-weight:600;margin-bottom:4px;' }));
    const inpFrom = document.createElement('input');
    inpFrom.type = 'date'; inpFrom.id = 'ta-revoke-from';
    inpFrom.min = startDate; inpFrom.max = endDate; inpFrom.value = startDate;
    inpFrom.style.cssText = 'width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;cursor:pointer;';
    inpFrom.addEventListener('click', () => { try { inpFrom.showPicker && inpFrom.showPicker(); } catch(e){} });
    fromBox.appendChild(inpFrom);
    const toBox = el('div');
    toBox.appendChild(el('label', { text: 'A *', style: 'display:block;font-size:12px;font-weight:600;margin-bottom:4px;' }));
    const inpTo = document.createElement('input');
    inpTo.type = 'date'; inpTo.id = 'ta-revoke-to';
    inpTo.min = startDate; inpTo.max = endDate; inpTo.value = endDate;
    inpTo.style.cssText = 'width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;cursor:pointer;';
    inpTo.addEventListener('click', () => { try { inpTo.showPicker && inpTo.showPicker(); } catch(e){} });
    toBox.appendChild(inpTo);
    grid.appendChild(fromBox); grid.appendChild(toBox);
    root.appendChild(grid);

    root.appendChild(el('div', { text: 'Range assenza: ' + fmtDate(startDate) + ' — ' + fmtDate(endDate), style: 'font-size:11px;color:#6b7280;margin-top:4px;' }));

    // Bottone "Tutto"
    const btnAll = el('button', { text: 'Revoca tutto il range', attrs: { type: 'button' }, style: 'margin-top:8px;padding:5px 10px;background:#fff;border:1px solid #d1d5db;border-radius:6px;font-size:11px;cursor:pointer;color:#374151;' });
    btnAll.addEventListener('click', () => {
      inpFrom.value = startDate;
      inpTo.value = endDate;
    });
    root.appendChild(btnAll);

    const warn = el('div', { style: 'margin-top:10px;padding:8px;background:#fef3c7;border:1px solid #fde68a;border-radius:6px;font-size:11px;color:#92400e;' });
    warn.textContent = 'Gli appuntamenti dei giorni con sostituto verranno restituiti al terapista originale. Notifiche inviate ai terapisti coinvolti.';
    root.appendChild(warn);

    Swal.fire({
      title: 'Revoca giorni assenza',
      html: root,
      focusConfirm: false,
      showCancelButton: true,
      cancelButtonText: 'Annulla',
      confirmButtonText: 'Conferma revoca',
      confirmButtonColor: '#dc2626',
      preConfirm: () => {
        const f = document.getElementById('ta-revoke-from').value;
        const t = document.getElementById('ta-revoke-to').value;
        if (!f || !t) { Swal.showValidationMessage('Seleziona entrambe le date'); return false; }
        if (f > t) { Swal.showValidationMessage('Data inizio dopo data fine'); return false; }
        return { from: f, to: t };
      }
    }).then(async (res) => {
      if (!res.isConfirmed || !res.value) return;
      const fd = new URLSearchParams();
      fd.append('_csrf-frontend', CSRF_TOKEN);
      fd.append('absenceId', String(absenceId));
      fd.append('startDate', res.value.from);
      fd.append('endDate', res.value.to);
      try {
        const r = await fetch(REVOKE_URL, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CSRF_TOKEN },
          body: fd
        });
        const data = await r.json();
        if (data.success) {
          const rangeStr = res.value.from === res.value.to
            ? 'giorno ' + fmtDate(res.value.from)
            : 'dal ' + fmtDate(res.value.from) + ' al ' + fmtDate(res.value.to);
          let msg = 'Revocato ' + rangeStr + '. Ripristinati ' + (data.restored || 0) + ' appuntamento/i.';
          if (data.skipped && data.skipped.length) {
            msg += ' ' + data.skipped.length + ' saltati per conflitti (terapista occupato).';
          }
          Swal.fire({ icon: 'success', title: 'Revoca completata', text: msg }).then(() => loadAbsences());
        } else {
          Swal.fire({ icon: 'error', title: 'Errore', text: data.error || 'Operazione fallita' });
        }
      } catch(e) {
        Swal.fire({ icon: 'error', title: 'Errore', text: e.message || 'Errore di rete' });
      }
    });
  }

  async function createAbsence(){
    if (!selectedTherapist) return;
    const startDate = document.getElementById('ta-start').value;
    const endDate = document.getElementById('ta-end').value;
    const type = document.getElementById('ta-type').value;
    const reason = document.getElementById('ta-reason').value.trim();

    if (!startDate || !endDate) { Swal.showValidationMessage('Date obbligatorie'); return; }
    if (startDate > endDate) { Swal.showValidationMessage('Data inizio dopo data fine'); return; }

    const fd = new URLSearchParams();
    fd.append('_csrf-frontend', CSRF_TOKEN);
    fd.append('therapistId', String(selectedTherapist.id));
    fd.append('startDate', startDate);
    fd.append('endDate', endDate);
    fd.append('type', type);
    fd.append('reason', reason);

    try {
      const r = await fetch(CREATE_URL, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': CSRF_TOKEN,
        },
        body: fd
      });
      const data = await r.json();
      if (data.success) {
        // reset form
        document.getElementById('ta-start').value = '';
        document.getElementById('ta-end').value = '';
        document.getElementById('ta-reason').value = '';
        // toast inline
        const status = document.getElementById('ta-status');
        status.style.display = 'block';
        status.textContent = 'Assenza creata. Aggiorno...';
        loadAbsences();
      } else {
        Swal.fire({ icon: 'error', title: 'Errore', text: data.error || 'Operazione fallita' });
      }
    } catch(e) {
      Swal.fire({ icon: 'error', title: 'Errore', text: e.message || 'Errore di rete' });
    }
  }
})();
JS;
$this->registerJs($js);

$this->registerCss(<<<CSS
div:where(.swal2-container) { z-index: 2147483647 !important; }
CSS);
?>
