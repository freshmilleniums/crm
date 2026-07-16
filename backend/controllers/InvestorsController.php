<?php

namespace backend\controllers;

use Yii;
use common\models\Investor;
use common\models\InvestorEmployee;
use backend\models\InvestorsSearch;
use backend\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * InvestorsController implements the CRUD actions for Investor model.
 */
class InvestorsController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Investor models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new InvestorsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $employeesList = User::getEmployeesDropdownList();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'employeesList' => $employeesList,
        ]);
    }

    /**
     * Displays a single Investor model (for expandable row).
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $model = $this->findModel($id);

        return json_encode([
            'success' => true,
            'tpl' => $this->renderAjax('_view_expanded', [
                'model' => $model,
            ])
        ]);
    }

    /**
     * Creates a new Investor model via AJAX.
     * @return mixed
     */
    public function actionCreateAjax()
    {
        $model = new Investor();

        if (Yii::$app->request->isGet) {
            return json_encode([
                'success' => true,
                'tpl' => $this->renderAjax('_form', [
                    'model' => $model,
                ])
            ]);
        }

        if ($model->load(Yii::$app->request->post())) {
            $model->created_by = Yii::$app->user->id;

            if ($model->save()) {
                \common\services\ActionLogService::logEntityCreate($model, \common\models\ActionLog::ENTITY_INVESTOR);
                return json_encode([
                    'success' => true,
                    'message' => 'Investor created successfully!',
                ]);
            } else {
                return json_encode([
                    'success' => false,
                    'message' => 'Failed to create investor.',
                    'tpl' => $this->renderAjax('_form', [
                        'model' => $model,
                    ])
                ]);
            }
        }

        return json_encode([
            'success' => false,
            'message' => 'Invalid request.',
        ]);
    }

    /**
     * Updates an existing Investor model (for expandable row).
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $model = $this->findModel($id);

        if (Yii::$app->request->isGet) {
            return json_encode([
                'success' => true,
                'tpl' => $this->renderAjax('_form', [
                    'model' => $model,
                ])
            ]);
        }

        if ($model->load(Yii::$app->request->post())) {
            $logData = \common\services\ActionLogService::prepareEntityUpdate(
                $model,
                \common\models\ActionLog::ENTITY_INVESTOR
            );

            if ($model->save()) {
                \common\services\ActionLogService::commitEntityUpdate($logData);
                return json_encode([
                    'success' => true,
                    'message' => 'Investor updated successfully!',
                ]);
            }
        }

        return json_encode([
            'success' => false,
            'message' => 'Failed to update investor.',
            'tpl' => $this->renderAjax('_form', [
                'model' => $model,
            ])
        ]);
    }

    /**
     * Deletes an existing Investor model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        \common\services\ActionLogService::logEntityDelete($model, \common\models\ActionLog::ENTITY_INVESTOR);
        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * Assign employees to investor (modal form).
     * @param integer $id Investor ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionAssignEmployees($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $model = $this->findModel($id);
        $employees = User::getEmployeesDropdownList();

        if (Yii::$app->request->isPost) {
            $employeeIds = Yii::$app->request->post('employee_ids', []);

            // Get current employees for logging
            $oldEmployeeIds = InvestorEmployee::find()
                ->select('employee_id')
                ->where(['investor_id' => $id])
                ->column();

            InvestorEmployee::deleteAll(['investor_id' => $id]);

            foreach ($employeeIds as $employeeId) {
                $investorEmployee = new InvestorEmployee();
                $investorEmployee->investor_id = $id;
                $investorEmployee->employee_id = $employeeId;
                $investorEmployee->assigned_by = Yii::$app->user->id;
                $investorEmployee->assigned_at = time();
                $investorEmployee->save();
            }

            // Log employee assignment
            \common\services\ActionLogService::log(
                \common\models\ActionLog::ENTITY_INVESTOR,
                $id,
                'assign_employee',
                ['employee_ids' => $oldEmployeeIds],
                ['employee_ids' => $employeeIds]
            );

            return json_encode([
                'success' => true,
                'message' => 'Employees assigned successfully!',
            ]);
        }

        $currentEmployeeIds = InvestorEmployee::find()
            ->select('employee_id')
            ->where(['investor_id' => $id])
            ->column();

        return json_encode([
            'success' => true,
            'tpl' => $this->renderAjax('_assign_employees_form', [
                'model' => $model,
                'employees' => $employees,
                'currentEmployeeIds' => $currentEmployeeIds,
            ])
        ]);
    }

    /**
     * Bulk assign investors to one employee (modal form).
     * @return mixed
     */
    public function actionBulkAssignEmployee()
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        if (Yii::$app->request->isPost) {
            $investorIds = Yii::$app->request->post('investor_ids', []);
            $employeeId = Yii::$app->request->post('employee_id');

            if (empty($investorIds) || !$employeeId) {
                return json_encode([
                    'success' => false,
                    'message' => 'Please select investors and an employee.',
                ]);
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                foreach ($investorIds as $investorId) {
                    $existingAssignment = InvestorEmployee::findOne([
                        'investor_id' => $investorId,
                        'employee_id' => $employeeId,
                    ]);

                    if (!$existingAssignment) {
                        $investorEmployee = new InvestorEmployee();
                        $investorEmployee->investor_id = $investorId;
                        $investorEmployee->employee_id = $employeeId;
                        $investorEmployee->assigned_by = Yii::$app->user->id;
                        $investorEmployee->assigned_at = time();
                        $investorEmployee->save();
                    }
                }

                $transaction->commit();

                // Log bulk assignment
                \common\services\ActionLogService::log(
                    \common\models\ActionLog::ENTITY_INVESTOR,
                    0,
                    'bulk_assign_employee',
                    null,
                    [
                        'investor_ids' => $investorIds,
                        'employee_id' => $employeeId,
                    ]
                );

                return json_encode([
                    'success' => true,
                    'message' => 'Investors assigned successfully!',
                ]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                return json_encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ]);
            }
        }

        return json_encode([
            'success' => true,
            'tpl' => $this->renderAjax('_bulk_assign_form', [
                'employees' => User::getEmployeesDropdownList(),
            ])
        ]);
    }

    /**
     * Finds the Investor model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Investor the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Investor::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested investor does not exist.');
    }
}