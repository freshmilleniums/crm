<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use common\models\EmailAccount;
use common\models\EmailAccountUser;
use common\models\UserCorporateEmail;
use common\models\CorporateEmailMessage;
use common\models\ExternalEmailMessage;
use common\models\ExternalEmailReadStatus;
use common\models\CorporateEmailAttachment;
use common\models\ExternalEmailAttachment;
use backend\models\CorporateEmailMessageSearch;
use backend\models\ExternalEmailMessageSearch;
use common\services\EmailSyncService;
use common\services\SmtpService;
use backend\models\User;

/**
 * EmailController handles email operations for all users
 * - Employees see only their corporate email (1 tab)
 * - Operators/Admins see corporate + assigned external emails (multiple tabs)
 */
class EmailController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'send' => ['post'],
                    'mark-read' => ['post'],
                    'mark-unread' => ['post'],
                    'mark-flagged' => ['post'],
                    'mark-unflagged' => ['post'],
                    'archive' => ['post'],
                    'unarchive' => ['post'],
                    'delete' => ['post'],
                    'sync' => ['post'],
                    'sync-all' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex($account_id = null, $account_type = null, $filter = 'inbox')
    {
        $userId = Yii::$app->user->id;

        $allAccounts = $this->getEmailAccountsForUser($userId);

        if (empty($allAccounts)) {
            $user = User::findOne($userId);

            if ($user && $user->shouldCreateCorporateEmail()) {
                $corporateEmail = $user->createCorporateEmail();

                if ($corporateEmail) {
                    Yii::$app->session->setFlash('success', 'Corporate email account created successfully.');

                    $allAccounts = $this->getEmailAccountsForUser($userId);
                }
            }

            if (empty($allAccounts)) {
                return $this->render('no-accounts');
            }
        }

        $activeAccount = $this->determineActiveAccount($allAccounts, $account_id, $account_type);

        if ($activeAccount['type'] == 'corporate') {
            $searchModel = new CorporateEmailMessageSearch();
            $searchModel->corporate_email_id = $activeAccount['id'];
            $searchModel->filter = $filter;

            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        } else {
            $searchModel = new ExternalEmailMessageSearch();
            $searchModel->email_account_id = $activeAccount['id'];
            $searchModel->filter = $filter;

            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        }

        $filterCounts = $this->getFilterCounts($activeAccount['id'], $activeAccount['type']);

        return $this->render('index', [
            'allAccounts' => $allAccounts,
            'activeAccount' => $activeAccount,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'filter' => $filter,
            'filterCounts' => $filterCounts,
        ]);
    }

    public function actionMessages($account_id, $account_type, $filter = 'inbox')
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!$this->canAccessAccount($account_id, $account_type)) {
            return [
                'success' => false,
                'message' => 'Access denied',
            ];
        }

        if ($account_type == 'corporate') {
            $searchModel = new CorporateEmailMessageSearch();
            $searchModel->corporate_email_id = $account_id;
            $searchModel->filter = $filter;

            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        } else {
            $searchModel = new ExternalEmailMessageSearch();
            $searchModel->email_account_id = $account_id;
            $searchModel->filter = $filter;

            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        }

        return [
            'success' => true,
            'html' => $this->renderPartial('_messages_list', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'accountType' => $account_type,
                'accountId' => $account_id,
                'filter' => $filter,
            ]),
        ];
    }

    public function actionView($id, $type)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if ($type == 'corporate') {
            $message = CorporateEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->corporate_email_id, 'corporate')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            if (!$message->is_read) {
                $message->is_read = 1;
                $message->save(false);
            }

        } else {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->email_account_id, 'external')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            ExternalEmailReadStatus::markAsRead($id, Yii::$app->user->id);
        }

        return [
            'success' => true,
            'html' => $this->renderPartial('_message_view', [
                'message' => $message,
                'messageType' => $type,
            ]),
        ];
    }

    public function actionCompose($account_id, $account_type)
    {
        if (!$this->canAccessAccount($account_id, $account_type)) {
            throw new ForbiddenHttpException('Access denied');
        }

        $account = $this->findAccountModel($account_id, $account_type);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_compose_form_modal', [
                'account' => $account,
                'accountType' => $account_type,
                'isModal'     => true,
            ]);
        }

        return $this->render('_compose_form', [
            'account' => $account,
            'accountType' => $account_type,
            'isModal'     => false,
        ]);
    }

    public function actionSend()
    {
        $accountId = Yii::$app->request->post('account_id');
        $accountType = Yii::$app->request->post('account_type');
        $to = Yii::$app->request->post('to');
        $subject = Yii::$app->request->post('subject');
        $body = Yii::$app->request->post('body');
        $cc = Yii::$app->request->post('cc');
        $bcc = Yii::$app->request->post('bcc');
        $isModal     = Yii::$app->request->post('is_modal') === '1';
        $attachments = UploadedFile::getInstancesByName('attachments');

        if (!$this->canAccessAccount($accountId, $accountType)) {
            if ($isModal) {
                return json_encode(['success' => false, 'message' => 'Access denied']);
            }
            throw new ForbiddenHttpException('Access denied');
        }

        $account = $this->findAccountModel($accountId, $accountType);

        try {
            $smtpService = new SmtpService();
            $smtpService->send($account, $accountType, $to, $subject, $body, $cc, $bcc, $attachments);

            if ($isModal) {
                return json_encode(['success' => true, 'message' => 'Email sent successfully.']);
            }

            Yii::$app->session->setFlash('success', 'Email sent successfully.');
            return $this->redirect(['index', 'account_id' => $accountId, 'account_type' => $accountType]);

        } catch (\Exception $e) {
            Yii::error("Failed to send email: " . $e->getMessage(), 'email');

            if ($isModal) {
                return json_encode(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()]);
            }

            Yii::$app->session->setFlash('error', 'Failed to send email: ' . $e->getMessage());
            return $this->redirect(Yii::$app->request->referrer ?: ['index']);
        }
    }

    public function actionReply($id, $type)
    {
        if ($type == 'corporate') {
            $message = CorporateEmailMessage::findOne($id);
            if (!$message || !$this->canAccessAccount($message->corporate_email_id, 'corporate')) {
                throw new ForbiddenHttpException('Access denied');
            }
            $account = UserCorporateEmail::findOne($message->corporate_email_id);
        } else {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message || !$this->canAccessAccount($message->email_account_id, 'external')) {
                throw new ForbiddenHttpException('Access denied');
            }
            $account = EmailAccount::findOne($message->email_account_id);
        }

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_compose_form_modal', [
                'account' => $account,
                'accountType' => $type,
                'replyTo' => $message,
                'isModal'     => true,
            ]);
        }

        return $this->render('reply', [
            'account' => $account,
            'accountType' => $type,
            'replyTo' => $message,
            'isModal' => false,
        ]);
    }

    public function actionForward($id, $type)
    {
        if ($type == 'corporate') {
            $message = CorporateEmailMessage::findOne($id);
            if (!$message || !$this->canAccessAccount($message->corporate_email_id, 'corporate')) {
                throw new ForbiddenHttpException('Access denied');
            }
            $account = UserCorporateEmail::findOne($message->corporate_email_id);
        } else {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message || !$this->canAccessAccount($message->email_account_id, 'external')) {
                throw new ForbiddenHttpException('Access denied');
            }
            $account = EmailAccount::findOne($message->email_account_id);
        }

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_compose_form_modal', [
                'account' => $account,
                'accountType' => $type,
                'replyTo' => $message,
                'isModal'     => true,
            ]);
        }

        return $this->render('forward', [
            'account' => $account,
            'accountType' => $type,
            'forwardMessage' => $message,
            'isModal' => false,
        ]);
    }

    public function actionDownloadAttachment($id, $type)
    {
        if ($type == 'corporate') {
            $attachment = CorporateEmailAttachment::findOne($id);
            if (!$attachment) {
                throw new NotFoundHttpException('Attachment not found.');
            }

            $message = CorporateEmailMessage::findOne($attachment->message_id);
            if (!$message || !$this->canAccessAccount($message->corporate_email_id, 'corporate')) {
                throw new ForbiddenHttpException('Access denied');
            }
        } else {
            $attachment = ExternalEmailAttachment::findOne($id);
            if (!$attachment) {
                throw new NotFoundHttpException('Attachment not found.');
            }

            $message = ExternalEmailMessage::findOne($attachment->message_id);
            if (!$message || !$this->canAccessAccount($message->email_account_id, 'external')) {
                throw new ForbiddenHttpException('Access denied');
            }
        }

        if (!file_exists($attachment->file_path)) {
            throw new NotFoundHttpException('File not found on server.');
        }

        return Yii::$app->response->sendFile($attachment->file_path, $attachment->filename);
    }

    public function actionMarkRead()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');

        if (!$id || !$type) {
            return ['success' => false, 'message' => 'Missing required parameters: id, type'];
        }

        if ($type == 'corporate') {
            $message = CorporateEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->corporate_email_id, 'corporate')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            $message->is_read = 1;
            $message->save(false);

        } else {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->email_account_id, 'external')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            ExternalEmailReadStatus::markAsRead($id, Yii::$app->user->id);
        }

        return ['success' => true];
    }

    public function actionMarkUnread()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');

        if (!$id || !$type) {
            return ['success' => false, 'message' => 'Missing required parameters: id, type'];
        }

        if ($type == 'corporate') {
            $message = CorporateEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->corporate_email_id, 'corporate')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            $message->is_read = 0;
            $message->save(false);

        } else {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->email_account_id, 'external')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            ExternalEmailReadStatus::markAsUnread($id, Yii::$app->user->id);
        }

        return ['success' => true];
    }

    public function actionMarkFlagged()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');

        if (!$id || !$type) {
            return ['success' => false, 'message' => 'Missing required parameters: id, type'];
        }

        if ($type == 'external') {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->email_account_id, 'external')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            ExternalEmailReadStatus::markAsFlagged($id, Yii::$app->user->id);

            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Flagging is only available for external emails'];
    }

    public function actionMarkUnflagged()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');

        if (!$id || !$type) {
            return ['success' => false, 'message' => 'Missing required parameters: id, type'];
        }

        if ($type == 'external') {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->email_account_id, 'external')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            ExternalEmailReadStatus::markAsUnflagged($id, Yii::$app->user->id);

            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Flagging is only available for external emails'];
    }

    public function actionArchive()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');

        if (!$id || !$type) {
            return ['success' => false, 'message' => 'Missing required parameters: id, type'];
        }

        if ($type == 'corporate') {
            $message = CorporateEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->corporate_email_id, 'corporate')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            if ($message->direction != 'incoming') {
                return ['success' => false, 'message' => 'Only incoming messages can be archived'];
            }

            $message->folder = 'Archive';
            $message->save(false);

        } else {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->email_account_id, 'external')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            if ($message->direction != 'incoming') {
                return ['success' => false, 'message' => 'Only incoming messages can be archived'];
            }

            $message->folder = 'Archive';
            $message->save(false);
        }

        return [
            'success' => true,
            'message' => 'Message archived.',
        ];
    }

    public function actionUnarchive()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');

        if (!$id || !$type) {
            return ['success' => false, 'message' => 'Missing required parameters: id, type'];
        }

        if ($type == 'corporate') {
            $message = CorporateEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->corporate_email_id, 'corporate')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            $message->folder = 'INBOX';
            $message->save(false);

        } else {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->email_account_id, 'external')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            $message->folder = 'INBOX';
            $message->save(false);
        }

        return [
            'success' => true,
            'message' => 'Message restored to inbox.',
        ];
    }

    public function actionDelete()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');

        if (!$id || !$type) {
            return ['success' => false, 'message' => 'Missing required parameters: id, type'];
        }

        $userRoles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
        if (!isset($userRoles['super-administrator']) && !isset($userRoles['administrator'])) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        if ($type == 'corporate') {
            $message = CorporateEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->corporate_email_id, 'corporate')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            foreach ($message->attachments as $attachment) {
                if (file_exists($attachment->file_path)) {
                    @unlink($attachment->file_path);
                }
            }

            $message->delete();

        } else {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message) {
                return ['success' => false, 'message' => 'Message not found'];
            }

            if (!$this->canAccessAccount($message->email_account_id, 'external')) {
                return ['success' => false, 'message' => 'Access denied'];
            }

            foreach ($message->attachments as $attachment) {
                if (file_exists($attachment->file_path)) {
                    @unlink($attachment->file_path);
                }
            }

            $message->delete();
        }

        return [
            'success' => true,
            'message' => 'Message permanently deleted.',
        ];
    }

    public function actionSync()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $account_id   = Yii::$app->request->post('account_id');
        $account_type = Yii::$app->request->post('account_type');

        if (!$account_id || !$account_type) {
            return ['success' => false, 'message' => 'Missing required parameters: account_id, account_type'];
        }

        if (!$this->canAccessAccount($account_id, $account_type)) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        try {
            $syncService = new EmailSyncService();

            if ($account_type == 'corporate') {
                $syncService->syncCorporateAccount($account_id);
                $label = 'Corporate Email';
            } else {
                $syncService->syncExternalAccount($account_id);
                $account = EmailAccount::findOne($account_id);
                $label = $account ? ($account->label ?: $account->email) : 'External Email';
            }

            Yii::$app->session->setFlash('success', "Email account '{$label}' synchronized successfully.");

        } catch (\Exception $e) {
            Yii::error("Sync failed for account {$account_id} ({$account_type}): " . $e->getMessage(), 'email');
            Yii::$app->session->setFlash('error', 'Sync failed: ' . $e->getMessage());
        }

        return ['success' => true];
    }

    public function actionSyncAll()
    {
        $userId = Yii::$app->user->id;

        $accounts = $this->getEmailAccountsForUser($userId);

        $syncService = new EmailSyncService();
        $synced = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                if ($account['type'] == 'corporate') {
                    $syncService->syncCorporateAccount($account['id']);
                } else {
                    $syncService->syncExternalAccount($account['id']);
                }
                $synced++;
            } catch (\Exception $e) {
                Yii::error("Sync failed for {$account['type']} account {$account['id']}: " . $e->getMessage(), 'email');
                $failed++;
            }
        }

        if ($synced > 0) {
            Yii::$app->session->setFlash('success', "Synchronized {$synced} email account(s).");
        }
        if ($failed > 0) {
            Yii::$app->session->setFlash('warning', "Failed to sync {$failed} account(s).");
        }

        return $this->redirect(['index']);
    }

    private function getEmailAccountsForUser($userId)
    {
        $allAccounts = [];

        $corporateEmail = UserCorporateEmail::find()
            ->where([
                'user_id' => $userId,
                'is_active' => 1,
            ])
            ->one();

        if ($corporateEmail) {
            $allAccounts[] = [
                'id' => $corporateEmail->id,
                'type' => 'corporate',
                'label' => 'Corporate Email',
                'email' => $corporateEmail->email,
                'model' => $corporateEmail,
            ];
        }

        $externalEmails = EmailAccount::find()
            ->innerJoin('email_account_users', 'email_account_users.email_account_id = email_accounts.id')
            ->where([
                'email_account_users.user_id' => $userId,
                'email_accounts.is_active' => 1,
            ])
            ->all();

        foreach ($externalEmails as $external) {
            $allAccounts[] = [
                'id' => $external->id,
                'type' => 'external',
                'label' => $external->label ?: $external->email,
                'email' => $external->email,
                'model' => $external,
            ];
        }

        return $allAccounts;
    }

    private function determineActiveAccount($allAccounts, $account_id, $account_type)
    {
        if ($account_id && $account_type) {
            foreach ($allAccounts as $acc) {
                if ($acc['id'] == $account_id && $acc['type'] == $account_type) {
                    return $acc;
                }
            }
        }

        return $allAccounts[0];
    }

    private function getFilterCounts($accountId, $accountType)
    {
        $counts = [];

        if ($accountType == 'corporate') {
            $baseQuery = CorporateEmailMessage::find()
                ->where(['corporate_email_id' => $accountId]);

            $counts['all'] = (clone $baseQuery)->count();
            $counts['inbox'] = (clone $baseQuery)->andWhere(['folder' => 'INBOX'])->count();
            $counts['sent'] = (clone $baseQuery)->andWhere(['folder' => 'Sent'])->count();
            $counts['unread'] = (clone $baseQuery)->andWhere(['is_read' => 0])->count();
            $counts['archive'] = (clone $baseQuery)->andWhere(['folder' => 'Archive'])->count();

        } else {
            $userId = Yii::$app->user->id;

            $baseQuery = ExternalEmailMessage::find()
                ->where(['email_account_id' => $accountId]);

            $counts['all'] = (clone $baseQuery)->count();
            $counts['inbox'] = (clone $baseQuery)->andWhere(['folder' => 'INBOX'])->count();
            $counts['sent'] = (clone $baseQuery)->andWhere(['folder' => 'Sent'])->count();

            $counts['unread'] = ExternalEmailMessage::find()
                ->alias('m')
                ->leftJoin('external_email_read_status rs', 'rs.message_id = m.id AND rs.user_id = :userId', [':userId' => $userId])
                ->where(['m.email_account_id' => $accountId])
                ->andWhere(['or',
                    ['rs.is_read' => 0],
                    ['rs.is_read' => null],
                ])
                ->count();

            $counts['flagged'] = ExternalEmailReadStatus::find()
                ->innerJoin('external_email_messages m', 'm.id = external_email_read_status.message_id')
                ->where([
                    'm.email_account_id' => $accountId,
                    'external_email_read_status.user_id' => $userId,
                    'external_email_read_status.is_flagged' => 1,
                ])
                ->count();

            $counts['archive'] = (clone $baseQuery)->andWhere(['folder' => 'Archive'])->count();
        }

        return $counts;
    }

    private function canAccessAccount($accountId, $accountType)
    {
        $userId = Yii::$app->user->id;

        if ($accountType == 'corporate') {
            return UserCorporateEmail::find()
                ->where([
                    'id' => $accountId,
                    'user_id' => $userId,
                    'is_active' => 1,
                ])
                ->exists();
        } else {
            return EmailAccountUser::find()
                ->where([
                    'email_account_id' => $accountId,
                    'user_id' => $userId,
                ])
                ->exists();
        }
    }

    private function findAccountModel($id, $type)
    {
        if ($type == 'corporate') {
            $model = UserCorporateEmail::findOne($id);
        } else {
            $model = EmailAccount::findOne($id);
        }

        if (!$model) {
            throw new NotFoundHttpException('Email account not found.');
        }

        return $model;
    }
}