<?php

use yii\helpers\Html;
use backend\models\Chat;
use backend\models\ChatMessage;
use yii\helpers\Url;
use backend\models\User;
use kartik\select2\Select2;
use yii\web\JsExpression;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $chats backend\models\Chat[] */
/* @var $selectedChat backend\models\Chat|null */
/* @var $messages backend\models\ChatMessage[] */
/* @var $currentUser backend\models\User */
/* @var $totalMessages int */
/* @var $availableChatTypes array */

$this->title = 'Chat System';
$this->params['breadcrumbs'][] = $this->title;

// Register CSS file
$this->registerCssFile('@web/css/chat.css', ['depends' => [\yii\web\YiiAsset::class]]);
?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="chat-layout">
                    <!-- Chat Sidebar -->
                    <div class="chat-sidebar">
                        <div class="sidebar-header">
                            <h5 class="sidebar-title">
                                <i class="fas fa-comments"></i>
                                Chats
                            </h5>
                            <?php /*if (!empty($availableChatTypes)): ?>
                                <div class="new-chat-buttons">
                                    <button type="button" class="btn new-chat-btn" data-toggle="modal" data-target="#newPrivateChatModal" title="New Private Chat">
                                        <i class="fas fa-user"></i>
                                    </button>

                                    <button type="button" class="btn new-chat-btn" data-toggle="modal" data-target="#newGroupChatModal" title="New Group Chat">
                                        <i class="fas fa-users"></i>
                                    </button>

                                </div>
                            <?php endif;*/ ?>
                        </div>

                        <div class="chat-list-container">
                            <?php if (!$adminChat && !Yii::$app->user->can('administrator') && !Yii::$app->user->can('super-administrator')): ?>
                                <a href="<?= Url::to(['chat/admin-chat']) ?>" class="chat-item admin-chat-item" style="background: #f8f9fa; border-bottom: 2px solid #6c757d;">
                                    <div class="chat-header-info">
                                        <h6 class="chat-title">
                                            <i class="fas fa-user-shield"></i>
                                            Chat with Administrator
                                        </h6>
                                    </div>
                                    <div class="chat-preview">
                                        <em>Support chat</em>
                                    </div>
                                </a>
                            <?php endif; ?>
                            <?php if (empty($chats)): ?>
                                <div class="no-chats">
                                    <i class="fas fa-comment-alt"></i>
                                    <h6>No chats yet</h6>
                                    <p>Start a new conversation!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($chats as $chat): ?>
                                    <a href="<?= Url::to(['chat/index', 'id' => $chat->id]) ?>"
                                       class="chat-item<?= $selectedChat && $selectedChat->id === $chat->id ? ' active' : '' ?>"
                                       data-chat-id="<?= $chat->id ?>">

                                        <div class="chat-header-info">
                                            <h6 class="chat-title">
                                                <i class="fas fa-<?= Chat::getChatIcon($chat->type) ?>"></i>
                                                <?= Html::encode($chat->title ?: Chat::getDefaultChatTitle($chat)) ?>
                                            </h6>
                                            <?php if ($chat->last_message_at): ?>
                                                <span class="chat-time">
                                            <?= Yii::$app->formatter->asRelativeTime($chat->last_message_at) ?>
                                        </span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($chat->lastMessage): ?>
                                            <div class="chat-preview">
                                                <strong><?= Html::encode($chat->lastMessage->sender ?
                                                        trim($chat->lastMessage->sender->first_name . ' ' . $chat->lastMessage->sender->last_name) : 'Support') ?>:</strong>
                                                <?php if ($chat->lastMessage->hasAttachments() && empty($chat->lastMessage->message_text)): ?>
                                                    <i class="fas fa-paperclip"></i> <?= $chat->lastMessage->getAttachmentsCount() ?> attachment<?= $chat->lastMessage->getAttachmentsCount() > 1 ? 's' : '' ?>
                                                <?php else: ?>
                                                    <?= Html::encode(mb_substr($chat->lastMessage->message_text, 0, 50)) ?>
                                                    <?= mb_strlen($chat->lastMessage->message_text) > 50 ? '...' : '' ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="chat-preview">
                                                <em>No messages yet</em>
                                            </div>
                                        <?php endif; ?>

                                        <?php
                                        $unreadCount = \backend\models\ChatMessageReadStatus::getUnreadCount($chat->id);
                                        if ($unreadCount > 0):
                                            ?>
                                            <span class="chat-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Chat Content -->
                    <div class="chat-content">
                        <?php if ($selectedChat): ?>
                            <?= $this->render('_chat_body', [
                                'selectedChat' => $selectedChat,
                                'messages' => $messages,
                                'currentUser' => $currentUser,
                                'totalMessages' => $totalMessages,
                            ]) ?>
                        <?php else: ?>
                            <div class="no-chat-selected">
                                <i class="fas fa-comment-dots"></i>
                                <h5>Select a chat to start messaging</h5>
                                <p>Choose a conversation from the sidebar</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?= $this->render('_new_private_chat_modal', [
    'availableChatTypes' => $availableChatTypes,
]) ?>

<?php /*= $this->render('_new_group_chat_modal', [
    'availableChatTypes' => $availableChatTypes,
]) */?>

<?php //= $this->render('_add_participant_modal') ?>

<?php
$chatData = [
    'selectedChatId' => $selectedChat ? $selectedChat->id : null,
    'lastMessageId' => !empty($messages) ? end($messages)->id : 0,
    'totalMessages' => $totalMessages,
    'loadedMessages' => count($messages),
    'urls' => [
        'loadMoreMessages' => Url::to(['chat/load-more-messages']),
        'sendMessage' => Url::to(['chat/send-message']),
        'editMessage' => Url::to(['chat/edit-message']),
        'createChat' => Url::to(['chat/create-chat']),
        'chatIndex' => Url::to(['chat/index']),
        'addParticipants' => Url::to(['chat/add-participants']),
        'removeParticipant' => Url::to(['chat/remove-participant']),
        'searchMessages' => Url::to(['/chat/search-messages']),
    ],
    'chatTypes' => [
        'employeePrivate' => Chat::TYPE_USER_PRIVATE,
        //'employeeGroup' => Chat::TYPE_USER_GROUP,
    ],
];

$this->registerJs('
    window.chatConfig = ' . json_encode($chatData) . ';
', \yii\web\View::POS_HEAD);

// Include the enhanced chat JavaScript
$this->registerJsFile('@web/js/chat-enhanced.js', ['depends' => [\yii\web\YiiAsset::class]]);

// Additional JavaScript for modals and chat creation
$this->registerJs('
$(document).ready(function() {
    // Private chat modal handlers
    $("#newPrivateChatModal").on("hidden.bs.modal", function() {
        $("#privateParticipantSelect").val(null).trigger("change");
        $("#createPrivateChatBtn").prop("disabled", true);
    });
    
    $("#privateParticipantSelect").on("change", function() {
        $("#createPrivateChatBtn").prop("disabled", !$(this).val());
    });

    $("#createPrivateChatBtn").click(function() {
        var selectedParticipant = $("#privateParticipantSelect").val();
        if (!selectedParticipant) return;
        
         //!!! For multiple select
        var participantId = Array.isArray(selectedParticipant) ? selectedParticipant[0] : selectedParticipant;
    
        
        $(this).prop("disabled", true).html(\'<i class="fas fa-spinner fa-spin"></i> Creating...\');
        
        $.ajax({
            url: window.chatConfig.urls.createChat,
            type: "POST",
            data: { 
                chat_type: window.chatConfig.chatTypes.employeePrivate, 
                //participant_ids: [selectedParticipant]
                participant_ids: [participantId] 
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#newPrivateChatModal").modal("hide");
                    if (response.redirect) {
                        window.location.href = window.chatConfig.urls.chatIndex + "?id=" + response.chatId;
                    } else {
                        window.location.href = window.chatConfig.urls.chatIndex + "?id=" + response.chatId;
                    }
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || "Failed to create chat");
            },
            complete: function() {
                $("#createPrivateChatBtn").prop("disabled", false).html(\'<i class="fas fa-plus"></i> Create Chat\');
            }
        });
    });
    
    /*
    // Group chat modal handlers  
    $("#newGroupChatModal").on("hidden.bs.modal", function() {
        $("#groupChatForm")[0].reset();
        $("#groupParticipantSelect").val(null).trigger("change");
        $("#createGroupChatBtn").prop("disabled", true);
    });
    
    function updateGroupCreateButtonState() {
        var hasTitle = $("#groupChatTitle").val().trim() !== "";
        var hasParticipants = $("#groupParticipantSelect").val()?.length > 0;
        $("#createGroupChatBtn").prop("disabled", !hasTitle || !hasParticipants);
    }
    
    $("#groupChatTitle").on("input", updateGroupCreateButtonState);
    $("#groupParticipantSelect").on("change", updateGroupCreateButtonState);
    
    $("#createGroupChatBtn").click(function() {
        var chatTitle = $("#groupChatTitle").val().trim();
        var selectedParticipants = $("#groupParticipantSelect").val();
        
        if (!chatTitle || !selectedParticipants?.length) {
            alert("Please fill in all required fields");
            return;
        }
        
        $(this).prop("disabled", true).html(\'<i class="fas fa-spinner fa-spin"></i> Creating...\');
        
        $.ajax({
            url: window.chatConfig.urls.createChat,
            type: "POST",
            data: { 
                chat_type: window.chatConfig.chatTypes.employeeGroup, 
                chat_title: chatTitle, 
                participant_ids: selectedParticipants 
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#newGroupChatModal").modal("hide");
                    window.location.href = window.chatConfig.urls.chatIndex + "?id=" + response.chatId;
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || "Failed to create chat");
            },
            complete: function() {
                $("#createGroupChatBtn").prop("disabled", false).html(\'<i class="fas fa-plus"></i> Create Chat\');
            }
        });
    });
    
    // Add participant modal handlers
    $("#addParticipantModal").on("hidden.bs.modal", function() {
        $("#addParticipantForm")[0].reset();
        $("#addParticipantSelect").val(null).trigger("change");
        $("#addParticipantBtn").prop("disabled", true);
    });
    
    $("#addParticipantSelect").on("change", function() {
        var hasParticipants = $(this).val() && $(this).val().length > 0;
        $("#addParticipantBtn").prop("disabled", !hasParticipants);
    });
    
    $("#addParticipantBtn").click(function() {
        var chatId = $("#addParticipantChatId").val();
        var selectedParticipants = $("#addParticipantSelect").val();
        
        if (!chatId || !selectedParticipants || selectedParticipants.length === 0) {
            alert("Please select participants to add");
            return;
        }
        
        $(this).prop("disabled", true).html(\'<i class="fas fa-spinner fa-spin"></i> Adding...\');
        
        $.ajax({
            url: window.chatConfig.urls.addParticipants,
            type: "POST",
            data: {
                chat_id: chatId,
                participant_ids: selectedParticipants
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#addParticipantModal").modal("hide");
                    
                    if (response.addedParticipants && response.addedParticipants.length > 0) {
                        var participantsList = $("#participants-" + chatId + " .participants-list");
                        
                        response.addedParticipants.forEach(function(participant) {
                            var participantHtml = 
                                \'<div class="participant-item" data-user-id="\' + participant.id + \'">\' +
                                    \'<div class="participant-info">\' +                                       
                                        \'<div class="participant-details">\' +
                                            \'<div class="participant-name">\' + participant.name + \'</div>\' +
                                            \'<div class="participant-role">\' + participant.role + \'</div>\' +
                                        \'</div>\' +
                                    \'</div>\' +
                                    \'<button class="btn btn-sm btn-outline-danger remove-participant-btn" \' +
                                            \'onclick="removeParticipant(\\\'\' + chatId + \'\\\', \' + participant.id + \', \\\'\' + participant.name + \'\\\')\">\' +
                                        \'<i class="fas fa-times"></i>\' +
                                    \'</button>\' +
                                \'</div>\';
                            
                            participantsList.append(participantHtml);
                        });
                        
                        var newCount = participantsList.find(\'.participant-item\').length;
                        $("#participants-" + chatId).closest(\'.chat-participants\').find(\'.participants-count\')
                            .text(newCount + \' participant\' + (newCount == 1 ? \'\' : \'s\'));
                    }
                    
                    showNotification(response.message, "success");
                } else {
                    showNotification("Error: " + response.message, "error");
                }
            },
            error: function(xhr) {
                var errorMessage = "Failed to add participants";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showNotification(errorMessage, "error");
            },
            complete: function() {
                $("#addParticipantBtn").prop("disabled", false).html(\'<i class="fas fa-plus"></i> Add Participants\');
            }
        });
    });
    */

    function showNotification(message, type) {
        var alertClass = type === "success" ? "alert-success" : "alert-danger";
        var notification = $(\'<div class="alert \' + alertClass + \' alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 300px;">\' +
            \'<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>\' +
            message + \'</div>\');
        
        $("body").append(notification);
        
        setTimeout(function() {
            notification.alert("close");
        }, 3000);
    }
    
    window.showNotification = showNotification;
});
', \yii\web\View::POS_READY);