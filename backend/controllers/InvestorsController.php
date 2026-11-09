<?php

namespace backend\controllers;

use Yii;
use common\models\Investor;
use common\models\InvestorEmployee;
use backend\models\InvestorsSearch;
use backend\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use common\services\SmtpService;
use common\models\Template;
use common\models\UserSentTemplate;

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
                        'send-email-ajax' => ['GET'],
                        'send-email'      => ['POST'],
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

    /**
     * Render "Send Email from Investor" modal form via AJAX.
     */
    public function actionSendEmailAjax()
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $companyId = Yii::$app->params['company_id'] ?? null;
        $currentUser     = Yii::$app->user->identity;
        $administratorId = null;

        if (Yii::$app->user->can('administrator')) {
            // Administrator sees only employees assigned to them
            $administratorId = $currentUser->id;
        }

        $employeesList = User::getEmployeesDropdownList($administratorId, User::SUBSTATUS_ACTIVE_EMPLOYEE);

        // Load only investors that have SMTP configured
        $investors     = Investor::getSmtpConfiguredList();
        $investorsList = ArrayHelper::map($investors, 'id', fn($investor) => $investor->getFullName() . ' <' . $investor->getSmtpFromEmail() . '>');

        $templatesList = ArrayHelper::map(
            Template::find()->orderBy(['title' => SORT_ASC])->all(),
            'id',
            fn($t) => '[' . $t->getCategoryName() . '] ' . $t->title
        );

        return json_encode([
            'tpl' => $this->renderAjax('_send_email_modal', [
                'investorsList' => $investorsList,
                'employeesList' => $employeesList,
                'templatesList' => $templatesList,
            ]),
        ]);
    }

    /**
     * Send email from the selected investor via its own SMTP credentials.
     */
    public function actionSendEmail()
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $investorId = (int)Yii::$app->request->post('investor_id');
        $employeeId = (int)Yii::$app->request->post('employee_id');
        $cc         = trim((string)Yii::$app->request->post('cc', ''));
        $bcc        = trim((string)Yii::$app->request->post('bcc', ''));
        $subject    = trim((string)Yii::$app->request->post('subject', ''));
        $body       = (string)Yii::$app->request->post('body', '');
        $templateId = (int)Yii::$app->request->post('template_id');

        if (!$investorId || !$employeeId) {
            return json_encode(['success' => false, 'message' => 'Investor and recipient are required']);
        }

        $employee = User::findOne($employeeId);
        if (!$employee || empty($employee->email)) {
            return json_encode(['success' => false, 'message' => 'Employee not found or has no email']);
        }

        $currentUser = Yii::$app->user->identity;
        if (Yii::$app->user->can('administrator') && $employee->administrator_id !== $currentUser->id) {
            return json_encode(['success' => false, 'message' => 'Access denied']);
        }

        $to = $employee->email;

        $companyId = Yii::$app->params['company_id'] ?? null;
        $investor  = Investor::findOne(['id' => $investorId, 'company_id' => $companyId]);

        if (!$investor) {
            return json_encode(['success' => false, 'message' => 'Investor not found']);
        }

        if (!$investor->hasSmtpConfig()) {
            return json_encode(['success' => false, 'message' => 'This investor has no SMTP configuration']);
        }

        $uploadedFiles   = UploadedFile::getInstancesByName('attachments');
        $fileAttachments = [];
        $template        = $templateId ? Template::findOne($templateId) : null;

        if ($template) {
            foreach ($template->documents as $doc) {
                $path = $doc->getFilePath();
                if ($path && file_exists($path)) {
                    $fileAttachments[] = [
                        'path' => $path,
                        'name' => $doc->getFileName(),
                        'mime' => mime_content_type($path) ?: 'application/octet-stream',
                    ];
                }
            }
        }

        $smtpService = new SmtpService();

        try {
            if ($template) {
                $replaced     = $template->replaceMacros($employee->id);
                $finalSubject = $replaced['subject'] ?: $subject ?: '(No Subject)';
                $finalBody    = $replaced['body'];

                $smtpService->sendFromInvestor(
                    $investor, $to, $finalSubject, $finalBody,
                    $cc ?: null, $bcc ?: null, $fileAttachments, $uploadedFiles ?: null
                );

                $record          = UserSentTemplate::createFromTemplate($template, $employee->id, Yii::$app->user->id);
                $record->subject = $finalSubject;
                $record->body    = $finalBody;
                $record->save();

            } else {
                if (!$subject) {
                    return json_encode(['success' => false, 'message' => 'Subject is required']);
                }

                $smtpService->sendFromInvestor(
                    $investor, $to, $subject, $body,
                    $cc ?: null, $bcc ?: null, $fileAttachments, $uploadedFiles ?: null
                );
            }

        } catch (\Exception $e) {
            Yii::error('Investor email send failed: ' . $e->getMessage(), 'investor-email');
            return json_encode(['success' => false, 'message' => 'Failed to send: ' . $e->getMessage()]);
        }

        return json_encode(['success' => true, 'message' => 'Email sent successfully.']);
    }
}