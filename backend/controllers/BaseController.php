<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class BaseController extends Controller
{
    /**
     * This method is invoked right before an action is executed.
     * Updates last_activity timestamp for authenticated users.
     *
     * @param \yii\base\Action $action the action to be executed.
     * @return bool whether the action should continue to run.
     */
    public function beforeAction($action) {
        if (parent::beforeAction($action)) {
            if ($action->controller->id == 'site' && in_array($action->id, ['login', 'error', 'signup'])) {
                // not redirect, allow isGuest
            } else {
                if (Yii::$app->user->isGuest) {
                    header('Location: '.\yii\helpers\Url::to('/crm-panel/login'));
                    exit();
                }
            }

            // Update last_activity for authenticated users
            if (!Yii::$app->user->isGuest) {
                $user = Yii::$app->user->identity;
                if ($user) {
                    $user->updateAttributes(['last_activity' => time()]);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Override missingAction to handle 404 for guests
     */
    public function missingAction($actionID)
    {
        if (Yii::$app->user->isGuest) {
            header('Location: ' . \yii\helpers\Url::to(['/site/login']));
            exit();
        }

        throw new NotFoundHttpException('Page not found.');
    }
}