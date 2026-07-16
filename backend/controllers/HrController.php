<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use common\services\UserService;

/**
 * HR controller
 */
class HrController extends BaseController
{
    public function actionIndex()
    {
        $userService = new UserService();
        $courierTabs = $userService->getTabsDataForCouriersBySubstatus();

        return $this->render('index', [
            'tabsData' => $courierTabs,
        ]);
    }
}
