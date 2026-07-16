<?php

namespace backend\controllers;

use Yii;
use common\models\ActionLog;
use common\services\ActionLogService;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * LogsActionController implements actions for viewing action logs.
 */
class LogsActionController extends BaseController
{
    /**
     * Lists all ActionLog models grouped by entity type (tabs).
     * @return mixed
     */
    public function actionIndex()
    {
        $service = new ActionLogService();
        $tabsData = $service->getTabsDataForLogs();

        return $this->render('index', [
            'tabsData' => $tabsData,
        ]);
    }

    /**
     * Displays a single ActionLog model with details (AJAX expandable row).
     * @param int $id ActionLog ID
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
     * Finds the ActionLog model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ActionLog ID
     * @return ActionLog the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ActionLog::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}