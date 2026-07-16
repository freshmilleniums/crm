<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\Json;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use common\services\RemindersUsersService;

/**
 * RemindersUsersController handles reminders_users operations
 */
class RemindersUsersController extends BaseController
{
    /**
     * @var RemindersUsersService
     */
    private $remindersUsersService;

    public function __construct($id, $module, RemindersUsersService $remindersUsersService, $config = [])
    {
        $this->remindersUsersService = $remindersUsersService;
        parent::__construct($id, $module, $config);
    }

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
                        'allow' => true,
                        'roles' => ['@'], // Only authenticated users
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'mark-read' => ['POST'],
                    'mark-all-read' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Display unread reminders for current user
     * @return string
     */
    public function actionIndex()
    {
        $userId = Yii::$app->user->id;
        $remindersData = $this->remindersUsersService->getUnreadRemindersData($userId);

        return $this->render('index', [
            'reminders' => $remindersData['reminders'],
            'totalCount' => $remindersData['totalCount'],
        ]);
    }

    /**
     * Mark single reminder as read
     * @param int $id
     * @return Response
     */
    public function actionMarkRead($id)
    {
        try {
            $this->remindersUsersService->markReminderAsRead($id, Yii::$app->user->id);

            return Json::encode([
                'success' => true,
                'message' => 'Reminder marked as read.',
            ]);
        } catch (\Exception $e) {
            Yii::error('Error in markRead: ' . $e->getMessage());
            return Json::encode([
                'success' => false,
                'message' => 'Unable to mark reminder as read.',
            ]);
        }
    }

    /**
     * Mark all unread reminders as read for current user
     * @return Response
     */
    public function actionMarkAllRead()
    {
        try {
            $this->remindersUsersService->markAllRemindersAsRead(Yii::$app->user->id);

            return Json::encode([
                'success' => true,
                'message' => 'All reminders marked as read.',
            ]);
        } catch (\Exception $e) {
            Yii::error('Error in markAllRead: ' . $e->getMessage());
            return Json::encode([
                'success' => false,
                'message' => 'Unable to mark all reminders as read.',
            ]);
        }
    }

    /**
     * Get unread reminders count (AJAX endpoint)
     * @return Response
     */
    public function actionGetUnreadCount()
    {
        try {
            $count = $this->remindersUsersService->getUnreadCount(Yii::$app->user->id);

            return Json::encode([
                'success' => true,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Yii::error('Error getting unread count: ' . $e->getMessage());
            return Json::encode([
                'success' => false,
                'message' => 'Unable to get count.',
            ]);
        }
    }
}