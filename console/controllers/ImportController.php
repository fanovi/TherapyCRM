<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\db\Exception;
use common\models\Provincia;
use common\models\Comune;
use common\models\District;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportController extends Controller
{
    /**
     * Importa province e comuni dal file CSV ISTAT
     * @param string $csvFilePath Percorso del file CSV da importare
     * @return void
     */
    public function actionImportProvinceComuni($csvFilePath)
    {
        if (!file_exists($csvFilePath)) {
            $this->stdout("File CSV non trovato: {$csvFilePath}\n");
            return;
        }

        // Array per cachare le province già create (ottimizzazione)
        $provinceCache = [];

        // Contatori per statistiche
        $provinceCreate = 0;
        $comuniCreati = 0;
        $errori = 0;

        $this->stdout("Inizio importazione da: {$csvFilePath}\n");

        // Apri il file CSV
        if (($handle = fopen($csvFilePath, 'r')) !== false) {
            // Salta la prima riga (intestazioni)
            $headers = fgetcsv($handle, 0, ';');

            if (!$headers) {
                $this->stdout("Errore nella lettura delle intestazioni del CSV\n");
                fclose($handle);
                return;
            }

            // Mappa gli indici delle colonne basandosi sui nomi
            $columnMap = array_flip($headers);

            // Verifica che le colonne necessarie esistano
            $requiredColumns = [
                'Comune',
                'Provincia/Uts',
                'Sigla automobilistica',
                'Codice catasto'
            ];

            foreach ($requiredColumns as $col) {
                if (!isset($columnMap[$col])) {
                    $this->stdout("Colonna richiesta '{$col}' non trovata nel CSV\n");
                    fclose($handle);
                    return;
                }
            }

            // Indici delle colonne
            $colComune = $columnMap['Comune'];                    // G
            $colProvincia = $columnMap['Provincia/Uts'];         // L
            $colSigla = $columnMap['Sigla automobilistica'];     // P
            $colCodiceCatasto = $columnMap['Codice catasto'];    // Q

            // Inizia una transazione per maggiore efficienza
            $transaction = \Yii::$app->db->beginTransaction();

            try {
                // Leggi ogni riga del CSV
                while (($data = fgetcsv($handle, 0, ';')) !== false) {
                    // Salta righe vuote
                    if (empty($data[$colProvincia]) || empty($data[$colComune])) {
                        continue;
                    }

                    $nomeProvincia = trim($data[$colProvincia]);
                    $siglaProvincia = trim($data[$colSigla]);
                    $nomeComune = trim($data[$colComune]);
                    $codiceCatasto = trim($data[$colCodiceCatasto]);

                    // Gestione provincia
                    if (!isset($provinceCache[$nomeProvincia])) {
                        // Cerca se la provincia esiste già nel database
                        $provincia = Provincia::findOne(['nome' => $nomeProvincia]);

                        if (!$provincia) {
                            // Crea nuova provincia
                            $provincia = new Provincia();
                            $provincia->nome = $nomeProvincia;
                            $provincia->sigla = $siglaProvincia;

                            if ($provincia->save()) {
                                $provinceCreate++;
                                $this->stdout("Creata provincia: {$nomeProvincia} ({$siglaProvincia})\n");
                            } else {
                                $this->stdout("Errore nel salvare provincia {$nomeProvincia}: " .
                                    implode(', ', $provincia->getFirstErrors()) . "\n");
                                $errori++;
                                continue;
                            }
                        }

                        // Salva in cache
                        $provinceCache[$nomeProvincia] = $provincia->id;
                    }

                    $provinciaId = $provinceCache[$nomeProvincia];

                    // Gestione comune
                    // Verifica se il comune esiste già (basandosi su codice_catasto che è univoco)
                    $comune = Comune::findOne(['codice_catasto' => $codiceCatasto]);

                    if (!$comune) {
                        // Crea nuovo comune
                        $comune = new Comune();
                        $comune->nome = $nomeComune;
                        $comune->provincia_id = $provinciaId;
                        $comune->codice_catasto = $codiceCatasto;

                        if ($comune->save()) {
                            $comuniCreati++;
                            if ($comuniCreati % 100 == 0) {
                                $this->stdout("Processati {$comuniCreati} comuni...\n");
                            }
                        } else {
                            $this->stdout("Errore nel salvare comune {$nomeComune}: " .
                                implode(', ', $comune->getFirstErrors()) . "\n");
                            $errori++;
                        }
                    }
                }

                // Conferma la transazione
                $transaction->commit();

                $this->stdout("\n=== IMPORTAZIONE COMPLETATA ===\n");
                $this->stdout("Province create: {$provinceCreate}\n");
                $this->stdout("Comuni creati: {$comuniCreati}\n");
                $this->stdout("Errori: {$errori}\n");
            } catch (\Exception $e) {
                // Rollback in caso di errore
                $transaction->rollback();
                $this->stdout("Errore durante l'importazione: " . $e->getMessage() . "\n");
            }

            fclose($handle);
        } else {
            $this->stdout("Impossibile aprire il file CSV\n");
        }
    }

    /**
     * Importa pazienti da file XLSX nel database
     * 
     * @param string $filePath Path del file XLSX
     * @return int Exit code
     */
    public function actionData($filePath, $model)
    {
        try {
            // Verifica che il file esista
            if (!file_exists($filePath)) {
                throw new \Exception("Il file '{$filePath}' non esiste.");
            }

            if (!in_array($model, ['Patient', 'Profile', 'AccountPatient'])) {
                throw new \Exception("Il modello non è valido.");
            }

            // Verifica l'estensione del file
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
            if (!in_array(strtolower($fileExtension), ['xlsx', 'xls'])) {
                throw new \Exception("Il file deve essere in formato XLSX o XLS.");
            }

            // Mostra il modello
            $this->stdout("Modello: {$model}\n");

            // Carica il file Excel
            $this->stdout("Caricamento del file: {$filePath}\n");
            $spreadsheet = IOFactory::load($filePath);

            // Ottieni il primo foglio di lavoro
            $worksheet = $spreadsheet->getActiveSheet();

            // Ottieni il numero massimo di righe e colonne con dati
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();

            // Verifica che ci siano righe di dati
            if ($highestRow < 2) {
                $this->stdout("Il file non contiene righe di dati oltre l'header.\n");
                return self::EXIT_CODE_NORMAL;
            }

            // Leggi l'header (prima riga) per mappare le colonne
            $headers = [];
            foreach (range('A', $highestColumn) as $col) {
                $headerValue = $worksheet->getCell($col . '1')->getValue();
                if ($headerValue !== null) {
                    // Normalizza il nome dell'header (rimuovi spazi, converti in lowercase)
                    $headers[$col] = strtolower(trim(str_replace(' ', '_', $headerValue)));
                }
            }

            $this->stdout("Headers trovati: " . implode(', ', $headers) . "\n\n");

            // Array per raccogliere i dati da inserire
            $batchData = [];
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            // Leggi tutte le righe di dati
            $this->stdout("Elaborazione righe...\n");

            if ($model === 'Patient') {
                // Crea il batch di pazienti
                $batch_array = $this->createPatientBatch($headers, $highestRow, $highestColumn, $worksheet);
            }
            $batchData = $batch_array['batchData'];
            $successCount = $batch_array['successCount'];
            $errorCount = $batch_array['errorCount'];
            $errors = $batch_array['errors'];

            // Inserisci le righe rimanenti
            if (!empty($batchData)) {
                $this->insertBatch($batchData);
            }

            // Libera memoria
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            // Report finale
            $this->stdout("\n" . str_repeat("=", 80) . "\n");
            $this->stdout("IMPORTAZIONE COMPLETATA\n");
            $this->stdout(str_repeat("=", 80) . "\n");
            $this->stdout("✓ Righe importate con successo: {$successCount}\n");

            if ($errorCount > 0) {
                $this->stdout("✗ Righe con errori: {$errorCount}\n");
                $this->stdout("\nDettaglio errori:\n");
                foreach ($errors as $error) {
                    $this->stderr("  - {$error}\n");
                }
            }

            return self::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stderr("Errore critico: " . $e->getMessage() . "\n");
            return self::EXIT_CODE_ERROR;
        }
    }

    /**
     * Crea un batch di pazienti
     */
    private function createPatientBatch($headers, $highestRow, $highestColumn, $worksheet)
    {
        $batchData = [];
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                $rowData = [];

                // Leggi i dati della riga corrente
                foreach (range('A', $highestColumn) as $col) {
                    $cell = $worksheet->getCell($col . $row);
                    $cellValue = $cell->getValue();

                    // Se è una formula, ottieni il valore calcolato
                    if ($cellValue !== null && is_string($cellValue) && strpos($cellValue, '=') === 0) {
                        $cellValue = $cell->getCalculatedValue();
                    }

                    // Controlla se la cella è formattata come data in Excel
                    $isDateColumn = isset($headers[$col]) && (
                        strpos($headers[$col], 'date') !== false ||
                        strpos($headers[$col], 'data') !== false ||
                        strpos($headers[$col], 'nascita') !== false
                    );

                    // Se è un numero e potrebbe essere una data
                    if (is_numeric($cellValue) && $isDateColumn) {
                        try {
                            // Verifica se è un numero seriale di Excel valido per una data
                            // I numeri seriali di Excel per date vanno da 1 (1/1/1900) a circa 2958465 (31/12/9999)
                            if ($cellValue > 0 && $cellValue < 3000000) {
                                $dateTime = ExcelDate::excelToDateTimeObject($cellValue);
                                $cellValue = $dateTime->format('Y-m-d');
                            }
                        } catch (\Exception $e) {
                            // Se la conversione fallisce, mantieni il valore originale
                            $this->stdout("Avviso: impossibile convertire il valore {$cellValue} in data alla riga {$row}, colonna {$col}\n");
                        }
                    }

                    // Alternativa: controlla anche il formato della cella
                    if (is_numeric($cellValue) && !$isDateColumn) {
                        // Prova a verificare se il formato della cella indica una data
                        $cellStyle = $cell->getStyle();
                        $numberFormat = $cellStyle->getNumberFormat()->getFormatCode();

                        // Controlla se il formato contiene indicatori di data
                        if (preg_match('/[dDmMyYhHsS]/', $numberFormat)) {
                            try {
                                $dateTime = ExcelDate::excelToDateTimeObject($cellValue);
                                $cellValue = $dateTime->format('Y-m-d');
                            } catch (\Exception $e) {
                                // Mantieni il valore originale se la conversione fallisce
                            }
                        }
                    }

                    $rowData[$headers[$col] ?? $col] = $cellValue;
                }

                // Prepara i dati per l'inserimento nel database
                $patientData = [
                    'first_name' => $this->getValueFromRow($rowData, ['nome', 'first_name', 'name']),
                    'last_name' => $this->getValueFromRow($rowData, ['cognome', 'last_name', 'surname']),
                    'birth_date' => $this->formatDate($this->getValueFromRow($rowData, ['data_nascita', 'birth_date', 'data_di_nascita', 'datanascita'])),
                    'birth_city' => $this->getValueFromRow($rowData, ['comune_di_nascita', 'città_nascita', 'birth_city', 'luogo_nascita', 'comune_nascita']),
                    'birth_province_name' => null,
                    'birth_province_code' => null,
                    'residence_address' => $this->getValueFromRow($rowData, ['indirizzo', 'residence_address', 'via']),
                    'residence_city' => $this->getValueFromRow($rowData, ['comune_di_residenza', 'città', 'residence_city', 'comune', 'citta_residenza']),
                    'residence_province_name' => null,
                    'residence_province_code' => $this->getValueFromRow($rowData, ['pro...', 'residence_province_code', 'prov', 'prov_residenza']),
                    'residence_postal_code' => $this->getValueFromRow($rowData, ['cap', 'residence_postal_code', 'codice_postale']),
                    'phone_number' => $this->getValueFromRow($rowData, ['telefono_1', 'telefono', 'phone_number', 'cellulare', 'numero_telefono']),
                    'fiscal_code' => $this->getValueFromRow($rowData, ['codice_fiscale', 'fiscal_code', 'cf']),
                    'gender' => $this->mapGender($this->getValueFromRow($rowData, ['sesso', 'gender', 'genere'])),
                    'born_in_italy' => 1,
                    'notes' => $this->getValueFromRow($rowData, ['telefono_2']) . ' ' . $this->getValueFromRow($rowData, ['telefono_3']),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'district_id' => $this->checkDistrict($this->getValueFromRow($rowData, ['distretto_di_appa...'])),
                ];

                // Valida i dati minimi richiesti
                if (empty($patientData['first_name']) || empty($patientData['last_name']) || empty($patientData['birth_date'])) {
                    throw new \Exception("Mancano dati obbligatori (nome, cognome o data di nascita)");
                }

                // Aggiungi al batch
                $batchData[] = $patientData;
                $successCount++;

                // Esegui inserimento batch ogni 100 righe
                if (count($batchData) >= 100) {
                    $this->insertBatch($batchData);
                    $this->stdout("Inserite {$successCount} righe...\n");
                    $batchData = [];
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Riga {$row}: " . $e->getMessage();
                $this->stderr("Errore riga {$row}: " . $e->getMessage() . "\n");
            }
        }

        return ['batchData' => $batchData, 'successCount' => $successCount, 'errorCount' => $errorCount, 'errors' => $errors];
    }

    /**
     * Inserisce un batch di pazienti nel database
     */
    private function insertBatch($data)
    {
        if (empty($data)) {
            return;
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $db->createCommand()->batchInsert(
                'patients',
                array_keys($data[0]),
                $data
            )->execute();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new \Exception("Errore inserimento batch: " . $e->getMessage());
        }
    }

    /**
     * Estrae un valore dalla riga cercando tra vari possibili nomi di colonna
     */
    private function getValueFromRow($rowData, $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            if (isset($rowData[$key]) && $rowData[$key] !== null && $rowData[$key] !== '') {
                return trim($rowData[$key]);
            }
        }
        return null;
    }

    /**
     * Formatta una data nel formato MySQL
     */
    private function formatDate($date)
    {
        if (empty($date)) {
            return null;
        }

        // Se è già nel formato corretto Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Se è un numero (probabile numero seriale Excel non convertito)
        if (is_numeric($date) && $date > 0 && $date < 3000000) {
            try {
                $dateTime = ExcelDate::excelToDateTimeObject($date);
                return $dateTime->format('Y-m-d');
            } catch (\Exception $e) {
                $this->stdout("Avviso: impossibile convertire il numero {$date} in data\n");
            }
        }

        // Prova vari formati comuni
        $formats = ['d/m/Y', 'd-m-Y', 'Y/m/d', 'Y-m-d', 'd.m.Y'];

        foreach ($formats as $format) {
            $dateObj = \DateTime::createFromFormat($format, $date);
            if ($dateObj !== false) {
                return $dateObj->format('Y-m-d');
            }
        }

        // Ultimo tentativo con strtotime
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Mappa il genere nel formato del database
     */
    private function mapGender($value)
    {
        if (empty($value)) {
            return 'N';
        }

        $value = strtoupper(trim($value));

        if (in_array($value, ['M', 'MASCHIO', 'MALE', 'UOMO'])) {
            return 'M';
        }

        if (in_array($value, ['F', 'FEMMINA', 'FEMALE', 'DONNA'])) {
            return 'F';
        }

        return 'N';
    }

    /**
     * Mappa il valore born_in_italy
     */
    private function mapBornInItaly($value)
    {
        if ($value === null || $value === '') {
            return 1; // Default: nato in Italia
        }

        $value = strtolower(trim($value));

        if (in_array($value, ['1', 'si', 'sì', 'yes', 'true', 'italia'])) {
            return 1;
        }

        if (in_array($value, ['0', 'no', 'false', 'estero'])) {
            return 0;
        }

        return 1; // Default
    }

    private function checkDistrict($value)
    {
        $district_id = null;

        if ($value === null || empty($value)) {
            $district = District::find()->one();
            if ($district) {
                $district_id = $district->id;
            }
        } else {
            preg_match('/\d+/', $value, $matches);
            $numero = isset($matches[0]) ? (int)$matches[0] : null;

            $district = District::find()->where(['code' => $numero])->one();

            if ($district) {
                $district_id = $district->id;
            } else {
                $district = new District();
                $district->code = (string) $numero;
                $district->name = $value;
                $district->asl_reference = strpos($value, 'SALERNO') !== false ? 'Salerno' : 'Napoli';
                if (!$district->save()) {
                    $this->stdout("Errore nel salvare il distretto: " . implode(', ', $district->getFirstErrors()) . "\n");
                }
                $district_id = $district->id;
            }
        }

        return $district_id;
    }
}
