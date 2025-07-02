<?php

namespace common\helpers;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use common\models\ActivityLog;

/**
 * Helper per la gestione dei log delle attività
 */
class ActivityLogHelper
{
    /**
     * Mappa dei nomi user-friendly per le entità
     * @var array
     */
    private static $entityLabels = [
        'User' => 'Utente',
        'Patient' => 'Paziente', 
        'Therapist' => 'Terapista',
        'Appointment' => 'Appuntamento',
        'TherapeuticPlan' => 'Piano Terapeutico',
        'DocumentRequest' => 'Richiesta Documento',
        'Notification' => 'Notifica',
        'Product' => 'Prodotto',
        'TreatmentType' => 'Tipo Trattamento',
        'Specialization' => 'Specializzazione',
        'CoordinatorGroup' => 'Gruppo Coordinatore',
    ];

    /**
     * Ritorna il nome user-friendly per un'entità
     * @param string $entityName
     * @return string
     */
    public static function getEntityLabel($entityName)
    {
        return self::$entityLabels[$entityName] ?? $entityName;
    }

    /**
     * Formatta le differenze tra valori vecchi e nuovi
     * @param array $oldValues
     * @param array $newValues
     * @return array
     */
    public static function formatChanges($oldValues, $newValues)
    {
        $changes = [];
        
        if (empty($oldValues) && empty($newValues)) {
            return $changes;
        }

        // Unisci le chiavi per controllare tutti i campi
        $allKeys = array_unique(array_merge(
            array_keys($oldValues ?: []),
            array_keys($newValues ?: [])
        ));

        foreach ($allKeys as $key) {
            $oldValue = $oldValues[$key] ?? null;
            $newValue = $newValues[$key] ?? null;

            // Salta se i valori sono identici
            if ($oldValue === $newValue) {
                continue;
            }

            // Formatta i valori per la visualizzazione
            $formattedOld = self::formatValue($oldValue);
            $formattedNew = self::formatValue($newValue);

            // Crea il testo del cambiamento
            $fieldLabel = self::getFieldLabel($key);
            
            if ($oldValue === null) {
                $changes[] = sprintf('<strong>%s:</strong> aggiunto "%s"', $fieldLabel, $formattedNew);
            } elseif ($newValue === null) {
                $changes[] = sprintf('<strong>%s:</strong> rimosso "%s"', $fieldLabel, $formattedOld);
            } else {
                $changes[] = sprintf('<strong>%s:</strong> "%s" → "%s"', $fieldLabel, $formattedOld, $formattedNew);
            }
        }

        return $changes;
    }

    /**
     * Formatta un valore per la visualizzazione
     * @param mixed $value
     * @return string
     */
    private static function formatValue($value)
    {
        if ($value === null) {
            return '(vuoto)';
        }

        if (is_bool($value)) {
            return $value ? 'Sì' : 'No';
        }

        if (is_array($value)) {
            return Json::encode($value);
        }

        // Tronca stringhe molto lunghe
        $stringValue = (string) $value;
        if (strlen($stringValue) > 50) {
            return substr($stringValue, 0, 47) . '...';
        }

        return Html::encode($stringValue);
    }

    /**
     * Ritorna l'etichetta user-friendly per un campo
     * @param string $fieldName
     * @return string
     */
    private static function getFieldLabel($fieldName)
    {
        $labels = [
            'name' => 'Nome',
            'email' => 'Email', 
            'username' => 'Nome Utente',
            'status' => 'Stato',
            'is_active' => 'Attivo',
            'description' => 'Descrizione',
            'price' => 'Prezzo',
            'phone' => 'Telefono',
            'address' => 'Indirizzo',
            'birth_date' => 'Data di Nascita',
            'created_at' => 'Data Creazione',
            'updated_at' => 'Data Modifica',
        ];

        return $labels[$fieldName] ?? ucfirst(str_replace('_', ' ', $fieldName));
    }

    /**
     * Ottiene un riassunto delle attività per utente in un periodo
     * @param int $userId
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public static function getActivitySummary($userId, $dateFrom, $dateTo)
    {
        $logs = ActivityLog::find()
            ->where(['user_id' => $userId])
            ->andWhere(['between', 'created_at', $dateFrom, $dateTo])
            ->all();

        $summary = [
            'total' => count($logs),
            'by_action' => [
                ActivityLog::ACTION_CREATE => 0,
                ActivityLog::ACTION_UPDATE => 0,
                ActivityLog::ACTION_DELETE => 0,
            ],
            'by_entity' => [],
            'recent_activities' => [],
        ];

        foreach ($logs as $log) {
            // Conta per azione
            $summary['by_action'][$log->action]++;

            // Conta per entità
            if (!isset($summary['by_entity'][$log->entity_name])) {
                $summary['by_entity'][$log->entity_name] = 0;
            }
            $summary['by_entity'][$log->entity_name]++;

            // Mantieni le attività più recenti
            if (count($summary['recent_activities']) < 10) {
                $summary['recent_activities'][] = [
                    'action' => $log->getActionDescription(),
                    'entity' => self::getEntityLabel($log->entity_name),
                    'entity_id' => $log->entity_id,
                    'created_at' => $log->created_at,
                ];
            }
        }

        // Ordina entità per numero di attività
        arsort($summary['by_entity']);

        return $summary;
    }

    /**
     * Esporta i log in Excel
     * @param ActivityLog[] $logs
     * @return \yii\web\Response
     */
    public static function exportToExcel($logs)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Intestazioni
        $headers = [
            'A1' => 'ID',
            'B1' => 'Data/Ora',
            'C1' => 'Utente',
            'D1' => 'Azione',
            'E1' => 'Entità',
            'F1' => 'ID Entità',
            'G1' => 'Modifiche',
            'H1' => 'IP Address',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Stile per le intestazioni
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E2E8F0');

        // Dati
        $row = 2;
        foreach ($logs as $log) {
            $changes = '';
            if ($log->action === ActivityLog::ACTION_UPDATE) {
                $changesArray = self::formatChanges(
                    $log->getOldValuesArray(),
                    $log->getNewValuesArray()
                );
                $changes = implode('; ', array_map('strip_tags', $changesArray));
            }

            $sheet->setCellValue('A' . $row, $log->id);
            $sheet->setCellValue('B' . $row, $log->created_at);
            $sheet->setCellValue('C' . $row, $log->getUserName());
            $sheet->setCellValue('D' . $row, $log->getActionDescription());
            $sheet->setCellValue('E' . $row, self::getEntityLabel($log->entity_name));
            $sheet->setCellValue('F' . $row, $log->entity_id);
            $sheet->setCellValue('G' . $row, $changes);
            $sheet->setCellValue('H' . $row, $log->ip_address);
            
            $row++;
        }

        // Auto-width per le colonne
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Genera il file
        $writer = new Xlsx($spreadsheet);
        $filename = 'activity_log_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_RAW;
        $response->headers->add('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->add('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        ob_start();
        $writer->save('php://output');
        $response->data = ob_get_clean();
        
        return $response;
    }

    /**
     * Invia notifica email per azioni critiche
     * @param ActivityLog $log
     */
    public static function sendCriticalActionNotification($log)
    {
        // Controlla se le notifiche sono abilitate
        $criticalEntities = Yii::$app->params['activityLog']['criticalEntities'] ?? [];
        $criticalActions = Yii::$app->params['activityLog']['criticalActions'] ?? [];
        $notificationEmails = Yii::$app->params['activityLog']['notificationEmails'] ?? [];

        if (empty($notificationEmails)) {
            return;
        }

        // Verifica se è un'azione critica
        $isCritical = false;
        
        if (in_array($log->entity_name, $criticalEntities) || in_array($log->action, $criticalActions)) {
            $isCritical = true;
        }

        // Verifica azioni di eliminazione (sempre critiche)
        if ($log->action === ActivityLog::ACTION_DELETE) {
            $isCritical = true;
        }

        if (!$isCritical) {
            return;
        }

        try {
            $subject = sprintf(
                '[TherapyCRM] Azione Critica: %s %s #%d',
                $log->getActionDescription(),
                self::getEntityLabel($log->entity_name),
                $log->entity_id
            );

            $body = self::buildNotificationEmailBody($log);

            foreach ($notificationEmails as $email) {
                Yii::$app->mailer->compose()
                    ->setTo($email)
                    ->setSubject($subject)
                    ->setHtmlBody($body)
                    ->send();
            }

        } catch (\Exception $e) {
            Yii::error('Errore invio notifica activity log: ' . $e->getMessage());
        }
    }

    /**
     * Costruisce il corpo dell'email di notifica
     * @param ActivityLog $log
     * @return string
     */
    private static function buildNotificationEmailBody($log)
    {
        $html = '<h3>Azione Critica Rilevata</h3>';
        $html .= '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
        $html .= '<tr><td><strong>Data/Ora:</strong></td><td>' . $log->created_at . '</td></tr>';
        $html .= '<tr><td><strong>Utente:</strong></td><td>' . Html::encode($log->getUserName()) . '</td></tr>';
        $html .= '<tr><td><strong>Azione:</strong></td><td>' . $log->getActionDescription() . '</td></tr>';
        $html .= '<tr><td><strong>Entità:</strong></td><td>' . self::getEntityLabel($log->entity_name) . ' #' . $log->entity_id . '</td></tr>';
        $html .= '<tr><td><strong>IP Address:</strong></td><td>' . Html::encode($log->ip_address) . '</td></tr>';

        if ($log->action === ActivityLog::ACTION_UPDATE) {
            $changes = self::formatChanges(
                $log->getOldValuesArray(),
                $log->getNewValuesArray()
            );
            if (!empty($changes)) {
                $html .= '<tr><td><strong>Modifiche:</strong></td><td>' . implode('<br>', $changes) . '</td></tr>';
            }
        }

        $html .= '</table>';

        return $html;
    }
} 