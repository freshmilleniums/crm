<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\CorporateEmailMessage;

class CorporateEmailMessageSearch extends Model
{
    public $corporate_email_id;
    public $filter;
    public $from_email;
    public $subject;
    public $date_from;
    public $date_to;
    public $is_read;

    public function rules()
    {
        return [
            [['corporate_email_id'], 'integer'],
            [['filter', 'from_email', 'subject'], 'string'],
            [['date_from', 'date_to'], 'safe'],
            [['is_read'], 'boolean'],
        ];
    }

    public function search($params)
    {
        $query = CorporateEmailMessage::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'received_at' => SORT_DESC,
                ],
                'attributes' => [
                    'received_at',
                    'from_email',
                    'subject',
                    'is_read',
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->corporate_email_id) {
            $query->andWhere(['corporate_email_id' => $this->corporate_email_id]);
        }

        $this->applyFilter($query);

        if ($this->from_email) {
            $query->andWhere(['like', 'from_email', $this->from_email]);
        }

        if ($this->subject) {
            $query->andWhere(['like', 'subject', $this->subject]);
        }

        if ($this->date_from) {
            $dateFrom = strtotime($this->date_from);
            if ($dateFrom) {
                $query->andWhere(['>=', 'received_at', $dateFrom]);
            }
        }

        if ($this->date_to) {
            $dateTo = strtotime($this->date_to . ' 23:59:59');
            if ($dateTo) {
                $query->andWhere(['<=', 'received_at', $dateTo]);
            }
        }

        if ($this->is_read !== null && $this->is_read !== '') {
            $query->andWhere(['is_read' => $this->is_read]);
        }

        return $dataProvider;
    }

    private function applyFilter($query)
    {
        switch ($this->filter) {
            case 'inbox':
                $query->andWhere(['folder' => 'INBOX']);
                break;

            case 'sent':
                $query->andWhere(['folder' => 'Sent']);
                break;

            case 'unread':
                $query->andWhere(['is_read' => 0]);
                break;

            case 'archive':
                $query->andWhere(['folder' => 'Archive']);
                break;

            case 'all':
            default:
                break;
        }
    }
}