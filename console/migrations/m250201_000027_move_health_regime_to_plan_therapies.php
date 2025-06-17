<?php

use yii\db\Migration;

/**
 * Migrazione per spostare il campo health_regime da therapeutic_plans a plan_therapies.
 * Questo consente di avere regimi sanitari diversi per ogni terapia nel piano.
 */
class m250201_000027_move_health_regime_to_plan_therapies extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Aggiungi il campo health_regime alla tabella plan_therapies
        $this->addColumn('{{%plan_therapies}}', 'health_regime', 
            "ENUM('L11', 'L11DOM', 'L11PG', 'L11SEM', 'ABA', 'FKT', 'Private', 'PDOM', 'Other') NOT NULL DEFAULT 'L11'"
        );

        // 2. Migra i dati esistenti da therapeutic_plans a plan_therapies
        // Aggiorna ogni piano-terapia con il health_regime del piano terapeutico corrispondente
        $this->execute("
            UPDATE {{%plan_therapies}} pt
            INNER JOIN {{%therapeutic_plans}} tp ON pt.therapeutic_plan_id = tp.id
            SET pt.health_regime = tp.health_regime
        ");

        // 3. Rimuovi il campo health_regime dalla tabella therapeutic_plans
        $this->dropColumn('{{%therapeutic_plans}}', 'health_regime');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // 1. Ripristina il campo health_regime nella tabella therapeutic_plans
        $this->addColumn('{{%therapeutic_plans}}', 'health_regime', 
            "ENUM('L11', 'L11DOM', 'L11PG', 'L11SEM', 'ABA', 'FKT', 'Private', 'PDOM', 'Other') NOT NULL DEFAULT 'L11'"
        );

        // 2. Migra i dati di ritorno da plan_therapies a therapeutic_plans
        // Prende il primo health_regime trovato per ogni therapeutic_plan_id
        $this->execute("
            UPDATE {{%therapeutic_plans}} tp
            SET tp.health_regime = (
                SELECT pt.health_regime 
                FROM {{%plan_therapies}} pt 
                WHERE pt.therapeutic_plan_id = tp.id 
                LIMIT 1
            )
        ");

        // 3. Rimuovi il campo health_regime dalla tabella plan_therapies
        $this->dropColumn('{{%plan_therapies}}', 'health_regime');
    }
} 