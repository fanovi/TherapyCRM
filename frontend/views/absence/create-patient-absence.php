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

    // Header navigazione mese
    const nav = el('div', { style: 'display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:10px;' });
    const btnPrev = el('button', { text: '←', attrs: { type: 'button', id: 'pa-prev-month' }, style: 'padding:6px 12px;background:#fff;border:1px solid #d1d5db;border-radius:6px;cursor:pointer;font-size:14px;' });
    const monthLabel = el('div', { attrs: { id: 'pa-month-label' }, text: '-', style: 'font-size:15px;font-weight:600;color:#1f2937;text-transform:capitalize;' });
    const btnNext = el('button', { text: '→', attrs: { type: 'button', id: 'pa-next-month' }, style: 'padding:6px 12px;background:#fff;border:1px solid #d1d5db;border-radius:6px;cursor:pointer;font-size:14px;' });
    const btnToday = el('button', { text: 'Oggi', attrs: { type: 'button', id: 'pa-today' }, style: 'padding:6px 12px;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;' });
    const navLeft = el('div', { style: 'display:flex;gap:6px;align-items:center;' });
    navLeft.appendChild(btnPrev); navLeft.appendChild(monthLabel); navLeft.appendChild(btnNext);
    nav.appendChild(navLeft); nav.appendChild(btnToday);
    root.appendChild(nav);

    // Stato
    const status = el('div', { attrs: { id: 'pa-status' }, text: 'Caricamento...', style: 'padding:8px;text-align:center;color:#6b7280;font-size:13px;' });
    root.appendChild(status);

    // Calendario
    const calWrap = el('div', { attrs: { id: 'pa-cal-wrap' }, style: 'display:none;' });
    // Header giorni settimana
    const dayHead = el('div', { style: 'display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:6px;' });
    ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'].forEach(d => {
      dayHead.appendChild(el('div', { text: d, style: 'text-align:center;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;padding:6px 0;' }));
    });
    calWrap.appendChild(dayHead);
    calWrap.appendChild(el('div', { attrs: { id: 'pa-cal-grid' }, style: 'display:grid;grid-template-columns:repeat(7,1fr);gap:4px;' }));
    root.appendChild(calWrap);

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

  let currentMonth = new Date();
  currentMonth.setDate(1);
  currentMonth.setHours(0,0,0,0);

  function setMonthLabel(){
    const m = currentMonth.toLocaleDateString('it-IT', { month: 'long', year: 'numeric' });
    const lbl = document.getElementById('pa-month-label');
    if (lbl) lbl.textContent = m;
  }

  function pad(n){ return n < 10 ? '0' + n : '' + n; }
  function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }

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
    setMonthLabel();
    document.getElementById('pa-prev-month').addEventListener('click', () => {
      currentMonth.setMonth(currentMonth.getMonth() - 1);
      setMonthLabel();
      loadAppointments();
    });
    document.getElementById('pa-next-month').addEventListener('click', () => {
      currentMonth.setMonth(currentMonth.getMonth() + 1);
      setMonthLabel();
      loadAppointments();
    });
    document.getElementById('pa-today').addEventListener('click', () => {
      currentMonth = new Date();
      currentMonth.setDate(1);
      currentMonth.setHours(0,0,0,0);
      setMonthLabel();
      loadAppointments();
    });
    document.getElementById('pa-btn-bulk').addEventListener('click', () => {
      if (selectedIds.size === 0) return;
      openMarkDialog(Array.from(selectedIds), null);
    });
    document.getElementById('pa-cal-grid').addEventListener('click', (e) => {
      const chip = e.target.closest('.pa-appt-chip');
      if (chip) {
        e.stopPropagation();
        const id = parseInt(chip.dataset.id);
        if (selectedIds.has(id)) selectedIds.delete(id); else selectedIds.add(id);
        chip.style.outline = selectedIds.has(id) ? '2px solid #2563eb' : 'none';
        chip.style.outlineOffset = '-2px';
        updateBulkBtn();
        return;
      }
      const cell = e.target.closest('[data-day]');
      if (cell) {
        const day = cell.dataset.day;
        const ids = appointments.filter(a => String(a.datetime).startsWith(day)).map(a=>a.id);
        if (ids.length === 0) return;
        const allSelected = ids.every(id => selectedIds.has(id));
        ids.forEach(id => allSelected ? selectedIds.delete(id) : selectedIds.add(id));
        renderCalendar();
      }
    });
  }

  async function loadAppointments(){
    if (!selectedPatient) return;
    const status = document.getElementById('pa-status');
    const wrap = document.getElementById('pa-cal-wrap');
    status.style.display = 'block';
    status.textContent = 'Caricamento appuntamenti...';
    wrap.style.display = 'none';
    selectedIds.clear();
    updateBulkBtn();
    // range = mese corrente
    const first = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
    const last = new Date(currentMonth.getFullYear(), currentMonth.getMonth()+1, 0);
    const from = ymd(first);
    const to = ymd(last);
    try {
      const r = await fetch(APPTS_URL+'?patientId='+selectedPatient.id+'&from='+from+'&to='+to);
      const data = await r.json();
      appointments = data.items || [];
      renderCalendar();
    } catch(e) {
      status.textContent = 'Errore caricamento: ' + e.message;
    }
  }

  function colorForStatus(s){
    const m = STATUS_MAP[s];
    return m ? m[1] : '#f3f4f6';
  }
  function textColorForStatus(s){
    const m = STATUS_MAP[s];
    return m ? m[2] : '#374151';
  }

  function renderCalendar(){
    const status = document.getElementById('pa-status');
    const wrap = document.getElementById('pa-cal-wrap');
    const grid = document.getElementById('pa-cal-grid');
    grid.replaceChildren();
    status.style.display = 'none';
    wrap.style.display = 'block';

    // group appointments by day
    const byDay = {};
    appointments.forEach(a => {
      const k = String(a.datetime).split(' ')[0];
      (byDay[k] = byDay[k] || []).push(a);
    });

    // primo giorno mese, calcola offset (Lun=0)
    const first = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
    let offset = first.getDay() - 1; // Sun=0 -> -1, Mon=1 -> 0
    if (offset < 0) offset = 6;
    const lastDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth()+1, 0).getDate();
    const totalCells = Math.ceil((offset + lastDay) / 7) * 7;
    const today = ymd(new Date());

    for (let i = 0; i < totalCells; i++) {
      const dayNum = i - offset + 1;
      const cell = el('div', { style: 'min-height:90px;padding:6px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;display:flex;flex-direction:column;gap:4px;' });
      if (dayNum < 1 || dayNum > lastDay) {
        cell.style.background = '#f9fafb';
        cell.style.opacity = '0.5';
        grid.appendChild(cell);
        continue;
      }
      const cellDate = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), dayNum);
      const dayKey = ymd(cellDate);
      cell.dataset.day = dayKey;
      cell.style.cursor = 'pointer';

      const head = el('div', { style: 'display:flex;align-items:center;justify-content:space-between;font-size:12px;font-weight:600;color:' + (dayKey === today ? '#2563eb' : '#374151') + ';' });
      head.appendChild(el('span', { text: String(dayNum) }));
      const apts = byDay[dayKey] || [];
      if (apts.length > 0) {
        head.appendChild(el('span', { text: apts.length + (apts.length > 1 ? ' app.' : ' app.'), style: 'font-size:10px;font-weight:500;color:#6b7280;' }));
      }
      cell.appendChild(head);

      apts.forEach(a => {
        const isSel = selectedIds.has(a.id);
        const chip = el('div', { cls: 'pa-appt-chip', style: 'padding:2px 6px;border-radius:4px;font-size:10px;cursor:pointer;background:' + colorForStatus(a.status) + ';color:' + textColorForStatus(a.status) + ';overflow:hidden;text-overflow:ellipsis;white-space:nowrap;outline:' + (isSel ? '2px solid #2563eb' : 'none') + ';outline-offset:-2px;' });
        chip.dataset.id = String(a.id);
        const time = fmtTime(a.datetime);
        chip.title = time + ' • ' + (a.therapist || '-') + ' • ' + (a.treatmentType || '-') + (a.isAdminAbsence ? ' [gestionale]' : '');
        chip.textContent = time + ' ' + (a.therapist || '-').substring(0, 12);
        cell.appendChild(chip);
      });

      grid.appendChild(cell);
    }
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
        const fd = new URLSearchParams();
        fd.append('_csrf-frontend', CSRF_TOKEN);
        ids.forEach(id => fd.append('appointmentIds[]', String(id)));
        fd.append('absenceType', res.value.type);
        fd.append('reason', res.value.reason);
        fd.append('notes', res.value.notes || '');
        const r = await fetch(MARK_URL, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CSRF_TOKEN,
          },
          body: fd
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
