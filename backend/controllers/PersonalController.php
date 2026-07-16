<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use common\models\Task;
use common\models\Project;
use backend\models\TasksSearch;
use backend\models\ProjectsSearch;
use backend\models\User;
use common\models\TasksDocuments;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use backend\models\TrainingModule;
use backend\models\TrainingModuleQuestion;
use backend\models\TrainingQuestionOption;
use backend\models\UserTrainingProgress;
use backend\models\UserModuleAnswer;

/**
 * Personal controller for employee personal area
 */
class PersonalController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'access' => [
                    'class' => AccessControl::class,
                    'rules' => [
                        [
                            'allow' => true,
                            'roles' => ['employee'],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Display tasks list for employee
     * @return mixed
     */
    public function actionTasks()
    {
        $searchModel = new TasksSearch();
        $searchModel->assigned_to = Yii::$app->user->id;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('tasks', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * View task details (for expandable row)
     * @param int $id Task ID
     * @return mixed
     */
    public function actionViewTask($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $model = $this->findTaskModel($id);

        return json_encode([
            'success' => true,
            'content' => $this->renderAjax('_task_view_expanded', ['model' => $model])
        ]);
    }

    /**
     * Start task execution
     * @return mixed
     */
    public function actionStartTask()
    {
        $id = Yii::$app->request->post('id');

        if (!$id) {
            return json_encode([
                'success' => false,
                'message' => 'Task ID is required.',
            ]);
        }

        $model = $this->findTaskModel($id);

        // Check if task is in correct status
        if ($model->status != Task::STATUS_NEW) {
            return json_encode([
                'success' => false,
                'message' => 'Task cannot be started in current status.',
            ]);
        }

        $model->status = Task::STATUS_IN_PROGRESS;

        if ($model->save(false)) {
            return json_encode([
                'success' => true,
                'message' => 'Task started successfully!',
                'newStatus' => $model->getStatusName(),
            ]);
        } else {
            return json_encode([
                'success' => false,
                'message' => 'Error starting the task.',
            ]);
        }
    }

    /**
     * Mark task as completed
     * @return mixed
     */
    public function actionCompleteTask()
    {
        $id = Yii::$app->request->post('id');

        if (!$id) {
            return json_encode([
                'success' => false,
                'message' => 'Task ID is required.',
            ]);
        }

        $model = $this->findTaskModel($id);

        // Check if task is in correct status
        if ($model->status != Task::STATUS_IN_PROGRESS) {
            return json_encode([
                'success' => false,
                'message' => 'Task must be in progress to be completed.',
            ]);
        }

        $model->status = Task::STATUS_COMPLETED;

        if ($model->save(false)) {
            // TODO: Send notification to task creator about completion
            return json_encode([
                'success' => true,
                'message' => 'Task completed successfully!',
                'newStatus' => $model->getStatusName(),
            ]);
        } else {
            return json_encode([
                'success' => false,
                'message' => 'Error completing the task.',
            ]);
        }
    }

    /**
     * Download task document file
     * @param int $id Document ID
     * @return mixed
     */
    public function actionDownloadDocument($id)
    {
        $document = TasksDocuments::findOne($id);

        if (!$document) {
            throw new NotFoundHttpException('Document not found.');
        }

        // Check if user has access to this document's task
        $task = $this->findTaskModel($document->task_id);

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
     * Display projects list for employee (assigned projects only)
     * @return mixed
     */
    public function actionProjects()
    {
        $searchModel = new ProjectsSearch();
        $searchModel->employee_id = Yii::$app->user->id;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('projects', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * View project details (for expandable row) - readonly for employee
     * @param int $id Project ID
     * @return mixed
     */
    public function actionViewProject($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $model = $this->findProjectModel($id);

        return json_encode([
            'success' => true,
            'content' => $this->renderAjax('_project_view_expanded', [
                'model' => $model,
            ])
        ]);
    }

    /**
     * Find task model based on ID and check employee access
     * @param int $id Task ID
     * @return Task
     * @throws NotFoundHttpException
     */
    protected function findTaskModel($id)
    {
        $employeeId = Yii::$app->user->id;

        if (($model = Task::findOne(['id' => $id, 'assigned_to' => $employeeId])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested task does not exist or you do not have access to it.');
    }

    /**
     * Find project model based on ID and check employee access
     * @param int $id Project ID
     * @return Project
     * @throws NotFoundHttpException
     */
    protected function findProjectModel($id)
    {
        $employeeId = Yii::$app->user->id;

        if (($model = Project::findOne(['id' => $id, 'employee_id' => $employeeId])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested project does not exist or you do not have access to it.');
    }

    /**
     * Display investors list for employee (assigned investors only)
     * @return mixed
     */
    public function actionInvestors()
    {
        $employeeId = Yii::$app->user->id;

        $investors = \common\models\Investor::find()
            ->joinWith('investorEmployees')
            ->where(['investor_employee.employee_id' => $employeeId])
            ->all();

        return $this->render('investors', [
            'investors' => $investors,
        ]);
    }

    /**
     * View investor details (for expandable row) - readonly for employee
     * @param int $id Investor ID
     * @return mixed
     */
    public function actionViewInvestor($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new NotFoundHttpException('Invalid request.');
        }

        $model = $this->findInvestorModel($id);

        return json_encode([
            'success' => true,
            'content' => $this->renderAjax('_investor_view_expanded', [
                'model' => $model,
            ])
        ]);
    }

    /**
     * Find investor model based on ID and check employee access
     * @param int $id Investor ID
     * @return \common\models\Investor
     * @throws NotFoundHttpException
     */
    protected function findInvestorModel($id)
    {
        $employeeId = Yii::$app->user->id;

        $model = \common\models\Investor::find()
            ->joinWith('investorEmployees')
            ->where([
                'investors.id' => $id,
                'investor_employee.employee_id' => $employeeId
            ])
            ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested investor does not exist or you do not have access to it.');
    }

    public function actionTraining()
    {
        $userId = Yii::$app->user->id;
        $user = User::findOne($userId);

        $progress = UserTrainingProgress::findOne(['user_id' => $userId]);

        if (!$progress) {
            $firstModule = TrainingModule::find()
                ->where(['is_active' => 1])
                ->orderBy(['sort' => SORT_ASC])
                ->one();

            if (!$firstModule) {
                Yii::$app->session->setFlash('error', 'No training modules available.');
                return $this->redirect(['/site/index']);
            }

            $progress = new UserTrainingProgress();
            $progress->user_id = $userId;
            $progress->current_module_id = $firstModule->id;

            if (!$progress->save()) {
                Yii::$app->session->setFlash('error', 'Error initializing training. Please contact support.');
                return $this->redirect(['/site/index']);
            }

            Yii::$app->session->setFlash('info', 'Welcome to the training program!');
        }

        // Check current module exists
        $currentModule = TrainingModule::findOne($progress->current_module_id);

        if (!$currentModule) {
            $firstModule = TrainingModule::find()
                ->where(['is_active' => 1])
                ->orderBy(['sort' => SORT_ASC])
                ->one();

            if ($firstModule) {
                $progress->current_module_id = $firstModule->id;
                $progress->current_module_attempts = 0;
                $progress->save();

                Yii::$app->session->setFlash('warning', 'Training sequence was updated.');
            }
        } else {
            // Check sequence - all modules before current must be completed
            $modulesBeforeCurrent = TrainingModule::find()
                ->where(['is_active' => 1])
                ->andWhere(['<', 'sort', $currentModule->sort])
                ->orderBy(['sort' => SORT_ASC])
                ->all();

            $completedIds = $progress->getCompletedModulesArray();

            foreach ($modulesBeforeCurrent as $module) {
                if (!in_array($module->id, $completedIds)) {
                    $progress->current_module_id = $module->id;
                    $progress->current_module_attempts = 0;
                    $progress->save();

                    Yii::$app->session->setFlash('warning', 'Please complete module "' . $module->title . '" to continue.');
                    break;
                }
            }
        }

        $modules = TrainingModule::find()
            ->where(['is_active' => 1])
            ->orderBy(['sort' => SORT_ASC])
            ->all();

        $completedModules = $progress->getCompletedModulesArray();

        return $this->render('training', [
            'user' => $user,
            'progress' => $progress,
            'modules' => $modules,
            'completedModules' => $completedModules,
        ]);
    }

    public function actionModule($id)
    {
        $userId = Yii::$app->user->id;
        $module = $this->findTrainingModuleModel($id);

        $progress = UserTrainingProgress::findOne(['user_id' => $userId]);

        if (!$progress) {
            Yii::$app->session->setFlash('info', 'Please start from the training main page.');
            return $this->redirect(['training']);
        }

        $currentModule = TrainingModule::findOne($progress->current_module_id);

        if (!$currentModule) {
            Yii::$app->session->setFlash('warning', 'Training sequence was updated.');
            return $this->redirect(['training']);
        }

        if (!$this->canViewModule($module, $progress)) {
            Yii::$app->session->setFlash('error', 'This module is not available.');
            return $this->redirect(['training']);
        }

        return $this->render('module', [
            'module' => $module,
            'progress' => $progress,
        ]);
    }

    public function actionModuleTest($id)
    {
        $userId = Yii::$app->user->id;
        $module = $this->findTrainingModuleModel($id);

        $progress = UserTrainingProgress::findOne(['user_id' => $userId]);

        if (!$progress) {
            Yii::$app->session->setFlash('info', 'Please start from the training main page.');
            return $this->redirect(['training']);
        }

        $currentModule = TrainingModule::findOne($progress->current_module_id);

        if (!$currentModule) {
            Yii::$app->session->setFlash('warning', 'Training sequence was updated.');
            return $this->redirect(['training']);
        }

        if (!$this->canTakeTest($module, $progress)) {
            Yii::$app->session->setFlash('error', 'You can only take the test for your current module.');
            return $this->redirect(['training']);
        }

        if ($module->is_final_task) {
            Yii::$app->session->setFlash('error', 'This module is completed via task, not test.');
            return $this->redirect(['training']);
        }

        $questions = TrainingModuleQuestion::find()
            ->where(['module_id' => $id])
            ->with('options')
            ->orderBy(['sort' => SORT_ASC])
            ->all();

        if (empty($questions)) {
            Yii::$app->session->setFlash('error', 'No questions available for this module.');
            return $this->redirect(['module', 'id' => $id]);
        }

        return $this->render('module-test', [
            'module' => $module,
            'questions' => $questions,
            'progress' => $progress,
        ]);
    }

    public function actionSubmitModuleTest($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $userId = Yii::$app->user->id;
        $module = $this->findTrainingModuleModel($id);
        $progress = UserTrainingProgress::findOne(['user_id' => $userId]);

        if (!$progress) {
            return ['success' => false, 'message' => 'Training not started.'];
        }

        if (!$this->canTakeTest($module, $progress)) {
            return ['success' => false, 'message' => 'You can only take the test for your current module.'];
        }

        if ($module->is_final_task) {
            return ['success' => false, 'message' => 'This module is completed via task, not test.'];
        }

        $answers = Yii::$app->request->post('answers', []);

        if (empty($answers)) {
            return ['success' => false, 'message' => 'No answers provided.'];
        }

        $transaction = Yii::$app->db->beginTransaction();

        $questions = TrainingModuleQuestion::find()
            ->where(['module_id' => $id])
            ->with('options')
            ->all();

        $totalQuestions = count($questions);
        $correctAnswers = 0;

        UserModuleAnswer::deleteAll(['user_id' => $userId, 'module_id' => $id]);

        foreach ($questions as $question) {
            $userAnswer = $answers[$question->id] ?? null;
            $isCorrect = $this->checkAnswer($question, $userAnswer);

            if ($isCorrect) {
                $correctAnswers++;
            }

            $answerModel = new UserModuleAnswer();
            $answerModel->user_id = $userId;
            $answerModel->module_id = $id;
            $answerModel->question_id = $question->id;
            $answerModel->question_text = $question->question_text;
            $answerModel->is_correct = $isCorrect ? 1 : 0;
            $answerModel->setAnswerDataArray([
                'type' => $question->type,
                'answer' => $userAnswer,
            ]);
            $answerModel->save();
        }

        $score = ($correctAnswers / $totalQuestions) * 100;
        $passed = $score >= $module->passing_score;

        if ($module->id == $progress->current_module_id) {
            $progress->current_module_attempts += 1;
        }

        $progress->last_attempt_at = time();
        $progress->last_attempt_score = $score;

        if ($passed) {
            $progress->addCompletedModule($module->id);
            if ($module->id == $progress->current_module_id) {
                $nextModule = TrainingModule::find()
                    ->where(['is_active' => 1])
                    ->andWhere(['>', 'sort', $module->sort])
                    ->orderBy(['sort' => SORT_ASC])
                    ->one();

                if ($nextModule) {
                    $progress->current_module_id = $nextModule->id;
                    $progress->current_module_attempts = 0;
                    if ($nextModule->is_final_task) {
                        $trainingTaskService = new \common\services\TrainingTaskService();
                        $trainingTaskService->createTaskForEmployee($nextModule, $userId);
                    }
                }
            }
        }

        $progress->save();
        $transaction->commit();

        return [
            'success' => true,
            'passed' => $passed,
            'attempts' => $progress->current_module_attempts,
        ];
    }

    protected function canViewModule($module, $progress)
    {
        return $module->id == $progress->current_module_id;
    }

    protected function canTakeTest($module, $progress)
    {
        if ($module->id == $progress->current_module_id) {
            return true;
        }

        $completedModules = $progress->getCompletedModulesArray();
        if (in_array($module->id, $completedModules)) {
            return true;
        }

        return false;
    }

    protected function checkAnswer($question, $userAnswer)
    {
        if (!$question->has_correct_answer) {
            return !empty($userAnswer);
        }

        $correctOptions = TrainingQuestionOption::find()
            ->where(['question_id' => $question->id, 'is_correct' => 1])
            ->all();

        switch ($question->type) {
            case TrainingModuleQuestion::TYPE_TEXT:
                return !empty(trim($userAnswer));

            case TrainingModuleQuestion::TYPE_RADIO:
                foreach ($correctOptions as $option) {
                    if ($option->id == $userAnswer) {
                        return true;
                    }
                }
                return false;

            case TrainingModuleQuestion::TYPE_CHECKBOX:
                if (!is_array($userAnswer)) {
                    return false;
                }
                $correctIds = array_map(function($opt) { return $opt->id; }, $correctOptions);
                sort($correctIds);
                $userIds = array_map('intval', $userAnswer);
                sort($userIds);
                return $correctIds === $userIds;

            default:
                return false;
        }
    }

    protected function findTrainingModuleModel($id)
    {
        if (($model = TrainingModule::findOne(['id' => $id, 'is_active' => 1])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested module does not exist.');
    }
}