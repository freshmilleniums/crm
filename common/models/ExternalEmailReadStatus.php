<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use backend\models\User;

/**
 * ExternalEmailReadStatus model
 *
 * @property int $id
 * @property int $message_id
 * @property int $user_id
 * @property int $is_read
 * @property int $is_flagged
 * @property int $read_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property ExternalEmailMessage $message
 * @property User $user
 */
class ExternalEmailReadStatus extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%external_email_read_status}}';
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
            [['message_id', 'user_id'], 'required'],
            [['message_id', 'user_id', 'read_at'], 'integer'],
            [['is_read', 'is_flagged'], 'boolean'],
            [['is_read', 'is_flagged'], 'default', 'value' => 0],

            [['message_id'], 'exist', 'skipOnError' => true, 'targetClass' => ExternalEmailMessage::class, 'targetAttribute' => ['message_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],

            [['message_id', 'user_id'], 'unique', 'targetAttribute' => ['message_id', 'user_id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message_id' => 'Message',
            'user_id' => 'User',
            'is_read' => 'Read',
            'is_flagged' => 'Flagged',
            'read_at' => 'Read At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && $this->is_read && !$this->read_at) {
            $this->read_at = time();
        }

        if (!$insert && $this->isAttributeChanged('is_read') && $this->is_read) {
            $this->read_at = time();
        }

        return true;
    }

    public function getMessage()
    {
        return $this->hasOne(ExternalEmailMessage::class, ['id' => 'message_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public static function getUnreadCount($userId)
    {
        return self::find()
            ->where(['user_id' => $userId, 'is_read' => 0])
            ->count();
    }

    public static function markAsRead($messageId, $userId)
    {
        $status = self::findOne(['message_id' => $messageId, 'user_id' => $userId]);

        if (!$status) {
            $status = new self();
            $status->message_id = $messageId;
            $status->user_id = $userId;
        }

        if (!$status->is_read) {
            $status->is_read = 1;
            $status->read_at = time();
            return $status->save(false);
        }

        return true;
    }

    public static function markAsUnread($messageId, $userId)
    {
        $status = self::findOne(['message_id' => $messageId, 'user_id' => $userId]);

        if (!$status) {
            $status = new self();
            $status->message_id = $messageId;
            $status->user_id = $userId;
            $status->is_read = 0;
            return $status->save(false);
        }

        if ($status->is_read) {
            $status->is_read = 0;
            $status->read_at = null;
            return $status->save(false);
        }

        return true;
    }

    public static function markAsFlagged($messageId, $userId)
    {
        $status = self::findOne(['message_id' => $messageId, 'user_id' => $userId]);

        if (!$status) {
            $status = new self();
            $status->message_id = $messageId;
            $status->user_id = $userId;
        }

        if (!$status->is_flagged) {
            $status->is_flagged = 1;
            return $status->save(false);
        }

        return true;
    }

    public static function markAsUnflagged($messageId, $userId)
    {
        $status = self::findOne(['message_id' => $messageId, 'user_id' => $userId]);

        if ($status && $status->is_flagged) {
            $status->is_flagged = 0;
            return $status->save(false);
        }

        return true;
    }
}