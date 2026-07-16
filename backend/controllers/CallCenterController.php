<?php

namespace backend\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use common\services\UserService;
use common\models\ScheduledCall;
use common\models\CallCenterScript;

class CallCenterController extends BaseController
{
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'schedule-call'        => ['GET', 'POST'],
                        'mark-call-done'       => ['POST'],
                        'delete-scheduled-call'=> ['POST'],
                    ],
                ],
            ]
        );
    }

    public function actionIndex()
    {
        $companyId = Yii::$app->params['company_id'];

        $userService = new UserService();
        $tabsData    = $userService->getTabsDataForCallCenter();

        $scripts = CallCenterScript::getActiveForCompany($companyId);

        return $this->render('index', [
            'tabsData' => $tabsData,
            'scripts'  => $scripts,
        ]);
    }

    /**
     * Load schedule call form and handle save
     */
    public function actionScheduleCall($candidateId)
    {
        $candidate = $this->findCandidate($candidateId);

        $model               = new ScheduledCall();
        $model->candidate_id = $candidateId;

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                return json_encode([
                    'success' => true,
                    'message' => 'Call scheduled successfully',
                ]);
            }

            return json_encode([
                'success' => false,
                'tpl'     => $this->renderAjax('_schedule_call_form', [
                    'model'          => $model,
                    'candidate'      => $candidate,
                ]),
            ]);
        }

        return json_encode([
            'success' => true,
            'tpl'     => $this->renderAjax('_schedule_call_form', [
                'model'          => $model,
                'candidate'      => $candidate,
            ]),
        ]);
    }

    /**
     * Mark scheduled call as done
     */
    public function actionMarkCallDone()
    {
        $id    = Yii::$app->request->post('id');
        $model = ScheduledCall::findOne([
            'id'          => $id,
            'operator_id' => Yii::$app->user->id,
        ]);

        if (!$model) {
            return json_encode(['success' => false, 'message' => 'Call not found']);
        }

        $model->is_done = 1;
        $model->save(false, ['is_done', 'updated_at']);

        return json_encode(['success' => true, 'message' => 'Marked as done']);
    }

    /**
     * Delete scheduled call
     */
    public function actionDeleteScheduledCall()
    {
        $id    = Yii::$app->request->post('id');
        $model = ScheduledCall::findOne([
            'id'          => $id,
            'operator_id' => Yii::$app->user->id,
        ]);

        if (!$model) {
            return json_encode(['success' => false, 'message' => 'Call not found']);
        }

        $model->delete();

        return json_encode(['success' => true, 'message' => 'Deleted successfully']);
    }

    protected function findCandidate(int $id): \backend\models\User
    {
        $model = \backend\models\User::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Candidate not found.');
        }
        return $model;
    }
}