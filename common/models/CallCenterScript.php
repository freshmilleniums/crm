<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property int $company_id
 * @property string $title
 * @property string $content
 * @property int $sort_order
 * @property int $is_active
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_at
 *
 * @property \backend\models\User $createdBy
 */
class CallCenterScript extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return '{{%call_center_scripts}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['title', 'content'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['content'], 'string'],
            [['sort_order', 'created_by', 'company_id'], 'integer'],
            [['is_active'], 'boolean'],
            [['is_active'], 'default', 'value' => 1],
            [['sort_order'], 'default', 'value' => 0],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'company_id' => 'Company',
            'title'      => 'Title',
            'content'    => 'Text',
            'sort_order' => 'Sort Order',
            'is_active'  => 'Active',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert) {
            $this->created_by = Yii::$app->user->id;
            $this->company_id = Yii::$app->params['company_id'] ?? 0;

            if (!$this->sort_order) {
                $max = static::find()
                    ->where(['company_id' => $this->company_id])
                    ->max('sort_order');
                $this->sort_order = ($max ?? 0) + 1;
            }
        }

        return true;
    }

    public function getCreatedBy()
    {
        return $this->hasOne(\backend\models\User::class, ['id' => 'created_by']);
    }

    /**
     * Get active scripts for current company ordered by sort
     */
    public static function getActiveForCompany(int $companyId): array
    {
        return static::find()
            ->where(['company_id' => $companyId, 'is_active' => 1])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();
    }

    /**
     * Update sort order from drag-and-drop array of ids
     */
    public static function updateSortOrder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            static::updateAll(['sort_order' => $index + 1], ['id' => (int)$id]);
        }
    }
}