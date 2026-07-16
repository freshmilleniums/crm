<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use backend\models\TrainingModule;
use backend\models\TrainingModuleQuestion;
use backend\models\TrainingQuestionOption;

class TrainingController extends BaseController
{
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete-module' => ['POST'],
                        'delete-question' => ['POST'],
                    ],
                ],
            ]
        );
    }

    public function actionIndex()
    {
        $modules = TrainingModule::find()
            ->orderBy(['sort' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'modules' => $modules,
        ]);
    }

    public function actionCreateModule()
    {
        $model = new TrainingModule();

        if ($model->load(Yii::$app->request->post())) {
            $maxSort = TrainingModule::find()->max('sort') ?? 0;
            $model->sort = $maxSort + 1;
            $model->file = \yii\web\UploadedFile::getInstance($model, 'file');

            if ($model->save()) {
                if ($model->file) {
                    $doc = new \common\models\TasksDocuments();
                    $doc->file = $model->file;
                    if ($doc->upload()) {
                        $model->task_file = $doc->path;
                        $model->save(false);
                    }
                }
                Yii::$app->session->setFlash('success', 'Module created successfully.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('module-form', [
            'model' => $model,
        ]);
    }

    public function actionUpdateModule($id)
    {
        $model = $this->findModuleModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $model->file = \yii\web\UploadedFile::getInstance($model, 'file');

            if ($model->save()) {
                if ($model->file) {
                    $doc = new \common\models\TasksDocuments();
                    $doc->file = $model->file;
                    if ($doc->upload()) {
                        $model->task_file = $doc->path;
                        $model->save(false);
                    }
                }
                Yii::$app->session->setFlash('success', 'Module updated successfully.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('module-form', [
            'model' => $model,
        ]);
    }

    public function actionDeleteModule($id)
    {
        $this->findModuleModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Module deleted successfully.');
        return $this->redirect(['index']);
    }

    public function actionUpdateSort()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $ids = Yii::$app->request->post('ids', []);

        foreach ($ids as $index => $id) {
            TrainingModule::updateAll(['sort' => $index + 1], ['id' => $id]);
        }

        return ['success' => true];
    }

    public function actionQuestions($moduleId)
    {
        $module = $this->findModuleModel($moduleId);

        $questions = TrainingModuleQuestion::find()
            ->where(['module_id' => $moduleId])
            ->with('options')
            ->orderBy(['sort' => SORT_ASC])
            ->all();

        return $this->render('questions', [
            'module' => $module,
            'questions' => $questions,
        ]);
    }

    public function actionCreateQuestion($moduleId)
    {
        $module = $this->findModuleModel($moduleId);
        $model = new TrainingModuleQuestion();
        $model->module_id = $moduleId;

        if ($model->load(Yii::$app->request->post())) {
            $options = Yii::$app->request->post('options', []);
            $correctAnswers = Yii::$app->request->post('correct_answers', []);

            $transaction = Yii::$app->db->beginTransaction();

            $maxSort = TrainingModuleQuestion::find()
                ->where(['module_id' => $moduleId])
                ->max('sort') ?? 0;
            $model->sort = $maxSort + 1;

            if ($model->save()) {
                if (in_array($model->type, [TrainingModuleQuestion::TYPE_RADIO, TrainingModuleQuestion::TYPE_CHECKBOX])) {
                    foreach ($options as $index => $optionText) {
                        if (!empty(trim($optionText))) {
                            $option = new TrainingQuestionOption();
                            $option->question_id = $model->id;
                            $option->option_text = trim($optionText);
                            $option->sort = $index;
                            $option->is_correct = in_array($index, $correctAnswers) ? 1 : 0;
                            $option->save();
                        }
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Question created successfully.');
                return $this->redirect(['questions', 'moduleId' => $moduleId]);
            } else {
                $transaction->rollBack();
            }
        }

        return $this->render('question-form', [
            'model' => $model,
            'module' => $module,
        ]);
    }

    public function actionUpdateQuestion($id)
    {
        $model = $this->findQuestionModel($id);
        $module = $model->module;

        if ($model->load(Yii::$app->request->post())) {
            $options = Yii::$app->request->post('options', []);
            $correctAnswers = Yii::$app->request->post('correct_answers', []);

            $transaction = Yii::$app->db->beginTransaction();

            if ($model->save()) {
                TrainingQuestionOption::deleteAll(['question_id' => $model->id]);

                if (in_array($model->type, [TrainingModuleQuestion::TYPE_RADIO, TrainingModuleQuestion::TYPE_CHECKBOX])) {
                    foreach ($options as $index => $optionText) {
                        if (!empty(trim($optionText))) {
                            $option = new TrainingQuestionOption();
                            $option->question_id = $model->id;
                            $option->option_text = trim($optionText);
                            $option->sort = $index;
                            $option->is_correct = in_array($index, $correctAnswers) ? 1 : 0;
                            $option->save();
                        }
                    }
                }

                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Question updated successfully.');
                return $this->redirect(['questions', 'moduleId' => $model->module_id]);
            } else {
                $transaction->rollBack();
            }
        }

        return $this->render('question-form', [
            'model' => $model,
            'module' => $module,
        ]);
    }

    public function actionDeleteQuestion($id)
    {
        $model = $this->findQuestionModel($id);
        $moduleId = $model->module_id;
        $model->delete();

        Yii::$app->session->setFlash('success', 'Question deleted successfully.');
        return $this->redirect(['questions', 'moduleId' => $moduleId]);
    }

    public function actionUpdateQuestionSort()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $ids = Yii::$app->request->post('ids', []);

        foreach ($ids as $index => $id) {
            TrainingModuleQuestion::updateAll(['sort' => $index + 1], ['id' => $id]);
        }

        return ['success' => true];
    }

    protected function findModuleModel($id)
    {
        if (($model = TrainingModule::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested module does not exist.');
    }

    protected function findQuestionModel($id)
    {
        if (($model = TrainingModuleQuestion::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested question does not exist.');
    }
}