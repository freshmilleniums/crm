<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "logs_action".
 *
 * @property int $id
 * @property int $user_id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $action
 * @property int $created_at
 *
 * @property User $user
 * @property ActionLogDetails $details
 */
class ActionLog extends ActiveRecord
{
    // Entity types
    const ENTITY_TASK = 'tasks';
    const ENTITY_PROJECT = 'projects';
    const ENTITY_USER = 'users';
    const ENTITY_INVESTOR = 'investors';
    const ENTITY_TEMPLATE = 'templates';
    const ENTITY_EMAIL = 'emails';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'logs_action';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'entity_type', 'entity_id', 'action'], 'required'],
            [['user_id', 'entity_id', 'created_at'], 'integer'],
            [['entity_type'], 'string', 'max' => 50],
            [['action'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User',
            'entity_type' => 'Entity Type',
            'entity_id' => 'Entity ID',
            'action' => 'Action',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Get user relation
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Get details relation
     * @return \yii\db\ActiveQuery
     */
    public function getDetails()
    {
        return $this->hasOne(ActionLogDetails::class, ['log_id' => 'id']);
    }

    /**
     * Get human-readable action name
     * Default: everything except 'create' and 'delete' shows as 'Updated'
     * @return string
     */
    public function getActionName(): string
    {
        $actionsList = [
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
        ];

        if (isset($actionsList[$this->action])) {
            return $actionsList[$this->action];
        }

        return 'Updated';
    }

    /**
     * Get human-readable entity type
     * @return string
     */
    public function getEntityTypeName(): string
    {
        $entityNames = [
            self::ENTITY_TASK => 'Task',
            self::ENTITY_PROJECT => 'Project',
            self::ENTITY_USER => 'User',
            self::ENTITY_INVESTOR => 'Investor',
            self::ENTITY_TEMPLATE => 'Template',
            self::ENTITY_EMAIL => 'Email',
        ];

        return $entityNames[$this->entity_type] ?? ucfirst($this->entity_type);
    }

    /**
     * Get entity name (e.g., task title, project name)
     * @return string|null
     */
    public function getEntityName(): ?string
    {
        switch ($this->entity_type) {
            case self::ENTITY_TASK:
                $entity = Task::findOne($this->entity_id);
                return $entity ? $entity->title : null;

            case self::ENTITY_PROJECT:
                $entity = Project::findOne($this->entity_id);
                return $entity ? $entity->name : null;

            case self::ENTITY_USER:
                $entity = User::findOne($this->entity_id);
                return $entity ? $entity->getFullName() : null;

            case self::ENTITY_INVESTOR:
                $entity = Investor::findOne($this->entity_id);
                return $entity ? $entity->getFullName() : null;

            case self::ENTITY_TEMPLATE:
                $entity = Template::findOne($this->entity_id);
                return $entity ? $entity->title : null;

            default:
                return null;
        }
    }

    /**
     * Get attribute labels for current entity type
     * Used in formatted difference display
     * @return array
     */
    public function getEntityAttributeLabels(): array
    {
        switch ($this->entity_type) {
            case self::ENTITY_TASK:
                return (new \common\models\Task())->attributeLabels();

            case self::ENTITY_PROJECT:
                return (new \common\models\Project())->attributeLabels();

            case self::ENTITY_INVESTOR:
                return (new \common\models\Investor())->attributeLabels();

            case self::ENTITY_TEMPLATE:
                return (new \common\models\Template())->attributeLabels();

            case self::ENTITY_USER:
                return (new \backend\models\User())->attributeLabels();

            default:
                return [];
        }
    }
}