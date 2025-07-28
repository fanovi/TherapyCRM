<?php

use yii\db\Migration;

class m250728_170500_delete_private_regime extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Verifica se esistono piani terapeutici che utilizzano il regime "Privato"
        $regimeId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%regime}}')
            ->where(['nome' => 'Privato'])
            ->scalar($this->db);

        if (!$regimeId) {
            echo "Regime 'Privato' non trovato. Nessuna azione necessaria.\n";
            return true;
        }

        // Conta i piani terapeutici che utilizzano questo regime
        $therapeuticPlansCount = (new \yii\db\Query())
            ->from('{{%therapeutic_plans}}')
            ->where(['regime_id' => $regimeId])
            ->count('*', $this->db);

        if ($therapeuticPlansCount > 0) {
            echo "ATTENZIONE: Trovati {$therapeuticPlansCount} piani terapeutici che utilizzano il regime 'Privato'.\n";
            echo "Prima di eliminare il regime, è necessario riassegnare questi piani ad un altro regime.\n";
            echo "Migration interrotta per sicurezza.\n";
            return false;
        }

        // Conta le impostazioni del regime
        $regimeSettingsCount = (new \yii\db\Query())
            ->from('{{%regime_setting}}')
            ->where(['regime_id' => $regimeId])
            ->count('*', $this->db);

        if ($regimeSettingsCount > 0) {
            echo "Trovate {$regimeSettingsCount} impostazioni associate al regime 'Privato'. Verranno eliminate automaticamente.\n";
        }

        // Procede con l'eliminazione
        echo "Eliminazione del regime 'Privato' in corso...\n";
        
        // Le impostazioni del regime verranno eliminate automaticamente grazie al CASCADE
        $deletedRows = $this->delete('{{%regime}}', ['nome' => 'Privato']);
        
        if ($deletedRows > 0) {
            echo "Regime 'Privato' eliminato con successo.\n";
        } else {
            echo "Nessun regime 'Privato' da eliminare.\n";
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "Ripristino del regime 'Privato'...\n";
        
        // Verifica se il regime esiste già
        $exists = (new \yii\db\Query())
            ->from('{{%regime}}')
            ->where(['nome' => 'Privato'])
            ->exists($this->db);

        if ($exists) {
            echo "Il regime 'Privato' esiste già. Nessuna azione necessaria.\n";
            return true;
        }

        // Ricreo il regime "Privato"
        $this->insert('{{%regime}}', [
            'nome' => 'Privato',
            'descrizione' => 'Regime Privato',
            'conteggio_ore' => 'monthly' // Valore di default basato sulla struttura vista
        ]);

        echo "Regime 'Privato' ripristinato con successo.\n";
        return true;
    }
}
