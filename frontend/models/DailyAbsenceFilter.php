<?php

namespace frontend\models;

use yii\base\Model;

/**
 * Filter model per la GridView del Riepilogo Giornaliero Terapisti.
 * Usato come filterModel di una ArrayDataProvider su righe gia' costruite in controller.
 */
class DailyAbsenceFilter extends Model
{
    public $full_name;
    public $specialization;
    public $status;
    public $absence_type;

    public function rules()
    {
        return [
            [['full_name', 'specialization', 'status', 'absence_type'], 'safe'],
            [['full_name', 'specialization', 'absence_type'], 'string', 'max' => 100],
            ['status', 'in', 'range' => ['present', 'absent']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'full_name' => 'Terapista',
            'specialization' => 'Specializzazione',
            'status' => 'Stato',
            'absence_type' => 'Tipo Assenza',
        ];
    }

    /**
     * Filtra l'array di righe in base ai valori del modello.
     * @param array $rows
     * @return array
     */
    public function apply(array $rows): array
    {
        return array_values(array_filter($rows, function ($r) {
            if (!empty($this->full_name)) {
                if (mb_stripos($r['full_name'], $this->full_name) === false) {
                    return false;
                }
            }
            if (!empty($this->specialization)) {
                if (mb_stripos($r['specialization'] ?? '', $this->specialization) === false) {
                    return false;
                }
            }
            if (!empty($this->status) && $r['status'] !== $this->status) {
                return false;
            }
            if (!empty($this->absence_type)) {
                if (empty($r['absence_type']) || mb_stripos($r['absence_type'], $this->absence_type) === false) {
                    return false;
                }
            }
            return true;
        }));
    }
}
