<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "logs_action_details".
 *
 * @property int $id
 * @property int $log_id
 * @property string|null $old_values
 * @property string|null $new_values
 *
 * @property ActionLog $log
 */
class ActionLogDetails extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'logs_action_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['log_id'], 'required'],
            [['log_id'], 'integer'],
            [['old_values', 'new_values'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'log_id' => 'Log ID',
            'old_values' => 'Old Values',
            'new_values' => 'New Values',
        ];
    }

    /**
     * Get log relation
     * @return \yii\db\ActiveQuery
     */
    public function getLog()
    {
        return $this->hasOne(ActionLog::class, ['id' => 'log_id']);
    }

    /**
     * Get decoded old values
     * @return array|null
     */
    public function getOldValuesArray(): ?array
    {
        return $this->old_values ? json_decode($this->old_values, true) : null;
    }

    /**
     * Get decoded new values
     * @return array|null
     */
    public function getNewValuesArray(): ?array
    {
        return $this->new_values ? json_decode($this->new_values, true) : null;
    }

    /**
     * Get list of changed fields
     * @return array
     */
    public function getChangedFields(): array
    {
        $oldValues = $this->getOldValuesArray() ?? [];
        $newValues = $this->getNewValuesArray() ?? [];

        return array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
    }

    /**
     * Get formatted difference for display
     * @param array $attributeLabels - Optional labels for fields
     * @return array
     */
    public function getFormattedDifference(array $attributeLabels = []): array
    {
        $oldValues = $this->getOldValuesArray() ?? [];
        $newValues = $this->getNewValuesArray() ?? [];

        $differences = [];
        $changedFields = $this->getChangedFields();

        foreach ($changedFields as $field) {
            $label = $attributeLabels[$field] ?? ucfirst(str_replace('_', ' ', $field));

            $oldValue = $oldValues[$field] ?? null;
            $newValue = $newValues[$field] ?? null;

            $differences[] = [
                'field' => $field,
                'label' => $label,
                'old' => $this->formatValue($field, $oldValue),
                'new' => $this->formatValue($field, $newValue),
            ];
        }

        return $differences;
    }

    /**
     * Format value for display
     * @param string $field
     * @param mixed $value
     * @return string
     */
    private function formatValue(string $field, $value): string
    {
        if ($value === null) {
            return '<em>empty</em>';
        }

        if (is_array($value)) {
            // Handle employee_ids array - show names instead of IDs
            if ($field === 'employee_ids') {
                $users = User::find()
                    ->where(['id' => $value])
                    ->all();
                return implode(', ', array_map(function($user) {
                    return $user->getFullName();
                }, $users));
            }
            return implode(', ', $value);
        }

        // Handle specific field types
        if (in_array($field, ['created_at', 'updated_at', 'due_date'])) {
            return Yii::$app->formatter->asDatetime($value);
        }

        if (in_array($field, ['status', 'priority', 'type'])) {
            return $this->formatEnumValue($field, $value);
        }

        if (in_array($field, ['assigned_to', 'employee_id', 'created_by'])) {
            $user = User::findOne($value);
            return $user ? $user->getFullName() : "User #{$value}";
        }

        return (string) $value;
    }

    /**
     * Format enum value (status, priority, type)
     * @param string $field
     * @param mixed $value
     * @return string
     */
    private function formatEnumValue(string $field, $value): string
    {
        $entityType = $this->log->entity_type ?? null;

        if ($entityType === ActionLog::ENTITY_TASK) {
            if ($field === 'status') {
                $list = Task::getStatusList();
                return $list[$value] ?? $value;
            }
            if ($field === 'priority') {
                $list = Task::getPriorityList();
                return $list[$value] ?? $value;
            }
        }

        if ($entityType === ActionLog::ENTITY_PROJECT) {
            if ($field === 'status') {
                $list = Project::getStatusList();
                return $list[$value] ?? $value;
            }
            if ($field === 'type') {
                $list = Project::getTypeList();
                return $list[$value] ?? $value;
            }
        }

        return (string) $value;
    }
}