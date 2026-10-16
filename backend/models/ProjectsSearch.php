<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Project;

/**
 * ProjectsSearch represents the model behind the search form of `common\models\Project`.
 */
class ProjectsSearch extends Project
{
    public $employee_filter;
    public $employee_id;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'type', 'status', 'created_by', 'created_at', 'updated_at','employee_filter'], 'integer'],
            [['name', 'comment'], 'safe'],
            [['net_worth', 'roi'], 'number'],
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
        $query = Project::find();

        if ($this->employee_id) {
            $query->innerJoin('project_employee pe_personal', 'pe_personal.project_id = projects.id')
                ->andWhere(['pe_personal.employee_id' => $this->employee_id]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Grid filtering conditions
        $query->andFilterWhere([
            'projects.id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'net_worth' => $this->net_worth,
            'roi' => $this->roi,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'comment', $this->comment]);

        if ($this->employee_filter) {
            $query->innerJoin('project_employee pe_filter', 'pe_filter.project_id = projects.id')
                ->andWhere(['pe_filter.employee_id' => $this->employee_filter]);
        }

        return $dataProvider;
    }
}