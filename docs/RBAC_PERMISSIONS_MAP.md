# Mappa Ruoli e Permessi - TherapyCRM

> Documento generato il 26/02/2026 — basato sulle migration RBAC attuali del sistema.

---

## Riepilogo Ruoli

| Ruolo | Descrizione | N. Permessi | Accesso |
|-------|-------------|:-----------:|---------|
| **super_admin** | Super Amministratore | 80+ | Backend Web |
| **admin** | Amministratore | ~80 | Piattaforma Web |
| **manager** | Manager | ~45 | Piattaforma Web |
| **coordinator** | Coordinatore | ~12 | Piattaforma Web |
| **therapist** | Terapista | ~13 | App Mobile |
| **patient** | Paziente | ~7 | App Mobile |
| **patient_family** | Familiare Paziente | ~8 | App Mobile |

---

## Matrice Permessi per Ruolo

### Legenda
- ✅ = Permesso assegnato
- ❌ = Permesso NON assegnato

---

### 1. Accesso al Sistema

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `platform_login` | Accesso piattaforma web | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `app_login` | Accesso app mobile | ✅ | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ |
| `manage_system` | Configurazione di sistema | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage_notifications` | Gestire notifiche di sistema | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `view_statistics` | Visualizzare statistiche/dashboard | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

### 2. Gestione Utenti — Admin

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_admin` | Creare admin | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `view_admin` | Visualizzare admin | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `update_admin` | Modificare admin | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `delete_admin` | Eliminare admin | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

### 3. Gestione Utenti — Manager

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_manager` | Creare manager | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `view_manager` | Visualizzare manager | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `update_manager` | Modificare manager | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `delete_manager` | Eliminare manager | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

### 4. Gestione Utenti — Coordinatore

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_coordinator` | Creare coordinatori | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `view_coordinator` | Visualizzare coordinatori | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `update_coordinator` | Modificare coordinatori | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `delete_coordinator` | Eliminare coordinatori | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage_coordinator_groups` | Gestire gruppi coordinamento | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |

---

### 5. Gestione Utenti — Terapista

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_therapist` | Creare terapisti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_therapist` | Visualizzare terapisti | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `update_therapist` | Modificare terapisti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `delete_therapist` | Eliminare terapisti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_therapist_schedule` | Gestire calendario terapisti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_therapist_substitutions` | Gestire sostituzioni | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_therapist_statistics` | Statistiche terapisti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

### 6. Gestione Utenti — Paziente

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_patient` | Creare pazienti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_patient` | Visualizzare pazienti | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| `update_patient` | Modificare pazienti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `delete_patient` | Eliminare pazienti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_patient_accounts` | Gestire account pazienti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_patient_statistics` | Statistiche pazienti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

### 7. Gestione Utenti — Generici

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_user` | Creare utenti | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `view_user` | Visualizzare utenti | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `update_user` | Modificare utenti | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `delete_user` | Eliminare utenti | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage_users` | Gestire utenti (generale) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage_patients` | Gestire pazienti (generale) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `manage_therapists` | Gestire terapisti (generale) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

### 8. Piani Terapeutici

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_therapeutic_plan` | Creare piani | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_therapeutic_plan` | Visualizzare piani | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `update_therapeutic_plan` | Modificare piani | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `delete_therapeutic_plan` | Eliminare piani | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_plan_therapies` | Gestire terapie del piano | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_plans` | Gestire piani (generale) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

### 9. Appuntamenti e Calendario

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_appointment` | Creare appuntamenti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_appointment` | Visualizzare appuntamenti | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| `update_appointment` | Modificare appuntamenti | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| `delete_appointment` | Eliminare appuntamenti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_appointments` | Gestire appuntamenti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_appointment_patterns` | Gestire pattern ricorrenti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_calendar` | Visualizzare calendario | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| `manage_calendar` | Gestire calendario completo | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_own_appointments` | Visualizzare propri appuntamenti | ✅ | ✅ | ❌ | ❌ | ✅ | ✅ | ❌ |
| `manage_own_schedule` | Gestire proprio calendario | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |

---

### 10. Assenze

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_absence` | Registrare assenze | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| `view_absence` | Visualizzare assenze | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| `update_absence` | Modificare assenze | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| `delete_absence` | Eliminare assenze | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_absence_recovery` | Gestire recuperi assenze | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_absence_statistics` | Statistiche assenze | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

### 11. Notifiche

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_notification` | Creare notifiche | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_notification` | Visualizzare notifiche | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ |
| `update_notification` | Modificare notifiche | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `delete_notification` | Eliminare notifiche | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_notification_templates` | Gestire template notifiche | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `send_notifications` | Inviare notifiche | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

### 12. Documenti

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_document_request` | Creare richieste documenti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_document_request` | Visualizzare richieste doc. | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `update_document_request` | Modificare richieste doc. | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `delete_document_request` | Eliminare richieste doc. | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_documents` | Gestire documenti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_documents` | Visualizzare documenti | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |
| `download_documents` | Scaricare documenti | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ |

---

### 13. Specializzazioni e Tipi Trattamento

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_specialization` | Creare specializzazioni | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_specialization` | Visualizzare specializzazioni | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `update_specialization` | Modificare specializzazioni | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `delete_specialization` | Eliminare specializzazioni | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_treatment_types` | Gestire tipi trattamento | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

### 14. Visite Specialistiche

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `create_specialist_visit` | Creare visite specialistiche | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_specialist_visit` | Visualizzare visite spec. | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `update_specialist_visit` | Modificare visite spec. | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `delete_specialist_visit` | Eliminare visite spec. | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

### 15. Report e Esportazione Dati

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `view_reports` | Visualizzare rapporti | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `generate_reports` | Generare rapporti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `export_data` | Esportare dati | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

### 16. Comunicazioni e Messaggi

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `manage_communications` | Gestire comunicazioni | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `send_messages` | Inviare messaggi | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view_messages` | Visualizzare messaggi | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

### 17. Dati Personali e Profilo

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `view_own_data` | Visualizzare propri dati | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ |
| `update_own_data` | Modificare propri dati | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ |
| `view_assigned_patients` | Visualizzare paz. assegnati | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |

---

### 18. Configurazione

| Permesso | Descrizione | super_admin | admin | manager | coordinator | therapist | patient | patient_family |
|----------|-------------|:-----------:|:-----:|:-------:|:-----------:|:---------:|:-------:|:--------------:|
| `manage_districts` | Gestire distretti | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_settings` | Gestire impostazioni | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## Controllo Accesso per Interfaccia

| Interfaccia | Ruoli con Accesso | Permesso Richiesto |
|-------------|-------------------|-------------------|
| **Backend Web** (admin panel) | `super_admin` | Controllo ruolo diretto |
| **Piattaforma Web** (frontend) | `admin`, `manager`, `coordinator` | `platform_login` |
| **App Mobile** | `therapist`, `patient`, `patient_family` | `app_login` |

---

## Note Importanti

1. **super_admin** eredita dinamicamente TUTTI i permessi esistenti (assegnati in fase di creazione ruolo).
2. **coordinator** aveva il permesso `update_therapist` che è stato **rimosso** dalla migration `m260225_000001`.
3. I permessi sono salvati nel database nelle tabelle `auth_item`, `auth_item_child` e `auth_assignment` (Yii2 DbManager).
4. Nuovi permessi possono essere aggiunti tramite CLI: `./yii rbac/create-permission [nome] [descrizione]`.

---

## File Sorgente di Riferimento

| File | Descrizione |
|------|-------------|
| `console/migrations/m250201_000025_create_rbac_tables.php` | Creazione tabelle RBAC e ruoli base |
| `console/migrations/m250201_000030_comprehensive_rbac_permissions.php` | Permessi completi (80+) |
| `console/migrations/m250619_205539_assign_rbac_permissions.php` | Assegnazione permessi ai ruoli |
| `console/migrations/m250620_000000_create_login_permissions.php` | Permessi di login |
| `console/migrations/m260204_133225_add_super_admin_role.php` | Ruolo super_admin |
| `console/migrations/m260225_000001_remove_coordinator_update_therapist.php` | Rimozione permesso al coordinator |
| `console/controllers/RbacController.php` | Comandi CLI per gestione RBAC |
| `common/models/AuthItem.php` | Modello ruoli/permessi |
| `common/models/AuthItemChild.php` | Modello relazioni ruolo-permesso |
| `common/models/AuthAssignment.php` | Modello assegnazioni utente-ruolo |
