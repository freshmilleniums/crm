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
use common\models\Template;
use common\models\UserSentTemplate;

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
                    'send-template' => ['post'],
                    'get-template'  => ['get'],
                    'quick-reply' => ['post'],
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
                'templatesList' => $this->getTemplatesList(),
            ]),
        ];
    }

    /**
     * Reply to the incoming message with a template without opening the compose form.
     * - Recipient is the sender of the original message.
     * - Macros are replaced only in template text, quoted original message stays untouched.
     * - If recipient is a system user, a UserSentTemplate log record is created.
     */
    public function actionQuickReply()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');
        $templateId = Yii::$app->request->post('template_id');

        if (!$id || !$type || !$templateId) {
            return ['success' => false, 'message' => 'Missing required parameters: id, type, template_id'];
        }

        if ($type == 'corporate') {
            $message = CorporateEmailMessage::findOne($id);
            if (!$message || !$this->canAccessAccount($message->corporate_email_id, 'corporate')) {
                return ['success' => false, 'message' => 'Access denied'];
            }
            $account = UserCorporateEmail::findOne($message->corporate_email_id);
        } else {
            $message = ExternalEmailMessage::findOne($id);
            if (!$message || !$this->canAccessAccount($message->email_account_id, 'external')) {
                return ['success' => false, 'message' => 'Access denied'];
            }
            $account = EmailAccount::findOne($message->email_account_id);
        }

        if (!$account) {
            return ['success' => false, 'message' => 'Email account not found'];
        }

        $template = Template::findOne($templateId);
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }

        // Build disk-based attachments from template documents
        $fileAttachments = [];
        foreach ($template->documents as $doc) {
            $path = $doc->getFilePath();
            if ($path && file_exists($path)) {
                $fileAttachments[] = [
                    'path' => $path,
                    'name' => $doc->getFileName(),
                    'mime' => mime_content_type($path) ?: 'application/octet-stream',
                ];
            }
        }

        $systemUser = $this->findUserByRecipient($message->from_email);

        // Template subject if set, otherwise "Re: ..."
        $subject = $template->subject
            ? Template::applyMacros($template->subject, $systemUser)
            : 'Re: ' . $message->subject;
        $body = Template::applyMacros($template->body, $systemUser) . $this->buildReplyQuote($message);

        try {
            $smtpService = new SmtpService();
            $smtpService->sendWithFileAttachments(
                $account,
                $type,
                $message->from_email,
                $subject,
                $body,
                null,
                null,
                $fileAttachments
            );

            // Log only for known system users
            if ($systemUser) {
                $record          = UserSentTemplate::createFromTemplate(
                    $template,
                    $systemUser->id,
                    Yii::$app->user->id
                );
                $record->subject = $subject;
                $record->body    = $body;
                $record->save();
            }
        } catch (\Exception $e) {
            Yii::error('Quick reply failed: ' . $e->getMessage(), 'email');
            return ['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Reply sent successfully.'];
    }

    /**
     * Build quoted original message block (same markup as in the reply compose form).
     */
    private function buildReplyQuote($message)
    {
        $originalBody = $message->body_html
            ?: '<pre style="font-family:inherit;">' . \yii\helpers\Html::encode($message->body_text) . '</pre>';

        return '<p></p><p></p>'
            . '<blockquote style="margin:10px 0 10px 10px;padding:10px 15px;border-left:3px solid #ccc;color:#555;">'
            . '<p style="margin:0 0 8px 0;"><strong>On ' . Yii::$app->formatter->asDatetime($message->received_at) . '</strong> '
            . \yii\helpers\Html::encode($message->from_name ?: $message->from_email)
            . ' &lt;' . \yii\helpers\Html::encode($message->from_email) . '&gt; wrote:</p>'
            . '<div>' . $originalBody . '</div>'
            . '</blockquote>';
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
        $templateId  = Yii::$app->request->post('template_id');
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
            $fileAttachments = [];
            $template = null;

            // If a template was selected — attach its documents as disk-based files
            if ($templateId) {
                $template = Template::findOne($templateId);
                if ($template) {
                    foreach ($template->documents as $doc) {
                        $path = $doc->getFilePath();
                        if ($path && file_exists($path)) {
                            $fileAttachments[] = [
                                'path' => $path,
                                'name' => $doc->getFileName(),
                                'mime' => mime_content_type($path) ?: 'application/octet-stream',
                            ];
                        }
                    }
                }
            }

            if ($template) {
                // Template selected: separate email per recipient, macros are replaced per recipient
                $recipients = $this->parseRecipientsPublic((string)$to);
                $quotedBody = (string)Yii::$app->request->post('quoted_body', '');
                $errors     = [];

                foreach ($recipients as $recipientEmail) {
                    $systemUser = $this->findUserByRecipient($recipientEmail);

                    // Macros are applied only to template text, quoted original message stays untouched
                    $personalSubject = $template->subject
                        ? Template::applyMacros($template->subject, $systemUser)
                        : $subject;
                    $personalBody    = Template::applyMacros($template->body, $systemUser) . $quotedBody;

                    try {
                        $smtpService->sendWithFileAttachments(
                            $account,
                            $accountType,
                            $recipientEmail,
                            $personalSubject,
                            $personalBody,
                            $cc ?: null,
                            $bcc ?: null,
                            $fileAttachments,
                            $attachments
                        );

                        // Log only for known system users
                        if ($systemUser) {
                            $record          = UserSentTemplate::createFromTemplate(
                                $template,
                                $systemUser->id,
                                Yii::$app->user->id
                            );
                            $record->subject = $personalSubject;
                            $record->body    = $personalBody;
                            $record->save();
                        }

                    } catch (\Exception $e) {
                        Yii::error('Failed to send to ' . $recipientEmail . ': ' . $e->getMessage(), 'email');
                        $errors[] = $recipientEmail;
                    }
                }

                if (!empty($errors)) {
                    throw new \Exception(
                        count($errors) === count($recipients)
                            ? 'Failed to send to all recipients'
                            : 'Sent with errors. Failed recipients: ' . implode(', ', $errors)
                    );
                }
            } else {
                $smtpService->sendWithFileAttachments(
                    $account,
                    $accountType,
                    $to,
                    $subject,
                    $body,
                    $cc ?: null,
                    $bcc ?: null,
                    $fileAttachments,
                    $attachments
                );
            }

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
                'templatesList'   => $this->getTemplatesList(),
            ]);
        }

        return $this->render('reply', [
            'account' => $account,
            'accountType' => $type,
            'replyTo' => $message,
            'isModal' => false,
            'templatesList' => $this->getTemplatesList(),
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
                'forwardMessage'  => $message,
                'isModal'     => true,
                'templatesList'   => $this->getTemplatesList(),
            ]);
        }

        return $this->render('forward', [
            'account' => $account,
            'accountType' => $type,
            'forwardMessage' => $message,
            'isModal' => false,
            'templatesList' => $this->getTemplatesList(),
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

    /**
     * Show compose-template form for the given email account.
     */
    public function actionComposeTemplate($account_id, $account_type)
    {
        if (!$this->canAccessAccount($account_id, $account_type)) {
            throw new ForbiddenHttpException('Access denied');
        }

        $templates = \yii\helpers\ArrayHelper::map(
            Template::find()->orderBy(['title' => SORT_ASC])->all(),
            'id',
            function ($t) {
                return '[' . $t->getCategoryName() . '] ' . $t->title;
            }
        );

        return $this->renderAjax('_compose_template_modal', [
            'accountId'   => $account_id,
            'accountType' => $account_type,
            'templates'   => $templates,
        ]);
    }

    /**
     * Send a template via the active email account.
     * - Each To recipient gets a separate email (one SMTP call per recipient).
     * - If recipient email matches a system user — macros are replaced and
     *   a UserSentTemplate log record is created.
     * - If no system user found — macros are removed, no log.
     * - CC/BCC recipients are included in every email sent to each To recipient.
     */
    public function actionSendTemplate()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $accountId  = Yii::$app->request->post('account_id');
        $accountType = Yii::$app->request->post('account_type');
        $templateId = Yii::$app->request->post('template_id');
        $to         = trim(Yii::$app->request->post('to', ''));
        $cc         = trim(Yii::$app->request->post('cc', ''));
        $bcc        = trim(Yii::$app->request->post('bcc', ''));

        if (!$accountId || !$accountType || !$templateId || !$to) {
            return ['success' => false, 'message' => 'Missing required fields'];
        }

        if (!$this->canAccessAccount($accountId, $accountType)) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $template = Template::findOne($templateId);
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }

        $account = $this->findAccountModel($accountId, $accountType);

        // Build disk-based attachments from template documents
        $fileAttachments = [];
        foreach ($template->documents as $doc) {
            $path = $doc->getFilePath();
            if ($path && file_exists($path)) {
                $fileAttachments[] = [
                    'path' => $path,
                    'name' => $doc->getFileName(),
                    'mime' => mime_content_type($path) ?: 'application/octet-stream',
                ];
            }
        }

        $recipients  = $this->parseRecipientsPublic($to);
        $smtpService = new SmtpService();
        $errors      = [];

        foreach ($recipients as $recipientEmail) {
            // Try to find a system user by email to apply macros and log
            $systemUser = $this->findUserByRecipient($recipientEmail);

            // Unknown recipient - macros are removed
            $replaced = $template->replaceMacros($systemUser ? $systemUser->id : null);
            $subject  = $replaced['subject'] ?: '(No Subject)';
            $body     = $replaced['body'];

            try {
                $smtpService->sendWithFileAttachments(
                    $account,
                    $accountType,
                    $recipientEmail,
                    $subject,
                    $body,
                    $cc ?: null,
                    $bcc ?: null,
                    $fileAttachments
                );

                // Log only for known system users
                if ($systemUser) {
                    $record          = UserSentTemplate::createFromTemplate(
                        $template,
                        $systemUser->id,
                        Yii::$app->user->id
                    );
                    $record->subject = $subject;
                    $record->body    = $body;
                    $record->save();
                }

            } catch (\Exception $e) {
                Yii::error('Failed to send template to ' . $recipientEmail . ': ' . $e->getMessage(), 'email');
                $errors[] = $recipientEmail;
            }
        }

        if (empty($errors)) {
            return ['success' => true, 'message' => 'Template sent successfully'];
        }

        if (count($errors) === count($recipients)) {
            return ['success' => false, 'message' => 'Failed to send to all recipients'];
        }

        return [
            'success' => true,
            'message' => 'Sent with errors. Failed recipients: ' . implode(', ', $errors),
        ];
    }

    /**
     * Parse comma/semicolon separated email string into array of addresses.
     */
    private function parseRecipientsPublic(string $value): array
    {
        $list   = preg_split('/[,;]+/', $value);
        $result = [];
        foreach ($list as $item) {
            $item = trim($item);
            if ($item !== '') {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Find system user by recipient string ("email" or "Name <email>").
     */
    private function findUserByRecipient($recipient)
    {
        $recipient = trim((string)$recipient);

        if (preg_match('/<([^>]+)>/', $recipient, $matches)) {
            $recipient = trim($matches[1]);
        }

        return $recipient !== '' ? User::findOne(['email' => $recipient]) : null;
    }

    /**
     * Get templates list for compose form selectors.
     */
    private function getTemplatesList(): array
    {
        return \yii\helpers\ArrayHelper::map(
            Template::find()->orderBy(['title' => SORT_ASC])->all(),
            'id',
            function ($t) {
                return '[' . $t->getCategoryName() . '] ' . $t->title;
            }
        );
    }

    /**
     * Return template subject, body (with macros replaced if recipient is a system user)
     * and attachment names for use in the compose form.
     * Called via AJAX when a template is selected in the compose/reply form.
     */
    public function actionGetTemplate()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $templateId     = Yii::$app->request->get('template_id');
        $recipientEmail = trim(Yii::$app->request->get('recipient_email', ''));

        if (!$templateId) {
            return ['success' => false, 'message' => 'Missing template_id'];
        }

        $template = Template::findOne($templateId);
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }

        // Apply macros if recipient is a known system user, otherwise remove them
        $recipients = $this->parseRecipientsPublic($recipientEmail);
        $systemUser = $recipients ? $this->findUserByRecipient($recipients[0]) : null;

        $replaced = $template->replaceMacros($systemUser ? $systemUser->id : null);
        $subject  = $replaced['subject'] ?: '';
        $body     = $replaced['body'];

        // Collect attachment names to display in the form
        $attachments = [];
        foreach ($template->documents as $doc) {
            $attachments[] = $doc->getFileName();
        }

        return [
            'success'     => true,
            'subject'     => $subject,
            'body'        => $body,
            // Raw template text (macros are not replaced), used on send
            'raw_subject' => (string)$template->subject,
            'raw_body'    => (string)$template->body,
            'attachments' => $attachments,
        ];
    }
}