<?php

use yii\db\Migration;

/**
 * Ticket #296: pulizia dei "gruppi fantasma".
 *
 * Quando un paziente veniva cancellato o spostato fuori da un appuntamento di
 * gruppo, il group_session_id non veniva mai rimosso dal superstite: gruppi
 * ormai ridotti a un solo appuntamento attivo continuavano a comparire nel
 * calendario come appuntamenti di gruppo.
 *
 * Questa migration azzera group_session_id sugli appuntamenti ATTIVI (non
 * cancellati) che appartengono a gruppi con al massimo un membro attivo.
 * Gli appuntamenti cancellati restano invariati per lo storico. Nessun altro
 * dato (data, orario, terapista, stato) viene toccato.
 */
class m260611_150000_dissolve_single_member_appointment_groups extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $count = $this->db->createCommand("
            SELECT COUNT(*) FROM {{%appointments}} a
            JOIN (
                SELECT group_session_id
                FROM {{%appointments}}
                WHERE group_session_id IS NOT NULL
                GROUP BY group_session_id
                HAVING SUM(status != 'cancelled') <= 1
            ) g ON a.group_session_id = g.group_session_id
            WHERE a.status != 'cancelled'
        ")->queryScalar();

        if ((int)$count === 0) {
            echo "Nessun gruppo fantasma da correggere.\n";
            return true;
        }

        $this->execute("
            UPDATE {{%appointments}} a
            JOIN (
                SELECT group_session_id
                FROM {{%appointments}}
                WHERE group_session_id IS NOT NULL
                GROUP BY group_session_id
                HAVING SUM(status != 'cancelled') <= 1
            ) g ON a.group_session_id = g.group_session_id
            SET a.group_session_id = NULL
            WHERE a.status != 'cancelled'
        ");

        echo "Gruppi fantasma dissolti: azzerato group_session_id su {$count} appuntamenti attivi.\n";

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Non reversibile: il group_session_id rimosso non e' recuperabile.
        // La modifica e' comunque puramente cosmetica (etichetta "gruppo" su
        // appuntamenti ormai singoli).
        echo "m260611_150000_dissolve_single_member_appointment_groups non e' reversibile (modifica solo dati derivati).\n";
        return true;
    }
}
