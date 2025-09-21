<?php

namespace common\models;

use yii\db\ActiveRecord;

class Comune extends ActiveRecord
{
    public static function tableName()
    {
        return 'comune';
    }

    public function rules()
    {
        return [
            [['nome', 'provincia_id', 'codice_catasto'], 'required'],
            [['nome'], 'string', 'max' => 255],
            [['codice_catasto'], 'string', 'max' => 4],
            [['provincia_id'], 'integer'],
            [['codice_catasto'], 'unique'],
            [['provincia_id'], 'exist', 'skipOnError' => true, 
                'targetClass' => Provincia::class, 'targetAttribute' => ['provincia_id' => 'id']],
        ];
    }

    public function getProvincia()
    {
        return $this->hasOne(Provincia::class, ['id' => 'provincia_id']);
    }
}