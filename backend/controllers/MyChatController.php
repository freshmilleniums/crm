<?php

namespace backend\controllers;

use backend\models\Chat;
use backend\models\ChatMessage;
use backend\models\ChatMessageAttachment;
use backend\models\ChatParticipant;
use backend\models\ChatMessageReadStatus;
use backend\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\UploadedFile;
use Yii;
use yii\web\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * MyChatController handles employee's chat functionality with enhanced features
 */
class MyChatController extends BaseController
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'send-message' => ['POST'],
                        'edit-message' => ['POST'],
                        'download-attachment' => ['GET'],
                        'mark-as-read' => ['POST'],
                        'load-more-messages' => ['GET'],
                        'get-latest-messages' => ['GET'],
                        'get-unread-count' => ['GET'],
                        'generate-websocket-token' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Display courier chat interface
     * @return string
     * @throws NotFoundHttpException if user doesn't have courier role
     */
    public function actionIndex()
    {
        $currentUser = Yii::$app->user->identity;

        if (!$currentUser) {
            throw new NotFoundHttpException('User not found');
        }

        // Check if user has employee role
        if (!Yii::$app->user->can('employee')) {
            throw new NotFoundHttpException('Access denied. Employee role required.');
        }
        // Find or create employee chat for current user
        $chat = $this->findOrCreateEmployeeChat($currentUser);

        // Get initial messages (latest 30) with attachments and reply info
        $messages = ChatMessage::find()
            ->where(['chat_id' => $chat->id])
            ->andWhere(['is_deleted' => ChatMessage::STATUS_ACTIVE])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(30)
            ->with(['sender', 'replyToMessage', 'replyToMessage.sender', 'attachments'])
            ->all();

        // Reverse for chronological order (oldest first)
        $messages = array_reverse($messages);

        // Mark messages as read for current user
        $this->markMessagesAsRead($chat->id, $currentUser->id);

        // Get total messages count for pagination
        $totalMessages = ChatMessage::find()
            ->where(['chat_id' => $chat->id])
            ->andWhere(['is_deleted' => ChatMessage::STATUS_ACTIVE])
            ->count();

        return $this->render('index', [
            'chat' => $chat,
            'messages' => $messages,
            'currentUser' => $currentUser,
            'totalMessages' => $totalMessages,
        ]);
    }

    /**
     * Send new message via AJAX
     * @return array JSON response
     */
    public function actionSendMessage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => 'Invalid request method'
            ];
        }

        $currentUser = Yii::$app->user->identity;

        // Check if user has employee role
        if (!Yii::$app->user->can('employee')) {
            return ['success' => false, 'message' => 'Access denied. Employee role required.'];
        }
        $chat = $this->findOrCreateEmployeeChat($currentUser);

        $messageText = trim(Yii::$app->request->post('message_text', ''));
        $messageType = (int) Yii::$app->request->post('message_type', ChatMessage::MESSAGE_TYPE_TEXT);
        $replyToMessageId = (int) Yii::$app->request->post('reply_to_message_id', 0) ?: null;

        // Get uploaded files
        $uploadedFiles = UploadedFile::getInstancesByName('attachments');

        // Validate that message has either text or attachments
        if (empty($messageText) && empty($uploadedFiles)) {
            return [
                'success' => false,
                'message' => 'Message must contain either text or attachments'
            ];
        }

        if (!empty($messageText) && mb_strlen($messageText) > 1000) {
            return [
                'success' => false,
                'message' => 'Message is too long (maximum 1000 characters)'
            ];
        }

        // Validate reply message if provided
        if ($replyToMessageId) {
            $replyToMessage = ChatMessage::findOne([
                'id' => $replyToMessageId,
                'chat_id' => $chat->id,
                'is_deleted' => ChatMessage::STATUS_ACTIVE
            ]);
            if (!$replyToMessage) {
                return [
                    'success' => false,
                    'message' => 'Reply message not found'
                ];
            }
        }

        // Check rate limiting (prevent spam)
        if (!$this->checkRateLimit($currentUser->id)) {
            return [
                'success' => false,
                'message' => 'You are sending messages too quickly. Please wait a moment.'
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Create new message
            $message = new ChatMessage([
                'chat_id' => $chat->id,
                'sender_id' => $currentUser->id,
                'message_text' => $messageText,
                'message_type' => $messageType,
                'reply_to_message_id' => $replyToMessageId,
            ]);

            // Set attachment files for processing
            $message->attachmentFiles = $uploadedFiles;

            if (!$message->save()) {
                throw new \Exception('Failed to save message: ' . implode(', ', $message->getFirstErrors()));
            }

            // Create read status entries for all participants except sender
            $this->createReadStatusForParticipants($message, $currentUser->id);

            $transaction->commit();

            // Load the message with all relations for response
            $message->refresh();
            $message = ChatMessage::find()
                ->where(['id' => $message->id])
                ->with(['sender', 'replyToMessage', 'replyToMessage.sender', 'attachments'])
                ->one();

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => 'Message sent successfully',
                'messageData' => $message->getFormattedData($currentUser->id)
            ];

            // Send WebSocket notification (failsafe - after successful commit)
            if (Yii::$app->has('webSocket')) {
                try {
                    $messageData = [
                        'id' => $message->id,
                        'sender_id' => $message->sender_id,
                        'sender_name' => trim($currentUser->first_name . ' ' . $currentUser->last_name),
                        'text' => $message->message_text,
                        'created_at' => $message->created_at,
                        'has_attachments' => $message->hasAttachments(),
                        'attachments' => []
                    ];

                    if ($message->hasAttachments()) {
                        foreach ($message->attachments as $attachment) {
                            $messageData['attachments'][] = $attachment->getPreviewData();
                        }
                    }

                    Yii::$app->webSocket->sendNewMessage($chat->id, $messageData);
                } catch (\Exception $e) {
                    // Log but don't fail the request
                    Yii::warning('WebSocket notification failed: ' . $e->getMessage(), __METHOD__);
                }
            }

            return $responseData;

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Failed to send message: ' . $e->getMessage(), __METHOD__);

            return [
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Edit message via AJAX
     * @return array JSON response
     */
    public function actionEditMessage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => 'Invalid request method'
            ];
        }

        $currentUser = Yii::$app->user->identity;

        // Check if user has employee role
        if (!Yii::$app->user->can('employee')) {
            return ['success' => false, 'message' => 'Access denied. Employee role required.'];
        }
        $chat = $this->findOrCreateEmployeeChat($currentUser);

        $messageId = (int) Yii::$app->request->post('message_id', 0);
        $newText = trim(Yii::$app->request->post('message_text', ''));

        // Validate inputs
        if (empty($messageId) || empty($newText)) {
            return [
                'success' => false,
                'message' => 'Message ID and text are required'
            ];
        }

        if (mb_strlen($newText) > 1000) {
            return [
                'success' => false,
                'message' => 'Message is too long (maximum 1000 characters)'
            ];
        }

        // Find the message
        $message = ChatMessage::find()
            ->where(['id' => $messageId, 'chat_id' => $chat->id, 'is_deleted' => ChatMessage::STATUS_ACTIVE])
            ->one();

        if (!$message) {
            return [
                'success' => false,
                'message' => 'Message not found'
            ];
        }

        // Check if user can edit this message
        if (!$message->canBeEditedBy($currentUser->id)) {
            return [
                'success' => false,
                'message' => 'You cannot edit this message'
            ];
        }

        // Edit the message
        if ($message->editMessage($newText)) {
            return [
                'success' => true,
                'message' => 'Message updated successfully',
                'messageData' => [
                    'id' => $message->id,
                    'text' => $message->message_text,
                    'updated_at' => $message->updated_at,
                    'updated_at_formatted' => Yii::$app->formatter->asDatetime($message->updated_at, 'short'),
                ]
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update message'
            ];
        }
    }

    /**
     * Download attachment
     * @param int $id Attachment ID
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionDownloadAttachment($id)
    {
        $currentUser = Yii::$app->user->identity;

        if (!Yii::$app->user->can('employee')) {
            throw new NotFoundHttpException('Access denied. Employee role required.');
        }
        $chat = $this->findOrCreateEmployeeChat($currentUser);

        $attachment = ChatMessageAttachment::findOne($id);

        if (!$attachment) {
            throw new NotFoundHttpException('Attachment not found');
        }

        // Verify attachment belongs to employee's cha
        if ($attachment->message->chat_id !== $chat->id) {
            throw new NotFoundHttpException('Access denied');
        }

        $filePath = $attachment->getFilePath();

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File not found on server');
        }

        return Yii::$app->response->sendFile($filePath, $attachment->original_name);
    }

    /**
     * Load more messages via AJAX (for pagination)
     * @return array JSON response
     */
    public function actionLoadMoreMessages()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $currentUser = Yii::$app->user->identity;

        if (!Yii::$app->user->can('employee')) {
            return ['success' => false, 'message' => 'Access denied. Employee role required.'];
        }
        $chat = $this->findOrCreateEmployeeChat($currentUser);

        $offset = (int) Yii::$app->request->get('offset', 0);
        $limit = 20; // Load 20 messages at a time

        // Get older messages
        $messages = ChatMessage::find()
            ->where(['chat_id' => $chat->id])
            ->andWhere(['is_deleted' => ChatMessage::STATUS_ACTIVE])
            ->orderBy(['created_at' => SORT_DESC])
            ->offset($offset)
            ->limit($limit)
            ->with(['sender', 'replyToMessage', 'replyToMessage.sender', 'attachments'])
            ->all();

        $messageData = [];
        foreach (array_reverse($messages) as $message) {
            $messageData[] = $message->getFormattedData($currentUser->id);
        }

        // Check if there are more messages
        $totalMessages = ChatMessage::find()
            ->where(['chat_id' => $chat->id])
            ->andWhere(['is_deleted' => ChatMessage::STATUS_ACTIVE])
            ->count();

        $hasMore = ($offset + $limit) < $totalMessages;

        return [
            'success' => true,
            'messages' => $messageData,
            'hasMore' => $hasMore,
            'nextOffset' => $offset + $limit,
        ];
    }

    /**
     * Mark messages as read for current user
     * @return array JSON response
     */
    public function actionMarkAsRead()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $currentUser = Yii::$app->user->identity;

        if (!Yii::$app->user->can('employee')) {
            return ['success' => false, 'message' => 'Access denied. Employee role required.'];
        }
        $chat = $this->findOrCreateEmployeeChat($currentUser);

        $result = ChatMessageReadStatus::markAllAsReadInChat($currentUser->id, $chat->id);

        return [
            'success' => $result,
            'message' => $result ? 'Messages marked as read' : 'Failed to mark messages as read'
        ];
    }

    /**
     * Get new messages count via AJAX
     * @return array JSON response
     */
    public function actionGetUnreadCount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $currentUser = Yii::$app->user->identity;

        if (!Yii::$app->user->can('employee')) {
            return ['success' => false, 'message' => 'Access denied. Employee role required.'];
        }
        $chat = $this->findOrCreateEmployeeChat($currentUser);

        $unreadCount = ChatMessageReadStatus::getUnreadCount($chat->id);

        return [
            'success' => true,
            'unreadCount' => $unreadCount
        ];
    }

    /**
     * Get latest messages via AJAX for real-time updates
     * @return array JSON response
     */
    public function actionGetLatestMessages()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $currentUser = Yii::$app->user->identity;

        if (!Yii::$app->user->can('employee')) {
            return ['success' => false, 'message' => 'Access denied. Employee role required.'];
        }
        $chat = $this->findOrCreateEmployeeChat($currentUser);

        $lastMessageId = (int) Yii::$app->request->get('last_message_id', 0);

        // Get messages newer than last known message
        $messages = ChatMessage::find()
            ->where(['chat_id' => $chat->id])
            ->andWhere(['is_deleted' => ChatMessage::STATUS_ACTIVE])
            ->andWhere(['>', 'id', $lastMessageId])
            ->orderBy(['created_at' => SORT_ASC])
            ->with(['sender', 'replyToMessage', 'replyToMessage.sender', 'attachments'])
            ->all();

        $messageData = [];
        foreach ($messages as $message) {
            $messageData[] = $message->getFormattedData($currentUser->id);
        }

        // Mark new messages as read for current user (only messages from others)
        if (!empty($messages)) {
            foreach ($messages as $message) {
                if ($message->sender_id != $currentUser->id) {
                    $message->markAsReadByUser($currentUser->id);
                }
            }
        }

        return [
            'success' => true,
            'messages' => $messageData,
            'hasNewMessages' => !empty($messageData)
        ];
    }

    /**
     * Search messages
     * @return array JSON response
     */
    public function actionSearchMessages()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $currentUser = Yii::$app->user->identity;

        if (!Yii::$app->user->can('employee')) {
            return ['success' => false, 'message' => 'Access denied. Employee role required.'];
        }
        $chat = $this->findOrCreateEmployeeChat($currentUser);

        $query = trim(Yii::$app->request->get('q', ''));
        $mode = Yii::$app->request->get('mode', 'full');

        if (empty($query)) {
            return ['success' => false, 'results' => []];
        }

        $messages = ChatMessage::find()
            ->where(['chat_id' => $chat->id])
            ->andWhere(['is_deleted' => ChatMessage::STATUS_ACTIVE])
            ->andWhere(['like', 'message_text', $query])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(100)
            ->with(['sender', 'attachments'])
            ->all();

        $results = [];
        foreach ($messages as $message) {
            $results[] = [
                'id' => $message->id,
                'text' => $message->message_text,
                'sender_name' => trim($message->sender->first_name . ' ' . $message->sender->last_name),
                'created_at' => Yii::$app->formatter->asDatetime($message->created_at, 'short'),
                'has_attachments' => $message->hasAttachments(),
                'is_own' => $message->sender_id == $currentUser->id,
            ];
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Find or create employee chat for the current user
     * @param User $user
     * @return Chat
     * @throws NotFoundHttpException if user doesn't have employee role
     */
    protected function findOrCreateEmployeeChat($user)
    {
        if (!Yii::$app->user->can('employee')) {
            throw new NotFoundHttpException('Access denied. Employee role required.');
        }

        // Try to find existing employee chat for this user
        $chat = Chat::find()
            ->where(['type' => Chat::TYPE_EMPLOYEE])
            ->andWhere(['employee_id' => $user->id])
            ->one();

        if (!$chat) {
            // Create new employee chat
            $transaction = Yii::$app->db->beginTransaction();

            try {
                $chat = new Chat([
                    'type' => Chat::TYPE_EMPLOYEE,
                    'title' => 'Support with ' . trim($user->first_name . ' ' . $user->last_name),
                    'employee_id' => $user->id,
                ]);

                if (!$chat->save()) {
                    throw new \Exception('Failed to create chat: ' . implode(', ', $chat->getFirstErrors()));
                }

                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        return $chat;
    }

    /**
     * Create read status entries for all participants except sender
     * @param ChatMessage $message
     * @param int $senderId
     */
    protected function createReadStatusForParticipants($message, $senderId)
    {
        // Get all users who should see employee messages (admins)
        $employees = User::find()
            ->innerJoin('auth_assignment', 'auth_assignment.user_id = user.id')
            ->where(['in', 'auth_assignment.item_name', ['super-administrator', 'administrator']])
            ->andWhere(['user.status' => User::STATUS_ACTIVE])
            ->andWhere(['!=', 'user.id', $senderId])
            ->all();

        foreach ($employees as $employee) {
            ChatMessageReadStatus::setReadStatus($message->id, $employee->id, false);
        }
    }

    /**
     * Check rate limit for sending messages
     * @param int $userId
     * @return bool
     */
    protected function checkRateLimit($userId)
    {
        $cache = Yii::$app->cache;
        if (!$cache) {
            return true; // If no cache component, allow all messages
        }

        $cacheKey = 'message_rate_limit_' . $userId;

        $messageCount = $cache->get($cacheKey);
        if ($messageCount === false) {
            $messageCount = 0;
        }

        // Allow maximum 20 messages per minute
        if ($messageCount >= 20) {
            return false;
        }

        // Increment counter
        $cache->set($cacheKey, $messageCount + 1, 60); // 1 minute TTL

        return true;
    }

    /**
     * Mark all messages in chat as read for user
     * @param string $chatId
     * @param int $userId
     */
    protected function markMessagesAsRead($chatId, $userId)
    {
        ChatMessageReadStatus::markAllAsReadInChat($userId, $chatId);
    }

    /**
     * Generate WebSocket authentication token using JWT
     */
    public function actionGenerateWebsocketToken()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new UnauthorizedHttpException('User not authenticated');
        }

        // Check if user has employee role
        if (!Yii::$app->user->can('employee')) {
            return ['success' => false, 'message' => 'Access denied. Employee role required.'];
        }

        // Create JWT payload
        $payload = [
            'userId' => $user->id,
            'role' => 'employee',
            'iat' => time(),                    // Issued at
            'exp' => time() + (24 * 60 * 60),  // Expires in 24 hours
            'iss' => 'chat-system',             // Issuer
            'aud' => 'websocket-server'         // Audience
        ];

        // Generate JWT token
        $secret = Yii::$app->params['websocketSecret'];
        $token = JWT::encode($payload, $secret, 'HS256');

        return [
            'success' => true,
            'token' => $token,
            'expires' => $payload['exp']
        ];
    }

    public function beforeAction($action)
    {
        if ($action->id === 'generate-websocket-token') {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }
}