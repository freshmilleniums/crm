<?php

namespace common\services;

use Yii;
use backend\models\TrainingModule;
use common\models\Task;
use common\models\TasksDocuments;

class TrainingTaskService
{
    public function createTaskForEmployee(TrainingModule $module, int $employeeId): ?Task
    {
        if (!$module->is_final_task) {
            return null;
        }

        $deadline = $module->task_deadline_hours
            ? time() + ($module->task_deadline_hours * 3600)
            : null;

        $task = new Task();
        $task->title                = $module->task_title;
        $task->subject              = $module->task_subject;
        $task->description          = $module->task_body;
        $task->assigned_to          = $employeeId;
        $task->created_by           = Yii::$app->user->id;
        $task->priority             = Task::PRIORITY_HIGH;
        $task->status               = Task::STATUS_NEW;
        $task->due_date             = $deadline;
        $task->is_training          = Task::IS_TRAINING;
        $task->training_module_id   = $module->id;
        $task->training_employee_id = $employeeId;

        if (!$task->save()) {
            Yii::error('TrainingTaskService: failed to create task for employee ' . $employeeId . ': ' . json_encode($task->errors));
            return null;
        }

        if ($module->task_file) {
            $uploadPath = \Yii::$app->params['uploadPath'];
            $sourcePath = $uploadPath . $module->task_file;

            if (file_exists($sourcePath)) {
                $fileNewName = md5($module->task_file . time());
                $fileDir = 'tasksDocuments/' . $fileNewName[0] . '/' . $fileNewName[1] . $fileNewName[2] . '/';
                $destDir = $uploadPath . $fileDir;

                if (!\yii\helpers\FileHelper::createDirectory($destDir)) {
                    \Yii::error('TrainingTaskService: failed to create directory ' . $destDir);
                } else {
                    $ext = pathinfo($module->task_file, PATHINFO_EXTENSION);
                    $newFileName = $fileDir . time() . '_' . $fileNewName . '.' . $ext;

                    if (copy($sourcePath, $uploadPath . $newFileName)) {
                        $doc = new TasksDocuments();
                        $doc->task_id = $task->id;
                        $doc->path = $newFileName;
                        $doc->save();
                    }
                }
            }
        }

        return $task;
    }
}