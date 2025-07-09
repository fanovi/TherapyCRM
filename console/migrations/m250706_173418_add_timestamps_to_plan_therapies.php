<?php

use yii\db\Migration;

/**
 * Class m250706_173418_add_timestamps_to_plan_therapies
 */
class m250706_173418_add_timestamps_to_plan_therapies extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%plan_therapies}}', 'created_at', $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'));
        $this->addColumn('{{%plan_therapies}}', 'updated_at', $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%plan_therapies}}', 'updated_at');
        $this->dropColumn('{{%plan_therapies}}', 'created_at');
    }
} 