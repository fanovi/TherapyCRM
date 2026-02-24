<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'Manuale - L\'App Mobile';
$this->params['breadcrumbs'][] = ['label' => 'Manuale d\'Uso', 'url' => ['/site/manuale']];
$this->params['breadcrumbs'][] = 'L\'App Mobile';

$imgBase = Yii::getAlias('@web') . '/img/manuale/app';
?>

<style>
    .manual-img-placeholder {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-height: 180px; border: 2px dashed #cbd5e1; border-radius: 12px;
        background: #f8fafc; color: #94a3b8; font-size: 0.85rem; padding: 1.5rem; text-align: center;
    }
    .dark .manual-img-placeholder { background: rgba(255,255,255,0.03); border-color: #334155; color: #64748b; }
    .manual-img { border-radius: 12px; border: 1px solid #e2e8f0; max-width: 100%; height: auto; }
    .manual-img-mobile { border-radius: 12px; border: 1px solid #e2e8f0; max-width: 280px; height: auto; margin: 0 auto; display: block; }
    .dark .manual-img, .dark .manual-img-mobile { border-color: #334155; }
    .toc-link { display: block; padding: 4px 0; font-size: 0.8rem; color: #64748b; transition: color 0.15s; text-decoration: none; cursor: pointer; }
    .toc-link:hover { color: #10b981; }
    .dark .toc-link { color: #94a3b8; }
    .dark .toc-link:hover { color: #34d399; }
    .section-card { border-radius: 12px; border: 1px solid #e5e7eb; background: white; padding: 1.5rem; margin-bottom: 1rem; }
    .dark .section-card { background: rgba(255,255,255,0.03); border-color: #1f2937; }
    .step-num { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #10b981; color: white; font-size: 0.75rem; font-weight: 600; flex-shrink: 0; }
    .info-box { border-left: 4px solid #10b981; background: #ecfdf5; padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; margin: 0.75rem 0; font-size: 0.85rem; }
    .dark .info-box { background: rgba(16,185,129,0.1); }
    .warn-box { border-left: 4px solid #f59e0b; background: #fffbeb; padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; margin: 0.75rem 0; font-size: 0.85rem; }
    .dark .warn-box { background: rgba(245,158,11,0.1); }
    .mobile-placeholder { max-width: 280px; margin: 0 auto; }
</style>

<div class="flex gap-6 p-4 md:p-6" x-data="{ openSection: 'accesso' }">

    <!-- TOC Sidebar -->
    <aside class="hidden xl:block w-56 flex-shrink-0">
        <div class="sticky top-24">
            <a href="<?= Url::to(['/site/manuale']) ?>" class="flex items-center gap-1.5 text-xs text-green-500 hover:text-green-600 mb-4">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Torna ai manuali
            </a>
            <h4 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Indice</h4>
            <nav class="space-y-0.5 max-h-[calc(100vh-10rem)] overflow-y-auto pr-2">
                <a class="toc-link" @click="openSection='accesso'; $nextTick(() => $refs.accesso.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='accesso' && 'text-green-500 font-medium'">1. Primo Accesso</a>
                <a class="toc-link" @click="openSection='differenze'; $nextTick(() => $refs.differenze.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='differenze' && 'text-green-500 font-medium'">2. Paziente vs Terapista</a>
                <a class="toc-link" @click="openSection='paziente-home'; $nextTick(() => $refs.pazienteHome.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='paziente-home' && 'text-green-500 font-medium'">3. Paziente: Home</a>
                <a class="toc-link" @click="openSection='paziente-calendario'; $nextTick(() => $refs.pazienteCalendario.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='paziente-calendario' && 'text-green-500 font-medium'">4. Paziente: Calendario</a>
                <a class="toc-link" @click="openSection='paziente-richieste'; $nextTick(() => $refs.pazienteRichieste.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='paziente-richieste' && 'text-green-500 font-medium'">5. Paziente: Richieste</a>
                <a class="toc-link" @click="openSection='paziente-notifiche'; $nextTick(() => $refs.pazienteNotifiche.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='paziente-notifiche' && 'text-green-500 font-medium'">6. Paziente: Notifiche</a>
                <a class="toc-link" @click="openSection='paziente-profilo'; $nextTick(() => $refs.pazienteProfilo.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='paziente-profilo' && 'text-green-500 font-medium'">7. Paziente: Profilo</a>
                <a class="toc-link" @click="openSection='terapista-dashboard'; $nextTick(() => $refs.terapistaDashboard.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='terapista-dashboard' && 'text-green-500 font-medium'">8. Terapista: Dashboard</a>
                <a class="toc-link" @click="openSection='terapista-pazienti'; $nextTick(() => $refs.terapistaPazienti.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='terapista-pazienti' && 'text-green-500 font-medium'">9. Terapista: Pazienti</a>
                <a class="toc-link" @click="openSection='terapista-agenda'; $nextTick(() => $refs.terapistaAgenda.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='terapista-agenda' && 'text-green-500 font-medium'">10. Terapista: Agenda</a>
                <a class="toc-link" @click="openSection='terapista-notifiche'; $nextTick(() => $refs.terapistaNotifiche.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='terapista-notifiche' && 'text-green-500 font-medium'">11. Terapista: Notifiche</a>
                <a class="toc-link" @click="openSection='notifiche-push'; $nextTick(() => $refs.notifichePush.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='notifiche-push' && 'text-green-500 font-medium'">12. Notifiche Push</a>
                <a class="toc-link" @click="openSection='bloccanti'; $nextTick(() => $refs.bloccanti.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='bloccanti' && 'text-green-500 font-medium'">13. Notifiche Bloccanti</a>
                <a class="toc-link" @click="openSection='sicurezza'; $nextTick(() => $refs.sicurezza.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='sicurezza' && 'text-green-500 font-medium'">14. Sicurezza</a>
                <a class="toc-link" @click="openSection='faq'; $nextTick(() => $refs.faq.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='faq' && 'text-green-500 font-medium'">FAQ</a>
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 max-w-4xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/20">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">Manuale dell'App Mobile</h2>
                    <p class="text-xs text-gray-400">Per Pazienti e Terapisti - Centro Medico San Luca</p>
                </div>
            </div>
        </div>

        <!-- 1. PRIMO ACCESSO -->
        <div class="section-card cursor-pointer" x-ref="accesso" @click="openSection = openSection === 'accesso' ? '' : 'accesso'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">1. Primo Accesso all'App</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'accesso' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'accesso'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">1.1 Installazione</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">L'app <strong>TherapyCRM</strong> e disponibile per dispositivi Android. Scaricarla e installarla seguendo le istruzioni fornite dal centro.</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">1.2 Login</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Aprire l'app</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Inserire <strong>email</strong> e <strong>password</strong> fornite dal centro</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Toccare <strong>"Accedi"</strong></span></div>
                </div>

                <!-- Placeholder img-01 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-01.jpg" alt="Schermata login app" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-01.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Schermata di login dell'app</span>
                    </div>
                </div>

                <div class="info-box">Le credenziali vengono fornite dal centro alla registrazione del paziente o del terapista (via email o su foglio cartaceo).</div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">1.3 Cambio Password Obbligatorio</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Al primo login, l'app richiede di <strong>cambiare la password</strong>. Inserire una nuova password personale, confermarla e toccare "Conferma".</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">1.4 Password Dimenticata</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Toccare <strong>"Password dimenticata?"</strong> nella schermata di login</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Inserire l'email e toccare "Invia"</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Controllare l'email e cliccare sul link ricevuto</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Impostare la nuova password e tornare all'app</span></div>
                </div>
            </div>
        </div>

        <!-- 2. DIFFERENZE PAZIENTE VS TERAPISTA -->
        <div class="section-card cursor-pointer" x-ref="differenze" @click="openSection = openSection === 'differenze' ? '' : 'differenze'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">2. Paziente vs Terapista: Le Differenze</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'differenze' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'differenze'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">L'app si presenta in modo diverso a seconda del tipo di utente:</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-800/50">
                            <tr><th class="px-3 py-2">Funzione</th><th class="px-3 py-2">Paziente/Familiare</th><th class="px-3 py-2">Terapista</th></tr>
                        </thead>
                        <tbody class="text-gray-600 dark:text-gray-400">
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Home</td><td class="px-3 py-2">Prossimi appuntamenti</td><td class="px-3 py-2">Dashboard con statistiche</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Calendario</td><td class="px-3 py-2">Vedi/annulla propri appuntamenti</td><td class="px-3 py-2">Vedi appuntamenti di tutti i pazienti</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Richieste</td><td class="px-3 py-2">Invia e traccia richieste documenti</td><td class="px-3 py-2">Non disponibile</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Notifiche</td><td class="px-3 py-2">Riceve notifiche dal centro</td><td class="px-3 py-2">Riceve notifiche sui pazienti</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Profilo</td><td class="px-3 py-2">Dati personali, info terapista</td><td class="px-3 py-2">Dati professionali, specializzazione</td></tr>
                            <tr><td class="px-3 py-2 font-medium">Funzioni esclusive</td><td class="px-3 py-2">Reclami/feedback</td><td class="px-3 py-2">Segna assenze, note, presenze</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. PAZIENTE: HOME -->
        <div class="section-card cursor-pointer" x-ref="pazienteHome" @click="openSection = openSection === 'paziente-home' ? '' : 'paziente-home'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">3. Paziente: Schermata Home</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'paziente-home' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'paziente-home'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">La home mostra i <strong>prossimi 3 appuntamenti</strong> con data/ora, tipo di terapia, nome del terapista e stato. Trascinare verso il basso per aggiornare.</p>

                <!-- Placeholder img-02 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-02.jpg" alt="Home paziente" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-02.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Home paziente con prossimi appuntamenti</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Selezione Paziente (Account Multipli)</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Se l'account gestisce <strong>piu pazienti</strong> (es. genitore con piu figli), compare un <strong>selettore paziente</strong>. Toccare il nome in alto per cambiare paziente. Tutte le schermate si aggiornano automaticamente.</p>

                <!-- Placeholder img-03 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-03.jpg" alt="Selettore paziente" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-03.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Selettore per cambiare paziente</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. PAZIENTE: CALENDARIO -->
        <div class="section-card cursor-pointer" x-ref="pazienteCalendario" @click="openSection = openSection === 'paziente-calendario' ? '' : 'paziente-calendario'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">4. Paziente: Calendario</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'paziente-calendario' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'paziente-calendario'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">La scheda <strong>Calendario</strong> mostra una vista mensile con pallini colorati sui giorni con appuntamenti. Toccare un giorno per vederne il dettaglio.</p>

                <!-- Placeholder img-04 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-04.jpg" alt="Calendario paziente" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-04.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Calendario mensile del paziente</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Annullare un Appuntamento</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Nel calendario, toccare l'appuntamento da annullare</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Toccare <strong>"Annulla Appuntamento"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Inserire la <strong>motivazione</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Confermare</span></div>
                </div>
                <div class="warn-box"><strong>Importante:</strong> L'annullamento e possibile solo con almeno <strong>24 ore di anticipo</strong>. Appuntamenti entro le 24 ore non possono essere annullati dall'app.</div>
            </div>
        </div>

        <!-- 5. PAZIENTE: RICHIESTE -->
        <div class="section-card cursor-pointer" x-ref="pazienteRichieste" @click="openSection = openSection === 'paziente-richieste' ? '' : 'paziente-richieste'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">5. Paziente: Richieste Documenti</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'paziente-richieste' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'paziente-richieste'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">La scheda <strong>Richieste</strong> permette di inviare e monitorare richieste di documenti.</p>

                <!-- Placeholder img-05 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-05.jpg" alt="Lista richieste" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-05.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Lista richieste documenti del paziente</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Inviare una Nuova Richiesta</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Toccare <strong>"Nuova Richiesta"</strong> o l'icona "+"</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Selezionare il <strong>tipo di documento</strong> (Certificato Medico, Relazione Terapeutica, ecc.)</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Aggiungere note facoltative</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Toccare <strong>"Invia Richiesta"</strong></span></div>
                </div>

                <!-- Placeholder img-06 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-06.jpg" alt="Nuova richiesta" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-06.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Form nuova richiesta documento</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Codice Colore degli Stati</h4>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium">Giallo = Inviata</span>
                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 font-medium">Blu = In Lavorazione</span>
                    <span class="px-3 py-1 rounded-full bg-orange-100 text-orange-700 font-medium">Arancione = Stampato</span>
                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-medium">Verde = Consegnato</span>
                </div>

                <div class="info-box">Quando il documento e pronto (stato "Stampato" o "Consegnato"), e possibile scaricarlo direttamente dall'app.</div>
            </div>
        </div>

        <!-- 6. PAZIENTE: NOTIFICHE -->
        <div class="section-card cursor-pointer" x-ref="pazienteNotifiche" @click="openSection = openSection === 'paziente-notifiche' ? '' : 'paziente-notifiche'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">6. Paziente: Notifiche</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'paziente-notifiche' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'paziente-notifiche'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">La scheda <strong>Notifiche</strong> mostra tutti i messaggi ricevuti:</p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                    <li><strong>Cambio stato richieste</strong> documenti</li>
                    <li><strong>Scadenza piano terapeutico</strong> (90, 60, 30, 15 giorni prima)</li>
                    <li><strong>Comunicazioni</strong> dal centro</li>
                    <li><strong>Promemoria</strong> appuntamenti</li>
                </ul>

                <!-- Placeholder img-07 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-07.jpg" alt="Notifiche paziente" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-07.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Schermata notifiche del paziente</span>
                    </div>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400">Le notifiche non lette sono evidenziate. Toccare una notifica per leggerla e segnarla come letta.</p>
            </div>
        </div>

        <!-- 7. PAZIENTE: PROFILO -->
        <div class="section-card cursor-pointer" x-ref="pazienteProfilo" @click="openSection = openSection === 'paziente-profilo' ? '' : 'paziente-profilo'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">7. Paziente: Profilo e Reclami</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'paziente-profilo' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'paziente-profilo'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">La scheda <strong>Profilo</strong> mostra: nome, cognome, email, codice fiscale, telefono, data di nascita e informazioni sul terapista assegnato.</p>

                <!-- Placeholder img-08 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-08.jpg" alt="Profilo paziente" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-08.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Profilo paziente con dati e terapista</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Reclami / Feedback</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Dal profilo, toccare <strong>"Reclami"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Inserire un <strong>titolo</strong> per il reclamo</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Scrivere una <strong>descrizione dettagliata</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Toccare <strong>"Invia"</strong></span></div>
                </div>
            </div>
        </div>

        <!-- 8. TERAPISTA: DASHBOARD -->
        <div class="section-card cursor-pointer" x-ref="terapistaDashboard" @click="openSection = openSection === 'terapista-dashboard' ? '' : 'terapista-dashboard'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">8. Terapista: Dashboard</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'terapista-dashboard' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'terapista-dashboard'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">La home del terapista mostra una dashboard con:</p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                    <li><strong>Numero di pazienti</strong> assegnati</li>
                    <li><strong>Appuntamenti della settimana</strong></li>
                    <li><strong>Ore lavorate questa settimana</strong></li>
                    <li><strong>Appuntamenti di oggi</strong></li>
                </ul>

                <!-- Placeholder img-09 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-09.jpg" alt="Dashboard terapista" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-09.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Dashboard del terapista con statistiche</span>
                    </div>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400">Le sedute sono raggruppate per <strong>setting terapeutico</strong>: Ambulatoriale, Domiciliare, Piccolo Gruppo, Centro Diurno, Semiconvitto.</p>
            </div>
        </div>

        <!-- 9. TERAPISTA: PAZIENTI -->
        <div class="section-card cursor-pointer" x-ref="terapistaPazienti" @click="openSection = openSection === 'terapista-pazienti' ? '' : 'terapista-pazienti'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">9. Terapista: I Miei Pazienti</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'terapista-pazienti' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'terapista-pazienti'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">La scheda <strong>Pazienti</strong> mostra l'elenco di tutti i pazienti assegnati con: nome, data di nascita, ultimo appuntamento, stato e contatti.</p>

                <!-- Placeholder img-10 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-10.jpg" alt="Lista pazienti terapista" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-10.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Lista pazienti del terapista</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 10. TERAPISTA: AGENDA -->
        <div class="section-card cursor-pointer" x-ref="terapistaAgenda" @click="openSection = openSection === 'terapista-agenda' ? '' : 'terapista-agenda'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">10. Terapista: Agenda e Presenze</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'terapista-agenda' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'terapista-agenda'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">L'<strong>Agenda</strong> mostra gli appuntamenti con vista mensile e giornaliera, con funzionalita aggiuntive rispetto al paziente.</p>

                <!-- Placeholder img-11 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-11.jpg" alt="Agenda terapista" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-11.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Agenda giornaliera del terapista</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Segnare un Paziente come Assente</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Toccare l'appuntamento</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Toccare <strong>"Segna Assenza"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Selezionare il tipo: <strong>Giustificata</strong> o <strong>Non Giustificata</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Selezionare la motivazione (non si e presentato, ritardo, non collaborativo, salute, problemi familiari, rifiuto, altro)</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">5</span><span>Confermare</span></div>
                </div>

                <!-- Placeholder img-12 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-12.jpg" alt="Segna assenza" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-12.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Schermata per segnare assenza paziente</span>
                    </div>
                </div>

                <div class="info-box"><strong>Appuntamenti di gruppo:</strong> Se la seduta e di gruppo, il sistema chiede se applicare l'assenza a tutto il gruppo o solo al singolo paziente.</div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Aggiungere Note</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Toccare l'appuntamento, poi <strong>"Aggiungi Nota"</strong>, scrivere la nota e salvare.</p>
            </div>
        </div>

        <!-- 11. TERAPISTA: NOTIFICHE -->
        <div class="section-card cursor-pointer" x-ref="terapistaNotifiche" @click="openSection = openSection === 'terapista-notifiche' ? '' : 'terapista-notifiche'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">11. Terapista: Notifiche e Profilo</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'terapista-notifiche' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'terapista-notifiche'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Il terapista riceve notifiche su aggiornamenti appuntamenti, comunicazioni dal centro e avvisi importanti.</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Il <strong>Profilo</strong> mostra nome, email, specializzazione/i e numero di licenza. Da qui e possibile effettuare il <strong>Logout</strong>.</p>

                <!-- Placeholder img-13 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-13.jpg" alt="Profilo terapista" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-13.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Profilo del terapista con specializzazioni</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 12. NOTIFICHE PUSH -->
        <div class="section-card cursor-pointer" x-ref="notifichePush" @click="openSection = openSection === 'notifiche-push' ? '' : 'notifiche-push'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">12. Notifiche Push</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'notifiche-push' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'notifiche-push'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">L'app invia <strong>notifiche push</strong> sul telefono anche quando l'app e chiusa: notifica sonora e visiva con anteprima del messaggio. Toccandola si apre l'app alla schermata pertinente.</p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                    <li>Promemoria appuntamenti</li>
                    <li>Aggiornamenti stato richieste documenti</li>
                    <li>Scadenza piano terapeutico</li>
                    <li>Comunicazioni urgenti dal centro</li>
                </ul>
                <div class="warn-box">Per ricevere le notifiche push, assicurarsi che le notifiche siano <strong>abilitate</strong> nelle impostazioni del telefono per l'app TherapyCRM.</div>
            </div>
        </div>

        <!-- 13. NOTIFICHE BLOCCANTI -->
        <div class="section-card cursor-pointer" x-ref="bloccanti" @click="openSection = openSection === 'bloccanti' ? '' : 'bloccanti'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">13. Notifiche Bloccanti</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'bloccanti' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'bloccanti'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Alcune notifiche sono <strong>bloccanti</strong>: impediscono l'utilizzo dell'app finche non vengono lette e confermate.</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Come funzionano</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>All'apertura dell'app appare una <strong>schermata modale</strong> a tutto schermo</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>La notifica mostra un messaggio importante che richiede attenzione</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>L'utente <strong>deve leggere</strong> il contenuto</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Toccare <strong>"Ho letto e confermo"</strong> per chiudere</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">5</span><span>L'app torna utilizzabile normalmente</span></div>
                </div>

                <!-- Placeholder img-14 -->
                <div class="my-4 mobile-placeholder">
                    <img src="<?= $imgBase ?>/img-14.jpg" alt="Notifica bloccante" class="manual-img-mobile"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="font-medium">img/manuale/app/img-14.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Esempio di notifica bloccante a tutto schermo</span>
                    </div>
                </div>

                <div class="warn-box">Il sistema controlla automaticamente ogni 30 secondi se ci sono nuove notifiche bloccanti. Non e possibile "saltare" queste notifiche.</div>
            </div>
        </div>

        <!-- 14. SICUREZZA -->
        <div class="section-card cursor-pointer" x-ref="sicurezza" @click="openSection = openSection === 'sicurezza' ? '' : 'sicurezza'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">14. Sicurezza dell'App</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'sicurezza' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'sicurezza'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">L'app include protezioni di sicurezza per i dati sensibili:</p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2 list-disc list-inside">
                    <li><strong>Protezione screenshot</strong> - Non e possibile fare screenshot o registrare lo schermo (per proteggere i dati dei pazienti)</li>
                    <li><strong>Sessione sicura</strong> - Il token di accesso scade automaticamente dopo un periodo di inattivita</li>
                    <li><strong>Logout automatico</strong> - In caso di problemi di autenticazione, l'app effettua il logout automatico</li>
                </ul>
            </div>
        </div>

        <!-- FAQ -->
        <div class="section-card cursor-pointer" x-ref="faq" @click="openSection = openSection === 'faq' ? '' : 'faq'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Domande Frequenti (FAQ)</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'faq' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'faq'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: L'app non mi permette di fare screenshot. E un bug?</p>
                        <p>No, e una funzionalita di sicurezza per proteggere i dati sensibili dei pazienti.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Non ricevo le notifiche push. Cosa faccio?</p>
                        <p>Verificare che le notifiche siano abilitate nelle impostazioni del telefono per l'app TherapyCRM e che la connessione internet sia attiva.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Come annullo un appuntamento?</p>
                        <p>Dal Calendario, toccare l'appuntamento e selezionare "Annulla". E possibile solo con almeno 24 ore di anticipo.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Gestisco piu figli. Come passo da uno all'altro?</p>
                        <p>Toccare il selettore paziente (nome in alto) per scegliere il familiare di cui visualizzare le informazioni.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Il terapista puo vedere le mie richieste documenti?</p>
                        <p>No, i terapisti non hanno accesso alla sezione richieste documenti.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Come terapista, posso modificare gli appuntamenti?</p>
                        <p>Il terapista puo segnare le assenze e aggiungere note, ma non puo creare, modificare o cancellare appuntamenti. La gestione completa avviene dal gestionale web.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Cosa sono le notifiche bloccanti?</p>
                        <p>Comunicazioni importanti che richiedono la lettura e la conferma obbligatoria prima di poter continuare a usare l'app.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Posso scaricare i documenti dall'app?</p>
                        <p>Si, quando lo stato della richiesta passa a "Stampato" o "Consegnato", il documento diventa scaricabile.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-400 dark:text-gray-500 mt-8 mb-4">
            <p>Manuale dell'App Mobile - Centro Medico San Luca - v1.0</p>
        </div>

    </div>
</div>
