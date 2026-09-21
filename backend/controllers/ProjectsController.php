<?php

namespace backend\controllers;

use Yii;
use common\models\Project;
use backend\models\ProjectsSearch;
use backend\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\UploadedFile;

/**
 * ProjectsController implements the CRUD actions for Project model.
 * Available for: super-administrator, administrator
 */
class ProjectsController extends BaseController
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
     * Lists all Project models.
     * Shows all projects for admin/super-admin.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ProjectsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $dataProvider->query->with(['employee', 'creator']);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Project model (AJAX expandable row).
     * @param int $id Project ID
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
     * Creates a new Project model via AJAX (modal window).
     * @return string JSON response
     */
    public function actionCreateAjax()
    {
        $model = new Project();
        $model->created_by = Yii::$app->user->id;

        $documents = [new \common\models\ProjectsDocuments()];
        $employees = $this->getEmployeesList();

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {

            $documents = \backend\models\MultipleModel::createMultiple(\common\models\ProjectsDocuments::class);
            \backend\models\MultipleModel::loadMultiple($documents, Yii::$app->request->post());

            $transaction = Yii::$app->db->beginTransaction();
            $success = false;
            $message = '';

            try {
                $flag = $model->save();

                if ($flag) {
                    foreach ($documents as $index => $doc) {
                        $doc->project_id = $model->id;
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
                    \common\services\ActionLogService::logEntityCreate($model, \common\models\ActionLog::ENTITY_PROJECT);
                    $success = true;
                    $message = 'Project created successfully';
                } else {
                    $transaction->rollBack();
                    $message = 'Failed to create project';
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
     * Updates an existing Project model via AJAX (expandable row).
     * @param int $id Project ID
     * @return string JSON response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $documents = $model->documents;
        $employees = $this->getEmployeesList();

        if (count($documents) == 0) {
            $documents = [new \common\models\ProjectsDocuments()];
        }

        if ($model->load(Yii::$app->request->post())) {

            $oldDocuments = $model->documents;

            $documents = \backend\models\MultipleModel::createMultiple(\common\models\ProjectsDocuments::class, $oldDocuments);
            \backend\models\MultipleModel::loadMultiple($documents, Yii::$app->request->post());

            $deletedDocumentIDs = array_diff(
                array_map(function($doc) { return $doc->id; }, $oldDocuments),
                array_filter(array_map(function($doc) { return $doc->id; }, $documents))
            );

            $transaction = Yii::$app->db->beginTransaction();

            try {
                $logData = \common\services\ActionLogService::prepareEntityUpdate(
                    $model,
                    \common\models\ActionLog::ENTITY_PROJECT
                );

                if (empty($employees)) {
                    $model->employee_id = $model->getOldAttribute('employee_id');
                }

                $flag = $model->save();

                $deletedDocumentNames = [];

                if ($flag && !empty($deletedDocumentIDs)) {
                    $deletedDocuments = \common\models\ProjectsDocuments::find()
                        ->where(['id' => $deletedDocumentIDs])
                        ->all();

                    $deletedDocumentNames = array_map(function($doc) {
                        return $doc->getFileName();
                    }, $deletedDocuments);

                    \common\models\ProjectsDocuments::deleteAll(['id' => $deletedDocumentIDs]);
                }

                $addedDocumentNames = [];

                if ($flag) {
                    foreach ($documents as $index => $doc) {
                        $doc->project_id = $model->id;
                        $doc->file = UploadedFile::getInstance($doc, "[{$index}]file");

                        if ($doc->file) {
                            if ($doc->upload()) {
                                if (!$doc->save(false)) {
                                    $flag = false;
                                    break;
                                } else {
                                    $addedDocumentNames[] = $doc->getFileName();
                                }
                            } else {
                                $flag = false;
                                break;
                            }
                        } else {
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
                    return json_encode(['success' => true, 'message' => 'Project updated successfully']);
                } else {
                    $transaction->rollBack();
                    return json_encode(['success' => false, 'message' => 'Failed to save project']);
                }

            } catch (\Exception $e) {
                $transaction->rollBack();
                return json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        }

        return json_encode([
            'tpl' => $this->renderAjax('update', [
                'model' => $model,
                'documents' => $documents,
                'employees' => $employees,
            ])
        ]);
    }

    /**
     * Get edit options for inline editing (Quick Edit).
     * @param int $id Project ID
     * @return string JSON response
     */
    public function actionGetEditOptions($id)
    {
        try {
            $model = $this->findModel($id);
        } catch (NotFoundHttpException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Project not found'
            ]);
        }

        return json_encode([
            'success' => true,
            'options' => [
                'types' => Project::getTypeList(),
                'statuses' => Project::getStatusList(),
                'employees' => $this->getEmployeesList(),
            ]
        ]);
    }

    /**
     * Save inline edit (Quick Edit).
     * @return string JSON response
     */
    public function actionSaveInlineEdit()
    {
        $id = Yii::$app->request->post('id');

        try {
            $model = $this->findModel($id);
        } catch (NotFoundHttpException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Project not found'
            ]);
        }

        $model->name = Yii::$app->request->post('name');
        $model->type = Yii::$app->request->post('type') ?: null;
        $model->net_worth = Yii::$app->request->post('net_worth') ?: null;
        $model->roi = Yii::$app->request->post('roi') ?: null;
        $model->status = Yii::$app->request->post('status') ?: null;

        $employees = $this->getEmployeesList();

        if (!empty($employees)) {
            $model->employee_id = Yii::$app->request->post('employee_id') ?: null;
        }

        $logData = \common\services\ActionLogService::prepareEntityUpdate(
            $model,
            \common\models\ActionLog::ENTITY_PROJECT
        );

        if ($model->save()) {
            \common\services\ActionLogService::commitEntityUpdate($logData);
            return json_encode([
                'success' => true,
                'message' => 'Project updated successfully'
            ]);
        } else {
            $errors = [];
            foreach ($model->getErrors() as $field => $messages) {
                $errors[] = implode(', ', $messages);
            }

            return json_encode([
                'success' => false,
                'message' => 'Failed to save project: ' . implode('; ', $errors)
            ]);
        }
    }

    /**
     * Deletes an existing Project model.
     * @param int $id Project ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        \common\services\ActionLogService::logEntityDelete($model, \common\models\ActionLog::ENTITY_PROJECT);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Project deleted successfully.');

        return $this->redirect(['index']);
    }

    /**
     * Assign employee to project (AJAX modal).
     * @param int $id Project ID
     * @return string JSON response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionAssignEmployee($id)
    {
        $model = $this->findModel($id);

        $employees = $this->getEmployeesList();

        if (Yii::$app->request->isPost) {

            if (!empty($employees)) {
                $model->employee_id = Yii::$app->request->post('employee_id');
            }

            $logData = \common\services\ActionLogService::prepareEntityUpdate(
                $model,
                \common\models\ActionLog::ENTITY_PROJECT,
                'assign_employee'
            );


            if ($model->save(false)) {
                \common\services\ActionLogService::commitEntityUpdate($logData);
                return json_encode([
                    'success' => true,
                    'message' => 'Employee assigned successfully',
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to assign employee',
                ]);
            }
        }

        // Show form
        return json_encode([
            'tpl' => $this->renderAjax('_assign_employee_form', [
                'model' => $model,
                'employees' => $employees,
            ])
        ]);
    }

    /**
     * Finds the Project model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id Project ID
     * @return Project the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Project::findOne($id)) !== null) {
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

    public function actionDownloadDocument($id)
    {
        $document = \common\models\ProjectsDocuments::findOne($id);

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

    public function actionDeleteDocument($id)
    {
        $document = \common\models\ProjectsDocuments::findOne($id);

        if (!$document) {
            return json_encode([
                'success' => false,
                'message' => 'Document not found'
            ]);
        }

        $projectId = $document->project_id;
        $fileName  = $document->getFileName();

        if ($document->delete()) {
            \common\services\ActionLogService::log(
                \common\models\ActionLog::ENTITY_PROJECT,
                $projectId,
                'delete_document',
                ['document_name' => $fileName],
                null
            );

            return json_encode([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);
        }

        return json_encode([
            'success' => false,
            'message' => 'Failed to delete document'
        ]);
    }
}