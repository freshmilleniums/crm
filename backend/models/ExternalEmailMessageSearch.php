<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\ExternalEmailMessage;

class ExternalEmailMessageSearch extends Model
{
    public $email_account_id;
    public $filter;
    public $from_email;
    public $subject;
    public $date_from;
    public $date_to;

    public function rules()
    {
        return [
            [['email_account_id'], 'integer'],
            [['filter', 'from_email', 'subject'], 'string'],
            [['date_from', 'date_to'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = ExternalEmailMessage::find();

        $needsAlias = in_array($this->filter ?? 'all', ['unread', 'flagged']);

        if ($needsAlias) {
            $query->alias('m');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'received_at' => SORT_DESC,
                ],
                'attributes' => $needsAlias ? [
                    'received_at' => [
                        'asc' => ['m.received_at' => SORT_ASC],
                        'desc' => ['m.received_at' => SORT_DESC],
                    ],
                    'from_email' => [
                        'asc' => ['m.from_email' => SORT_ASC],
                        'desc' => ['m.from_email' => SORT_DESC],
                    ],
                    'subject' => [
                        'asc' => ['m.subject' => SORT_ASC],
                        'desc' => ['m.subject' => SORT_DESC],
                    ],
                ] : [
                    'received_at',
                    'from_email',
                    'subject',
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->email_account_id) {
            $fieldName = $needsAlias ? 'm.email_account_id' : 'email_account_id';
            $query->andWhere([$fieldName => $this->email_account_id]);
        }

        $this->applyFilter($query, $needsAlias);

        $fromField = $needsAlias ? 'm.from_email' : 'from_email';
        $subjectField = $needsAlias ? 'm.subject' : 'subject';
        $receivedField = $needsAlias ? 'm.received_at' : 'received_at';

        if ($this->from_email) {
            $query->andWhere(['like', $fromField, $this->from_email]);
        }

        if ($this->subject) {
            $query->andWhere(['like', $subjectField, $this->subject]);
        }

        if ($this->date_from) {
            $dateFrom = strtotime($this->date_from);
            if ($dateFrom) {
                $query->andWhere(['>=', $receivedField, $dateFrom]);
            }
        }

        if ($this->date_to) {
            $dateTo = strtotime($this->date_to . ' 23:59:59');
            if ($dateTo) {
                $query->andWhere(['<=', $receivedField, $dateTo]);
            }
        }

        return $dataProvider;
    }

    private function applyFilter($query, $needsAlias)
    {
        $userId = Yii::$app->user->id;
        $folderField = $needsAlias ? 'm.folder' : 'folder';

        switch ($this->filter) {
            case 'inbox':
                $query->andWhere([$folderField => 'INBOX']);
                break;

            case 'sent':
                $query->andWhere([$folderField => 'Sent']);
                break;

            case 'unread':
                $query->leftJoin(
                    'external_email_read_status rs',
                    'rs.message_id = m.id AND rs.user_id = :userId',
                    [':userId' => $userId]
                );
                $query->andWhere(['or',
                    ['rs.is_read' => 0],
                    ['rs.is_read' => null],
                ]);
                break;

            case 'flagged':
                $query->innerJoin(
                    'external_email_read_status rs',
                    'rs.message_id = m.id'
                );
                $query->andWhere([
                    'rs.user_id' => $userId,
                    'rs.is_flagged' => 1,
                ]);
                break;

            case 'archive':
                $query->andWhere([$folderField => 'Archive']);
                break;

            case 'all':
            default:
                break;
        }
    }
}