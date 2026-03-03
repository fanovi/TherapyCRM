<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * Model per la tabella permission_metadata.
 * Traccia quali permessi sono attivi (controllati nel codice) e quali sono orfani.
 *
 * @property string $permission_name
 * @property int $is_active
 * @property string|null $notes
 *
 * @property AuthItem $authItem
 */
class PermissionMetadata extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%permission_metadata}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['permission_name'], 'required'],
            [['permission_name'], 'string', 'max' => 64],
            [['is_active'], 'integer'],
            [['notes'], 'string'],
            [['permission_name'], 'exist', 'targetClass' => AuthItem::class, 'targetAttribute' => 'name'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'permission_name' => 'Nome Permesso',
            'is_active' => 'Attivo',
            'notes' => 'Note',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthItem()
    {
        return $this->hasOne(AuthItem::class, ['name' => 'permission_name']);
    }

    /**
     * Restituisce l'array dei nomi dei permessi inattivi (orfani).
     *
     * @return string[]
     */
    public static function getInactivePermissionNames()
    {
        return static::find()
            ->select('permission_name')
            ->where(['is_active' => 0])
            ->column();
    }
}
