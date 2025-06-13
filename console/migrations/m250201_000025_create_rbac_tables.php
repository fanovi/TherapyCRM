<?php

use yii\db\Migration;

/**
 * Handles the creation of RBAC tables for Yii2.
 */
class m250201_000025_create_rbac_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Usa i nomi standard delle tabelle RBAC
        $ruleTable = '{{%auth_rule}}';
        $itemTable = '{{%auth_item}}';
        $itemChildTable = '{{%auth_item_child}}';
        $assignmentTable = '{{%auth_assignment}}';
        
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        
        // Controlla se le tabelle RBAC esistono già
        $tableSchema = $this->db->getTableSchema($ruleTable);
        if ($tableSchema === null) {
            $this->createTable($ruleTable, [
                'name' => $this->string(64)->notNull(),
                'data' => $this->binary(),
                'created_at' => $this->integer(),
                'updated_at' => $this->integer(),
            ], $tableOptions);
            $this->addPrimaryKey('pk-auth_rule', $ruleTable, 'name');
        }

        $tableSchema = $this->db->getTableSchema($itemTable);
        if ($tableSchema === null) {
            $this->createTable($itemTable, [
                'name' => $this->string(64)->notNull(),
                'type' => $this->smallInteger()->notNull(),
                'description' => $this->text(),
                'rule_name' => $this->string(64),
                'data' => $this->binary(),
                'created_at' => $this->integer(),
                'updated_at' => $this->integer(),
            ], $tableOptions);
            
            $this->addPrimaryKey('pk-auth_item', $itemTable, 'name');
            $this->createIndex('idx-auth_item-type', $itemTable, 'type');
            $this->addForeignKey('fk-auth_item-rule_name', $itemTable, 'rule_name', $ruleTable, 'name', 'SET NULL', 'CASCADE');
        }

        $tableSchema = $this->db->getTableSchema($itemChildTable);
        if ($tableSchema === null) {
            $this->createTable($itemChildTable, [
                'parent' => $this->string(64)->notNull(),
                'child' => $this->string(64)->notNull(),
            ], $tableOptions);
            
            $this->addPrimaryKey('pk-auth_item_child', $itemChildTable, ['parent', 'child']);
            $this->addForeignKey('fk-auth_item_child-parent', $itemChildTable, 'parent', $itemTable, 'name', 'CASCADE', 'CASCADE');
            $this->addForeignKey('fk-auth_item_child-child', $itemChildTable, 'child', $itemTable, 'name', 'CASCADE', 'CASCADE');
        }

        $tableSchema = $this->db->getTableSchema($assignmentTable);
        if ($tableSchema === null) {
            $this->createTable($assignmentTable, [
                'item_name' => $this->string(64)->notNull(),
                'user_id' => $this->string(64)->notNull(),
                'created_at' => $this->integer(),
            ], $tableOptions);
            
            $this->addPrimaryKey('pk-auth_assignment', $assignmentTable, ['item_name', 'user_id']);
            $this->createIndex('idx-auth_assignment-user_id', $assignmentTable, 'user_id');
            $this->addForeignKey('fk-auth_assignment-item_name', $assignmentTable, 'item_name', $itemTable, 'name', 'CASCADE', 'CASCADE');
        }

        // Inserisci ruoli e permessi di base solo se non esistono già
        $this->insertRbacData();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    { 
        // Usa i nomi standard delle tabelle RBAC
        $ruleTable = '{{%auth_rule}}';
        $itemTable = '{{%auth_item}}';
        $itemChildTable = '{{%auth_item_child}}';
        $assignmentTable = '{{%auth_assignment}}';
        
        // Rimuovi solo se le tabelle esistono
        $tableSchema = $this->db->getTableSchema($assignmentTable);
        if ($tableSchema !== null) {
            $this->dropTable($assignmentTable);
        }
        
        $tableSchema = $this->db->getTableSchema($itemChildTable);
        if ($tableSchema !== null) {
            $this->dropTable($itemChildTable);
        }
        
        $tableSchema = $this->db->getTableSchema($itemTable);
        if ($tableSchema !== null) {
            $this->dropTable($itemTable);
        }
        
        $tableSchema = $this->db->getTableSchema($ruleTable);
        if ($tableSchema !== null) {
            $this->dropTable($ruleTable);
        }
    }

    /**
     * Inserisce i dati RBAC di base
     */
    private function insertRbacData()
    {
        // Controlla se i dati esistono già
        $existingRoles = $this->db->createCommand("SELECT COUNT(*) FROM {{%auth_item}} WHERE type = 1")->queryScalar();
        
        if ($existingRoles == 0) {
            // Ruoli
            $this->batchInsert('{{%auth_item}}', ['name', 'type', 'description'], [
                ['admin', 1, 'Amministratore'],
                ['coordinator', 1, 'Coordinatore'],
                ['therapist', 1, 'Terapista'],
                ['patient_family', 1, 'Familiare paziente'],
            ]);

            // Permessi
            $this->batchInsert('{{%auth_item}}', ['name', 'type', 'description'], [
                ['manage_users', 2, 'Gestire utenti'],
                ['manage_patients', 2, 'Gestire pazienti'],
                ['manage_therapists', 2, 'Gestire terapisti'],
                ['manage_appointments', 2, 'Gestire appuntamenti'],
                ['view_reports', 2, 'Visualizzare report'],
                ['manage_plans', 2, 'Gestire piani terapeutici'],
                ['view_own_appointments', 2, 'Visualizzare i propri appuntamenti'],
                ['view_patient_data', 2, 'Visualizzare dati paziente'],
            ]);

            // Assegnazione permessi ai ruoli
            $this->batchInsert('{{%auth_item_child}}', ['parent', 'child'], [
                // Admin ha tutti i permessi
                ['admin', 'manage_users'],
                ['admin', 'manage_patients'],
                ['admin', 'manage_therapists'],
                ['admin', 'manage_appointments'],
                ['admin', 'view_reports'],
                ['admin', 'manage_plans'],
                
                // Coordinator
                ['coordinator', 'manage_patients'],
                ['coordinator', 'manage_therapists'],
                ['coordinator', 'manage_appointments'],
                ['coordinator', 'view_reports'],
                ['coordinator', 'manage_plans'],
                
                // Therapist
                ['therapist', 'view_own_appointments'],
                ['therapist', 'view_patient_data'],
                
                // Patient Family
                ['patient_family', 'view_patient_data'],
            ]);
        }
    }
} 