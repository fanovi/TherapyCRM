<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%complaint}}`.
 */
class m260114_093626_create_complaint_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        $this->createTable('{{%complaint}}', [
            'id' => $this->primaryKey()->comment('ID del reclamo'),
            'account_id' => $this->integer()->notNull()->comment('ID dell\'account che ha creato il reclamo (FK a users)'),
            'patient_id' => $this->integer()->notNull()->comment('ID del paziente correlato al reclamo (FK a patients)'),
            'title' => $this->string(200)->notNull()->comment('Titolo del reclamo'),
            'description' => $this->text()->notNull()->comment('Descrizione dettagliata del reclamo'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->comment('Data e ora di creazione del reclamo'),
        ]);

        // Foreign key per account_id (users)
        $this->addForeignKey(
            'fk_complaint_account',
            '{{%complaint}}',
            'account_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Foreign key per patient_id (patients)
        $this->addForeignKey(
            'fk_complaint_patient',
            '{{%complaint}}',
            'patient_id',
            '{{%patients}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Indici per migliorare le performance
        $this->createIndex('idx_complaint_account', '{{%complaint}}', 'account_id');
        $this->createIndex('idx_complaint_patient', '{{%complaint}}', 'patient_id');
        $this->createIndex('idx_complaint_created_at', '{{%complaint}}', 'created_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Rimuove prima le foreign key
        $this->dropForeignKey('fk_complaint_account', '{{%complaint}}');
        $this->dropForeignKey('fk_complaint_patient', '{{%complaint}}');
        
        // Rimuove gli indici
        $this->dropIndex('idx_complaint_account', '{{%complaint}}');
        $this->dropIndex('idx_complaint_patient', '{{%complaint}}');
        $this->dropIndex('idx_complaint_created_at', '{{%complaint}}');
        
        // Rimuove la tabella
        $this->dropTable('{{%complaint}}');
    }
}
