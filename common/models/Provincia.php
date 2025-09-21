<?php

namespace common\models;

use yii\db\ActiveRecord;

class Provincia extends ActiveRecord
{
    public static function tableName()
    {
        return 'provincia';
    }

    public function rules()
    {
        return [
            [['nome', 'sigla'], 'required'],
            [['nome'], 'string', 'max' => 255],
            [['sigla'], 'string', 'max' => 2],
            [['nome'], 'unique'],
            [['sigla'], 'unique'],
        ];
    }

    public function getComuni()
    {
        return $this->hasMany(Comune::class, ['provincia_id' => 'id']);
    }
}