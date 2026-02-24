<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'Manuale - Il Gestionale';
$this->params['breadcrumbs'][] = ['label' => 'Manuale d\'Uso', 'url' => ['/site/manuale']];
$this->params['breadcrumbs'][] = 'Il Gestionale';

$imgBase = Yii::getAlias('@web') . '/img/manuale/gestionale';
?>

<style>
    .manual-img-placeholder {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        min-height: 180px; border: 2px dashed #cbd5e1; border-radius: 12px;
        background: #f8fafc; color: #94a3b8; font-size: 0.85rem; padding: 1.5rem; text-align: center;
    }
    .dark .manual-img-placeholder { background: rgba(255,255,255,0.03); border-color: #334155; color: #64748b; }
    .manual-img { border-radius: 12px; border: 1px solid #e2e8f0; max-width: 100%; height: auto; }
    .dark .manual-img { border-color: #334155; }
    .toc-link { display: block; padding: 4px 0; font-size: 0.8rem; color: #64748b; transition: color 0.15s; text-decoration: none; }
    .toc-link:hover { color: #3b82f6; }
    .dark .toc-link { color: #94a3b8; }
    .dark .toc-link:hover { color: #60a5fa; }
    .section-card { border-radius: 12px; border: 1px solid #e5e7eb; background: white; padding: 1.5rem; margin-bottom: 1rem; }
    .dark .section-card { background: rgba(255,255,255,0.03); border-color: #1f2937; }
    .step-num { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: #3b82f6; color: white; font-size: 0.75rem; font-weight: 600; flex-shrink: 0; }
    .info-box { border-left: 4px solid #3b82f6; background: #eff6ff; padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; margin: 0.75rem 0; font-size: 0.85rem; }
    .dark .info-box { background: rgba(59,130,246,0.1); }
    .warn-box { border-left: 4px solid #f59e0b; background: #fffbeb; padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; margin: 0.75rem 0; font-size: 0.85rem; }
    .dark .warn-box { background: rgba(245,158,11,0.1); }
</style>

<div class="flex gap-6 p-4 md:p-6" x-data="{ openSection: 'accesso' }">

    <!-- TOC Sidebar -->
    <aside class="hidden xl:block w-56 flex-shrink-0">
        <div class="sticky top-24">
            <a href="<?= Url::to(['/site/manuale']) ?>" class="flex items-center gap-1.5 text-xs text-blue-500 hover:text-blue-600 mb-4">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Torna ai manuali
            </a>
            <h4 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Indice</h4>
            <nav class="space-y-0.5 max-h-[calc(100vh-10rem)] overflow-y-auto pr-2">
                <a class="toc-link" @click="openSection='accesso'; $nextTick(() => $refs.accesso.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='accesso' && 'text-blue-500 font-medium'">1. Accesso al Sistema</a>
                <a class="toc-link" @click="openSection='dashboard'; $nextTick(() => $refs.dashboard.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='dashboard' && 'text-blue-500 font-medium'">2. La Dashboard</a>
                <a class="toc-link" @click="openSection='pazienti'; $nextTick(() => $refs.pazienti.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='pazienti' && 'text-blue-500 font-medium'">3. Gestione Pazienti</a>
                <a class="toc-link" @click="openSection='terapisti'; $nextTick(() => $refs.terapisti.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='terapisti' && 'text-blue-500 font-medium'">4. Gestione Terapisti</a>
                <a class="toc-link" @click="openSection='piani'; $nextTick(() => $refs.piani.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='piani' && 'text-blue-500 font-medium'">5. Piani Terapeutici</a>
                <a class="toc-link" @click="openSection='calendario'; $nextTick(() => $refs.calendario.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='calendario' && 'text-blue-500 font-medium'">6. Calendario</a>
                <a class="toc-link" @click="openSection='assenze'; $nextTick(() => $refs.assenze.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='assenze' && 'text-blue-500 font-medium'">7. Gestione Assenze</a>
                <a class="toc-link" @click="openSection='documenti'; $nextTick(() => $refs.documenti.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='documenti' && 'text-blue-500 font-medium'">8. Richieste Documenti</a>
                <a class="toc-link" @click="openSection='notifiche'; $nextTick(() => $refs.notifiche.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='notifiche' && 'text-blue-500 font-medium'">9. Notifiche</a>
                <a class="toc-link" @click="openSection='statistiche'; $nextTick(() => $refs.statistiche.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='statistiche' && 'text-blue-500 font-medium'">10. Statistiche</a>
                <a class="toc-link" @click="openSection='gruppi'; $nextTick(() => $refs.gruppi.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='gruppi' && 'text-blue-500 font-medium'">11. Gruppi Coordinatori</a>
                <a class="toc-link" @click="openSection='utenti'; $nextTick(() => $refs.utenti.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='utenti' && 'text-blue-500 font-medium'">12. Gestione Utenti</a>
                <a class="toc-link" @click="openSection='ricerca'; $nextTick(() => $refs.ricerca.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='ricerca' && 'text-blue-500 font-medium'">13. Ricerca Globale</a>
                <a class="toc-link" @click="openSection='automatismi'; $nextTick(() => $refs.automatismi.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='automatismi' && 'text-blue-500 font-medium'">14. Automatismi</a>
                <a class="toc-link" @click="openSection='ruoli'; $nextTick(() => $refs.ruoli.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='ruoli' && 'text-blue-500 font-medium'">App. A - Ruoli e Permessi</a>
                <a class="toc-link" @click="openSection='stati'; $nextTick(() => $refs.stati.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='stati' && 'text-blue-500 font-medium'">App. B - Stati</a>
                <a class="toc-link" @click="openSection='faq'; $nextTick(() => $refs.faq.scrollIntoView({behavior:'smooth', block:'start'}))" :class="openSection==='faq' && 'text-blue-500 font-medium'">App. C - FAQ</a>
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 max-w-4xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">Manuale del Gestionale</h2>
                    <p class="text-xs text-gray-400">Applicazione Web - Centro Medico San Luca</p>
                </div>
            </div>
        </div>

        <!-- 1. ACCESSO AL SISTEMA -->
        <div x-ref="accesso" class="section-card cursor-pointer" @click="openSection = openSection === 'accesso' ? '' : 'accesso'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">1. Accesso al Sistema</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'accesso' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'accesso'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">1.1 Login</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Per accedere al gestionale, aprire il browser e navigare all'indirizzo del sistema. Si presenta la schermata di login.</p>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Inserire il proprio <strong>indirizzo email</strong> nel campo "Email"</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Inserire la <strong>password</strong> nel campo "Password"</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>(Facoltativo) Spuntare "Ricordami" per restare collegati</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Cliccare su <strong>"Accedi"</strong></span></div>
                </div>

                <!-- Placeholder img-01 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-01.jpg" alt="Schermata di login" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-01.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Schermata di login del gestionale</span>
                    </div>
                </div>

                <div class="info-box">Al primo accesso potrebbe essere richiesto di cambiare la password. Seguire le istruzioni a schermo per impostarne una nuova.</div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-6">1.2 Password Dimenticata</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Se non si ricorda la password:</p>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Nella schermata di login, cliccare su <strong>"Hai dimenticato la password?"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Inserire l'indirizzo email associato al proprio account</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Cliccare su <strong>"Invia"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Controllare la propria casella email (anche la cartella spam)</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">5</span><span>Cliccare sul link ricevuto via email e impostare la nuova password</span></div>
                </div>
                <div class="warn-box">Il link di reset scade dopo 24 ore. Se scaduto, ripetere la procedura.</div>

                <!-- Placeholder img-02 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-02.jpg" alt="Form recupero password" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-02.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Form di recupero password</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-6">1.3 Cambio Password</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Cliccare sul proprio <strong>nome utente</strong> in alto a destra</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Selezionare <strong>"Cambia Password"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Inserire la password attuale, la nuova password e confermarla</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Cliccare <strong>"Salva"</strong></span></div>
                </div>
            </div>
        </div>

        <!-- 2. LA DASHBOARD -->
        <div x-ref="dashboard" class="section-card cursor-pointer" @click="openSection = openSection === 'dashboard' ? '' : 'dashboard'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">2. La Dashboard</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'dashboard' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'dashboard'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Dopo il login, si viene portati alla <strong>Dashboard</strong> (pagina principale) che mostra un riepilogo dell'attivita del centro.</p>

                <!-- Placeholder img-03 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-03.jpg" alt="Dashboard principale" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-03.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Dashboard principale del gestionale</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Cosa mostra la Dashboard</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                    <li><strong>Pazienti totali</strong> - Numero di pazienti attivi nel sistema</li>
                    <li><strong>Nuovi pazienti del mese</strong> - Pazienti registrati nel mese corrente</li>
                    <li><strong>Terapisti attivi</strong> - Numero di terapisti operativi</li>
                    <li><strong>Piani terapeutici attivi</strong> - Piani attualmente in corso</li>
                    <li><strong>Piani in scadenza</strong> - Piani che scadranno a breve</li>
                    <li><strong>Ore settimanali di trattamento</strong> - Ore di terapia erogate nella settimana</li>
                    <li><strong>Grafici</strong> - Crescita pazienti, top trattamenti, appuntamenti settimanali, richieste documenti, assenze</li>
                </ul>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Navigazione</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                    <li><strong>Barra laterale (menu a sinistra)</strong> - Contiene tutte le sezioni del gestionale. Le voci visibili dipendono dal proprio ruolo.</li>
                    <li><strong>Icona campanella</strong> (in alto a destra) - Notifiche, il numero indica quelle non lette.</li>
                    <li><strong>Nome utente</strong> (in alto a destra) - Menu personale (profilo, cambio password, logout).</li>
                    <li><strong>Breadcrumb</strong> (sotto la barra) - Percorso della pagina corrente.</li>
                </ul>

                <!-- Placeholder img-04 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-04.jpg" alt="Menu laterale e navigazione" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-04.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Menu laterale e barra di navigazione</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. GESTIONE PAZIENTI -->
        <div x-ref="pazienti" class="section-card cursor-pointer" @click="openSection = openSection === 'pazienti' ? '' : 'pazienti'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">3. Gestione Pazienti</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'pazienti' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'pazienti'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">3.1 Elenco Pazienti</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Menu:</strong> Pazienti</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">La pagina mostra la lista di tutti i pazienti registrati. E possibile cercare per nome, cognome, codice fiscale o email; filtrare per distretto e stato; ordinare cliccando sulle intestazioni delle colonne.</p>

                <!-- Placeholder img-05 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-05.jpg" alt="Elenco pazienti" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-05.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Lista pazienti con filtri e ricerca</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">3.2 Creare un Nuovo Paziente</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Dalla lista pazienti, cliccare <strong>"Nuovo Paziente"</strong> (o icona "+")</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Compilare i dati: <strong>Nome e Cognome</strong> (obbligatori), Data di nascita, Luogo di nascita, Genere, Codice Fiscale (generabile automaticamente), Email, Telefono, Indirizzo, Distretto, Foto (facoltativa)</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Cliccare <strong>"Salva"</strong></span></div>
                </div>

                <!-- Placeholder img-06 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-06.jpg" alt="Form creazione paziente" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-06.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Form di creazione nuovo paziente</span>
                    </div>
                </div>

                <div class="info-box">Email, telefono e indirizzo vengono criptati automaticamente nel database per proteggere i dati sensibili.</div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">3.3 Scheda Paziente</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Cliccando su un paziente si accede alla sua scheda completa che mostra: dati anagrafici, piani terapeutici associati, appuntamenti programmati e passati, storico trattamenti e documenti richiesti.</p>

                <!-- Placeholder img-07 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-07.jpg" alt="Scheda paziente" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-07.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Scheda dettaglio paziente</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">3.4 Account Familiari (Gestione Multi-Paziente)</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Un account familiare permette a un genitore o tutore di gestire piu pazienti (es. una madre con due figli in terapia).</p>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Dalla scheda del paziente, accedere alla sezione <strong>"Account"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Cliccare <strong>"Collega Paziente"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Cercare il paziente, selezionare il tipo di relazione e la potesta genitoriale</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Confermare il collegamento</span></div>
                </div>
                <div class="info-box">L'account familiare e quello usato per accedere all'app mobile. Un unico accesso permette di vedere tutti i familiari collegati.</div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">3.5 Credenziali per l'App Mobile</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Alla creazione di un paziente, il sistema genera le credenziali per l'app mobile. Possono essere inviate via email, scaricate come PDF o rigenerate in caso di smarrimento.</p>

                <!-- Placeholder img-08 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-08.jpg" alt="Credenziali paziente" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-08.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Sezione credenziali e account del paziente</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. GESTIONE TERAPISTI -->
        <div x-ref="terapisti" class="section-card cursor-pointer" @click="openSection = openSection === 'terapisti' ? '' : 'terapisti'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">4. Gestione Terapisti</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'terapisti' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'terapisti'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">4.1 Elenco Terapisti</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Menu:</strong> Terapisti. Mostra tutti i terapisti con nome, specializzazione, stato e distretto. Filtrabili per specializzazione, distretto e disponibilita.</p>

                <!-- Placeholder img-09 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-09.jpg" alt="Elenco terapisti" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-09.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Lista terapisti con filtri</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">4.2 Creare un Nuovo Terapista</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Cliccare <strong>"Nuovo Terapista"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Compilare: Email (per il login), Nome e Cognome, Telefono, <strong>Specializzazione/i</strong> (Logopedia, Psicomotricita, Educazione Speciale, Terapia Occupazionale, ecc.), Distretto</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Cliccare <strong>"Salva"</strong> - Il sistema crea l'account e genera la password automaticamente</span></div>
                </div>

                <!-- Placeholder img-10 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-10.jpg" alt="Form creazione terapista" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-10.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Form creazione nuovo terapista</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">4.3 Gestione Credenziali</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Dalla scheda del terapista: <strong>Reset Password</strong> (genera una nuova password temporanea), <strong>Scarica Credenziali PDF</strong> (documento con email e password), <strong>Invio credenziali via email</strong>.</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">4.4 Attivare/Disattivare un Terapista</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Dalla scheda del terapista, cliccare "Modifica" e cambiare lo stato. Un terapista disattivato non puo accedere ma i dati storici vengono mantenuti.</p>
            </div>
        </div>

        <!-- 5. PIANI TERAPEUTICI -->
        <div x-ref="piani" class="section-card cursor-pointer" @click="openSection = openSection === 'piani' ? '' : 'piani'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">5. Piani Terapeutici</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'piani' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'piani'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">5.1 Cosa e un Piano Terapeutico</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Il piano terapeutico definisce il percorso di cura di un paziente: il <strong>regime</strong> di terapia, le <strong>terapie</strong> assegnate (es. 2 sedute di logopedia + 1 di psicomotricita a settimana), il <strong>periodo</strong> di validita e i <strong>terapisti</strong> assegnati.</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">5.2 Elenco Piani</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Menu:</strong> Piani Terapeutici. Mostra paziente, regime, date, stato (attivo/scaduto/bozza) e giorni rimanenti. Filtrabili per paziente, regime, distretto, stato.</p>

                <!-- Placeholder img-11 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-11.jpg" alt="Elenco piani terapeutici" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-11.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Lista piani terapeutici</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">5.3 Creare un Piano Terapeutico</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Cliccare <strong>"Nuovo Piano"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span><strong>Selezionare il paziente</strong> dall'elenco</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span><strong>Scegliere il regime</strong> (tipo/frequenza di terapia)</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span><strong>Impostare le date</strong> di inizio e fine</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">5</span><span><strong>Aggiungere le terapie:</strong> per ognuna specificare tipo di trattamento, frequenza settimanale, durata seduta, terapista assegnato e tipo di ciclo</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">6</span><span>Cliccare <strong>"Salva"</strong></span></div>
                </div>

                <!-- Placeholder img-12 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-12.jpg" alt="Form creazione piano terapeutico" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-12.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Form di creazione piano terapeutico</span>
                    </div>
                </div>

                <div class="info-box"><strong>Cosa succede dopo il salvataggio:</strong> Il sistema genera automaticamente gli appuntamenti ricorrenti, che compaiono nel calendario. Vengono pianificate le notifiche automatiche di scadenza (a 90, 60, 30 e 15 giorni dalla fine).</div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">5.4 Setting e Regimi</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">I <strong>regimi</strong> definiscono configurazioni predefinite. I <strong>setting terapeutici</strong> disponibili sono: Ambulatoriale, Domiciliare, Piccolo Gruppo, Centro Diurno, Semiconvitto.</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">5.5 Scadenza e Rinnovo</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Quando un piano si avvicina alla scadenza, il sistema invia notifiche automatiche alla famiglia a 90, 60, 30 e 15 giorni. Alla scadenza il piano viene marcato come "Scaduto". Per rinnovare, creare un nuovo piano.</p>
            </div>
        </div>

        <!-- 6. CALENDARIO -->
        <div x-ref="calendario" class="section-card cursor-pointer" @click="openSection = openSection === 'calendario' ? '' : 'calendario'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">6. Calendario e Appuntamenti</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'calendario' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'calendario'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Menu:</strong> Calendario. Mostra tutti gli appuntamenti con viste mensile, settimanale e giornaliera. Ogni tipo di trattamento ha un colore diverso.</p>

                <!-- Placeholder img-13 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-13.jpg" alt="Vista calendario" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-13.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Vista calendario con appuntamenti</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Filtri del Calendario</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Filtrabile per: <strong>Terapista</strong>, <strong>Paziente</strong>, <strong>Tipo di trattamento</strong>.</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Dettaglio Appuntamento</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Cliccando su un appuntamento si vedono: data/ora, durata, paziente, terapista, tipo di trattamento, stato, note, categoria (regolare/recupero/avanzamento/extra/compensazione).</p>

                <!-- Placeholder img-14 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-14.jpg" alt="Dettaglio appuntamento" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-14.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Modale dettaglio appuntamento</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Stati degli Appuntamenti</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-800/50">
                            <tr><th class="px-3 py-2">Stato</th><th class="px-3 py-2">Colore</th><th class="px-3 py-2">Significato</th></tr>
                        </thead>
                        <tbody class="text-gray-600 dark:text-gray-400">
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Programmato</td><td class="px-3 py-2"><span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span> Blu</td><td class="px-3 py-2">Confermato, non ancora avvenuto</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Completato</td><td class="px-3 py-2"><span class="inline-block w-3 h-3 rounded-full bg-green-500"></span> Verde</td><td class="px-3 py-2">Seduta effettuata</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Assenza Giustificata</td><td class="px-3 py-2"><span class="inline-block w-3 h-3 rounded-full bg-yellow-500"></span> Giallo</td><td class="px-3 py-2">Paziente assente con motivazione</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Assenza Non Giustificata</td><td class="px-3 py-2"><span class="inline-block w-3 h-3 rounded-full bg-red-500"></span> Rosso</td><td class="px-3 py-2">Assenza senza preavviso</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Cancellato</td><td class="px-3 py-2"><span class="inline-block w-3 h-3 rounded-full bg-gray-400"></span> Grigio</td><td class="px-3 py-2">Annullato prima dell'esecuzione</td></tr>
                            <tr><td class="px-3 py-2 font-medium">Terapista Assente</td><td class="px-3 py-2"><span class="inline-block w-3 h-3 rounded-full bg-orange-500"></span> Arancione</td><td class="px-3 py-2">Terapista non disponibile</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="info-box">Gli appuntamenti passati ancora "Programmato" vengono automaticamente marcati come "Completato" dal sistema (vedi sezione Automatismi).</div>
            </div>
        </div>

        <!-- 7. GESTIONE ASSENZE -->
        <div x-ref="assenze" class="section-card cursor-pointer" @click="openSection = openSection === 'assenze' ? '' : 'assenze'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">7. Gestione Assenze</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'assenze' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'assenze'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Menu:</strong> Assenze. Gestisce le assenze dei terapisti (ferie, malattia, permessi).</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Registrare un'Assenza</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Cliccare <strong>"Nuova Assenza"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span><strong>Selezionare il terapista</strong> e il <strong>periodo</strong> (data inizio e fine)</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Scegliere il <strong>tipo</strong>: Ferie, Malattia, Permesso o Altro</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">4</span><span>Il sistema chiede se <strong>aggiornare gli appuntamenti</strong> del periodo come "Terapista Assente"</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">5</span><span>Cliccare <strong>"Salva"</strong></span></div>
                </div>

                <!-- Placeholder img-15 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-15.jpg" alt="Form registrazione assenza" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-15.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Registrazione assenza terapista</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Assenze dei Pazienti</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Le assenze dei pazienti si registrano dal <strong>Calendario</strong>: aprire l'appuntamento, cambiare stato in "Assenza Giustificata" o "Non Giustificata", inserire la motivazione e salvare. Oppure tramite l'app del terapista.</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Sostituzioni</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Quando un terapista e assente, e possibile assegnare un <strong>sostituto</strong> dalla scheda dell'assenza. Gli appuntamenti possono essere riassegnati e il sostituto li vedra nel proprio calendario.</p>
            </div>
        </div>

        <!-- 8. RICHIESTE DOCUMENTI -->
        <div x-ref="documenti" class="section-card cursor-pointer" @click="openSection = openSection === 'documenti' ? '' : 'documenti'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">8. Richieste Documenti</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'documenti' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'documenti'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Menu:</strong> Richieste Documenti. I pazienti/familiari richiedono documenti dall'app; gli operatori gestiscono il flusso dal gestionale.</p>

                <!-- Placeholder img-16 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-16.jpg" alt="Elenco richieste documenti" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-16.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Lista richieste documenti</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Flusso di Lavorazione</h4>
                <div class="flex items-center gap-2 text-sm flex-wrap my-3">
                    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium text-xs">INVIATA</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 font-medium text-xs">IN LAVORAZIONE</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="px-3 py-1 rounded-full bg-orange-100 text-orange-700 font-medium text-xs">STAMPATO</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-medium text-xs">CONSEGNATO</span>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Come Amministratore:</strong> Aprire la richiesta, verificare i dettagli, aggiornare lo stato da "Inviata" a "In Lavorazione", poi a "Stampato".</p>
                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Come Manager:</strong> Confermare la consegna aggiornando lo stato a "Consegnato".</p>
                <div class="info-box">Il paziente riceve una notifica nell'app ad ogni cambio di stato della sua richiesta.</div>
            </div>
        </div>

        <!-- 9. NOTIFICHE -->
        <div x-ref="notifiche" class="section-card cursor-pointer" @click="openSection = openSection === 'notifiche' ? '' : 'notifiche'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">9. Notifiche e Comunicazioni</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'notifiche' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'notifiche'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">L'<strong>icona campanella</strong> in alto a destra mostra le notifiche. Il numero rosso indica le non lette. Cliccando si apre l'elenco; per vedere tutte, cliccare "Vedi tutte".</p>

                <!-- Placeholder img-17 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-17.jpg" alt="Centro notifiche" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-17.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Pannello notifiche</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Tipi di Notifiche</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                    <li><strong>Comunicazione Interna</strong> - Messaggi dal sistema o da altri operatori</li>
                    <li><strong>Scadenza Piano Terapeutico</strong> - Avvisi per piani in scadenza</li>
                    <li><strong>Stato Richiesta Documento</strong> - Aggiornamenti sulle richieste</li>
                    <li><strong>Promemoria Appuntamento</strong> - Promemoria per appuntamenti imminenti</li>
                    <li><strong>Informativa</strong> - Comunicazioni generali</li>
                </ul>
            </div>
        </div>

        <!-- 10. STATISTICHE -->
        <div x-ref="statistiche" class="section-card cursor-pointer" @click="openSection = openSection === 'statistiche' ? '' : 'statistiche'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">10. Statistiche e Report</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'statistiche' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'statistiche'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Menu:</strong> Statistiche. Offre panoramiche e analisi dettagliate su diverse aree.</p>

                <!-- Placeholder img-18 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-18.jpg" alt="Dashboard statistiche" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-18.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Pagina principale statistiche</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Sezioni Disponibili</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2 list-disc list-inside">
                    <li><strong>Dashboard Statistiche</strong> - Metriche chiave e grafici: pazienti, terapisti, piani, ore settimanali, crescita, top trattamenti</li>
                    <li><strong>Statistiche Assenze</strong> - Tasso assenza mensile, assenze per motivazione/terapista/trattamento, andamento nel tempo</li>
                    <li><strong>Statistiche Pazienti</strong> - Distribuzione demografica, pazienti per distretto, andamento iscrizioni</li>
                    <li><strong>Statistiche Trattamenti</strong> - Trattamenti piu richiesti, combinazioni, ore per tipo, utilizzo terapisti</li>
                    <li><strong>Statistiche Piani</strong> - Piani per stato/regime, durata, tasso scadenza, piani in scadenza prossimi 30 giorni</li>
                </ul>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Esportazione</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Da qualsiasi pagina di statistiche: applicare i filtri, cliccare <strong>"Esporta in Excel"</strong> per scaricare un file .xlsx con i dati filtrati.</p>

                <!-- Placeholder img-19 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-19.jpg" alt="Statistiche assenze" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-19.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Pagina statistiche assenze con grafici</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 11. GRUPPI COORDINATORI -->
        <div x-ref="gruppi" class="section-card cursor-pointer" @click="openSection = openSection === 'gruppi' ? '' : 'gruppi'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">11. Gruppi Coordinatori</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'gruppi' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'gruppi'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">I gruppi coordinatori organizzano i terapisti sotto la supervisione di un coordinatore. Ogni gruppo ha un coordinatore responsabile, uno o piu terapisti e un distretto.</p>
                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Creare un Gruppo (Solo Amministratori/Manager)</h4>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Menu: Gruppi Coordinatori > <strong>"Nuovo Gruppo"</strong></span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>Inserire nome, selezionare coordinatore, aggiungere terapisti e distretto</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Cliccare <strong>"Salva"</strong></span></div>
                </div>

                <!-- Placeholder img-20 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-20.jpg" alt="Gruppi coordinatori" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-20.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Gestione gruppi coordinatori</span>
                    </div>
                </div>

                <div class="info-box">Un terapista puo appartenere a piu gruppi contemporaneamente.</div>
            </div>
        </div>

        <!-- 12. GESTIONE UTENTI -->
        <div x-ref="utenti" class="section-card cursor-pointer" @click="openSection = openSection === 'utenti' ? '' : 'utenti'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">12. Gestione Utenti</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'utenti' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'utenti'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400"><strong>Menu:</strong> Gestione Utenti. Permette di creare e gestire gli account del sistema per ruolo.</p>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-2 list-disc list-inside">
                    <li><strong>Amministratori</strong> - Accesso completo. Compilare username, email, password, dati personali, distretto.</li>
                    <li><strong>Manager</strong> - Gestiscono le operazioni quotidiane. Permessi simili ma non possono gestire altri manager o amministratori.</li>
                    <li><strong>Coordinatori</strong> - Supervisionano gruppi di terapisti. Associazione ai gruppi coordinatori.</li>
                </ul>

                <!-- Placeholder img-21 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-21.jpg" alt="Gestione utenti" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-21.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Pagina gestione utenti</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 13. RICERCA GLOBALE -->
        <div x-ref="ricerca" class="section-card cursor-pointer" @click="openSection = openSection === 'ricerca' ? '' : 'ricerca'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">13. Ricerca Globale</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'ricerca' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'ricerca'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">La <strong>barra di ricerca</strong> nella parte superiore permette di cercare rapidamente terapisti (nome, cognome, specializzazione), pazienti (nome, cognome, codice fiscale) e account pazienti (email, nome familiare).</p>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-start gap-2"><span class="step-num">1</span><span>Digitare almeno <strong>2 caratteri</strong> nella barra di ricerca</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">2</span><span>I risultati appaiono in tempo reale raggruppati per categoria</span></div>
                    <div class="flex items-start gap-2"><span class="step-num">3</span><span>Cliccare sul risultato per aprire la scheda</span></div>
                </div>

                <!-- Placeholder img-22 -->
                <div class="my-4">
                    <img src="<?= $imgBase ?>/img-22.jpg" alt="Ricerca globale" class="manual-img"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="manual-img-placeholder" style="display:none;">
                        <svg class="w-8 h-8 mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="font-medium">img/manuale/gestionale/img-22.jpg</span>
                        <span class="text-xs mt-1 opacity-60">Ricerca globale con risultati raggruppati</span>
                    </div>
                </div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Generazione Codice Fiscale</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Il sistema include un generatore automatico: inserire i dati anagrafici e cliccare "Genera Codice Fiscale". Il sistema calcola automaticamente il codice.</p>
            </div>
        </div>

        <!-- 14. AUTOMATISMI -->
        <div x-ref="automatismi" class="section-card cursor-pointer" @click="openSection = openSection === 'automatismi' ? '' : 'automatismi'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">14. Automatismi del Sistema</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'automatismi' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'automatismi'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Il sistema esegue automaticamente diverse operazioni a intervalli regolari, senza necessita di intervento manuale.</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Completamento Automatico Appuntamenti</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Eseguito periodicamente durante la giornata. Cerca gli appuntamenti "Programmato" la cui data/ora + durata sono passate e li marca come <strong>"Completato"</strong>.</p>
                <div class="info-box"><strong>Esempio:</strong> Un appuntamento delle 10:00 di 45 minuti resta "Programmato" fino alle 10:45. Dopo quell'ora il sistema lo segna come "Completato".</div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Notifiche Scadenza Piano Terapeutico</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Ogni giorno alle 2:00 di notte. Controlla i piani attivi e invia notifiche ai familiari con potesta genitoriale alle seguenti soglie:</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-800/50">
                            <tr><th class="px-3 py-2">Giorni alla scadenza</th><th class="px-3 py-2">Azione</th></tr>
                        </thead>
                        <tbody class="text-gray-600 dark:text-gray-400">
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">90 giorni</td><td class="px-3 py-2">Prima notifica alla famiglia</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">60 giorni</td><td class="px-3 py-2">Seconda notifica</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">30 giorni</td><td class="px-3 py-2">Terza notifica</td></tr>
                            <tr><td class="px-3 py-2 font-medium">15 giorni</td><td class="px-3 py-2">Ultima notifica urgente</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="info-box">Ogni notifica viene inviata una sola volta per soglia (nessun duplicato). Le notifiche arrivano sia nell'app che come push.</div>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Aggiornamento Stato Piani</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Ogni giorno a mezzanotte. I piani con data fine passata vengono marcati come <strong>"Scaduto"</strong>.</p>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Pulizia Comunicazioni</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">Periodica. Rimuove le comunicazioni piu vecchie di 90 giorni per mantenere pulito il database.</p>
            </div>
        </div>

        <!-- APPENDICE A - RUOLI E PERMESSI -->
        <div x-ref="ruoli" class="section-card cursor-pointer" @click="openSection = openSection === 'ruoli' ? '' : 'ruoli'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Appendice A - Ruoli e Permessi</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'ruoli' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'ruoli'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-800/50">
                            <tr><th class="px-2 py-2">Funzionalita</th><th class="px-2 py-2 text-center">Admin</th><th class="px-2 py-2 text-center">Manager</th><th class="px-2 py-2 text-center">Coordinatore</th><th class="px-2 py-2 text-center">Terapista</th></tr>
                        </thead>
                        <tbody class="text-gray-600 dark:text-gray-400">
                            <tr class="border-b dark:border-gray-700"><td class="px-2 py-1.5">Dashboard completa</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-yellow-500">Limitata</td><td class="px-2 py-1.5 text-center text-yellow-500">Limitata</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-2 py-1.5">Gestire Admin/Manager</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-2 py-1.5">Gestire Coordinatori/Terapisti</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-2 py-1.5">Creare/Modificare Pazienti</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-2 py-1.5">Creare/Modificare Piani</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-2 py-1.5">Calendario</td><td class="px-2 py-1.5 text-center">Tutto</td><td class="px-2 py-1.5 text-center">Tutto</td><td class="px-2 py-1.5 text-center">Proprio gruppo</td><td class="px-2 py-1.5 text-center">Proprio</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-2 py-1.5">Richieste Documenti</td><td class="px-2 py-1.5 text-center">Fino a Stampato</td><td class="px-2 py-1.5 text-center">Consegnato</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-2 py-1.5">Statistiche</td><td class="px-2 py-1.5 text-center text-green-500">Tutte</td><td class="px-2 py-1.5 text-center text-green-500">Tutte</td><td class="px-2 py-1.5 text-center text-yellow-500">Limitate</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td></tr>
                            <tr><td class="px-2 py-1.5">Esportare Dati</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-green-500">&#10003;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td><td class="px-2 py-1.5 text-center text-red-500">&#10007;</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- APPENDICE B - STATI -->
        <div x-ref="stati" class="section-card cursor-pointer" @click="openSection = openSection === 'stati' ? '' : 'stati'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Appendice B - Tabella Stati</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'stati' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'stati'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">Categorie Appuntamento</h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                    <li><strong>Regolare</strong> - Standard previsto dal piano</li>
                    <li><strong>Recupero</strong> - Per un appuntamento saltato</li>
                    <li><strong>Avanzamento</strong> - Anticipato rispetto alla programmazione</li>
                    <li><strong>Extra</strong> - Aggiuntivo non previsto dal piano</li>
                    <li><strong>Compensazione</strong> - Seduta di compensazione</li>
                </ul>

                <h4 class="font-semibold text-gray-700 dark:text-gray-300 text-sm mt-4">Stati Richieste Documenti</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-800/50">
                            <tr><th class="px-3 py-2">Stato</th><th class="px-3 py-2">Colore</th><th class="px-3 py-2">Significato</th></tr>
                        </thead>
                        <tbody class="text-gray-600 dark:text-gray-400">
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Inviata</td><td class="px-3 py-2"><span class="inline-block w-3 h-3 rounded-full bg-yellow-500"></span> Giallo</td><td class="px-3 py-2">Appena inviata dal paziente</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">In Lavorazione</td><td class="px-3 py-2"><span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span> Blu</td><td class="px-3 py-2">L'ufficio sta preparando il documento</td></tr>
                            <tr class="border-b dark:border-gray-700"><td class="px-3 py-2 font-medium">Stampato</td><td class="px-3 py-2"><span class="inline-block w-3 h-3 rounded-full bg-orange-500"></span> Arancione</td><td class="px-3 py-2">Documento pronto per la consegna</td></tr>
                            <tr><td class="px-3 py-2 font-medium">Consegnato</td><td class="px-3 py-2"><span class="inline-block w-3 h-3 rounded-full bg-green-500"></span> Verde</td><td class="px-3 py-2">Consegnato al paziente</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- APPENDICE C - FAQ -->
        <div x-ref="faq" class="section-card cursor-pointer" @click="openSection = openSection === 'faq' ? '' : 'faq'">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Appendice C - Domande Frequenti (FAQ)</h3>
                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="openSection === 'faq' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
        <div x-show="openSection === 'faq'" x-collapse class="mb-4">
            <div class="section-card !mt-0 !rounded-t-none border-t-0 space-y-4">
                <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Non riesco ad accedere. Cosa faccio?</p>
                        <p>Verificare l'email corretta. Usare "Password dimenticata" dalla schermata di login. Se il problema persiste, contattare l'amministratore.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Perche un appuntamento risulta "Completato" senza intervento?</p>
                        <p>Il sistema segna automaticamente come "Completato" gli appuntamenti passati ancora in stato "Programmato" tramite un processo automatico periodico.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Come rinnovo un piano terapeutico scaduto?</p>
                        <p>Non e possibile modificare un piano scaduto. L'amministratore o il manager devono creare un nuovo piano per il paziente.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Gli appuntamenti vengono generati automaticamente?</p>
                        <p>Si, quando viene creato un piano con terapie e frequenze, il sistema genera automaticamente tutti gli appuntamenti ricorrenti per la durata del piano.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">D: Quanto tempo ci vuole per una richiesta documento?</p>
                        <p>Il tempo stimato e indicato per ciascun tipo di documento. Lo stato puo essere monitorato in tempo reale.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-gray-400 dark:text-gray-500 mt-8 mb-4">
            <p>Manuale del Gestionale - Centro Medico San Luca - v1.0</p>
        </div>

    </div>
</div>
