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

        $employees = $this->getEmployeesList();

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {

            $success = false;
            $message = '';

            try {
                if ($model->save()) {
                    \common\services\ActionLogService::logEntityCreate($model, \common\models\ActionLog::ENTITY_PROJECT);
                    $success = true;
                    $message = 'Project created successfully';
                } else {
                    $message = 'Failed to create project';
                }

            } catch (\Exception $e) {
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
                        'employees' => $employees,
                    ])
                ]);
            }
        }

        // Initial form render
        return json_encode([
            'tpl' => $this->renderAjax('_form_create', [
                'model' => $model,
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
        try {
            $model = $this->findModel($id);
        } catch (NotFoundHttpException $e) {
            return json_encode([
                'success' => false,
                'message' => 'Project not found'
            ]);
        }

        $employees = $this->getEmployeesList();

        if ($this->request->isPost && $model->load($this->request->post())) {
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
                return json_encode([
                    'success' => false,
                    'tpl' => $this->renderAjax('update', [
                        'model' => $model,
                        'employees' => $employees,
                    ])
                ]);
            }
        }

        return json_encode([
            'success' => true,
            'tpl' => $this->renderAjax('update', [
                'model' => $model,
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
        $model->employee_id = Yii::$app->request->post('employee_id') ?: null;

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
            $employeeId = Yii::$app->request->post('employee_id');

            $model->employee_id = $employeeId;

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
        $employees = User::find()
            ->innerJoin('auth_assignment', 'auth_assignment.user_id = user.id')
            ->where(['auth_assignment.item_name' => 'employee'])
            ->andWhere(['user.administrator_id' => Yii::$app->user->id])
            ->select(['user.id', 'user.first_name', 'user.last_name'])
            ->all();

        $result = [];
        foreach ($employees as $employee) {
            $result[$employee->id] = $employee->first_name . ' ' . $employee->last_name;
        }

        return $result;
    }
}