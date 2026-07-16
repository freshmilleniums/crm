<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "investor_employee".
 *
 * @property int $id
 * @property int $investor_id
 * @property int $employee_id
 * @property int|null $assigned_by
 * @property int|null $assigned_at
 *
 * @property Investor $investor
 * @property User $employee
 * @property User $assignedBy
 */
class InvestorEmployee extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'investor_employee';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['investor_id', 'employee_id'], 'required'],
            [['investor_id', 'employee_id', 'assigned_by', 'assigned_at'], 'integer'],
            [['investor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Investor::class, 'targetAttribute' => ['investor_id' => 'id']],
            [['employee_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['employee_id' => 'id']],
            [['assigned_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['assigned_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'investor_id' => 'Investor ID',
            'employee_id' => 'Employee ID',
            'assigned_by' => 'Assigned By',
            'assigned_at' => 'Assigned At',
        ];
    }

    /**
     * Gets query for [[Investor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvestor()
    {
        return $this->hasOne(Investor::class, ['id' => 'investor_id']);
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
     * Gets query for [[AssignedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssignedBy()
    {
        return $this->hasOne(User::class, ['id' => 'assigned_by']);
    }
}