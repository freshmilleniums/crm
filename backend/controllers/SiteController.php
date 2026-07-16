<?php

namespace backend\controllers;

use common\models\LoginForm;
use common\models\SignupForm;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\Url;
use backend\models\User;
use backend\models\Chat;
use common\services\NotificationService;
use common\services\EmployersDashboardStatisticsService;

/**
 * Site controller
 */
class SiteController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'signup'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if ($action->id === 'error' && Yii::$app->user->isGuest) {
            $exception = Yii::$app->errorHandler->exception;

            if ($exception instanceof \yii\web\NotFoundHttpException) {
                header('Location: ' . \yii\helpers\Url::to(['/site/login']));
                exit();
            }
        }

        return true;
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $authManager = Yii::$app->authManager;
        $roles = $authManager->getRolesByUser(Yii::$app->user->id);

        if (isset($roles['super-administrator'])) {
            $stats = new EmployersDashboardStatisticsService();

            return $this->render('dashboard_super_administrator', [
                'employeeStatusCounts' => $stats->getEmployeeStatusCounts(),
                'taskStats' => $stats->getTaskStatistics(),
                'administratorsOverview' => $stats->getAdministratorsOverview(),
                'callCenterLoad' => $stats->getCallCenterOperatorsLoad(),
                'trainingSummary' => $stats->getTrainingProgressSummary(),
                'unassignedResources' => $stats->getUnassignedInvestorsAndProjects(),
                'statsService' => $stats,
            ]);
        }

        if (isset($roles['administrator'])) {
            $stats = new EmployersDashboardStatisticsService();
            $adminId = Yii::$app->user->id;

            $employeeIds = User::find()
                ->where(['administrator_id' => $adminId])
                ->select('id')
                ->column();

            return $this->render('dashboard_administrator', [
                'employeeStatusCounts' => $stats->getEmployeeStatusCounts($adminId),
                'taskStats' => $stats->getTaskStatistics(['employee_ids' => $employeeIds]),
                'trainingSummary' => $stats->getTrainingProgressSummary($adminId),
                'unassignedResources' => $stats->getUnassignedInvestorsAndProjects(),
            ]);
        }

        if (isset($roles['phone-operator'])) {
            $stats = new EmployersDashboardStatisticsService();
            $operatorId = Yii::$app->user->id;

            return $this->render('dashboard_phone_operator', [
                'employeeStatusCounts' => $stats->getEmployeeStatusCounts(null, $operatorId),
                'scheduledCalls' => $stats->getScheduledCallsForOperator($operatorId),
                'statsService' => $stats,
            ]);
        }

        if (isset($roles['email-task-operator'])) {
            $stats = new EmployersDashboardStatisticsService();

            $relevantGroups = ['contract_sent', 'training', 'active_employees', 'refused', 'archived'];
            $allCounts = $stats->getEmployeeStatusCounts();

            // Filter only relevant groups for this role
            $employeeStatusCounts = array_intersect_key($allCounts, array_flip($relevantGroups));

            return $this->render('dashboard_email_task_operator', [
                'employeeStatusCounts' => $employeeStatusCounts,
                'taskStats' => $stats->getTaskStatistics(['created_by' => Yii::$app->user->id]),
            ]);
        }

        if (isset($roles['employee'])) {
            $stats  = new EmployersDashboardStatisticsService();
            $userId = Yii::$app->user->id;

            return $this->render('dashboard_employee', [
                'trainingProgress' => $stats->getTrainingProgress($userId),
                'taskStats'        => $stats->getTaskStatistics(['assigned_to' => $userId]),
                'investorProject'  => $stats->getInvestorProjectSummary($userId),
                'user'             => Yii::$app->user->identity,
            ]);
        }

        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return string|Response
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'blank';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Signup action for new employees
     *
     * @return string|Response
     */
    public function actionSignup()
    {
        $this->layout = 'blank';

        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            $user = $model->signup();
            if ($user) {
                // Assign employee role
                $auth = Yii::$app->authManager;
                $role = $auth->getRole('employee');

                if ($role) {
                    $auth->assign($role, $user->id);
                }

                // Assign call center operator via weighted round-robin
                try {
                    $operatorId = \common\models\CallCenterDistribution::getNextOperatorId(
                        Yii::$app->params['company_id']
                    );
                    if ($operatorId) {
                        \backend\models\User::updateAll(
                            ['call_center_operator_id' => $operatorId],
                            ['id' => $user->id]
                        );
                    }
                } catch (\Exception $e) {
                    Yii::error('Failed to assign call center operator: ' . $e->getMessage());
                }

                // Assign administrator via round-robin (least employees per company)
                try {
                    $userService = new \common\services\UserService();
                    $userService->assignAdministratorToEmployee($user->id, Yii::$app->params['company_id'] ?? null);
                } catch (\Exception $e) {
                    Yii::error('Failed to assign administrator: ' . $e->getMessage());
                }

                // Create employee chat after successful registration
                try {
                    $this->createEmployeeChat($user);
                } catch (\Exception $e) {
                    Yii::error('Failed to create employee chat: ' . $e->getMessage());
                }

                // Send welcome notification after successful registration
                try {
                    $notificationService = new NotificationService();
                    $notificationService->sendWelcomeNotification($user->id);
                } catch (\Exception $e) {
                    Yii::error('Failed to send welcome notification: ' . $e->getMessage());
                }

                Yii::$app->session->setFlash('success', 'Thank you for registration. You can now log in.');
                return $this->redirect(['login']);
            } else {
                Yii::$app->session->setFlash('error', 'Registration failed. Please check the form for errors.');
                return $this->render('signup', [
                    'model' => $model,
                ]);
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Create employee chat for newly registered employee
     * @param User $user
     * @return Chat|null
     */
    protected function createEmployeeChat($user)
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Create new employee private chat
            $chat = new Chat([
                'type' => Chat::TYPE_EMPLOYEE,
                'title' => 'Support with ' . trim($user->first_name . ' ' . $user->last_name),
                'employee_id' => $user->id,
            ]);

            if (!$chat->save()) {
                throw new \Exception('Failed to create chat: ' . implode(', ', $chat->getFirstErrors()));
            }

            $transaction->commit();

            Yii::info("Employee chat created for user ID: {$user->id}, chat ID: {$chat->id}");
            return $chat;

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Failed to create employee chat for user ID: {$user->id}. Error: " . $e->getMessage());
            throw $e;
        }
    }
}