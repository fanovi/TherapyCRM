<?php

use yii\db\Migration;

/**
 * Crea tabella permission_metadata per tracciare permessi attivi/orfani.
 */
class m260303_120000_create_permission_metadata_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%permission_metadata}}', [
            'permission_name' => $this->string(64)->notNull(),
            'is_active' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'notes' => $this->text()->null(),
        ], 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB');

        $this->addPrimaryKey('pk_permission_metadata', '{{%permission_metadata}}', 'permission_name');

        $this->addForeignKey(
            'fk_permission_metadata_auth_item',
            '{{%permission_metadata}}',
            'permission_name',
            '{{%auth_item}}',
            'name',
            'CASCADE',
            'CASCADE'
        );

        // Popola con tutti i permessi esistenti (type=2)
        $permissions = (new \yii\db\Query())
            ->select('name')
            ->from('{{%auth_item}}')
            ->where(['type' => 2])
            ->column();

        // Permessi orfani (non controllati nel codice)
        $orphanPermissions = [
            'manage_system',
            'manage_notifications',
            'create_user',
            'view_user',
            'update_user',
            'delete_user',
            'manage_users',
            'manage_therapists',
            'manage_therapist_schedule',
            'manage_therapist_substitutions',
            'view_therapist_statistics',
            'manage_patients',
            'manage_patient_accounts',
            'view_patient_statistics',
            'manage_plans',
            'manage_plan_therapies',
            'create_appointment',
            'view_appointment',
            'update_appointment',
            'delete_appointment',
            'manage_appointments',
            'manage_appointment_patterns',
            'manage_absence_recovery',
            'view_absence_statistics',
            'create_notification',
            'view_notification',
            'update_notification',
            'delete_notification',
            'manage_notification_templates',
            'send_notifications',
            'create_document_request',
            'view_document_request',
            'update_document_request',
            'delete_document_request',
            'download_documents',
            'create_specialization',
            'view_specialization',
            'update_specialization',
            'delete_specialization',
            'manage_treatment_types',
            'create_specialist_visit',
            'view_specialist_visit',
            'update_specialist_visit',
            'delete_specialist_visit',
            'view_reports',
            'generate_reports',
            'manage_communications',
            'send_messages',
            'view_messages',
            'view_own_data',
            'update_own_data',
            'view_own_appointments',
            'view_assigned_patients',
            'manage_own_schedule',
            'manage_coordinator_groups',
        ];

        foreach ($permissions as $permName) {
            $isOrphan = in_array($permName, $orphanPermissions);
            $this->insert('{{%permission_metadata}}', [
                'permission_name' => $permName,
                'is_active' => $isOrphan ? 0 : 1,
                'notes' => $isOrphan ? 'Permesso non controllato nel codice - nascosto dalle viste di gestione' : null,
            ]);
        }
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_permission_metadata_auth_item', '{{%permission_metadata}}');
        $this->dropTable('{{%permission_metadata}}');
    }
}
