<?php

use yii\db\Migration;

/**
 * Handles dropping of column `location_type` from table `{{%appointment_patterns}}`.
 */
class m241220_000000_drop_location_type_from_appointment_patterns extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%appointment_patterns}}', 'location_type');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('{{%appointment_patterns}}', 'location_type', $this->string()->notNull()->defaultValue('office'));
        
        // Add back the constraint if needed
        $this->addCommentOnColumn('{{%appointment_patterns}}', 'location_type', 'office or home');
    }
} 