<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "projects".
 *
 * @property int $id
 * @property string $name
 * @property int|null $type
 * @property float|null $net_worth
 * @property float|null $roi
 * @property string|null $comment
 * @property int|null $status
 * @property int|null $created_by
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User[] $employees
 * @property User $creator
 * @property Task[] $tasks
 * @property ProjectsDocuments[] $documents
 */
class Project extends \yii\db\ActiveRecord
{
    const TYPE_TYPE1 = 1;
    const TYPE_TYPE2 = 2;
    const TYPE_TYPE3 = 3;

    const STATUS_PLANNING = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_ON_HOLD = 3;
    const STATUS_COMPLETED = 4;
    const STATUS_CANCELLED = 5;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'projects';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['type', 'status', 'created_by', 'created_at', 'updated_at'], 'integer'],
            [['net_worth', 'roi'], 'number'],
            [['name'], 'string', 'max' => 255],
            [['comment'], 'string', 'max' => 5000],
            [['type'], 'in', 'range' => array_keys(self::getTypeList())],
            [['status'], 'in', 'range' => array_keys(self::getStatusList())],
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
            'name' => 'Project Name',
            'type' => 'Type',
            'net_worth' => 'Net Worth',
            'roi' => 'ROI (%)',
            'comment' => 'Comment',
            'status' => 'Status',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return array
     */
    public static function getTypeList()
    {
        return [
            self::TYPE_TYPE1 => 'Type 1',
            self::TYPE_TYPE2 => 'Type 2',
            self::TYPE_TYPE3 => 'Type 3',
        ];
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

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                if ($this->company_id === null) {
                    $this->company_id = \Yii::$app->params['company_id'] ?? null;
                }
                if ($this->company_id === null || $this->company_id < 1) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        $typeList = self::getTypeList();
        return isset($typeList[$this->type]) ? $typeList[$this->type] : 'Unknown';
    }

    /**
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_PLANNING => 'Planning',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * @return string
     */
    public function getStatusName()
    {
        $statusList = self::getStatusList();
        return isset($statusList[$this->status]) ? $statusList[$this->status] : 'Unknown';
    }

    /**
     * @return string|null
     */
    public function getEmployeeNames(): string
    {
        $names = [];
        foreach ($this->employees as $employee) {
            $names[] = trim($employee->first_name . ' ' . $employee->last_name);
        }
        return implode('<br>', $names);
    }

    /**
     * @return string|null
     */
    public function getCreatorName()
    {
        if ($this->created_by && $this->creator) {
            return trim($this->creator->first_name . ' ' . $this->creator->last_name);
        }
        return null;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProjectEmployees()
    {
        return $this->hasMany(ProjectEmployee::class, ['project_id' => 'id']);
    }

    public function getEmployees()
    {
        return $this->hasMany(User::class, ['id' => 'employee_id'])
            ->viaTable('project_employee', ['project_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTasks()
    {
        return $this->hasMany(Task::class, ['project_id' => 'id']);
    }

    public function getDocuments()
    {
        return $this->hasMany(ProjectsDocuments::class, ['project_id' => 'id']);
    }

    public static function getProjectsDropdownList(): array
    {
        $projects = self::find()
            ->select(['name', 'id'])
            ->orderBy(['name' => SORT_ASC])
            ->asArray()
            ->all();

        return \yii\helpers\ArrayHelper::map($projects, 'id', 'name');
    }

    public static function findByEmployee(int $employeeId)
    {
        return static::find()
            ->innerJoin('project_employee pe', 'pe.project_id = projects.id')
            ->andWhere(['pe.employee_id' => $employeeId]);
    }
}