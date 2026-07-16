<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\ActionLog;

/**
 * ActionLogSearch represents the model behind the search form for `common\models\ActionLog`.
 */
class ActionLogSearch extends ActionLog
{
    public $userName;
    public $dateFrom;
    public $dateTo;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'entity_id'], 'integer'],
            [['entity_type', 'action', 'userName'], 'safe'],
            [['dateFrom', 'dateTo'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Get action filter list for dropdown
     * @return array
     */
    public static function getActionFilterList()
    {
        return [
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = ActionLog::find()
            ->alias('log')
            ->joinWith(['user', 'details']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ],
                'attributes' => [
                    'id',
                    'user_id',
                    'entity_type',
                    'entity_id',
                    'action',
                    'created_at',
                    'userName' => [
                        'asc' => ['user.first_name' => SORT_ASC, 'user.last_name' => SORT_ASC],
                        'desc' => ['user.first_name' => SORT_DESC, 'user.last_name' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Filter by basic fields
        $query->andFilterWhere([
            'log.id' => $this->id,
            'log.user_id' => $this->user_id,
            'log.entity_type' => $this->entity_type,
            'log.entity_id' => $this->entity_id,
        ]);

        // Filter by action with special logic
        if (!empty($this->action)) {
            if ($this->action === 'create') {
                $query->andWhere(['log.action' => 'create']);
            } elseif ($this->action === 'delete') {
                $query->andWhere(['log.action' => 'delete']);
            } elseif ($this->action === 'update') {
                // Update = everything except create and delete
                $query->andWhere(['not in', 'log.action', ['create', 'delete']]);
            }
        }

        // Filter by user name
        if (!empty($this->userName)) {
            $query->andWhere([
                'or',
                ['like', 'user.first_name', $this->userName],
                ['like', 'user.last_name', $this->userName],
                ['like', "CONCAT(user.first_name, ' ', user.last_name)", $this->userName],
            ]);
        }

        // Filter by date range
        if (!empty($this->dateFrom)) {
            $dateFromTimestamp = strtotime($this->dateFrom . ' 00:00:00');
            if ($dateFromTimestamp !== false) {
                $query->andFilterWhere(['>=', 'log.created_at', $dateFromTimestamp]);
            }
        }

        if (!empty($this->dateTo)) {
            $dateToTimestamp = strtotime($this->dateTo . ' 23:59:59');
            if ($dateToTimestamp !== false) {
                $query->andFilterWhere(['<=', 'log.created_at', $dateToTimestamp]);
            }
        }

        return $dataProvider;
    }
}