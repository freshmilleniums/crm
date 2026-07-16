<?php

namespace backend\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use common\models\CallCenterDistribution;
use common\models\CallCenterScript;
use backend\models\User;

class CallCenterSettingsController extends BaseController
{
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'save-distribution' => ['POST'],
                        'create-script'     => ['GET', 'POST'],
                        'update-script'     => ['GET', 'POST'],
                        'view-script'       => ['GET'],
                        'delete-script'     => ['POST'],
                        'update-sort'       => ['POST'],
                    ],
                ],
            ]
        );
    }

    public function actionIndex()
    {
        $companyId = Yii::$app->params['company_id'];

        $operators = User::find()
            ->innerJoin('auth_assignment aa', 'aa.user_id = user.id')
            ->where(['aa.item_name' => 'phone-operator'])
            ->andWhere(['user.status' => User::STATUS_ACTIVE])
            ->andWhere(['user.company_id' => $companyId])
            ->all();

        $distributionSettings = CallCenterDistribution::find()
            ->where(['company_id' => $companyId])
            ->indexBy('operator_id')
            ->all();

        $scripts = CallCenterScript::find()
            ->where(['company_id' => $companyId])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'operators'            => $operators,
            'distributionSettings' => $distributionSettings,
            'scripts'              => $scripts,
        ]);
    }

    public function actionSaveDistribution()
    {
        $companyId = Yii::$app->params['company_id'];
        $data      = Yii::$app->request->post('distribution', []);

        $transaction = Yii::$app->db->beginTransaction();

        try {
            foreach ($data as $operatorId => $params) {
                $isCustom   = !empty($params['is_custom']) ? 1 : 0;
                $percentage = $isCustom ? (int)($params['percentage'] ?? 0) : null;

                $record = CallCenterDistribution::findOne([
                    'company_id'  => $companyId,
                    'operator_id' => (int)$operatorId,
                ]);

                if (!$record) {
                    $record              = new CallCenterDistribution();
                    $record->company_id  = $companyId;
                    $record->operator_id = (int)$operatorId;
                }

                $record->is_custom  = $isCustom;
                $record->percentage = $percentage;

                if (!$record->save()) {
                    $transaction->rollBack();
                    return json_encode([
                        'success' => false,
                        'message' => 'Save error for operator ' . $operatorId,
                    ]);
                }
            }

            CallCenterDistribution::resetCycle($companyId);

            $transaction->commit();
            return json_encode(['success' => true, 'message' => 'Distribution saved successfully']);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage());
            return json_encode(['success' => false, 'message' => 'An error occurred']);
        }
    }

    public function actionCreateScript()
    {
        $model             = new CallCenterScript();
        $model->company_id = Yii::$app->params['company_id'];

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                return json_encode(['success' => true, 'message' => 'Script created successfully']);
            }
            return json_encode([
                'success' => false,
                'tpl'     => $this->renderAjax('_script_form', ['model' => $model]),
            ]);
        }

        return json_encode([
            'success' => true,
            'tpl'     => $this->renderAjax('_script_form', ['model' => $model]),
        ]);
    }

    public function actionUpdateScript($id)
    {
        $model = $this->findScript($id);

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                return json_encode(['success' => true, 'message' => 'Script updated successfully']);
            }
            return json_encode([
                'success' => false,
                'tpl'     => $this->renderAjax('_script_form', ['model' => $model]),
            ]);
        }

        return json_encode([
            'success' => true,
            'tpl'     => $this->renderAjax('_script_form', ['model' => $model]),
        ]);
    }

    public function actionViewScript($id)
    {
        $model = $this->findScript($id);

        return json_encode([
            'success' => true,
            'tpl'     => $this->renderAjax('_script_view', ['model' => $model]),
        ]);
    }

    public function actionDeleteScript($id)
    {
        $this->findScript($id)->delete();

        return json_encode(['success' => true, 'message' => 'Script deleted successfully']);
    }

    public function actionUpdateSort()
    {
        $ids = Yii::$app->request->post('ids', []);
        CallCenterScript::updateSortOrder($ids);

        return json_encode(['success' => true]);
    }

    protected function findScript(int $id): CallCenterScript
    {
        $model = CallCenterScript::findOne([
            'id'         => $id,
            'company_id' => Yii::$app->params['company_id'],
        ]);

        if ($model === null) {
            throw new NotFoundHttpException('Script not found.');
        }

        return $model;
    }
}