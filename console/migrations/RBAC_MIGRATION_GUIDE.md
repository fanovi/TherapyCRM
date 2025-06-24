# Guida alla Migration RBAC Completa - TherapyCRM

## 📋 Panoramica

La migration `m250125_000000_comprehensive_rbac_permissions.php` è stata creata per **omologare e standardizzare** tutti i permessi, ruoli e assegnazioni del sistema TherapyCRM.

## 🎯 Obiettivi della Migration

1. **Centralizzare** tutti i permessi in un'unica migration comprensiva
2. **Standardizzare** la nomenclatura dei permessi e ruoli
3. **Documentare** completamente la struttura RBAC
4. **Sincronizzare** permessi con le effettive necessità del sistema

## 📊 Struttura Implementata

### Ruoli Principali

| Ruolo            | Descrizione                          | Accesso        |
| ---------------- | ------------------------------------ | -------------- |
| `admin`          | Amministratore completo              | Platform + API |
| `manager`        | Gestione completa pazienti/terapisti | Platform       |
| `coordinator`    | Gestione limitata gruppi             | Platform       |
| `therapist`      | Gestione propri appuntamenti         | Mobile App     |
| `patient`        | Visualizzazione propri dati          | Mobile App     |
| `patient_family` | Visualizzazione dati familiari       | Mobile App     |

### Categorie Permessi

#### 🔧 Permessi Sistema (5)

- `manage_system`: Configurazione di sistema
- `view_statistics`: Statistiche e dashboard
- `manage_notifications`: Gestione notifiche
- `platform_login`: Accesso piattaforma web
- `app_login`: Accesso app mobile

#### 👥 Permessi Utenti (20 CRUD + specializzati)

- **Pattern CRUD**: `{action}_{entity}` (create, view, update, delete)
- **Entità**: user, admin, manager, coordinator, therapist, patient
- **Specializzati**: groups, accounts, schedules, statistics

#### 🏥 Permessi Clinici (25)

- **Piani Terapeutici**: create/view/update/delete + manage_plans
- **Appuntamenti**: create/view/update/delete + manage_appointments + patterns + calendar
- **Assenze**: create/view/update/delete + recovery + statistics

#### 📄 Permessi Documenti & Comunicazioni (15)

- **Documenti**: create/view/update/delete + manage + download
- **Notifiche**: create/view/update/delete + templates + send
- **Comunicazioni**: manage + send/view messages

#### ⚙️ Permessi Configurazione (10)

- **Specializzazioni**: create/view/update/delete + treatment_types
- **Visite Specialistiche**: create/view/update/delete
- **Sistema**: reports, export, districts, settings

#### 👤 Permessi Personali (5)

- `view_own_data`: Visualizzare propri dati
- `update_own_data`: Modificare propri dati
- `view_own_appointments`: Propri appuntamenti
- `view_assigned_patients`: Pazienti assegnati
- `manage_own_schedule`: Proprio calendario

## 🔐 Distribuzione Permessi per Ruolo

### 👑 ADMIN (80 permessi)

- **Accesso completo** a tutti i permessi del sistema
- **Platform login** per gestionale web
- **Gestione sistema** completa

### 👨‍💼 MANAGER (45 permessi)

```
Platform Access + Statistics + Reports
├── Gestione Terapisti (7 permessi)
├── Gestione Pazienti (6 permessi)
├── Gestione Piani Terapeutici (6 permessi)
├── Gestione Appuntamenti (8 permessi)
├── Gestione Assenze (6 permessi)
├── Comunicazioni & Documenti (12 permessi)
└── Configurazione Sistema (5 permessi)
```

### 👨‍💼 COORDINATOR (12 permessi)

```
Platform Access
├── Visualizzazione Terapisti (2 permessi)
├── Gestione Gruppi (1 permesso)
├── Visualizzazione Pazienti (1 permesso)
├── Calendar View (1 permesso)
├── Documenti (2 permessi)
├── Reports (1 permesso)
├── Appuntamenti limitati (2 permessi)
└── Dati personali (2 permessi)
```

### 👨‍⚕️ THERAPIST (11 permessi)

```
App Access
├── Pazienti Assegnati (1 permesso)
├── Propri Appuntamenti (4 permessi)
├── Gestione Assenze (3 permessi)
├── Calendar View (1 permesso)
├── Notifiche (1 permesso)
└── Dati personali (2 permessi)
```

### 👤 PATIENT (7 permessi)

```
App Access
├── Dati Personali (2 permessi)
├── Propri Appuntamenti (1 permesso)
├── Notifiche (1 permesso)
├── Documenti Personali (2 permessi)
└── Comunicazioni (1 permesso)
```

### 👶 PATIENT_FAMILY (8 permessi)

```
App Access
├── Dati Paziente (1 permesso)
├── Appuntamenti Paziente (1 permesso)
├── Notifiche (1 permesso)
├── Documenti Paziente (2 permessi)
├── Comunicazioni (1 permesso)
└── Dati personali (2 permessi)
```

## 🚀 Come Applicare la Migration

### 1. Backup del Sistema

```bash
# Backup database
mysqldump -u username -p therapycrm > backup_before_rbac.sql

# Backup configurazione RBAC esistente
./yii rbac/export-config > rbac_backup.json
```

### 2. Applicazione Migration

```bash
# Vai nella directory del progetto
cd /path/to/TherapyCRM

# Applica la migration
./yii migrate --migrationPath=console/migrations

# Verifica l'applicazione
./yii rbac/list-roles
./yii rbac/list-permissions
```

### 3. Verifica Funzionamento

```bash
# Test permessi admin
./yii rbac/user-assignments 1

# Test permessi therapist
./yii rbac/user-assignments [therapist_user_id]

# Pulisci cache
./yii cache/flush-all
```

## 🔍 Troubleshooting

### Errori Comuni

#### 1. "Ruolo già esistente"

La migration gestisce automaticamente i ruoli esistenti, non è un errore bloccante.

#### 2. "Permesso già assegnato"

Normale durante l'esecuzione, indica che l'assegnazione è già presente.

#### 3. "AuthManager non configurato"

Verifica la configurazione RBAC in `common/config/main.php`:

```php
'authManager' => [
    'class' => 'yii\rbac\DbManager',
],
```

### Log e Debug

```bash
# Monitora i log durante l'applicazione
tail -f frontend/runtime/logs/app.log

# Verifica tabelle RBAC
mysql -u username -p therapycrm
> SELECT * FROM auth_item WHERE type = 1; -- Ruoli
> SELECT * FROM auth_item WHERE type = 2; -- Permessi
> SELECT COUNT(*) FROM auth_item_child; -- Assegnazioni
```

## 📝 Modifiche alle Cursor Rules

Il file `.cursor/rules/therapy-rules.mdc` è stato aggiornato con:

1. **Regola Fondamentale**: Tutti i permessi via migration, mai console
2. **Pattern Migration RBAC**: Template standardizzato
3. **Controlli Controller**: Best practices implementazione
4. **Naming Convention**: Standard per permessi e ruoli
5. **Debugging Tools**: Comandi utili per troubleshooting
6. **Security Rules**: Principi di sicurezza

## 🎯 Prossimi Passi

### 1. Test Approfonditi

- [ ] Test accesso per ogni ruolo
- [ ] Verifica permessi nei controller
- [ ] Test app mobile con nuovi permessi

### 2. Documentazione Utente

- [ ] Manuale per admin sui nuovi permessi
- [ ] Guida per coordinatori
- [ ] FAQ sui cambiamenti

### 3. Monitoraggio

- [ ] Log accessi negati
- [ ] Audit trail modifiche permessi
- [ ] Performance monitoring RBAC

## 🔒 Sicurezza

### Principi Implementati

1. **Least Privilege**: Permessi minimi necessari
2. **Separation of Duties**: Ruoli distinti per responsabilità
3. **Defense in Depth**: Controlli multipli
4. **Audit Trail**: Logging completo
5. **Regular Review**: Processo di revisione permessi

### Controlli di Accesso

- **Platform Login**: Solo admin, manager, coordinator
- **App Login**: Solo therapist, patient, patient_family
- **Cross-Platform**: Admin può accedere ovunque
- **Resource-Based**: Accesso limitato a risorse assegnate

## 📞 Supporto

Per problemi con la migration RBAC:

1. **Controllare i log**: `frontend/runtime/logs/app.log`
2. **Verificare configurazione RBAC**: `common/config/main.php`
3. **Testare permessi**: `./yii rbac/user-assignments [user_id]`
4. **Rollback se necessario**: `./yii migrate/down`

---

**Data Creazione**: 25 Gennaio 2025  
**Versione Migration**: m250125_000000_comprehensive_rbac_permissions  
**Compatibilità**: Yii2 Advanced Template + TherapyCRM v1.0+
