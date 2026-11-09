<?php

namespace backend\controllers;

use Yii;
use common\models\Task;
use common\models\TasksDocuments;
use backend\models\TasksSearch;
use backend\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use backend\models\MultipleModel;
use yii\helpers\Json;
use yii\web\Response;

/**
 * TasksController implements the CRUD actions for Task model.
 * Available for: super-administrator, administrator, email-task-operator
 */
class TasksController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'delete-document' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Task models.
     * Shows only tasks created by current user.
     * @return mixed
     */
    public function actionIndex()
    {
        $activeTab = Yii::$app->request->get('tab', 'tasks');

        // Tab "Tasks" — regular tasks
        $searchModel = new TasksSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['tasks.is_template' => Task::IS_NOT_TEMPLATE]);

        // Tab "Templates" — task templates
        $searchModelTemplates = new TasksSearch();
        $dataProviderTemplates = $searchModelTemplates->search(Yii::$app->request->queryParams);
        $dataProviderTemplates->query->andWhere(['tasks.is_template' => Task::IS_TEMPLATE]);

        if (!Yii::$app->user->can('super-administrator')) {
            $dataProvider->query->andWhere(['tasks.created_by' => Yii::$app->user->id]);
            $dataProviderTemplates->query->andWhere(['tasks.created_by' => Yii::$app->user->id]);
        }

        $dataProvider->query->with(['assignedUser', 'creator']);
        $dataProviderTemplates->query->with(['assignedUser', 'creator']);

        return $this->render('index', [
            'searchModel'          => $searchModel,
            'dataProvider'         => $dataProvider,
            'searchModelTemplates' => $searchModelTemplates,
            'dataProviderTemplates'=> $dataProviderTemplates,
            'activeTab'            => $activeTab,
        ]);
    }

    /**
     * Displays a single Task model (AJAX expandable row).
     * @param int $id Task ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return json_encode([
            'tpl' => $this->renderAjax('_view_expanded', [
                'model' => $model,
            ])
        ]);
    }

    /**
     * Creates a new Task model via AJAX (modal window).
     * @return string JSON response
     */
    public function actionCreateAjax()
    {
        $model = new Task();
        $model->is_template = (int) Yii::$app->request->get('is_template', Task::IS_NOT_TEMPLATE);
        $model->created_by = Yii::$app->user->id;

        $documents = [new TasksDocuments()];
        $employees = $this->getEmployeesList();
        $sourceTask        = null;
        $sourceDocumentIds = [];

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {

            $documents = MultipleModel::createMultiple(TasksDocuments::class);
            MultipleModel::loadMultiple($documents, Yii::$app->request->post());

            // Only regular tasks can be cloned from a template
            $sourceTaskId = (int) Yii::$app->request->post('source_task_id');
            if ($sourceTaskId && !$model->is_template) {
                $sourceTask        = Task::findVisible($sourceTaskId, Task::IS_TEMPLATE);
                $sourceDocumentIds = array_filter(array_map('intval', (array) Yii::$app->request->post('source_document_ids', [])));
            }

            // Physical files created during this request, removed on rollback
            $createdFilePaths = [];

            $transaction = Yii::$app->db->beginTransaction();
            $success = false;
            $message = '';

            try {
                // Templates are not bound to an employee or a deadline
                if ($model->is_template) {
                    $model->assigned_to = null;
                    $model->due_date = null;
                }
                $flag = $model->save();

                if ($flag) {
                    foreach ($documents as $index => $doc) {
                        $doc->task_id = $model->id;
                        $doc->file = UploadedFile::getInstance($doc, "[{$index}]file");

                        if ($doc->file) {
                            if ($doc->upload()) {
                                $createdFilePaths[] = $doc->path;
                                if (!$doc->save(false)) {
                                    $flag = false;
                                    break;
                                }
                            } else {
                                $flag = false;
                                break;
                            }
                        }
                    }
                }

                if ($flag && $sourceTask) {
                    $flag = $model->copyDocumentsFrom($sourceTask, $sourceDocumentIds, $createdFilePaths);
                }

                if ($flag) {
                    $transaction->commit();

                    // Log task creation
                    \common\services\ActionLogService::logEntityCreate($model, \common\models\ActionLog::ENTITY_TASK);

                    if ($sourceTask) {
                        \common\services\ActionLogService::log(
                            \common\models\ActionLog::ENTITY_TASK,
                            $model->id,
                            'clone',
                            null,
                            ['source_task_id' => $sourceTask->id]
                        );
                    }

                    $success = true;
                    $message = $model->is_template ? 'Task template created successfully' : 'Task created successfully';
                } else {
                    $transaction->rollBack();
                    $message = 'Failed to create task';
                }

            } catch (\Exception $e) {
                $transaction->rollBack();
                $message = 'Error: ' . $e->getMessage();
            }

            if ($success) {
                return json_encode([
                    'success' => true,
                    'message' => $message,
                ]);
            } else {
                foreach ($createdFilePaths as $createdFilePath) {
                    TasksDocuments::deleteFile($createdFilePath);
                }
                
                return json_encode([
                    'success' => false,
                    'message' => $message,
                    'tpl' => $this->renderAjax($model->is_template ? '_form_create_template' : '_form_create', [
                        'model' => $model,
                        'documents' => $documents,
                        'employees' => $employees,
                        'sourceTask'        => $sourceTask,
                        'sourceDocumentIds' => $sourceDocumentIds,
                    ])
                ]);
            }
        }

        // Initial form render
        return json_encode([
            'tpl' => $this->renderAjax($model->is_template ? '_form_create_template' : '_form_create', [
                'model' => $model,
                'documents' => $documents,
                'employees' => $employees,
                'sourceTask'        => $sourceTask,
                'sourceDocumentIds' => $sourceDocumentIds,
            ])
        ]);
    }

    /**
     * Updates an existing Task model (separate page).
     * @param int $id Task ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $documents = $model->documents;
        $employees = $this->getEmployeesList();

        if (count($documents) == 0) {
            $documents = [new TasksDocuments()];
        }

        if ($model->load(Yii::$app->request->post())) {

            $oldDocuments = $model->documents;

            $documents = MultipleModel::createMultiple(TasksDocuments::class, $oldDocuments);
            MultipleModel::loadMultiple($documents, Yii::$app->request->post());

            $deletedDocumentIDs = array_diff(
                array_map(function($doc) { return $doc->id; }, $oldDocuments),
                array_filter(array_map(function($doc) { return $doc->id; }, $documents))
            );

            $transaction = Yii::$app->db->beginTransaction();

            try {
                $logData = \common\services\ActionLogService::prepareEntityUpdate(
                    $model,
                    \common\models\ActionLog::ENTITY_TASK
                );

                if (empty($employees)) {
                    $model->assigned_to = $model->getOldAttribute('assigned_to');
                }

                $flag = $model->save();

                $deletedDocumentNames = [];
                $deletedDocumentPaths = [];

                if ($flag) {
                    // Delete removed documents
                    if (!empty($deletedDocumentIDs)) {
                        // Get names before deletion for logging
                        $deletedDocuments = TasksDocuments::find()
                            ->andWhere(['id' => $deletedDocumentIDs])
                            ->all();

                        $deletedDocumentNames = array_map(function($doc) {
                            return $doc->getFileName();
                        }, $deletedDocuments);

                        $deletedDocumentPaths = array_map(function($doc) {
                            return $doc->path;
                        }, $deletedDocuments);

                        TasksDocuments::deleteAll(['id' => $deletedDocumentIDs]);
                    }
                }

                $addedDocumentNames = [];

                if ($flag) {
                    foreach ($documents as $index => $doc) {
                        $doc->task_id = $model->id;
                        $doc->file = UploadedFile::getInstance($doc, "[{$index}]file");

                        if ($doc->file) {
                            if ($doc->upload()) {
                                if (!$doc->save(false)) {
                                    $flag = false;
                                    break;
                                } else {
                                    // Track added document name
                                    $addedDocumentNames[] = $doc->getFileName();
                                }
                            } else {
                                $flag = false;
                                break;
                            }
                        } else {
                            // Save without file upload if no new file
                            if ($doc->isNewRecord) {
                                continue;
                            }
                        }
                    }
                }

                if ($flag) {
                    $additionalChanges = [];

                    if (!empty($addedDocumentNames)) {
                        $additionalChanges['documents_added'] = $addedDocumentNames;
                    }

                    if (!empty($deletedDocumentNames)) {
                        $additionalChanges['documents_deleted'] = $deletedDocumentNames;
                    }

                    \common\services\ActionLogService::commitEntityUpdate($logData, $additionalChanges);

                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Task updated successfully.');

                    // Remove physical files only after successful commit
                    foreach ($deletedDocumentPaths as $deletedPath) {
                        TasksDocuments::deleteFile($deletedPath);
                    }

                    return $this->redirect(['index', 'tab' => $model->is_template ? 'templates' : 'tasks']);
                } else {
                    $transaction->rollBack();
                }

            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $this->render('update', [
            'model' => $model,
            'documents' => $documents,
            'employees' => $employees,
        ]);
    }

    /**
     * Deletes an existing Task model.
     * @param int $id Task ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Collect file paths before deletion
        $documentPaths = array_map(function($doc) {
            return $doc->path;
        }, $model->documents);

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Log before deletion
            \common\services\ActionLogService::logEntityDelete($model, \common\models\ActionLog::ENTITY_TASK);

            TasksDocuments::deleteAll(['task_id' => $model->id]);
            $model->delete();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Remove physical files only after successful commit
        foreach ($documentPaths as $documentPath) {
            TasksDocuments::deleteFile($documentPath);
        }

        Yii::$app->session->setFlash('success', 'Task deleted successfully.');

        return $this->redirect(['index', 'tab' => $model->is_template ? 'templates' : 'tasks']);
    }

    /**
     * Download task document file.
     * @param int $id Document ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if document cannot be found
     */
    public function actionDownloadDocument($id)
    {
        $document = TasksDocuments::findOne($id);

        if (!$document) {
            throw new NotFoundHttpException('Document not found.');
        }

        if (!file_exists($document->getFilePath())) {
            throw new NotFoundHttpException('File not found.');
        }

        return Yii::$app->response->sendFile(
            $document->getFilePath(),
            $document->getFileName(),
            ['inline' => false]
        );
    }

    /**
     * Delete task document (AJAX).
     * @param int $id Document ID
     * @return string JSON response
     */
    public function actionDeleteDocument($id)
    {
        $document = TasksDocuments::findOne($id);

        if (!$document) {
            return json_encode([
                'success' => false,
                'message' => 'Document not found'
            ]);
        }

        $taskId = $document->task_id;
        $fileName = $document->getFileName();

        if ($document->delete()) {
            // Log document deletion
            \common\services\ActionLogService::log(
                \common\models\ActionLog::ENTITY_TASK,
                $taskId,
                'delete_document',
                ['document_name' => $fileName],
                null
            );

            return json_encode([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
        } else {
            return json_encode([
                'success' => false,
                'message' => 'Failed to delete document'
            ]);
        }
    }

    /**
     * Finds the Task model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * Only returns tasks created by current user.
     * @param int $id Task ID
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $query = Task::find()->andWhere(['id' => (int) $id]);

        // Same visibility rules as on tasks index page
        if (!Yii::$app->user->can('super-administrator')) {
            $query->andWhere(['created_by' => Yii::$app->user->id]);
        }

        $model = $query->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Get employees list for current admin.
     * @return array
     */
    protected function getEmployeesList()
    {
        $query = User::find()
            ->innerJoin('auth_assignment', 'auth_assignment.user_id = user.id')
            ->where(['auth_assignment.item_name' => 'employee'])
            ->select(['user.id', 'user.first_name', 'user.last_name']);

        if (!Yii::$app->user->can('super-administrator')) {
            $query->andWhere(['user.administrator_id' => Yii::$app->user->id]);
        }
        $employees = $query->all();

        $result = [];
        foreach ($employees as $employee) {
            $result[$employee->id] = $employee->first_name . ' ' . $employee->last_name;
        }

        return $result;
    }

    /**
     * Search tasks for Select2. Pass is_template=1 for templates (clone), 0 for regular tasks (assign).
     * Visibility is the same as on tasks index page.
     * @return array JSON response
     */
    public function actionSearchTasks()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q          = trim(Yii::$app->request->get('q', ''));
        $isTemplate = (int) Yii::$app->request->get('is_template', Task::IS_NOT_TEMPLATE);

        $query = Task::find()
            ->with(['assignedUser'])
            ->andWhere(['is_template' => $isTemplate])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(20);

        if (!Yii::$app->user->can('super-administrator')) {
            $query->andWhere(['created_by' => Yii::$app->user->id]);
        }

        if ($q !== '') {
            $query->andWhere([
                'or',
                ['like', 'title',   $q],
                ['like', 'subject', $q],
            ]);
        }

        $results = [];
        foreach ($query->all() as $task) {
            $results[] = [
                'id'          => $task->id,
                'text'        => $task->title,
                'assignee'    => $task->getAssignedUserName(),
                'created'     => date('Y-m-d', $task->created_at),
                'is_training' => (int) $task->is_training === Task::IS_TRAINING,
            ];
        }

        return ['results' => $results];
    }

    /**
     * Get task data for prefill (clone) or preview (assign existing).
     * @param int $id Task ID
     * @return array JSON response
     */
    public function actionGetTaskData($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $isTemplate = (int) Yii::$app->request->get('is_template', Task::IS_NOT_TEMPLATE);
        $task       = Task::findVisible($id, $isTemplate);

        if (!$task) {
            return [
                'success' => false,
                'message' => 'Task not found.',
            ];
        }

        $documents = [];
        foreach ($task->documents as $document) {
            $documents[] = [
                'id'   => $document->id,
                'name' => $document->getFileName(),
                'url'  => $document->getUrl(),
                'type' => $document->getPreviewType(),
            ];
        }

        return [
            'success' => true,
            'task'    => [
                'id'            => $task->id,
                'title'         => $task->title,
                'subject'       => $task->subject,
                'description'   => $task->description,
                'priority'      => $task->priority,
                'priority_name' => $task->getPriorityName(),
                'status'        => $task->status,
                'status_name'   => $task->getStatusName(),
                'due_date'      => $task->due_date_formatted,
                'assigned_to'   => $task->assigned_to,
                'assignee'      => $task->getAssignedUserName(),
            ],
            'documents' => $documents,
        ];
    }
}