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
use yii\web\Response;
use Yii;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * ChatController handles admin/operator chat functionality
 */
class ChatController extends BaseController
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
                        'add-participants' => ['POST'],
                        'remove-participant' => ['POST'],
                        'create-chat' => ['POST'],
                        'generate-websocket-token' => ['POST'],
                        'get-unread-count' => ['GET'],
                    ],
                ],
            ]
        );
    }

    /**
     * Display employee chat interface with chat list
     * @param string|null $id Selected chat ID
     * @return string
     */
    public function actionIndex($id = null)
    {
        $currentUser = Yii::$app->user->identity;

        if (!$currentUser) {
            throw new NotFoundHttpException('User not found');
        }

        // Initialize variables
        $selectedChat = null;
        $messages = [];
        $totalMessages = 0;

        // If chat ID provided, load that chat
        if ($id) {
            $selectedChat = $this->findChatForUser($id, $currentUser);
            if ($selectedChat) {
                if ($selectedChat->type === Chat::TYPE_EMPLOYEE) {
                    $this->ensureUserIsParticipant($selectedChat->id, $currentUser->id);
                }

                // Get initial messages (latest 50) with reply relations and attachments
                $messages = ChatMessage::find()
                    ->where(['chat_id' => $selectedChat->id])
                    ->andWhere(['is_deleted' => ChatMessage::STATUS_ACTIVE])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->limit(50)
                    ->with(['sender', 'replyToMessage', 'replyToMessage.sender', 'attachments'])
                    ->all();

                // Reverse for chronological order (oldest first)
                $messages = array_reverse($messages);

                // Mark messages as read for current user
                $this->markMessagesAsRead($selectedChat->id, $currentUser->id);

                // Get total messages count
                $totalMessages = ChatMessage::find()
                    ->where(['chat_id' => $selectedChat->id])
                    ->andWhere(['is_deleted' => ChatMessage::STATUS_ACTIVE])
                    ->count();
            }
        }

        // Get available chat types based on user permissions
        $availableChatTypes = $this->getAvailableChatTypes();

        // Get user's chats
        $chats = $this->getUserChats($currentUser);

        // Separate admin chat from other chats
        $adminChat = null;
        $otherChats = [];
        $adminIds = $this->getAdminUserIds();

        if (!empty($chats)) {
            $authManager = Yii::$app->authManager;

            foreach ($chats as $chat) {
                if ($chat->type === Chat::TYPE_USER_PRIVATE) {
                    $isAdminChat = false;

                    foreach ($chat->activeParticipants as $participant) {
                        if ($participant->id != $currentUser->id && in_array($participant->id, $adminIds)) {
                            $isAdminChat = true;
                            break;
                        }
                    }

                    if ($isAdminChat) {
                        $adminChat = $chat;
                    } else {
                        $otherChats[] = $chat;
                    }
                } else {
                    $otherChats[] = $chat;
                }
            }
        }

        // Reassemble chats with admin chat first
        if ($adminChat) {
            $chats = array_merge([$adminChat], $otherChats);
        } else {
            $chats = $otherChats;
        }






        return $this->render('index', [
            'chats' => $chats,
            'selectedChat' => $selectedChat,
            'messages' => $messages,
            'currentUser' => $currentUser,
            'totalMessages' => $totalMessages,
            'availableChatTypes' => $availableChatTypes,
            'adminChat' => $adminChat,
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

        $chatId = trim(Yii::$app->request->post('chat_id', ''));
        $messageText = trim(Yii::$app->request->post('message_text', ''));
        $messageType = (int) Yii::$app->request->post('message_type', ChatMessage::MESSAGE_TYPE_TEXT);
        $replyToMessageId = (int) Yii::$app->request->post('reply_to_message_id', 0) ?: null;

        // Validate inputs
        if (empty($chatId)) {
            return [
                'success' => false,
                'message' => 'Chat ID is required'
            ];
        }

        $currentUser = Yii::$app->user->identity;

        // Verify user has access to this chat
        $chat = $this->findChatForUser($chatId, $currentUser);
        if (!$chat) {
            return [
                'success' => false,
                'message' => 'Chat not found or access denied'
            ];
        }

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
                'chat_id' => $chatId,
                'is_deleted' => ChatMessage::STATUS_ACTIVE
            ]);
            if (!$replyToMessage) {
                return [
                    'success' => false,
                    'message' => 'Reply message not found'
                ];
            }
        }

        // Check rate limiting
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
                'message_text' => $messageText ?: '',
                'message_type' => $messageType,
                'reply_to_message_id' => $replyToMessageId,
            ]);

            // Set attachment files for processing
            $message->attachmentFiles = $uploadedFiles;

            if (!$message->save()) {
                throw new \Exception('Failed to save message: ' . implode(', ', $message->getFirstErrors()));
            }

            // Create read status entries for all participants except sender
            $this->createReadStatusForParticipants($message, $currentUser->id, $chat);

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

        $currentUser = Yii::$app->user->identity;

        // Find the message
        $message = ChatMessage::find()
            ->where(['id' => $messageId, 'is_deleted' => ChatMessage::STATUS_ACTIVE])
            ->with(['chat'])
            ->one();

        if (!$message) {
            return [
                'success' => false,
                'message' => 'Message not found'
            ];
        }

        // Verify user has access to the chat
        $chat = $this->findChatForUser($message->chat_id, $currentUser);
        if (!$chat) {
            return [
                'success' => false,
                'message' => 'Access denied'
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
     */
    public function actionDownloadAttachment($id)
    {
        $attachment = ChatMessageAttachment::findOne($id);

        if (!$attachment) {
            throw new NotFoundHttpException('Attachment not found');
        }

        $currentUser = Yii::$app->user->identity;

        // Verify user has access to the chat containing this message
        $chat = $this->findChatForUser($attachment->message->chat_id, $currentUser);
        if (!$chat) {
            throw new NotFoundHttpException('Access denied');
        }

        $filePath = $attachment->getFilePath();

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File not found on server');
        }

        return Yii::$app->response->sendFile($filePath, $attachment->original_name);
    }

    /**
     * Load more messages via AJAX
     * @return array JSON response
     */
    public function actionLoadMoreMessages()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $chatId = Yii::$app->request->get('chat_id');
        $offset = (int) Yii::$app->request->get('offset', 0);
        $limit = 20;

        $currentUser = Yii::$app->user->identity;

        // Verify access to chat
        $chat = $this->findChatForUser($chatId, $currentUser);
        if (!$chat) {
            return [
                'success' => false,
                'message' => 'Chat not found or access denied'
            ];
        }

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
     * Create new chat
     * @return array JSON response
     */
    public function actionCreateChat()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return [
                'success' => false,
                'message' => 'Invalid request method'
            ];
        }

        $chatType = Yii::$app->request->post('chat_type');
        $chatTitle = trim(Yii::$app->request->post('chat_title', ''));
        $participantIds = Yii::$app->request->post('participant_ids', []);

        $currentUser = Yii::$app->user->identity;

        // Validate inputs
        if (empty($chatType) || empty($participantIds)) {
            return [
                'success' => false,
                'message' => 'Chat type and participants are required'
            ];
        }

        // Check if user can create this type of chat
        $availableChatTypes = $this->getAvailableChatTypes();
        if (!in_array($chatType, $availableChatTypes)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to create this type of chat'
            ];
        }

        // Validate participant count and requirements based on chat type
        if ($chatType === Chat::TYPE_USER_PRIVATE) {
            if (count($participantIds) !== 1) {
                return [
                    'success' => false,
                    'message' => 'Private chat must have exactly one other participant'
                ];
            }

            // Check for existing private chat
            $existingChat = $this->findExistingPrivateChat($currentUser->id, $participantIds[0]);
            if ($existingChat) {
                return [
                    'success' => true,
                    'message' => 'Redirecting to existing chat',
                    'chatId' => $existingChat->id,
                    'redirect' => true
                ];
            }
        }

        if ($chatType === Chat::TYPE_USER_GROUP) {
            if (count($participantIds) < 1) {
                return [
                    'success' => false,
                    'message' => 'Group chat must have at least one other participant'
                ];
            }

            if (empty($chatTitle)) {
                return [
                    'success' => false,
                    'message' => 'Group chat title is required'
                ];
            }
        }

        // Validate that all participants exist and are valid employees
        $validParticipants = User::find()
            ->where(['id' => $participantIds])
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->count();

        if ($validParticipants !== count($participantIds)) {
            return [
                'success' => false,
                'message' => 'One or more selected participants are invalid'
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Create chat
            $chat = new Chat([
                'type' => $chatType,
                'title' => $chatTitle ?: null,
            ]);

            if (!$chat->save()) {
                throw new \Exception('Failed to create chat: ' . implode(', ', $chat->getFirstErrors()));
            }

            // Add current user as participant
            $currentUserParticipant = new ChatParticipant([
                'chat_id' => $chat->id,
                'user_id' => $currentUser->id,
            ]);

            if (!$currentUserParticipant->save()) {
                throw new \Exception('Failed to add current user to chat: ' . implode(', ', $currentUserParticipant->getFirstErrors()));
            }

            // Add other participants
            foreach ($participantIds as $participantId) {
                // Skip if trying to add current user (shouldn't happen, but safety check)
                if ($participantId == $currentUser->id) {
                    continue;
                }

                $participant = new ChatParticipant([
                    'chat_id' => $chat->id,
                    'user_id' => $participantId,
                ]);

                if (!$participant->save()) {
                    throw new \Exception('Failed to add participant to chat: ' . implode(', ', $participant->getFirstErrors()));
                }
            }

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Chat created successfully',
                'chatId' => $chat->id
            ];

        } catch (\Exception $e) {
            $transaction->rollBack();

            // Log the error for debugging
            Yii::error('Failed to create chat: ' . $e->getMessage(), __METHOD__);

            return [
                'success' => false,
                'message' => 'Failed to create chat. Please try again.'
            ];
        }
    }

    /**
     * Get user's chats based on their permissions
     * @param User $user
     * @return Chat[]
     */
    protected function getUserChats($user)
    {
        $query = Chat::find()
            ->with(['lastMessage.sender', 'activeParticipants', 'employee'])
            ->orderBy(['last_message_at' => SORT_DESC, 'created_at' => SORT_DESC]);

        $userRole = key(Yii::$app->authManager->getRolesByUser(Yii::$app->user->id));

        if (in_array($userRole, ['super-administrator', 'administrator'])) {
            // Admins see all employee chats + their own private chats
            $query->andWhere([
                'or',
                ['type' => Chat::TYPE_EMPLOYEE],
                [
                    'and',
                    ['type' => Chat::TYPE_USER_PRIVATE],
                    ['in', 'id',
                        ChatParticipant::find()
                            ->select('chat_id')
                            ->where(['user_id' => $user->id])
                            ->andWhere(['left_at' => null])
                    ]
                ]
            ]);
        } elseif (in_array($userRole, ['email-task-operator'])) {
            $query->andWhere([
                'and',
                ['type' => Chat::TYPE_USER_PRIVATE],
                ['in', 'id',
                    ChatParticipant::find()
                        ->select('chat_id')
                        ->where(['user_id' => $user->id])
                        ->andWhere(['left_at' => null])
                ]
            ]);
        } else {
            $query->andWhere('1=0');
        }
        return $query->all();
    }

    /**
     * Find chat that user has access to
     * @param string $chatId
     * @param User $user
     * @return Chat|null
     */
    protected function findChatForUser($chatId, $user)
    {
        $query = Chat::find()
            ->where(['id' => $chatId])
            ->with(['lastMessage.sender', 'activeParticipants', 'employee']);

        $userRole = key(Yii::$app->authManager->getRolesByUser(Yii::$app->user->id));

        if (in_array($userRole, ['super-administrator', 'administrator'])) {
            $query->andWhere([
                'or',
                // employee chats
                ['type' => Chat::TYPE_EMPLOYEE],
                // Employee chats where user is participant
                [
                    'and',
                    ['in', 'type', [Chat::TYPE_USER_PRIVATE/*, Chat::TYPE_USER_GROUP*/]],
                    ['in', 'id',
                        ChatParticipant::find()
                            ->select('chat_id')
                            ->where(['user_id' => $user->id])
                            ->andWhere(['left_at' => null])
                    ]
                ]
            ]);
        }elseif ($userRole == 'employee') {
            $query->andWhere([
                'and',
                ['type' => Chat::TYPE_EMPLOYEE],
                ['employee_id' => $user->id]
            ]);
        } else {
            // Other employees can only access chats where they are participants
            $query->andWhere([
                'and',
                ['in', 'type', [Chat::TYPE_USER_PRIVATE/*, Chat::TYPE_USER_GROUP*/]],
                ['in', 'id',
                    ChatParticipant::find()
                        ->select('chat_id')
                        ->where(['user_id' => $user->id])
                        ->andWhere(['left_at' => null])
                ]
            ]);
        }

        return $query->one();
    }

    /**
     * Get available chat types for current user
     * @return array
     */
    protected function getAvailableChatTypes()
    {
        $types = [];

        // All employees can create private and group chats
        $types[] = Chat::TYPE_USER_PRIVATE;
        // $types[] = Chat::TYPE_USER_GROUP;

        return $types;
    }

    /**
     * Find existing private chat between two users
     * @param int $user1Id
     * @param int $user2Id
     * @return Chat|null
     */
    protected function findExistingPrivateChat($user1Id, $user2Id)
    {
        // Find chats where both users are participants
        $chatIds = ChatParticipant::find()
            ->select('chat_id')
            ->where(['user_id' => $user1Id])
            ->andWhere(['left_at' => null])
            ->column();

        if (empty($chatIds)) {
            return null;
        }

        return Chat::find()
            ->where(['type' => Chat::TYPE_USER_PRIVATE])
            ->andWhere(['in', 'id', $chatIds])
            ->andWhere(['in', 'id',
                ChatParticipant::find()
                    ->select('chat_id')
                    ->where(['user_id' => $user2Id])
                    ->andWhere(['left_at' => null])
            ])
            ->one();
    }

    /**
     * Create read status entries for participants
     * @param ChatMessage $message
     * @param int $senderId
     * @param Chat $chat
     */
    protected function createReadStatusForParticipants($message, $senderId, $chat)
    {
        // For employee chats, create read status for all active participants except sender
        $participants = ChatParticipant::find()
            ->where(['chat_id' => $chat->id])
            ->andWhere(['left_at' => null])
            ->andWhere(['!=', 'user_id', $senderId])
            ->all();

        foreach ($participants as $participant) {
            ChatMessageReadStatus::setReadStatus($message->id, $participant->user_id, false);
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
            return true;
        }

        $cacheKey = 'message_rate_limit_' . $userId;
        $messageCount = $cache->get($cacheKey);
        if ($messageCount === false) {
            $messageCount = 0;
        }

        // Allow maximum 30 messages per minute for employees
        if ($messageCount >= 30) {
            return false;
        }

        $cache->set($cacheKey, $messageCount + 1, 60);
        return true;
    }

    /**
     * Mark messages as read for user
     * @param string $chatId
     * @param int $userId
     */
    protected function markMessagesAsRead($chatId, $userId)
    {
        ChatMessageReadStatus::markAllAsReadInChat($userId, $chatId);
    }

    /**
     * Add participants to chat
     * @return array JSON response
     */
    public function actionAddParticipants()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        $chatId = Yii::$app->request->post('chat_id');
        $participantIds = Yii::$app->request->post('participant_ids', []);
        $currentUser = Yii::$app->user->identity;

        // Validate inputs
        if (empty($chatId) || empty($participantIds)) {
            return ['success' => false, 'message' => 'Chat ID and participants are required'];
        }

        // Check if current user is a participant in this chat
        $isParticipant = ChatParticipant::find()
            ->where(['chat_id' => $chatId, 'user_id' => $currentUser->id, 'left_at' => null])
            ->exists();

        if (!$isParticipant) {
            return ['success' => false, 'message' => 'Access denied. You are not a participant in this chat.'];
        }

        // Verify chat exists and is a group chat
        $chat = Chat::findOne(['id' => $chatId, 'type' => Chat::TYPE_USER_GROUP]);
        if (!$chat) {
            return ['success' => false, 'message' => 'Group chat not found'];
        }

        // Validate that all participants exist and are valid employees
        $validUsers = User::find()
            ->where(['id' => $participantIds])
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->all();

        if (count($validUsers) !== count($participantIds)) {
            return ['success' => false, 'message' => 'One or more selected users are invalid'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        $addedCount = 0;
        $alreadyInChat = [];
        $addedParticipants = [];

        try {
            foreach ($validUsers as $user) {
                // Check if user is already in chat
                $existingParticipant = ChatParticipant::findOne([
                    'chat_id' => $chatId,
                    'user_id' => $user->id
                ]);

                if ($existingParticipant) {
                    if ($existingParticipant->left_at === null) {
                        // User is already active participant
                        $alreadyInChat[] = trim($user->first_name . ' ' . $user->last_name);
                        continue;
                    } else {
                        // User was in chat before but left - rejoin them
                        $existingParticipant->left_at = null;
                        if ($existingParticipant->save()) {
                            $addedCount++;
                            $addedParticipants[] = [
                                'id' => $user->id,
                                'name' => trim($user->first_name . ' ' . $user->last_name),
                                'role' => $this->getUserRole($user->id)
                            ];
                        }
                    }
                } else {
                    // Add new participant
                    $participant = new ChatParticipant([
                        'chat_id' => $chatId,
                        'user_id' => $user->id,
                    ]);

                    if ($participant->save()) {
                        $addedCount++;
                        $addedParticipants[] = [
                            'id' => $user->id,
                            'name' => trim($user->first_name . ' ' . $user->last_name),
                            'role' => $this->getUserRole($user->id)
                        ];
                    }
                }
            }

            $transaction->commit();

            $message = '';
            if ($addedCount > 0) {
                $message .= $addedCount . ' participant' . ($addedCount == 1 ? '' : 's') . ' added successfully.';
            }
            if (!empty($alreadyInChat)) {
                $message .= ' ' . implode(', ', $alreadyInChat) . ' already in chat.';
            }

            return [
                'success' => true,
                'message' => trim($message),
                'addedCount' => $addedCount,
                'addedParticipants' => $addedParticipants
            ];

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Failed to add participants: ' . $e->getMessage(), __METHOD__);

            return [
                'success' => false,
                'message' => 'Failed to add participants. Please try again.'
            ];
        }
    }

    /**
     * Remove participant from chat
     * @return array JSON response
     */
    public function actionRemoveParticipant()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'message' => 'Invalid request method'];
        }

        $chatId = Yii::$app->request->post('chat_id');
        $userId = (int) Yii::$app->request->post('user_id');
        $currentUser = Yii::$app->user->identity;

        // Validate inputs
        if (empty($chatId) || empty($userId)) {
            return ['success' => false, 'message' => 'Chat ID and user ID are required'];
        }

        // Check if current user is a participant in this chat
        $isParticipant = ChatParticipant::find()
            ->where(['chat_id' => $chatId, 'user_id' => $currentUser->id, 'left_at' => null])
            ->exists();

        if (!$isParticipant) {
            return ['success' => false, 'message' => 'Access denied. You are not a participant in this chat.'];
        }

        // Verify chat exists
        $chat = Chat::findOne($chatId);
        if (!$chat) {
            return ['success' => false, 'message' => 'Chat not found'];
        }

        // Find participant to remove
        $participant = ChatParticipant::findOne([
            'chat_id' => $chatId,
            'user_id' => $userId,
            'left_at' => null
        ]);

        if (!$participant) {
            return ['success' => false, 'message' => 'Participant not found or already removed'];
        }

        // Prevent removing the last participant
        $activeParticipantsCount = ChatParticipant::find()
            ->where(['chat_id' => $chatId, 'left_at' => null])
            ->count();

        if ($activeParticipantsCount <= 1) {
            return ['success' => false, 'message' => 'Cannot remove the last participant from the chat'];
        }

        // Remove participant
        $participant->left_at = time();
        if ($participant->save()) {
            return [
                'success' => true,
                'message' => 'Participant removed successfully',
                'removedUserId' => $userId
            ];
        }

        return ['success' => false, 'message' => 'Failed to remove participant'];
    }

    /**
     * Get user's primary role
     * @param int $userId
     * @return string
     */
    private function getUserRole($userId)
    {
        $userRoles = Yii::$app->authManager->getRolesByUser($userId);
        if (!empty($userRoles)) {
            return ucfirst(key($userRoles));
        }
        return 'Employee';
    }

    public function actionSearchMessages()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $chatId = Yii::$app->request->get('chat_id');
        $query = trim(Yii::$app->request->get('q', ''));
        $mode = Yii::$app->request->get('mode', 'full');

        if (empty($chatId) || empty($query)) {
            return ['success' => false, 'results' => []];
        }

        $currentUser = Yii::$app->user->identity;
        $chat = $this->findChatForUser($chatId, $currentUser);

        if (!$chat) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $messages = ChatMessage::find()
            ->where(['chat_id' => $chatId])
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
     * Generate WebSocket authentication token using JWT
     */
    public function actionGenerateWebsocketToken()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        if (!$user) {
            throw new UnauthorizedHttpException('User not authenticated');
        }

        // Get user role
        $roles = Yii::$app->authManager->getRolesByUser($user->id);
        $role = !empty($roles) ? key($roles) : 'employee';

        // Create JWT payload
        $payload = [
            'userId' => $user->id,
            'role' => $role,
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

    public function actionGetUnreadCount()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $unreadCount = ChatMessageReadStatus::getUnreadCount();

        return [
            'success' => true,
            'unreadCount' => $unreadCount
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
        $chatId = Yii::$app->request->post('chat_id');

        if (!$chatId) {
            return [
                'success' => false,
                'message' => 'Chat ID is required'
            ];
        }

        // Verify user has access to this chat
        $chat = $this->findChatForUser($chatId, $currentUser);
        if (!$chat) {
            return [
                'success' => false,
                'message' => 'Chat not found or access denied'
            ];
        }

        try {
            $result = ChatMessageReadStatus::markAllAsReadInChat($currentUser->id, $chatId);

            return [
                'success' => $result,
                'message' => $result ? 'Messages marked as read' : 'Failed to mark messages as read'
            ];
        } catch (Exception $e) {
            Yii::error('Failed to mark messages as read: ' . $e->getMessage(), __METHOD__);
            return [
                'success' => false,
                'message' => 'Failed to mark messages as read'
            ];
        }
    }

    /**
     * Open or create chat with administrator
     * @return Response
     */
    public function actionAdminChat()
    {
        $currentUser = Yii::$app->user->identity;

        if (!$currentUser) {
            throw new NotFoundHttpException('User not found');
        }

        // Find existing chat with any administrator
        $existingChat = $this->findExistingAdminChat($currentUser->id);

        if ($existingChat) {
            return $this->redirect(['index', 'id' => $existingChat->id]);
        }

        // Find first available administrator
        $adminIds = $this->getAdminUserIds();

        if (empty($adminIds)) {
            Yii::$app->session->setFlash('error', 'No administrators available');
            return $this->redirect(['index']);
        }

        $admin = User::find()
            ->where(['id' => $adminIds])
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        if (!$admin) {
            Yii::$app->session->setFlash('error', 'No administrators available');
            return $this->redirect(['index']);
        }

        // Create new private chat with admin
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $chat = new Chat([
                'type' => Chat::TYPE_USER_PRIVATE,
                'title' => null,
            ]);

            if (!$chat->save()) {
                throw new \Exception('Failed to create chat: ' . implode(', ', $chat->getFirstErrors()));
            }

            $currentUserParticipant = new ChatParticipant([
                'chat_id' => $chat->id,
                'user_id' => $currentUser->id,
            ]);

            if (!$currentUserParticipant->save()) {
                throw new \Exception('Failed to add current user to chat');
            }

            $adminParticipant = new ChatParticipant([
                'chat_id' => $chat->id,
                'user_id' => $admin->id,
            ]);

            if (!$adminParticipant->save()) {
                throw new \Exception('Failed to add administrator to chat');
            }

            $transaction->commit();

            return $this->redirect(['index', 'id' => $chat->id]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('Failed to create admin chat: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'Failed to create chat with administrator');
            return $this->redirect(['index']);
        }
    }

    /**
     * Find existing private chat with any administrator
     * @param int $userId
     * @return Chat|null
     */
    protected function findExistingAdminChat($userId)
    {
        $adminIds = $this->getAdminUserIds();

        if (empty($adminIds)) {
            return null;
        }

        $userChatIds = ChatParticipant::find()
            ->select('chat_id')
            ->where(['user_id' => $userId])
            ->andWhere(['left_at' => null])
            ->column();

        if (empty($userChatIds)) {
            return null;
        }

        return Chat::find()
            ->where(['type' => Chat::TYPE_USER_PRIVATE])
            ->andWhere(['in', 'id', $userChatIds])
            ->andWhere(['in', 'id',
                ChatParticipant::find()
                    ->select('chat_id')
                    ->where(['in', 'user_id', $adminIds])
                    ->andWhere(['left_at' => null])
            ])
            ->one();
    }

    /**
     * Get all active administrator user IDs
     * @return array
     */
    protected function getAdminUserIds()
    {
        return User::find()
            ->select('user.id')
            ->innerJoin('auth_assignment', 'auth_assignment.user_id = user.id')
            ->where(['in', 'auth_assignment.item_name', ['administrator', 'super-administrator']])
            ->andWhere(['user.status' => User::STATUS_ACTIVE])
            ->distinct()
            ->column();
    }

    /**
     * Ensure user is a participant in the chat
     * Adds user as participant if they are not already in the chat
     *
     * @param string $chatId
     * @param int $userId
     */
    protected function ensureUserIsParticipant($chatId, $userId)
    {
        // Check if user is already a participant
        $isParticipant = ChatParticipant::find()
            ->where([
                'chat_id' => $chatId,
                'user_id' => $userId,
            ])
            ->exists();

        if (!$isParticipant) {
            $participant = new ChatParticipant([
                'chat_id' => $chatId,
                'user_id' => $userId,
            ]);

            if (!$participant->save()) {
                Yii::error('Failed to add user as chat participant: ' . json_encode($participant->errors), __METHOD__);
            } else {
                Yii::info("User {$userId} added as participant to employee chat {$chatId}", __METHOD__);
            }
        }
    }
}