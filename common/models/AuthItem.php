<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * AuthItem model
 *
 * @property string $name
 * @property integer $type
 * @property string $description
 * @property string $rule_name
 * @property resource $data
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property AuthAssignment[] $authAssignments
 * @property AuthItemChild[] $authItemChildren
 * @property AuthItemChild[] $authItemChildren0
 * @property AuthItem[] $children
 * @property AuthItem[] $parents
 */
class AuthItem extends ActiveRecord
{
    const TYPE_ROLE = 1;
    const TYPE_PERMISSION = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%auth_item}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'type'], 'required'],
            [['type', 'created_at', 'updated_at'], 'integer'],
            [['description', 'data'], 'string'],
            [['name', 'rule_name'], 'string', 'max' => 64],
            [['type'], 'in', 'range' => [self::TYPE_ROLE, self::TYPE_PERMISSION]],
            [['name'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'name' => 'Nome',
            'type' => 'Tipo',
            'description' => 'Descrizione',
            'rule_name' => 'Regola',
            'data' => 'Dati',
            'created_at' => 'Creato il',
            'updated_at' => 'Aggiornato il',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthAssignments()
    {
        return $this->hasMany(AuthAssignment::class, ['item_name' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthItemChildren()
    {
        return $this->hasMany(AuthItemChild::class, ['parent' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthItemParents()
    {
        return $this->hasMany(AuthItemChild::class, ['child' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(AuthItem::class, ['name' => 'child'])
            ->viaTable('{{%auth_item_child}}', ['parent' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParents()
    {
        return $this->hasMany(AuthItem::class, ['name' => 'parent'])
            ->viaTable('{{%auth_item_child}}', ['child' => 'name']);
    }

    /**
     * Verifica se è un ruolo
     * @return bool
     */
    public function isRole()
    {
        return $this->type == self::TYPE_ROLE;
    }

    /**
     * Verifica se è un permesso
     * @return bool
     */
    public function isPermission()
    {
        return $this->type == self::TYPE_PERMISSION;
    }

    /**
     * Trova tutti i ruoli
     * @return array
     */
    public static function findRoles()
    {
        return static::find()
            ->where(['type' => self::TYPE_ROLE])
            ->all();
    }

    /**
     * Trova tutti i permessi
     * @return array
     */
    public static function findPermissions()
    {
        return static::find()
            ->where(['type' => self::TYPE_PERMISSION])
            ->all();
    }

    /**
     * Trova solo i permessi attivi (esclude quelli orfani tramite permission_metadata).
     * @return \yii\db\ActiveQuery
     */
    public static function findActivePermissions()
    {
        $inactive = PermissionMetadata::find()
            ->select('permission_name')
            ->where(['is_active' => 0]);

        return static::find()
            ->where(['type' => self::TYPE_PERMISSION])
            ->andWhere(['NOT IN', 'name', $inactive])
            ->orderBy(['name' => SORT_ASC]);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => \common\behaviors\ActivityLogBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
                'entityNameCallback' => function($model) {
                    return $this->type == self::TYPE_ROLE ? 'Ruolo' : 'Permesso';
                },
            ],
        ];
    }
} 