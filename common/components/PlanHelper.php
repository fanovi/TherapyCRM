<?php

namespace common\components;

use common\models\Appointment;
use common\models\PlanTherapy;
use common\models\Setting;

class PlanHelper
{
    public static function getPlanTherapySettingFromAppointment($appointment)
    {
        if($appointment->plan_therapy_id){
            $planTherapy = PlanTherapy::findOne($appointment->plan_therapy_id);
            return $planTherapy->setting;
        }else{
            $setting_ambulatoriale = Setting::find()->where(['nome' => 'Ambulatoriale'])->one();
            return $setting_ambulatoriale;
        }
    }
}