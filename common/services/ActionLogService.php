<?php

namespace common\services;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use backend\models\ActionLogSearch;
use common\models\ActionLog;
use common\models\ActionLogDetails;

/**
 * Service for managing action logs (read and write)
 */
class ActionLogService
{
    private const ENTITY_TYPE_LABELS = [
        'tasks' => 'Tasks',
        'projects' => 'Projects',
        'users' => 'Users',
        'investors' => 'Investors',
        'templates' => 'Templates',
        'emails' => 'Emails',
    ];

    private const IGNORED_FIELDS = ['id', 'created_at', 'updated_at'];

    private const IGNORED_FIELDS_USER = [
        'id', 'created_at', 'updated_at',
        'password_hash', 'auth_key',
        'password_reset_token', 'verification_token'
    ];

    public function getTabsDataForLogs(): array
    {
        $availableEntityTypes = ['tasks', 'projects', 'investors', 'templates', 'users'];

        $tabs = [];
        $firstEntityType = $availableEntityTypes[0];

        foreach ($availableEntityTypes as $entityType) {
            $searchModel = new ActionLogSearch();
            $dataProvider = $this->createDataProviderForEntityType($entityType, $searchModel);

            $tabs[] = [
                'id' => "tab-{$entityType}",
                'entity_type' => $entityType,
                'label' => self::ENTITY_TYPE_LABELS[$entityType] ?? ucfirst($entityType),
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'active' => $entityType === $firstEntityType,
            ];
        }

        return $tabs;
    }

    private function createDataProviderForEntityType(string $entityType, ActionLogSearch $searchModel): ActiveDataProvider
    {
        $searchModel->entity_type = $entityType;
        return $searchModel->search(Yii::$app->request->queryParams);
    }

    public static function log(
        string $entityType,
        int $entityId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): bool {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $log = new ActionLog();
            $log->user_id = Yii::$app->user->id;
            $log->entity_type = $entityType;
            $log->entity_id = $entityId;
            $log->action = $action;

            if (!$log->save()) {
                $transaction->rollBack();
                Yii::error("Failed to save ActionLog: " . json_encode($log->getErrors()));
                return false;
            }

            if ($oldValues !== null || $newValues !== null) {
                $details = new ActionLogDetails();
                $details->log_id = $log->id;
                $details->old_values = $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
                $details->new_values = $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;

                if (!$details->save()) {
                    $transaction->rollBack();
                    Yii::error("Failed to save ActionLogDetails: " . json_encode($details->getErrors()));
                    return false;
                }
            }

            $transaction->commit();
            return true;

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("ActionLog exception: " . $e->getMessage());
            return false;
        }
    }

    public static function logEntityCreate(ActiveRecord $model, string $entityType): bool
    {
        $attributes = $model->getAttributes();

        $ignoredFields = $entityType === ActionLog::ENTITY_USER
            ? self::IGNORED_FIELDS_USER
            : self::IGNORED_FIELDS;

        foreach ($ignoredFields as $field) {
            unset($attributes[$field]);
        }

        $filledAttributes = array_filter($attributes, function($value) {
            return $value !== null && $value !== '';
        });

        return self::log($entityType, $model->id, 'create', null, $filledAttributes);
    }

    /**
     * Prepare update log data BEFORE save()
     * Returns snapshot of changes to use in commitEntityUpdate()
     */
    public static function prepareEntityUpdate(ActiveRecord $model, string $entityType, string $action = 'update'): array
    {
        $oldValues = [];
        $newValues = [];

        $ignoredFields = $entityType === ActionLog::ENTITY_USER
            ? self::IGNORED_FIELDS_USER
            : self::IGNORED_FIELDS;

        foreach ($model->getDirtyAttributes() as $attribute => $newValue) {
            if (in_array($attribute, $ignoredFields)) {
                continue;
            }

            $oldValue = $model->getOldAttribute($attribute);

            if ($oldValue == $newValue) {
                continue;
            }

            $oldValues[$attribute] = $oldValue;
            $newValues[$attribute] = $newValue;
        }

        return [
            'entityType' => $entityType,
            'entityId' => $model->id,
            'action' => $action,
            'oldValues' => $oldValues,
            'newValues' => $newValues,
        ];
    }

    /**
     * Commit update log AFTER save()
     * @param array $prepared - result of prepareEntityUpdate()
     * @param array $additionalChanges - documents etc.
     */
    public static function commitEntityUpdate(array $prepared, array $additionalChanges = []): bool
    {
        $oldValues = $prepared['oldValues'];
        $newValues = $prepared['newValues'];

        if (!empty($additionalChanges)) {
            $newValues = array_merge($newValues, $additionalChanges);
        }

        if (empty($oldValues) && empty($additionalChanges)) {
            return true;
        }

        $specificAction = self::determineSpecificAction(
            $oldValues,
            $newValues,
            $prepared['action'],
            $prepared['entityType']
        );

        return self::log(
            $prepared['entityType'],
            $prepared['entityId'],
            $specificAction,
            $oldValues ?: null,
            $newValues ?: null
        );
    }

    public static function logEntityDelete(ActiveRecord $model, string $entityType): bool
    {
        $attributes = $model->getAttributes();

        $ignoredFields = $entityType === ActionLog::ENTITY_USER
            ? self::IGNORED_FIELDS_USER
            : self::IGNORED_FIELDS;

        foreach ($ignoredFields as $field) {
            unset($attributes[$field]);
        }

        $filledAttributes = array_filter($attributes, function($value) {
            return $value !== null && $value !== '';
        });

        return self::log($entityType, $model->id, 'delete', $filledAttributes, null);
    }

    private static function determineSpecificAction(array $oldValues, array $newValues, string $defaultAction, string $entityType): string
    {
        $changedFields = array_keys($oldValues);

        if (count($changedFields) === 1) {
            $field = $changedFields[0];

            if ($field === 'status') {
                return 'change_status';
            }

            if ($field === 'priority' && $entityType === ActionLog::ENTITY_TASK) {
                return 'change_priority';
            }

            if ($field === 'type' && $entityType === ActionLog::ENTITY_PROJECT) {
                return 'change_type';
            }

            if ($field === 'assigned_to' || $field === 'employee_id') {
                return 'assign_employee';
            }

            if ($field === 'due_date' && $entityType === ActionLog::ENTITY_TASK) {
                return 'change_due_date';
            }
        }

        return $defaultAction;
    }
}