<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%group_therapists}}`.
 */
class m250201_000011_create_group_therapists_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%group_therapists}}', [
            'id' => $this->primaryKey(),
            'group_id' => $this->integer()->notNull(),
            'therapist_id' => $this->integer()->notNull(),
            'assigned_from' => $this->date()->notNull(),
            'assigned_to' => $this->date(),
            'assigned_by' => $this->integer()->notNull(),
        ]);

        // Crea foreign keys
        $this->addForeignKey(
            'fk-group_therapists-group_id',
            '{{%group_therapists}}',
            'group_id',
            '{{%coordinator_groups}}',
            'id'
        );

        $this->addForeignKey(
            'fk-group_therapists-therapist_id',
            '{{%group_therapists}}',
            'therapist_id',
            '{{%therapists}}',
            'id'
        );

        $this->addForeignKey(
            'fk-group_therapists-assigned_by',
            '{{%group_therapists}}',
            'assigned_by',
            '{{%users}}',
            'id'
        );

        // Crea indici
        $this->createIndex('idx_group_active', '{{%group_therapists}}', ['group_id', 'assigned_to']);
        $this->createIndex('idx_therapist', '{{%group_therapists}}', 'therapist_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuovi foreign keys
        $this->dropForeignKey('fk-group_therapists-assigned_by', '{{%group_therapists}}');
        $this->dropForeignKey('fk-group_therapists-therapist_id', '{{%group_therapists}}');
        $this->dropForeignKey('fk-group_therapists-group_id', '{{%group_therapists}}');
        
        $this->dropTable('{{%group_therapists}}');
    }
} 