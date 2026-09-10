<?php

namespace frontend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Holiday;

/**
 * HolidaySearch represents the model behind the search form of `common\models\Holiday`.
 */
class HolidaySearch extends Holiday
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['kind'], 'in', 'range' => array_keys(Holiday::getKindOptions())],
            [['month', 'year'], 'integer'],
            [['is_active'], 'boolean'],
            [['name'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Holiday::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['occurrence' => SORT_ASC],
                'attributes' => [
                    'name',
                    // Ordine di default: ricorrenti prima (year = 0), poi le una tantum per data
                    'occurrence' => [
                        'asc' => ['year' => SORT_ASC, 'month' => SORT_ASC, 'day' => SORT_ASC, 'rule' => SORT_ASC],
                        'desc' => ['year' => SORT_DESC, 'month' => SORT_DESC, 'day' => SORT_DESC, 'rule' => SORT_DESC],
                    ],
                ],
            ],
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'name', $this->name]);
        $query->andFilterWhere(['month' => $this->month]);
        $query->andFilterWhere(['is_active' => $this->is_active]);

        // Anno: le chiusure valide in quell'anno, cioe' le ricorrenti piu' le una tantum dell'anno
        if ($this->year !== null && $this->year !== '') {
            $query->andWhere(['year' => [0, (int)$this->year]]);
        }

        switch ($this->kind) {
            case Holiday::KIND_RECURRING:
                $query->andWhere(['rule' => Holiday::RULE_NONE, 'year' => 0]);
                break;
            case Holiday::KIND_ONE_OFF:
                $query->andWhere(['rule' => Holiday::RULE_NONE])->andWhere(['>', 'year', 0]);
                break;
            case Holiday::KIND_COMPUTED:
                $query->andWhere(['rule' => array_keys(Holiday::getComputedRuleOptions())]);
                break;
            case Holiday::KIND_WEEKDAY:
                $query->andWhere(['rule' => Holiday::RULE_WEEKDAY]);
                break;
        }

        return $dataProvider;
    }
}
