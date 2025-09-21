<?php

namespace common\components;

use common\models\Comune;
use Yii;
use common\models\Provincia;
use yii\helpers\ArrayHelper;

class Helper
{
    public static function getProvinceOptions()
    {
        $province = Provincia::find()->select(['nome', 'sigla'])->orderBy('nome ASC')->all();
        return ArrayHelper::map($province, 'sigla', 'nome');
    }

    public static function getComuneOptions($provincia_id = null)
    {
        $comuni = Comune::find()->select(['nome']);
        if($provincia_id) {
            $comuni = $comuni->where(['provincia_id' => $provincia_id]);
        }
        $comuni = $comuni->orderBy('nome ASC')->all();
        return ArrayHelper::map($comuni, 'nome', 'nome');
    }
}