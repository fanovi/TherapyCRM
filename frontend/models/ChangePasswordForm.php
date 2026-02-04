<?php

namespace frontend\models;

use common\models\User;
use yii\base\Model;
use Yii;

/**
 * Form per il cambio password quando l'utente è già loggato
 */
class ChangePasswordForm extends Model
{
    public $current_password;
    public $password;
    public $password_repeat;

    /** @var \common\models\User */
    private $_user;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->_user = Yii::$app->user->identity;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['current_password', 'password', 'password_repeat'], 'required'],
            ['current_password', 'validateCurrentPassword'],
            ['password', 'string', 'min' => Yii::$app->params['user.passwordMinLength'], 'max' => Yii::$app->params['user.passwordMaxLength']],
            ['password', 'match', 'pattern' => '/^.*(?=.*\d)(?=\S*[\W])(?=.*[a-z])(?=.*[A-Z]).*$/', 'message' => 'La password deve essere compresa tra 8 e 20 caratteri tra cui: un carattere maiuscolo, un carattere minuscolo, un numero ed un carattere speciale'],
            ['password_repeat', 'required'],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'message' => 'Le password non coincidono.'],
        ];
    }

    /**
     * Valida la password corrente
     */
    public function validateCurrentPassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (!$this->_user || !$this->_user->validatePassword($this->current_password)) {
                $this->addError($attribute, 'La password corrente non è corretta.');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'current_password' => 'Password Corrente',
            'password' => 'Nuova Password',
            'password_repeat' => 'Conferma Nuova Password',
        ];
    }

    /**
     * Cambia la password dell'utente
     *
     * @return bool se la password è stata cambiata con successo
     */
    public function changePassword()
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->_user;
        $user->setPassword($this->password);
        $user->generateAuthKey();

        return $user->save(false);
    }
}
