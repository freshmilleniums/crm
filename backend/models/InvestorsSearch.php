<?php

namespace backend\models;

use yii\data\ActiveDataProvider;
use common\models\Investor;
use yii\db\Expression;

/**
 * InvestorsSearch represents the model behind the search form of `common\models\Investor`.
 */
class InvestorsSearch extends Investor
{
    public $employee_filter;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'investor_type', 'created_by', 'created_at', 'updated_at', 'employee_filter'], 'integer'],
            [['first_name', 'last_name', 'email', 'address', 'comment'], 'safe'],
            [['net_value'], 'number'],
        ];
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
        $query = Investor::find()
            ->select([
                'investors.*',
                'employees_count' => new Expression(
                    '(SELECT COUNT(*) FROM investor_employee WHERE investor_employee.investor_id = investors.id)'
                )
            ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ]
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'investors.id' => $this->id,
            'investors.net_value' => $this->net_value,
            'investors.investor_type' => $this->investor_type,
            'investors.created_by' => $this->created_by,
            'investors.created_at' => $this->created_at,
            'investors.updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'investors.first_name', $this->first_name])
            ->andFilterWhere(['like', 'investors.last_name', $this->last_name])
            ->andFilterWhere(['like', 'investors.email', $this->email])
            ->andFilterWhere(['like', 'investors.address', $this->address])
            ->andFilterWhere(['like', 'investors.comment', $this->comment]);

        if ($this->employee_filter) {
            $query->joinWith(['investorEmployees'])
                ->andWhere(['investor_employee.employee_id' => $this->employee_filter]);
        }

        return $dataProvider;
    }
}