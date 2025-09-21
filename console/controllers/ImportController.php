<?php

namespace console\controllers;

use yii\console\Controller;
use yii\db\Exception;
use common\models\Provincia;
use common\models\Comune;

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
}