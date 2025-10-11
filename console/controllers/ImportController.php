<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\db\Exception;
use common\models\Provincia;
use common\models\Comune;
use common\models\District;
use common\models\Patient;
use common\models\TherapeuticPlan;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use yii\helpers\Console;

class ImportController extends Controller
{
    public $errors = [];
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

            if (!in_array($model, ['patients'])) {
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

            if ($model === 'patients') {
                // Crea il batch di pazienti
                $batch_array = $this->createPatientBatch($headers, $highestRow, $highestColumn, $worksheet);
            }
            $batchData = $batch_array['batchData'];
            $successCount = $batch_array['successCount'];
            $errorCount = $batch_array['errorCount'];
            $errors = $batch_array['errors'];

            // Inserisci le righe rimanenti
            if (!empty($batchData)) {
                $this->insertBatch($batchData, $model);
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

    public function actionPlan($filePath)
    {
        // Aumenta il limite di memoria
        ini_set('memory_limit', '2G'); // o anche '2G' se necessario

        $this->stdout("Importazione del file: {$filePath}\n");
        $this->stdout("Caricamento del file: {$filePath} - " . date('H:i:s') . "\n");
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $ria_plans = [];
        $aba_plans = [];
        $private_plans = 0;
        $plans_types = [];
        $patients = [];
        $trattamenti = [];
        $terapie = [];
        $trattamenti_piani_ria = [];
        $trattamenti_piani_aba = [];

        $end_row = 1306;
        $debug_end_row = 27;

        foreach ($worksheet->getRowIterator(2, $end_row) as $row) {
            $rowIndex = $row->getRowIndex();

            $type = $worksheet->getCell('P' . $rowIndex)->getValue();
            if (isset($plans_types[$type])) {
                $plans_types[$type]++;
            } else {
                $plans_types[$type] = 1;
            }

            $trattamento = $worksheet->getCell('Q' . $rowIndex)->getValue();
            if (isset($trattamenti[$trattamento])) {
                $trattamenti[$trattamento]++;
            } else {
                $trattamenti[$trattamento] = 1;
            }

            $terapia = $worksheet->getCell('R' . $rowIndex)->getValue();
            if (isset($terapie[$terapia])) {
                $terapie[$terapia]++;
            } else {
                $terapie[$terapia] = 1;
            }

            $fiscal_code = $worksheet->getCell('K' . $rowIndex)->getValue();
            if (isset($patients[$fiscal_code])) {
                $temp_patient = $patients[$fiscal_code];
            } else {
                $temp_patient = $this->getPatient($fiscal_code);
                if (!$temp_patient) {
                    $this->errors[] = "Paziente non trovato: " . $fiscal_code . " alla riga " . $rowIndex;
                    continue;
                }
                $patients[$fiscal_code] = $temp_patient;
            }


            if ($type === 'ABA') {
                $index = $worksheet->getCell('C' . $rowIndex)->getValue() . $worksheet->getCell('K' . $rowIndex)->getValue();
                $aba_plans[$index] = $this->getPlanDataForBatch($worksheet, $rowIndex, $temp_patient);
                $trattamenti_piani_aba[$index][] = $this->getPlanTherapyForBatch($worksheet, $rowIndex, $temp_patient);
            } elseif ($type === 'RIA') {
                $protocol = $worksheet->getCell('A' . $rowIndex)->getValue();
                $district = $worksheet->getCell('O' . $rowIndex)->getValue();
                $index =  hash('sha256', $protocol . $district);
                $ria_plans[$index] = $this->getPlanDataForBatch($worksheet, $rowIndex, $temp_patient);
                $trattamenti_piani_ria[$index][] = $this->getPlanTherapyForBatch($worksheet, $rowIndex, $temp_patient);
            } else {
                $private_plans++;
            }
        }

        $this->stdout("Plans types: " . json_encode($plans_types) . "\n");
        $this->stdout("Trattamenti: " . json_encode($trattamenti) . "\n");
        $this->stdout("Terapie: " . json_encode($terapie) . "\n");
        $this->stdout("RIA plans: " . count($ria_plans) . "\n");
        $this->stdout("ABA plans: " . count($aba_plans) . "\n");
        $this->stdout("Private plans: " . $private_plans . "\n\n");
        $this->stdout("Start inserting RIA plans - " . date('H:i:s') . "\n");

        $flat_ria_plans = [];
        foreach ($ria_plans as $protocol_plans) {
            $flat_ria_plans[] = $protocol_plans;
        }

        $flat_aba_plans = [];
        foreach ($aba_plans as $protocol_plans) {
            $flat_aba_plans[] = $protocol_plans;
        }
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()->batchInsert(
                'therapeutic_plans',
                ['patient_id', 'start_date', 'duration_days', 'created_by', 'regime_id', 'approval_date', 'protocol_number', 'status', 'district_id'],
                $flat_ria_plans
            )->execute();

            // Yii::$app->db->createCommand()->batchInsert(
            //     'therapeutic_plans',
            //     ['patient_id', 'start_date', 'duration_days', 'created_by', 'regime_id', 'approval_date', 'protocol_number'],
            //     $flat_aba_plans
            // )->execute();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $trattamenti_piani_ria_flat = [];
        foreach ($trattamenti_piani_ria as $protocol_plans) {
            $trattamenti_piani_ria_flat = array_merge($trattamenti_piani_ria_flat, $this->getPlanTherapyFlatForBatch($protocol_plans));
        }

        $this->stdout("Trattamenti RIA piani: " . count($trattamenti_piani_ria_flat) . "\n");
        $this->stdout("Primo trattamento RIA piano: " . json_encode($trattamenti_piani_ria_flat[0]) . "\n\n");

        Yii::$app->db->createCommand()->batchInsert(
            'plan_therapies',
            ['therapeutic_plan_id', 'treatment_type_id', 'weekly_hours', 'is_group', 'setting_id'],
            $trattamenti_piani_ria_flat
        )->execute();

        $this->stdout("End inserting RIA plans - " . date('H:i:s') . "\n");
        $this->stdout("Errors: " . count($this->errors) . "\n");
        $this->stdout("Errors: " . implode(', ', $this->errors) . "\n");
    }

    private function getPlanTherapyFlatForBatch($trattamenti_piani_ria_flat)
    {
        $trattamenti_piani_ria_flat_flat = [];

        foreach ($trattamenti_piani_ria_flat as $trattamento) {
            $temp = [
                $this->getPlanIdFromProtocolNumberDistrict($trattamento['protocol_number'], $trattamento['district_id']),
                $trattamento['treatment_type_id'],
                $trattamento['weekly_hours'],
                $trattamento['is_group'],
                $trattamento['setting_id']
            ];
            $trattamenti_piani_ria_flat_flat[] = $temp;
        }
        return $trattamenti_piani_ria_flat_flat;
    }

    private function getPlanIdFromProtocolNumberDistrict($protocol_number, $district_id)
    {
        $plan = TherapeuticPlan::find()
        ->where(['protocol_number' => $protocol_number, 'district_id' => $district_id])
        ->one();
        if($plan){
            return $plan->id;
        }
        $this->stdout("Piano non trovato: " . $protocol_number . " " . $district_id . "\n", Console::FG_RED);
        return null;
    }

    /**
     * Get setting id by setting name
     * @param mixed $setting_name
     * @return int|null
     */
    private function getSetting($setting_name)
    {
        switch ($setting_name) {
            case "ABA SP":
                return 7;
            case "ABA PT":
                return 8;
            case "ABA RBT":
                return 9;
            case "AMBULATORIALE":
                return 1;
            case "AMBULATORIALE PICCOLO GRUPPO":
                return 4;
            case "DOMICILIARE":
                return 2;
            case "MEDICINA DI BASE PRIVATA":
                return 10;
            case "MEDICINA DI BASE CONVENZIONATA":
                return 11;
            case "CENTRO DIURNO":
                return 5;
            case "SEMICONVITTO MEDIO":
                return 6;
            case "SEMICONVITTO GRAVE":
                return 12;
        }
    }

    /**
     * Get treatment id by treatment name
     * @param mixed $terapy_name
     * @return int|null
     */
    private function getTerapia($terapy_name)
    {
        switch ($terapy_name) {
            case 'SUPERVISOR':
                return 25;
            case 'PSICOTERAPIA FAMIL.':
                return 24;
            case 'RBT':
                return 26;
            case 'LOGOPEDIA':
                return 13;
            case 'PSICOTERAPIA':
                return 22;
            case 'TER. OCCUPAZIONALE':
                return 20;
            case 'TER. OCCUPAZIONALE PG':
                return 21;
            case 'RIAB. NEUROMOTORIA':
                return 17;
            case 'PSICOMOTRICITA':
            case 'PSICOMOTRICITA\'':
                return 15;
            case 'F.K.T. RESPIRATORIA':
                return 18;
            case 'FISIOKINESITERAPIA':
                return 19;
            case 'TERAPIA STRUMENTALE':
                return 27;
            default:
                return null;
        }
    }

    /**
     * Get plan therapy data for batch
     * @param mixed $worksheet
     * @param mixed $rowIndex
     * @return array<int|mixed>
     */
    private function getPlanTherapyForBatch($worksheet, $rowIndex, $patient)
    {
        $terapia_id = $this->getTerapia($worksheet->getCell('R' . $rowIndex)->getValue());

        $plan_therapy_data = [
            'therapeutic_plan_id' => $this->getPlanIdFromPatient($patient->id),
            'treatment_type_id' => $terapia_id,
            'weekly_hours' => $worksheet->getCell('S' . $rowIndex)->getValue(),
            'is_group' => $terapia_id == 21 ? 1 : 0,
            'setting_id' => $this->getSetting($worksheet->getCell('Q' . $rowIndex)->getValue()),
            'protocol_number' => $worksheet->getCell('A' . $rowIndex)->getValue(),
            'district_id' => $this->checkDistrict($worksheet->getCell('O' . $rowIndex)->getValue()),
        ];

        return $plan_therapy_data;
    }


    /**
     * Get plan id from patient id
     * @param mixed $protocol_number
     * @return int|null
     */
    private function getPlanIdFromPatient($id)
    {
        $plan = TherapeuticPlan::find()
        ->where(['patient_id' => $id])
        ->one();
        if($plan){
            return $plan->id;
        }
        return null;
    }

    /**
     * Get plan data for batch
     * @param mixed $worksheet
     * @param mixed $rowIndex
     * @return array<int|mixed>
     */
    private function getPlanDataForBatch($worksheet, $rowIndex, $patient)
    {
        $start = $this->formatDate($worksheet->getCell('C' . $rowIndex)->getValue());
        $end = $this->formatDate($worksheet->getCell('D' . $rowIndex)->getValue());
        $duration = $this->getDays($start, $end);
        $approval_date = $this->formatDate($worksheet->getCell('B' . $rowIndex)->getValue());
        $status = $this->getPlanStatus($start,$end);
        $district = $this->checkDistrict($worksheet->getCell('O' . $rowIndex)->getValue());

        $plan_data = [
            $patient->id, // paziente
            $start, //start
            $duration, // duration
            // $end, //end viene calcolato in automatico da mysql
            1, // created_by
            $this->getRegimeId($worksheet->getCell('P' . $rowIndex)->getValue()), // regime_id
            $approval_date, // approval_date
            $worksheet->getCell('A' . $rowIndex)->getValue(), // protocol_number
            $status, // status
            $district, // district_id
        ];

        return $plan_data;
    }

    private function getPlanStatus($start, $end)
    {
        $status = 'draft';
        if($start > date('Y-m-d')){
            $status = 'active';
        }
        if($start <= date('Y-m-d') && $end >= date('Y-m-d')){
            $status = 'active';
        }
        if($end < date('Y-m-d')){
            $status = 'expired';
        }
        return $status;
    }

    private function getRegimeId($regime)
    {
        switch ($regime) {
            case 'RIA':
                return 1;
            case 'ABA':
                return 2;
            case 'MB':
                return 1;
            default:
                return 1;
        }
    }

    /**
     * Get patient id by fiscal code
     * @param mixed $fiscal_code
     * @return int|null
     */
    private function getPatient($fiscal_code)
    {
        $patient = Patient::findOne(['fiscal_code' => $fiscal_code]);
        if ($patient) {
            return $patient;
        }
        return null;
    }

    /**
     * Get days between two dates
     * @param mixed $start
     * @param mixed $end
     * @return int
     */
    private function getDays($start, $end)
    {
        $date1 = new DateTime($start);
        $date2 = new DateTime($end);
        return  $date1->diff($date2)->days;
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
                            // Verifica se è un numero seriale di Excel valido per una dataa
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
                    $this->insertBatch($batchData, 'patients');
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
    private function insertBatch($data, $table)
    {
        if (empty($data)) {
            return;
        }


        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $db->createCommand()->batchInsert(
                $table,
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

    public $districts = [];
    private function checkDistrict($value)
    {
        if(isset($this->districts[$value])){
            $district_id = $this->districts[$value];
            return $district_id;
        }

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
                $this->districts[$value] = $district_id;
            } else {
                $district = new District();
                $district->code = (string) $numero;
                $district->name = $value;
                $district->asl_reference = strpos($value, 'SALERNO') !== false ? 'Salerno' : 'Napoli';
                if (!$district->save()) {
                    $this->stdout("Errore nel salvare il distretto: " . implode(', ', $district->getFirstErrors()) . "\n");
                }
                $district_id = $district->id;
                $this->districts[$value] = $district_id;
            }
        }

        return $district_id;
    }
}
