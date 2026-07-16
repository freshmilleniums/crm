<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use backend\models\User;

/**
 * This is the model class for table "call_attempts".
 *
 * @property int $id
 * @property int $user_id Courier ID
 * @property string $context Interview or contract call context
 * @property int $created_at Timestamp when attempt was made
 * @property int $created_by Call center operator ID
 *
 * @property User $user
 * @property User $operator
 */
class CallAttempt extends ActiveRecord
{
    const CONTEXT_INTERVIEW = 'interview';
    const CONTEXT_CONTRACT = 'contract';

    public static function tableName()
    {
        return 'call_attempts';
    }

    public function rules()
    {
        return [
            [['user_id', 'context', 'created_at', 'created_by'], 'required'],
            [['user_id', 'created_at', 'created_by'], 'integer'],
            [['context'], 'in', 'range' => [self::CONTEXT_INTERVIEW, self::CONTEXT_CONTRACT]],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'context' => 'Context',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
        ];
    }

    /**
     * Gets query for [[User]]
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Gets query for [[Operator]]
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Get total count of attempts for user in specific context
     *
     * @param int $userId
     * @param string $context
     * @return int
     */
    public static function getAttemptCount($userId, $context)
    {
        return self::find()
            ->where(['user_id' => $userId, 'context' => $context])
            ->count();
    }

    /**
     * Get last attempt for user in specific context
     *
     * @param int $userId
     * @param string $context
     * @return CallAttempt|null
     */
    public static function getLastAttempt($userId, $context)
    {
        return self::find()
            ->where(['user_id' => $userId, 'context' => $context])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();
    }

    /**
     * Get full history of attempts for user in specific context
     *
     * @param int $userId
     * @param string $context
     * @return CallAttempt[]
     */
    public static function getAttemptHistory($userId, $context)
    {
        return self::find()
            ->where(['user_id' => $userId, 'context' => $context])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
    }

    /**
     * Delete all attempts for user (called when user moves between tabs)
     *
     * @param int $userId
     * @return int Number of rows deleted
     */
    public static function deleteAllAttempts($userId)
    {
        return self::deleteAll(['user_id' => $userId]);
    }

    /**
     * Get available context options
     *
     * @return array
     */
    public static function getContextOptions()
    {
        return [
            self::CONTEXT_INTERVIEW => 'Interview',
            self::CONTEXT_CONTRACT => 'Contract',
        ];
    }
}