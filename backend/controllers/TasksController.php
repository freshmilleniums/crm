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
        $searchModel = new TasksSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Super-administrator sees all employees, others see only their employees
        if (!Yii::$app->user->can('super-administrator')) {
            $dataProvider->query->andWhere(['created_by' => Yii::$app->user->id]);
        }

        $dataProvider->query->with(['assignedUser', 'creator']);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
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
        $model->created_by = Yii::$app->user->id;

        $documents = [new TasksDocuments()];
        $employees = $this->getEmployeesList();

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {

            $documents = MultipleModel::createMultiple(TasksDocuments::class);
            MultipleModel::loadMultiple($documents, Yii::$app->request->post());

            $transaction = Yii::$app->db->beginTransaction();
            $success = false;
            $message = '';

            try {
                $flag = $model->save();

                if ($flag) {
                    foreach ($documents as $index => $doc) {
                        $doc->task_id = $model->id;
                        $doc->file = UploadedFile::getInstance($doc, "[{$index}]file");

                        if ($doc->file) {
                            if ($doc->upload()) {
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

                if ($flag) {
                    $transaction->commit();

                    // Log task creation
                    \common\services\ActionLogService::logEntityCreate($model, \common\models\ActionLog::ENTITY_TASK);

                    $success = true;
                    $message = 'Task created successfully';
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
                return json_encode([
                    'success' => false,
                    'message' => $message,
                    'tpl' => $this->renderAjax('_form_create', [
                        'model' => $model,
                        'documents' => $documents,
                        'employees' => $employees,
                    ])
                ]);
            }
        }

        // Initial form render
        return json_encode([
            'tpl' => $this->renderAjax('_form_create', [
                'model' => $model,
                'documents' => $documents,
                'employees' => $employees,
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

                if ($flag) {
                    // Delete removed documents
                    if (!empty($deletedDocumentIDs)) {
                        // Get names before deletion for logging
                        $deletedDocuments = TasksDocuments::find()
                            ->where(['id' => $deletedDocumentIDs])
                            ->all();

                        $deletedDocumentNames = array_map(function($doc) {
                            return $doc->getFileName();
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
                    return $this->redirect(['index']);
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

        // Log before deletion
        \common\services\ActionLogService::logEntityDelete($model, \common\models\ActionLog::ENTITY_TASK);

        $model->delete();
        Yii::$app->session->setFlash('success', 'Task deleted successfully.');

        return $this->redirect(['index']);
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
        $model = Task::find()
            ->where(['id' => $id])
            ->andWhere(['created_by' => Yii::$app->user->id])
            ->one();

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
}