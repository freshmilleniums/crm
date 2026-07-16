<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\EmailAccount;

class EmailAccountSearch extends EmailAccount
{
    public function rules()
    {
        return [
            [['id', 'imap_port', 'smtp_port', 'imap_encryption', 'smtp_encryption', 'is_corporate', 'is_active'], 'integer'],
            [['email', 'label', 'username', 'imap_host', 'smtp_host'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = EmailAccount::find();

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
            'id' => $this->id,
            'imap_port' => $this->imap_port,
            'smtp_port' => $this->smtp_port,
            'imap_encryption' => $this->imap_encryption,
            'smtp_encryption' => $this->smtp_encryption,
            'is_corporate' => $this->is_corporate,
            'is_active' => $this->is_active,
        ]);

        $query->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'label', $this->label])
            ->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'imap_host', $this->imap_host])
            ->andFilterWhere(['like', 'smtp_host', $this->smtp_host]);

        return $dataProvider;
    }
}