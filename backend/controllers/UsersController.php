<?php

namespace backend\controllers;

use backend\models\User;
use backend\models\UserSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Yii;
use common\services\UserService;
use yii\helpers\Json;
use yii\web\Response;
use common\models\UserComments;
use \backend\models\ChangePasswordForm;
use backend\models\NotificationModel;
use common\services\NotificationService;
use yii\bootstrap4\ActiveForm;

/**
 * UsersController implements the CRUD actions for User model.
 */
class UsersController extends BaseController
{

    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                        'remove-employer' => ['POST'],
                        'restore-employer'=> ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all User models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $userService = new UserService();
        $tabsData = $userService->getTabsDataForRoles();
        $countries = \common\models\User::getCountries();

        return $this->render('index', [
            'tabsData' => $tabsData,
            'countries' => $countries,
        ]);
    }

    /**
     * Displays a single User model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        try {
            $model = $this->findModel($id);
            $countries = \common\models\User::getCountries();
            $isEmployee = $model->role === 'employee';

            $documentsProvider     = null;
            $sentTemplatesProvider = null;
            $projectsProvider      = null;
            $investorsProvider     = null;
            $tasksProvider         = null;

            if ($isEmployee) {
                $documentsProvider = new \yii\data\ActiveDataProvider([
                    'query'      => \common\models\UserDocument::find()->where(['user_id' => $model->id]),
                    'pagination' => false,
                    'sort'       => ['sortParam' => 'docs-sort', 'defaultOrder' => ['created_at' => SORT_DESC]],
                ]);

                $sentTemplatesProvider = new \yii\data\ActiveDataProvider([
                    'query'      => \common\models\UserSentTemplate::find()->where(['user_id' => $model->id]),
                    'pagination' => ['pageSize' => 10, 'pageParam' => 'tpl-page'],
                    'sort'       => ['sortParam' => 'tpl-sort', 'defaultOrder' => ['sent_at' => SORT_ASC]],
                ]);

                $projectsProvider = new \yii\data\ActiveDataProvider([
                    'query'      => \common\models\Project::find()->where(['employee_id' => $model->id]),
                    'pagination' => ['pageSize' => 10, 'pageParam' => 'proj-page'],
                    'sort'       => ['sortParam' => 'proj-sort', 'defaultOrder' => ['created_at' => SORT_DESC]],
                ]);

                $investorsProvider = new \yii\data\ActiveDataProvider([
                    'query'      => \common\models\InvestorEmployee::find()
                        ->where(['employee_id' => $model->id])
                        ->with(['investor']),
                    'pagination' => ['pageSize' => 10, 'pageParam' => 'inv-page'],
                    'sort'       => ['sortParam' => 'inv-sort', 'defaultOrder' => ['assigned_at' => SORT_DESC]],
                ]);

                $tasksProvider = new \yii\data\ActiveDataProvider([
                    'query'      => \common\models\Task::find()->where(['assigned_to' => $model->id]),
                    'pagination' => ['pageSize' => 10, 'pageParam' => 'task-page'],
                    'sort'       => ['sortParam' => 'task-sort', 'defaultOrder' => ['created_at' => SORT_DESC]],
                ]);
            }

            return Json::encode([
                'success' => true,
                'tpl'     => $this->renderAjax('view', [
                    'model'                 => $model,
                    'countries'             => $countries,
                    'isEmployee'            => $isEmployee,
                    'documentsProvider'     => $documentsProvider,
                    'sentTemplatesProvider' => $sentTemplatesProvider,
                    'projectsProvider'      => $projectsProvider,
                    'investorsProvider'     => $investorsProvider,
                    'tasksProvider'         => $tasksProvider,
                ])
            ]);
        } catch (NotFoundHttpException $e) {
            return Json::encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate($role = null)
    {
        if (!Yii::$app->user->can('administrator') && !Yii::$app->user->can('super-administrator')) {
            throw new \yii\web\ForbiddenHttpException('Only administrators can create users.');
        }

        $model = new User();
        $password = Yii::$app->security->generateRandomString(8);

        $userService = new UserService();
        $availableRoles = $userService->getAvailableRolesForUser(Yii::$app->user->id);

        if ($role && !array_key_exists($role, $availableRoles)) {
            throw new \yii\web\ForbiddenHttpException('You are not allowed to create this role.');
        }

        if ($role) {
            $model->role = $role;
        }
        $administrators = User::getAdministratorsList();

        if ($this->request->isPost) {
            $submittedRole = $this->request->post('User')['role'] ?? null;
            if ($submittedRole && !array_key_exists($submittedRole, $availableRoles)) {
                $model->addError('role', 'You are not allowed to create this role.');
                return $this->render('create', [
                    'model'          => $model,
                    'administrators' => $administrators,
                    'availableRoles' => $availableRoles,
                ]);
            }
            $model->setPassword($password);
            $model->status = User::STATUS_ACTIVE;
            $model->generateAuthKey();
            $model->generateEmailVerificationToken();
            $model->created_at = time();
            $model->updated_at = time();
            if ($model->load($this->request->post()) && $model->save()) {
                $auth = Yii::$app->authManager;
                $role = $auth->getRole($model->role);
                if ($role) {
                    $auth->assign($role, $model->id);
                }

                \common\services\ActionLogService::logEntityCreate($model, \common\models\ActionLog::ENTITY_USER);

                return $this->render('create-success', [
                    'email' => $model->email,
                    'password' => $password,
                    'model' => $model,
                ]);
            }
        } else {
            $model->loadDefaultValues();
            if ($role) {
                $model->role = $role;
            }
        }

        return $this->render('create', [
            'model' => $model,
            'administrators' => $administrators,
            'availableRoles' => $availableRoles,
        ]);
    }

    public function actionUpdate($id)
    {
        try {
            $model = $this->findModel($id);
        } catch (NotFoundHttpException $e) {
            return Json::encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        $administrators = User::getAdministratorsList();
        $operators      = User::getPhoneOperatorsList();
        $isEmployee     = $model->role === 'employee';
        $userService    = new UserService();
        $availableRoles = $userService->getAvailableRolesForUser(Yii::$app->user->id);

        $existingDocuments = [];

        if ($isEmployee) {
            $existingDocuments = \common\models\UserDocument::find()
                ->where(['user_id' => $model->id])
                ->all();
        }

        $documents = !empty($existingDocuments)
            ? $existingDocuments
            : ($isEmployee ? [new \common\models\UserDocument()] : []);

        if ($this->request->isPost && $model->load($this->request->post())) {
            if (!Yii::$app->user->can('administrator') && !Yii::$app->user->can('super-administrator')) {
                $model->role = $model->getOldAttribute('role');
            } elseif (!array_key_exists($model->role, $availableRoles)) {
                $model->role = $model->getOldAttribute('role');
            }

            $logData = \common\services\ActionLogService::prepareEntityUpdate(
                $model,
                \common\models\ActionLog::ENTITY_USER
            );

            $oldDocuments = [];
            $newDocuments = [];
            $deletedIds   = [];

            if ($isEmployee) {
                $oldDocuments = \common\models\UserDocument::find()
                    ->where(['user_id' => $model->id])
                    ->all();

                $newDocuments = \backend\models\MultipleModel::createMultiple(
                    \common\models\UserDocument::class,
                    $oldDocuments
                );
                \backend\models\MultipleModel::loadMultiple($newDocuments, $this->request->post());

                $deletedIds = array_diff(
                    array_map(fn($document) => $document->id, $oldDocuments),
                    array_filter(array_map(fn($document) => $document->id, $newDocuments))
                );
            }

            $transaction = Yii::$app->db->beginTransaction();

            try {
                $flag = $model->save();

                if ($flag && $isEmployee) {
                    if (!empty($deletedIds)) {
                        $documentsToDelete = \common\models\UserDocument::find()
                            ->where(['id' => $deletedIds])
                            ->all();
                        foreach ($documentsToDelete as $documentToDelete) {
                            $documentToDelete->delete();
                        }
                    }

                    foreach ($newDocuments as $index => $document) {
                        $document->user_id     = $model->id;
                        $document->uploaded_by = Yii::$app->user->id;
                        $document->file        = \yii\web\UploadedFile::getInstance($document, "[{$index}]file");

                        if ($document->file) {
                            if ($document->upload()) {
                                if (!$document->save(false)) {
                                    $flag = false;
                                    break;
                                }
                            } else {
                                $flag = false;
                                break;
                            }
                        } elseif (!$document->isNewRecord) {
                            $document->save(false);
                        }
                    }
                }

                if ($flag) {
                    $auth = Yii::$app->authManager;
                    $role = $auth->getRole($model->role);
                    if ($role) {
                        $auth->revokeAll($model->id);
                        $auth->assign($role, $model->id);
                    }

                    \common\services\ActionLogService::commitEntityUpdate($logData);
                    $transaction->commit();

                    return Json::encode([
                        'success' => true,
                        'message' => 'User updated successfully'
                    ]);
                }

                $transaction->rollBack();

                return Json::encode([
                    'success' => false,
                    'tpl'     => $this->renderAjax('_form_update_ajax', [
                        'model'          => $model,
                        'administrators' => $administrators,
                        'operators'      => $operators,
                        'documents'      => !empty($newDocuments) ? $newDocuments : $documents,
                        'isEmployee'     => $isEmployee,
                        'availableRoles' => $availableRoles,
                    ])
                ]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                return Json::encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ]);
            }
        }

        return Json::encode([
            'success' => true,
            'tpl'     => $this->renderAjax('_form_update_ajax', [
                'model'          => $model,
                'administrators' => $administrators,
                'operators'      => $operators,
                'documents'      => $documents,
                'isEmployee'     => $isEmployee,
                'availableRoles' => $availableRoles,
            ])
        ]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if ( !Yii::$app->user->can('super-administrator')) {
            throw new \yii\web\ForbiddenHttpException('Only administrators can delete users.');
        }
        $model = $this->findModel($id);
        \common\services\ActionLogService::logEntityDelete($model, \common\models\ActionLog::ENTITY_USER);
        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionAjaxValidation($id) {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Get list of employees (workers) for assignment/selection
     * @return array JSON response
     */
    public function actionGetWorkers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $query = isset($_GET['q']) ? $_GET['q'] : '';

        $employees = User::find()
            ->innerJoin('auth_assignment', 'auth_assignment.user_id = user.id')
            ->where(['auth_assignment.item_name' => 'employee'])
           // ->andWhere(['user.status' => User::STATUS_ACTIVE])
            ->andFilterWhere(['like', "CONCAT(user.first_name, ' ', user.last_name)", $query])
            ->limit(20)
            ->all();

        $results = [];
        foreach ($employees as $employee) {
            $results[] = [
                'id' => $employee->id,
                'name' => $employee->getFullName(),
                'email' => $employee->email,
            ];
        }

        return $results;
    }

    /**
     *
     */
    public function actionChangePassword($id)
    {
        if (!Yii::$app->user->can('super-administrator')) {
            Yii::$app->getResponse()->setStatusCode(403);
            throw new NotFoundHttpException();
        }

        try {
            $user = $this->findModel($id);
        } catch (NotFoundHttpException $e) {
            return Json::encode([
                'success' => false,
                'message' => 'User not found.'
            ]);
        }

        $model = new ChangePasswordForm();
        $model->setUser($user);

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->changePassword()) {
                return Json::encode([
                    'success' => true,
                    'message' => 'Password changed successfully.'
                ]);
            }

            return Json::encode([
                'success' => false,
                'tpl' => $this->renderAjax('change-password', [
                    'model' => $model,
                ])
            ]);
        }

        return Json::encode([
            'success' => true,
            'tpl' => $this->renderAjax('change-password', [
                'model' => $model,
            ])
        ]);
    }

  /*  public function actionViewContractPdf($id)
    {
        $user = User::findOne($id);

        if (!$user->contract_pdf_path || !file_exists(Yii::getAlias('@webroot/uploads/') . $user->contract_pdf_path)) {
            throw new NotFoundHttpException('Contract PDF not found.');
        }

        $filePath = Yii::getAlias('@webroot/uploads/') . $user->contract_pdf_path;

        return Yii::$app->response->sendFile($filePath, 'contract.pdf', [
            'mimeType' => 'application/pdf',
            'inline' => true
        ]);
    }*/

    /**
     * Get list of users (excluding employees) for chat
     * @return array JSON response
     */
    public function actionGetUsers()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $query = Yii::$app->request->get('q', ''); 
        $currentUser = Yii::$app->user->identity;

        if (!$currentUser) {
            return [
                'success' => false,
                'message' => 'User not authenticated'
            ];
        }

        try {
            $userQuery = User::find()
                ->alias('u')
                ->leftJoin('auth_assignment aa', 'aa.user_id = u.id')
                ->where(['u.status' => User::STATUS_ACTIVE])
                ->andWhere(['!=', 'u.id', $currentUser->id])
                ->andWhere([
                    'or',
                    ['aa.item_name' => null],
                    ['!=', 'aa.item_name', 'employee']
                ])
                ->select(['u.id', 'u.first_name', 'u.last_name', 'u.email']);

            if (!empty($query)) {
                $userQuery->andWhere([
                    'or',
                    ['like', 'u.first_name', $query],
                    ['like', 'u.last_name', $query],
                    ['like', "CONCAT(u.first_name, ' ', u.last_name)", $query]
                ]);
            }

            $users = $userQuery
                ->orderBy(['u.first_name' => SORT_ASC, 'u.last_name' => SORT_ASC])
                ->limit(30)
                ->asArray()
                ->all();

            return [
                'success' => true,
                'users' => $users
            ];

        } catch (\Exception $e) {
            Yii::error('Failed to get users list: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to load users'
            ];
        }
    }

    /**
     * Assign investors to selected employees.
     */
    public function actionAssignInvestors()
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        if (Yii::$app->request->isPost) {
            $employeeIds = (array) Yii::$app->request->post('employee_ids', []);
            $investorIds = (array) Yii::$app->request->post('investor_ids', []);

            if (empty($employeeIds) || empty($investorIds)) {
                return Json::encode([
                    'success' => false,
                    'message' => 'Please select employees and investors.',
                ]);
            }

            $transaction = Yii::$app->db->beginTransaction();
            try {
                foreach ($employeeIds as $employeeId) {
                    foreach ($investorIds as $investorId) {
                        $existingAssignment = \common\models\InvestorEmployee::findOne([
                            'investor_id' => $investorId,
                            'employee_id' => $employeeId,
                        ]);

                        if (!$existingAssignment) {
                            $investorEmployee = new \common\models\InvestorEmployee();
                            $investorEmployee->investor_id = $investorId;
                            $investorEmployee->employee_id = $employeeId;
                            $investorEmployee->assigned_by = Yii::$app->user->id;
                            $investorEmployee->assigned_at = time();
                            $investorEmployee->save();
                        }
                    }
                }

                $transaction->commit();

                return Json::encode([
                    'success' => true,
                    'message' => 'Investors assigned successfully!',
                ]);
            } catch (\Exception $e) {
                $transaction->rollBack();
                return Json::encode([
                    'success' => false,
                    'message' => 'Failed to assign investors: ' . $e->getMessage(),
                ]);
            }
        }

        return Json::encode([
            'success' => true,
            'tpl' => $this->renderAjax('_assign_investors_form', [
                'investorsList' => \common\models\Investor::getInvestorsDropdownList(),
            ])
        ]);
    }

    /**
     * Create new project with pre-assigned employee.
     */
    public function actionAddProject($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $employee = $this->findModel($id);
        $project = new \common\models\Project();
        $project->employee_id = $id;
        $project->created_by = Yii::$app->user->id;

        if (Yii::$app->request->isPost && $project->load(Yii::$app->request->post())) {
            if ($project->save()) {
                return Json::encode([
                    'success' => true,
                    'message' => 'Project created successfully!',
                ]);
            }

            return Json::encode([
                'success' => false,
                'message' => 'Failed to create project.',
                'tpl' => $this->renderAjax('_add_project_form', [
                    'project'  => $project,
                    'employee' => $employee,
                ])
            ]);
        }

        return Json::encode([
            'success' => true,
            'tpl' => $this->renderAjax('_add_project_form', [
                'project'  => $project,
                'employee' => $employee,
            ])
        ]);
    }

    /**
     * Create new task with pre-assigned employee.
     */
    public function actionAddTask($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $employee = $this->findModel($id);
        $task = new \common\models\Task();
        $task->assigned_to = $id;
        $task->created_by = Yii::$app->user->id;

        $documents = [new \common\models\TasksDocuments()];

        if (Yii::$app->request->isPost && $task->load(Yii::$app->request->post())) {
            $documents = \backend\models\MultipleModel::createMultiple(\common\models\TasksDocuments::class);
            \backend\models\MultipleModel::loadMultiple($documents, Yii::$app->request->post());

            $transaction = Yii::$app->db->beginTransaction();
            try {
                $flag = $task->save();

                if ($flag) {
                    foreach ($documents as $index => $document) {
                        $document->task_id = $task->id;
                        $document->file = \yii\web\UploadedFile::getInstance($document, "[{$index}]file");

                        if ($document->file) {
                            if ($document->upload()) {
                                if (!$document->save(false)) {
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
                    return Json::encode([
                        'success' => true,
                        'message' => 'Task created successfully!',
                    ]);
                }

                $transaction->rollBack();
                return Json::encode([
                    'success' => false,
                    'message' => 'Failed to create task.',
                    'tpl' => $this->renderAjax('_add_task_form', [
                        'task'      => $task,
                        'employee'  => $employee,
                        'documents' => $documents,
                    ])
                ]);

            } catch (\Exception $e) {
                $transaction->rollBack();
                return Json::encode([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ]);
            }
        }

        return Json::encode([
            'success' => true,
            'tpl' => $this->renderAjax('_add_task_form', [
                'task'      => $task,
                'employee'  => $employee,
                'documents' => $documents,
            ])
        ]);
    }

    /**
     * Show send template form for employee.
     */
    public function actionSendTemplate($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $employee = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            $templateId = Yii::$app->request->post('template_id');
            $template   = \common\models\Template::findOne($templateId);

            if (!$template) {
                return Json::encode(['success' => false, 'message' => 'Template not found']);
            }

            $service = new \common\services\TemplateSendService();
            $result  = $service->send($template, $id, Yii::$app->user->id);

            return Json::encode($result);
        }

        $templatesList = \yii\helpers\ArrayHelper::map(
            \common\models\Template::find()->orderBy('title ASC')->all(),
            'id',
            function($template) {
                return '[' . $template->getCategoryName() . '] ' . $template->title;
            }
        );

        return Json::encode([
            'success' => true,
            'tpl' => $this->renderAjax('_send_template_form', [
                'employee'      => $employee,
                'templatesList' => $templatesList,
            ])
        ]);
    }

    /**
     * Archive page — shows deleted users by role tabs.
     */
    public function actionArchive()
    {
        $userService = new UserService();
        $tabsData = $userService->getTabsDataForArchive();
        $countries = \common\models\User::getCountries();

        return $this->render('archive', [
            'tabsData' => $tabsData,
            'countries' => $countries,
        ]);
    }

    /**
     * Remove employer — save snapshot, detach from entities, set status deleted.
     */
    public function actionRemoveEmployer()
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        if (!Yii::$app->user->can('deleteUser')) {
            return Json::encode([
                'success' => false,
                'message' => 'Access denied.',
            ]);
        }

        $id = Yii::$app->request->post('id');

        $employee = $this->findModel($id);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $userRole = Yii::$app->authManager->getRolesByUser($id);
            $userRoleKey = key($userRole);

            if ($userRoleKey === 'employee') {
                $projectIds = \common\models\Project::find()
                    ->select('id')
                    ->where(['employee_id' => $id])
                    ->column();

                $investorIds = \common\models\InvestorEmployee::find()
                    ->select('investor_id')
                    ->where(['employee_id' => $id])
                    ->column();

                $snapshot = new \common\models\UserArchiveSnapshot();
                $snapshot->user_id = $id;
                $snapshot->setSnapshotData([
                    'projects'  => $projectIds,
                    'investors' => $investorIds,
                ]);
                $snapshot->archived_by = Yii::$app->user->id;
                $snapshot->archived_at = time();

                if (!$snapshot->save()) {
                    $transaction->rollBack();
                    return Json::encode([
                        'success' => false,
                        'message' => 'Failed to save snapshot.',
                    ]);
                }

                \common\models\Project::updateAll(
                    ['employee_id' => null],
                    ['employee_id' => $id]
                );

                \common\models\InvestorEmployee::deleteAll(['employee_id' => $id]);
            }

            $employee->status = User::STATUS_DELETED;

            if (!$employee->save(false)) {
                $transaction->rollBack();
                return Json::encode([
                    'success' => false,
                    'message' => 'Failed to archive user.',
                ]);
            }

            $transaction->commit();

            \common\services\ActionLogService::log(
                \common\models\ActionLog::ENTITY_USER,
                $id,
                'archive',
                null,
                ['status' => User::STATUS_DELETED]
            );

            return Json::encode([
                'success' => true,
                'message' => 'User archived successfully.',
            ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return Json::encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Restore employer — restore from snapshot, reattach to entities, set status active.
     */
    public function actionRestoreEmployer()
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        if (!Yii::$app->user->can('deleteUser')) {
            return Json::encode([
                'success' => false,
                'message' => 'Access denied.',
            ]);
        }

        $id = Yii::$app->request->post('id');

        $employee = $this->findModel($id);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $snapshot = \common\models\UserArchiveSnapshot::findOne(['user_id' => $id]);

            if ($snapshot) {
                $projectIds  = $snapshot->getProjectIds();
                $investorIds = $snapshot->getInvestorIds();
                $skippedProjects = [];

                foreach ($projectIds as $projectId) {
                    $project = \common\models\Project::findOne($projectId);

                    if (!$project) {
                        continue;
                    }

                    $isActiveStatus = in_array($project->status, [
                        \common\models\Project::STATUS_ACTIVE,
                        \common\models\Project::STATUS_PLANNING,
                    ]);

                    $isFree = $project->employee_id === null;

                    if ($isActiveStatus && $isFree) {
                        $project->employee_id = $id;
                        $project->save(false);
                    } else {
                        $reason = !$isActiveStatus
                            ? 'closed'
                            : 'assigned to another employee';
                        $skippedProjects[] = $project->name . ' (' . $reason . ')';
                    }
                }

                foreach ($investorIds as $investorId) {
                    $existingAssignment = \common\models\InvestorEmployee::findOne([
                        'investor_id' => $investorId,
                        'employee_id' => $id,
                    ]);

                    if (!$existingAssignment) {
                        $investorEmployee = new \common\models\InvestorEmployee();
                        $investorEmployee->investor_id = $investorId;
                        $investorEmployee->employee_id = $id;
                        $investorEmployee->assigned_by = Yii::$app->user->id;
                        $investorEmployee->assigned_at = time();
                        $investorEmployee->save();
                    }
                }

                $snapshot->delete();
            }

            $employee->status = User::STATUS_ACTIVE;

            if (!$employee->save(false)) {
                $transaction->rollBack();
                return Json::encode([
                    'success' => false,
                    'message' => 'Failed to restore user.',
                ]);
            }

            $transaction->commit();

            \common\services\ActionLogService::log(
                \common\models\ActionLog::ENTITY_USER,
                $id,
                'restore',
                null,
                ['status' => User::STATUS_ACTIVE]
            );

            $message = 'User restored successfully.';

            if (!empty($skippedProjects)) {
                $message .= ' Projects not restored: ' . implode(', ', $skippedProjects) . '.';
            }

            return Json::encode([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return Json::encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * View employee card for call center (read-only + status change + comments)
     * @param int $id
     * @return string JSON response
     */
    public function actionForCallCenterView($id)
    {
        $model = $this->findModel($id);

        $newComment = new UserComments();
        $newComment->user_id      = $model->id;
        $newComment->commented_by = Yii::$app->user->id;

        $userComments = UserComments::find()
            ->where([
                'user_id'      => $model->id,
                'commented_by' => Yii::$app->user->id,
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $renderView = function() use ($model, &$newComment, &$userComments) {
            return $this->renderAjax('_call_center_view', [
                'model'        => $model,
                'newComment'   => $newComment,
                'userComments' => $userComments,
            ]);
        };

        if (Yii::$app->request->isPost) {
            $action = Yii::$app->request->post('action');

            switch ($action) {
                case 'add':
                    $comment = trim(Yii::$app->request->post('comment', ''));
                    if (!empty($comment)) {
                        $newComment->comment = $comment;
                        if ($newComment->save()) {
                            $newComment   = new UserComments([
                                'user_id'      => $model->id,
                                'commented_by' => Yii::$app->user->id,
                            ]);
                            $userComments = UserComments::find()
                                ->where([
                                    'user_id'      => $model->id,
                                    'commented_by' => Yii::$app->user->id,
                                ])
                                ->orderBy(['created_at' => SORT_DESC])
                                ->all();

                            return Json::encode([
                                'success' => true,
                                'message' => 'Comment added successfully',
                                'tpl'     => $renderView(),
                            ]);
                        }
                    }
                    break;

                case 'update':
                    $commentId = Yii::$app->request->post('comment_id');
                    $comment   = trim(Yii::$app->request->post('comment', ''));

                    $commentModel = UserComments::findOne([
                        'id'           => $commentId,
                        'commented_by' => Yii::$app->user->id,
                    ]);

                    if ($commentModel && !empty($comment)) {
                        $commentModel->comment = $comment;
                        if ($commentModel->save()) {
                            $userComments = UserComments::find()
                                ->where([
                                    'user_id'      => $model->id,
                                    'commented_by' => Yii::$app->user->id,
                                ])
                                ->orderBy(['created_at' => SORT_DESC])
                                ->all();

                            return Json::encode([
                                'success' => true,
                                'message' => 'Comment updated successfully',
                                'tpl'     => $renderView(),
                            ]);
                        }
                    }
                    break;

                case 'delete':
                    $commentId = Yii::$app->request->post('comment_id');

                    $commentModel = UserComments::findOne([
                        'id'           => $commentId,
                        'commented_by' => Yii::$app->user->id,
                    ]);

                    if ($commentModel && $commentModel->delete()) {
                        $userComments = UserComments::find()
                            ->where([
                                'user_id'      => $model->id,
                                'commented_by' => Yii::$app->user->id,
                            ])
                            ->orderBy(['created_at' => SORT_DESC])
                            ->all();

                        return Json::encode([
                            'success' => true,
                            'message' => 'Comment deleted successfully',
                            'tpl'     => $renderView(),
                        ]);
                    }
                    break;
            }

            return Json::encode([
                'success' => false,
                'message' => 'Operation failed',
                'tpl'     => $renderView(),
            ]);
        }

        return Json::encode([
            'success' => true,
            'tpl'     => $renderView(),
        ]);
    }

    /**
     * Change employee substatus
     * @return string JSON response
     */
    public function actionChangeStatus()
    {
        if (!Yii::$app->request->isPost) {
            return Json::encode([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        $id        = Yii::$app->request->post('id');
        $newStatus = (int) Yii::$app->request->post('substatus');

        if (!$id || !$newStatus) {
            return Json::encode([
                'success' => false,
                'message' => 'Missing required parameters'
            ]);
        }

        $model = $this->findModel($id);

        if (Yii::$app->user->can('phone-operator')) {
            $allowedTransitions = $model->getAllowedStatusTransitionsForPhoneOperator();
            if (!array_key_exists($newStatus, $allowedTransitions)) {
                return Json::encode([
                    'success' => false,
                    'message' => 'Status transition not allowed'
                ]);
            }
        }

        $model->substatus = $newStatus;

        if ($model->save()) {
            \common\services\ActionLogService::log(
                \common\models\ActionLog::ENTITY_USER,
                $model->id,
                'change_status',
                null,
                ['substatus' => $newStatus]
            );

            return Json::encode([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
        }

        return Json::encode([
            'success' => false,
            'message' => 'Failed to update status: ' . implode(', ', $model->getFirstErrors())
        ]);
    }

    /**
     * Create new investor with pre-assigned employee.
     */
    public function actionAddInvestor($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $employee = $this->findModel($id);
        $investor = new \common\models\Investor();

        if (Yii::$app->request->isPost && $investor->load(Yii::$app->request->post())) {
            $investor->created_by = Yii::$app->user->id;

            if ($investor->save()) {
                $investorEmployee = new \common\models\InvestorEmployee();
                $investorEmployee->investor_id = $investor->id;
                $investorEmployee->employee_id = $id;
                $investorEmployee->assigned_by = Yii::$app->user->id;
                $investorEmployee->assigned_at = time();
                $investorEmployee->save();

                return \yii\helpers\Json::encode([
                    'success' => true,
                    'message' => 'Investor created and assigned successfully!',
                ]);
            }

            return \yii\helpers\Json::encode([
                'success' => false,
                'message' => 'Failed to create investor.',
                'tpl' => $this->renderAjax('_add_investor_form', [
                    'investor' => $investor,
                    'employee' => $employee,
                ])
            ]);
        }

        return \yii\helpers\Json::encode([
            'success' => true,
            'tpl' => $this->renderAjax('_add_investor_form', [
                'investor' => $investor,
                'employee' => $employee,
            ])
        ]);
    }

    public function actionQuickSearch(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $q = trim(Yii::$app->request->get('q', ''));

        if (strlen($q) < 2) {
            return ['items' => []];
        }

        $users = User::find()
            ->alias('u')
            ->innerJoin('auth_assignment aa', 'aa.user_id = u.id')
            ->where(['aa.item_name' => 'employee'])
            ->andWhere(['u.status' => User::STATUS_ACTIVE])
            ->andWhere([
                'or',
                ['like', 'u.first_name',   $q],
                ['like', 'u.last_name',    $q],
                ['like', 'u.email',        $q],
                ['like', 'u.phone_number', $q],
                ['like', "CONCAT(u.first_name, ' ', u.last_name)", $q],
            ])
            ->select(['u.id', 'u.first_name', 'u.last_name', 'u.email', 'u.phone_number'])
            ->limit(20)
            ->asArray()
            ->all();

        $items = array_map(fn($user) => [
            'id'    => $user['id'],
            'text'  => trim($user['first_name'] . ' ' . $user['last_name']),
            'email' => $user['email'],
            'phone' => $user['phone_number'],
        ], $users);

        return ['items' => $items];
    }

}
