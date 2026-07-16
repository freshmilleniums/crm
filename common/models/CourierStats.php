<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "courier_stats".
 *
 * @property int $id
 * @property string $date
 * @property int $new_couriers
 * @property int $passed_test
 * @property int $interviewed
 * @property int $signed_contract
 * @property int $workers
 * @property int $removed_by_reminders
 */
class CourierStats extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'courier_stats';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date'], 'required'],
            [['date'], 'date', 'format' => 'php:Y-m-d'],
            [['date'], 'unique'],
            [['new_couriers', 'passed_test', 'interviewed', 'signed_contract', 'workers', 'removed_by_reminders'], 'integer', 'min' => 0],
            [['new_couriers', 'passed_test', 'interviewed', 'signed_contract', 'workers', 'removed_by_reminders'], 'default', 'value' => 0],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'new_couriers' => 'New Couriers',
            'passed_test' => 'Passed Test',
            'interviewed' => 'Interviewed',
            'signed_contract' => 'Signed Contract',
            'workers' => 'Workers',
            'removed_by_reminders' => 'Removed by Reminders',
        ];
    }

    /**
     * Get formatted date for display
     * @return string
     */
    public function getFormattedDate()
    {
        return date('M d, Y', strtotime($this->date));
    }

    /**
     * Get statistics for a specific date
     * @param string $date Date in Y-m-d format
     * @return CourierStats|null
     */
    public static function getStatsForDate($date)
    {
        return self::find()->where(['date' => $date])->one();
    }

    /**
     * Get statistics for today
     * @return CourierStats|null
     */
    public static function getTodayStats()
    {
        return self::getStatsForDate(date('Y-m-d'));
    }

    /**
     * Get statistics for yesterday
     * @return CourierStats|null
     */
    public static function getYesterdayStats()
    {
        return self::getStatsForDate(date('Y-m-d', strtotime('-1 day')));
    }

    /**
     * Increment specific field for today
     * @param string $field Field name to increment
     * @param int $amount Amount to increment (default 1)
     * @return bool
     */
    public static function incrementTodayStats($field, $amount = 1)
    {
        $today = date('Y-m-d');
        $stats = self::find()->where(['date' => $today])->one();

        if (!$stats) {
            $stats = new self();
            $stats->date = $today;
        }

        if ($stats->hasAttribute($field)) {
            $stats->$field += $amount;
            return $stats->save();
        }

        return false;
    }
}