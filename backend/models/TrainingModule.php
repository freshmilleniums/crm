<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * TrainingModule model
 *
 * @property int $id
 * @property string $title
 * @property string $content
 * @property int $sort
 * @property int $is_active
 * @property int $passing_score
 * @property int $is_final_task
 * @property string|null $task_title
 * @property string|null $task_subject
 * @property string|null $task_body
 * @property string|null $task_file
 * @property int|null $task_deadline_hours
 * @property int $created_at
 * @property int $updated_at
 */
class TrainingModule extends \yii\db\ActiveRecord
{

    public $file;
    public static function tableName()
    {
        return '{{%training_modules}}';
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
            [['title'], 'required'],
            [['content', 'task_body'], 'string'],
            [['sort', 'is_active', 'passing_score', 'is_final_task', 'task_deadline_hours'], 'integer'],
            [['passing_score'], 'default', 'value' => 80],
            [['is_active'], 'default', 'value' => 1],
            [['sort'], 'default', 'value' => 0],
            [['is_final_task'], 'default', 'value' => 0],
            [['task_title'], 'required', 'when' => function($model) { return (int)$model->is_final_task === 1; },
                'whenClient' => "function(attr, val) { return $('#trainingmodule-is_final_task').is(':checked'); }"],
            [['task_file'], 'string', 'max' => 500],
            [['is_final_task'], 'validateOnlyOneFinalTask'],
            [['title', 'task_title', 'task_subject'], 'string', 'max' => 255],
            [['file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, gif, pdf, doc, docx, odt'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'content' => 'Content',
            'sort' => 'Sort',
            'is_active' => 'Active',
            'passing_score' => 'Passing Score (%)',
            'created_at' => 'Created',
            'updated_at' => 'Updated',
            'is_final_task'           => 'Final task (no test)',
            'task_title'              => 'Task title',
            'task_subject'            => 'Task subject',
            'task_body'               => 'Task body',
            'task_file'               => 'Attached file path',
            'task_deadline_hours'     => 'Deadline (hours from start)',
        ];
    }

    public function getQuestions()
    {
        return $this->hasMany(TrainingModuleQuestion::class, ['module_id' => 'id'])
            ->orderBy(['sort' => SORT_ASC]);
    }

    public function getQuestionCount()
    {
        return TrainingModuleQuestion::find()
            ->where(['module_id' => $this->id])
            ->count();
    }

    public function validateOnlyOneFinalTask($attribute): void
    {
        if ((int)$this->$attribute !== 1) {
            return;
        }

        $exists = self::find()
            ->where(['is_final_task' => 1])
            ->andWhere(['!=', 'id', $this->id ?? 0])
            ->exists();

        if ($exists) {
            $this->addError($attribute, 'Only one module can be marked as final task. Uncheck the existing one first.');
        }
    }
}