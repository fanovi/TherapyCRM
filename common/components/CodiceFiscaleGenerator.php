<?php

namespace common\components;

use common\models\Comune;
use yii\base\Component;

class CodiceFiscaleGenerator extends Component
{
    /**
     * Genera il codice fiscale italiano
     * 
     * @param string $cognome
     * @param string $nome
     * @param string $dataNascita Formato: YYYY-MM-DD
     * @param string $sesso M o F
     * @param string $comune
     * @return string|null Codice fiscale generato o null in caso di errore
     */
    public static function generaCodiceFiscale($cognome, $nome, $dataNascita, $sesso, $comune)
    {
        try {
            // Normalizza i dati (IMPORTANTE: normalizza correttamente prima di processare)
            $cognome = self::normalizzaStringa($cognome);
            $nome = self::normalizzaStringa($nome);
            $sesso = strtoupper($sesso);

            // 1. Calcola parte del cognome (3 caratteri)
            $parteCognome = self::calcolaParteCognome($cognome);

            // 2. Calcola parte del nome (3 caratteri)
            $parteNome = self::calcolaParteNome($nome);

            // 3. Calcola parte della data di nascita (5 caratteri)
            $parteData = self::calcolaParteData($dataNascita, $sesso);

            // 4. Calcola parte del comune (4 caratteri - codice catastale)
            $parteComune = self::calcolaParteComune($comune);

            if (!$parteComune) {
                return null;
            }

            // 5. Compone le prime 15 cifre
            $codice15 = $parteCognome . $parteNome . $parteData . $parteComune;

            // 6. Calcola carattere di controllo (16° carattere)
            $carattereControllo = self::calcolaCarattereControllo($codice15);

            return $codice15 . $carattereControllo;
        } catch (\Exception $e) {
            \Yii::error("Errore nella generazione del codice fiscale: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Normalizza una stringa rimuovendo accenti e caratteri speciali
     * IMPORTANTE: Prima sostituisce gli accenti, POI rimuove i caratteri non alfabetici
     */
    private static function normalizzaStringa($stringa)
    {
        // Prima converte in maiuscolo
        $stringa = strtoupper($stringa);

        // Sostituisce le lettere con accento PRIMA di rimuovere caratteri speciali
        $accents = [
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Ñ' => 'N',
            'Ç' => 'C'
        ];

        $stringa = strtr($stringa, $accents);

        // SOLO DOPO rimuove i caratteri non alfabetici (spazi, apostrofi, trattini, etc.)
        $stringa = preg_replace('/[^A-Z]/', '', $stringa);

        return $stringa;
    }

    /**
     * Calcola la parte del cognome (3 caratteri)
     */
    private static function calcolaParteCognome($cognome)
    {
        // Estrae consonanti e vocali
        $consonanti = preg_replace('/[AEIOU]/', '', $cognome);
        $vocali = preg_replace('/[^AEIOU]/', '', $cognome);

        // Prende le prime 3 consonanti
        $parte = substr($consonanti, 0, 3);

        // Se le consonanti sono meno di 3, aggiunge le vocali
        if (strlen($parte) < 3) {
            $parte .= substr($vocali, 0, 3 - strlen($parte));
        }

        // Se ancora meno di 3 caratteri, aggiunge X
        if (strlen($parte) < 3) {
            $parte = str_pad($parte, 3, 'X');
        }

        return $parte;
    }

    /**
     * Calcola la parte del nome (3 caratteri)
     */
    private static function calcolaParteNome($nome)
    {
        // Estrae consonanti e vocali
        $consonanti = preg_replace('/[AEIOU]/', '', $nome);
        $vocali = preg_replace('/[^AEIOU]/', '', $nome);

        // Se ci sono 4 o più consonanti, prende la 1°, 3° e 4°
        if (strlen($consonanti) >= 4) {
            $parte = $consonanti[0] . $consonanti[2] . $consonanti[3];
        } else {
            // Altrimenti prende le prime 3 consonanti
            $parte = substr($consonanti, 0, 3);
        }

        // Se le consonanti sono meno di 3, aggiunge le vocali
        if (strlen($parte) < 3) {
            $parte .= substr($vocali, 0, 3 - strlen($parte));
        }

        // Se ancora meno di 3 caratteri, aggiunge X
        if (strlen($parte) < 3) {
            $parte = str_pad($parte, 3, 'X');
        }

        return $parte;
    }

    /**
     * Calcola la parte della data di nascita (5 caratteri)
     */
    private static function calcolaParteData($dataNascita, $sesso)
    {
        // Crea DateTime assicurandosi del timezone corretto
        $data = \DateTime::createFromFormat('Y-m-d', $dataNascita);
        if (!$data) {
            // Prova altri formati comuni
            $data = \DateTime::createFromFormat('d/m/Y', $dataNascita);
            if (!$data) {
                $data = \DateTime::createFromFormat('d-m-Y', $dataNascita);
                if (!$data) {
                    throw new \Exception("Formato data non valido: $dataNascita");
                }
            }
        }

        // Estrae anno (ultime 2 cifre), mese e giorno
        $anno = $data->format('y'); // Ultime 2 cifre dell'anno
        $mese = $data->format('m');
        $giorno = $data->format('d');

        // Codice mese (lettera)
        $codiciMese = [
            '01' => 'A',
            '02' => 'B',
            '03' => 'C',
            '04' => 'D',
            '05' => 'E',
            '06' => 'H',
            '07' => 'L',
            '08' => 'M',
            '09' => 'P',
            '10' => 'R',
            '11' => 'S',
            '12' => 'T'
        ];

        if (!isset($codiciMese[$mese])) {
            throw new \Exception("Mese non valido: $mese");
        }

        $codiceMese = $codiciMese[$mese];

        // Giorno: per le donne aggiunge 40
        $giornoNum = (int)$giorno;
        if (strtoupper($sesso) === 'F') {
            $giornoNum += 40;
        }

        $giornoCodice = str_pad($giornoNum, 2, '0', STR_PAD_LEFT);

        return $anno . $codiceMese . $giornoCodice;
    }

    /**
     * Recupera il codice catastale del comune per il calcolo del codice fiscale
     */
    private static function calcolaParteComune($comune)
    {
        // Cache statica per evitare query ripetute
        static $cache = [];

        $comuneKey = strtoupper(trim($comune));
        if (isset($cache[$comuneKey])) {
            return $cache[$comuneKey];
        }

        // 1. Prima cerca esatto
        $comuneModel = Comune::find()
            ->select(['codice_catasto'])
            ->where(['nome' => $comune])
            ->one();

        // 2. Cerca case-insensitive
        if (!$comuneModel) {
            $comuneModel = Comune::find()
                ->select(['codice_catasto'])
                ->where(['UPPER(nome)' => strtoupper($comune)])
                ->one();
        }

        // 3. Cerca con LIKE per gestire piccole variazioni
        if (!$comuneModel) {
            $comuneModel = Comune::find()
                ->select(['codice_catasto'])
                ->where(['LIKE', 'nome', $comune])
                ->one();
        }

        // 4. Cerca parziale (inizio del nome)
        if (!$comuneModel) {
            $comuneModel = Comune::find()
                ->select(['codice_catasto'])
                ->where(['LIKE', 'nome', $comune . '%'])
                ->one();
        }

        // 5. Ultima prova: rimuovi spazi extra e cerca
        if (!$comuneModel) {
            $comunePulito = preg_replace('/\s+/', ' ', trim($comune));
            $comuneModel = Comune::find()
                ->select(['codice_catasto'])
                ->where(['UPPER(nome)' => strtoupper($comunePulito)])
                ->one();
        }

        if ($comuneModel && !empty($comuneModel->codice_catasto)) {
            $codice = strtoupper($comuneModel->codice_catasto);
            $cache[$comuneKey] = $codice;
            return $codice;
        }

        throw new \Exception("Codice catastale non trovato per il comune: $comune");
    }

    /**
     * Calcola il carattere di controllo (16° carattere)
     */
    private static function calcolaCarattereControllo($codice15)
    {
        // Le tue tabelle erano corrette
        $pari = [
            '0' => 0,
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            '7' => 7,
            '8' => 8,
            '9' => 9,
            'A' => 0,
            'B' => 1,
            'C' => 2,
            'D' => 3,
            'E' => 4,
            'F' => 5,
            'G' => 6,
            'H' => 7,
            'I' => 8,
            'J' => 9,
            'K' => 10,
            'L' => 11,
            'M' => 12,
            'N' => 13,
            'O' => 14,
            'P' => 15,
            'Q' => 16,
            'R' => 17,
            'S' => 18,
            'T' => 19,
            'U' => 20,
            'V' => 21,
            'W' => 22,
            'X' => 23,
            'Y' => 24,
            'Z' => 25
        ];

        $dispari = [
            '0' => 1,
            '1' => 0,
            '2' => 5,
            '3' => 7,
            '4' => 9,
            '5' => 13,
            '6' => 15,
            '7' => 17,
            '8' => 19,
            '9' => 21,
            'A' => 1,
            'B' => 0,
            'C' => 5,
            'D' => 7,
            'E' => 9,
            'F' => 13,
            'G' => 15,
            'H' => 17,
            'I' => 19,
            'J' => 21,
            'K' => 2,
            'L' => 4,
            'M' => 18,
            'N' => 20,
            'O' => 11,
            'P' => 3,
            'Q' => 6,
            'R' => 8,
            'S' => 12,
            'T' => 14,
            'U' => 16,
            'V' => 10,
            'W' => 22,
            'X' => 25,
            'Y' => 24,
            'Z' => 23
        ];

        $controllo = [
            0 => 'A',
            1 => 'B',
            2 => 'C',
            3 => 'D',
            4 => 'E',
            5 => 'F',
            6 => 'G',
            7 => 'H',
            8 => 'I',
            9 => 'J',
            10 => 'K',
            11 => 'L',
            12 => 'M',
            13 => 'N',
            14 => 'O',
            15 => 'P',
            16 => 'Q',
            17 => 'R',
            18 => 'S',
            19 => 'T',
            20 => 'U',
            21 => 'V',
            22 => 'W',
            23 => 'X',
            24 => 'Y',
            25 => 'Z'
        ];

        $somma = 0;

        for ($i = 0; $i < 15; $i++) {
            $carattere = $codice15[$i];
            // IMPORTANTE: posizione nel codice fiscale (non nell'array)
            // Posizioni dispari (1°, 3°, 5°...) = indici 0, 2, 4...
            // Posizioni pari (2°, 4°, 6°...) = indici 1, 3, 5...
            if (($i + 1) % 2 == 0) {  // Posizioni pari (2°, 4°, 6°...)
                $somma += $pari[$carattere];
            } else {  // Posizioni dispari (1°, 3°, 5°...)
                $somma += $dispari[$carattere];
            }
        }

        $resto = $somma % 26;
        return $controllo[$resto];
    }

    /**
     * Verifica se un codice fiscale è valido
     */
    public static function verificaCodiceFiscale($codiceFiscale)
    {
        if (strlen($codiceFiscale) !== 16) {
            return false;
        }

        $codiceFiscale = strtoupper($codiceFiscale);
        $codice15 = substr($codiceFiscale, 0, 15);
        $carattereControlloCalcolato = self::calcolaCarattereControllo($codice15);
        $carattereControllo = substr($codiceFiscale, 15, 1);

        return $carattereControlloCalcolato === $carattereControllo;
    }
}
