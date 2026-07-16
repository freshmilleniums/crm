<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "chats".
 *
 * @property string $id
 * @property string $type
 * @property string|null $title
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $last_message_at
 * @property int|null $employee_id
 *
 * Relations:
 * @property User|null $employee
 * @property ChatParticipant[] $chatParticipants
 * @property ChatMessage[] $messages
 * @property User[] $participants
 */
class Chat extends ActiveRecord
{
    const TYPE_EMPLOYEE = 'employee';
    const TYPE_USER_PRIVATE = 'user_private';
// const TYPE_USER_GROUP = 'user_group';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chats';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type'], 'required'],
            [['created_at', 'updated_at', 'last_message_at', 'employee_id'], 'integer'],
            [['id'], 'string', 'max' => 36],
            [['id'], 'default', 'value' => function() { return $this->generateUuidV4(); }],
            [['created_at', 'updated_at'], 'default', 'value' => time()],
            [['type'], 'in', 'range' => [self::TYPE_EMPLOYEE, self::TYPE_USER_PRIVATE]],
            [['title'], 'string', 'max' => 255],
            [['title', 'last_message_at', 'employee_id'], 'default', 'value' => null],
            [['id'], 'unique'],
            [['employee_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['employee_id' => 'id']],
            [['company_id'], 'integer'],
            [['company_id'], 'default', 'value' => function() {
                return \Yii::$app->params['company_id'] ?? null;
            }],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'title' => 'Title',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'last_message_at' => 'Last Message At',
            'employee_id' => 'Employee ID',
        ];
    }

    /**
     * Get all chat type labels
     * @return array
     */
    public static function getTypeLabels()
    {
        return [
            self::TYPE_EMPLOYEE => 'Employee Chat',
            self::TYPE_USER_PRIVATE => 'Private Chat',
        ];
    }

    /**
     * Get current type label
     * @return string
     */
    public function getTypeLabel()
    {
        $labels = self::getTypeLabels();
        return $labels[$this->type] ?? 'Unknown';
    }

    /**
     * Get default chat title
     */
    public static function getDefaultChatTitle($chat)
    {
        switch ($chat->type) {
            case self::TYPE_EMPLOYEE:
                return $chat->employee ? 'Support: ' . trim($chat->employee->first_name . ' ' . $chat->employee->last_name) : 'Support Chat';

            case self::TYPE_USER_PRIVATE:
                // Check if this is a chat with administrator
                $authManager = Yii::$app->authManager;
                foreach ($chat->activeParticipants as $participant) {
                    if ($participant->id != Yii::$app->user->id) {
                        // Check if this participant is an admin
                        $roles = $authManager->getRolesByUser($participant->id);
                        $isAdmin = isset($roles['administrator']) || isset($roles['super-administrator']);

                        if ($isAdmin) {
                            return 'Administrator';
                        } else {
                            return trim($participant->first_name . ' ' . $participant->last_name);
                        }
                    }
                }
                return 'Private Chat';

            /* case self::TYPE_EMPLOYEE_GROUP: // Disabled
                return $chat->title ?: 'Group Chat'; */

            default:
                return 'Chat';
        }
    }

    /**
     * Get type options for dropdowns
     * @return array
     */
    public static function getTypeOptions()
    {
        return self::getTypeLabels();
    }

    /**
     * Gets query for [[Employee]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEmployee()
    {
        return $this->hasOne(User::class, ['id' => 'employee_id']);
    }

    /**
     * Gets query for [[ChatParticipants]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChatParticipants()
    {
        return $this->hasMany(ChatParticipant::class, ['chat_id' => 'id']);
    }

    /**
     * Gets query for [[Messages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMessages()
    {
        return $this->hasMany(ChatMessage::class, ['chat_id' => 'id']);
    }

    /**
     * Gets query for [[Participants]] through chat_participants table.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParticipants()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->viaTable('chat_participants', ['chat_id' => 'id']);
    }

    /**
     * Gets active participants (not left the chat)
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActiveParticipants()
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->viaTable('chat_participants', ['chat_id' => 'id'], function ($query) {
                $query->andWhere(['left_at' => null]);
            });
    }

    /**
     * Get last message
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLastMessage()
    {
        return $this->hasOne(ChatMessage::class, ['chat_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        $this->updated_at = time();

        if ($insert) {
            if ($this->company_id === null) {
                $this->company_id = \Yii::$app->params['company_id'] ?? null;
            }

            // Prevent saving if company_id is not valid
            if ($this->company_id === null || $this->company_id < 1) {
                return false;
            }
        }

        return parent::beforeSave($insert);
    }

    public static function find()
    {
        $query = parent::find();
        $companyId = \Yii::$app->params['company_id'] ?? null;

        if ($companyId === null || $companyId < 1) {
            $query->where('1=0');
            return $query;
        }

        $query->andWhere(['company_id' => $companyId]);
        return $query;
    }

    public function generateUuidV4() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Get chat icon based on type
     */
    public static function getChatIcon($type)
    {
        switch ($type) {
            case self::TYPE_EMPLOYEE:
                return 'headset';
            case self::TYPE_USER_PRIVATE:
                return 'user';
            /*case self::TYPE_EMPLOYEE_GROUP:
                return 'users';*/
            default:
                return 'comment';
        }
    }

}