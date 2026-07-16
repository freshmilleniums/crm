<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use backend\models\ChatMessage;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $chat backend\models\Chat */
/* @var $messages backend\models\ChatMessage[] */
/* @var $currentUser backend\models\User */
/* @var $totalMessages int */

$this->title = 'Chat';
$this->params['breadcrumbs'][] = $this->title;

// Register necessary CSS
$this->registerCssFile('@web/css/chat.css', ['depends' => [\yii\web\YiiAsset::class]]);

?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-10 mx-auto">
                <div class="chat-container courier-chat-container">
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="chat-header-top">
                            <div class="chat-title">
                                <i class="fas fa-comments"></i>
                                Support Chat
                            </div>
                            <div class="chat-status">
                                <div id="unread-count" class="badge badge-danger" style="display: none;">0</div>
                            </div>
                            <div class="chat-search">
                                <button type="button" class="btn btn-sm btn-outline-light search-toggle-btn" title="Search messages">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="search-container" style="display: none;">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control search-input" placeholder="Search messages..." maxlength="100">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-light search-btn" type="button" title="Search">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button class="btn btn-outline-light clear-search-btn" type="button" title="Cancel search">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Container -->
                    <div class="messages-container" id="messages-container">
                        <!-- Load More Button -->
                        <button type="button" class="load-more-btn" id="load-more-btn"
                                style="<?= count($messages) >= $totalMessages ? 'display: none;' : '' ?>">
                            <i class="fas fa-chevron-up"></i> Load earlier messages
                        </button>

                        <!-- Loading Indicator -->
                        <div class="loading-indicator" id="loading-indicator">
                            <i class="fas fa-spinner fa-spin"></i> Loading messages...
                        </div>

                        <!-- Messages -->
                        <div id="messages-list">
                            <?php if (empty($messages)): ?>
                                <div class="no-messages" id="no-messages">
                                    <i class="fas fa-comment-alt fa-3x"></i>
                                    <h5>No messages yet</h5>
                                    <p>Start the conversation with our support team!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <?= $this->render('/chat/_message_item', [
                                        'message' => $message,
                                        'currentUser' => $currentUser
                                    ]) ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Typing Indicator -->
                        <div class="typing-indicator" id="typing-indicator">
                            <i class="fas fa-ellipsis-h"></i> Support is typing...
                        </div>
                    </div>

                    <!-- Reply Preview -->
                    <div class="reply-preview" id="reply-preview" style="display: none;">
                        <div class="reply-preview-content">
                            <div class="reply-preview-header">
                                <i class="fas fa-reply"></i>
                                <span>Replying to <strong id="reply-sender-name"></strong></span>
                                <button type="button" class="btn btn-sm btn-link cancel-reply-btn" onclick="cancelReply()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="reply-preview-text" id="reply-preview-text"></div>
                        </div>
                    </div>

                    <!-- Message Form -->
                    <div class="message-form">
                        <?php $form = ActiveForm::begin([
                            'id' => 'message-form',
                            'options' => [
                                'data-pjax' => false,
                                'enctype' => 'multipart/form-data'
                            ],
                        ]); ?>

                        <!-- Attachment Preview -->
                        <div class="attachment-preview-container" id="attachment-preview-container" style="display: none;">
                            <div class="attachment-preview-header">
                                <i class="fas fa-paperclip"></i>
                                <span>Attachments</span>
                                <button type="button" class="btn btn-sm btn-link clear-attachments-btn" onclick="clearAttachments()">
                                    <i class="fas fa-times"></i> Clear all
                                </button>
                            </div>
                            <div class="attachment-preview-list" id="attachment-preview-list"></div>
                        </div>

                        <div class="row align-items-end">
                            <div class="col-1 text-center message-attachment-input">
                                <!-- Attachment Button -->
                                <label for="attachment-input" class="btn btn-light attachment-btn" title="Add attachments">
                                    <i class="fas fa-paperclip"></i>
                                    <input type="file" id="attachment-input" name="attachments[]" multiple
                                           accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.odt" style="display: none;">
                                </label>
                            </div>
                            <div class="col-10 message-text-field">
                                <?= Html::textarea('message_text', '', [
                                    'class' => 'form-control message-input',
                                    'placeholder' => 'Type your message...',
                                    'rows' => 1,
                                    'id' => 'message-input',
                                    'maxlength' => 1000,
                                ]) ?>
                            </div>
                            <div class="col-1 text-center message-send-button">
                                <?= Html::button('<i class="fas fa-paper-plane"></i>', [
                                    'class' => 'btn btn-primary send-button',
                                    'id' => 'send-button',
                                    'title' => 'Send message (Ctrl+Enter)',
                                ]) ?>
                            </div>
                        </div>

                        <?= Html::hiddenInput('message_type', ChatMessage::MESSAGE_TYPE_TEXT, ['id' => 'message-type']) ?>
                        <?= Html::hiddenInput('reply_to_message_id', '', ['id' => 'reply-to-message-id']) ?>

                        <div class="form-footer">
                            <small class="text-muted">Press Ctrl+Enter to send</small>
                            <small class="char-counter" id="char-counter"></small>
                        </div>

                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Message Modal -->
    <div class="modal fade" id="editMessageModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Message</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit-message-text">Message Text</label>
                        <textarea class="form-control" id="edit-message-text" rows="3" maxlength="1000"></textarea>
                        <small class="form-text text-muted">Maximum 1000 characters</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-edit-btn">Save Changes</button>
                    <input type="hidden" id="edit-message-id">
                </div>
            </div>
        </div>
    </div>

<?php
$chatData = [
    'chatId' => $chat->id,
    'lastMessageId' => !empty($messages) ? end($messages)->id : 0,
    'totalMessages' => $totalMessages,
    'loadedMessages' => count($messages),
    'urls' => [
        'loadMoreMessages' => Url::to(['my-chat/load-more-messages']),
        'sendMessage' => Url::to(['my-chat/send-message']),
        'editMessage' => Url::to(['my-chat/edit-message']),
        'downloadAttachment' => Url::to(['my-chat/download-attachment']),
        'searchMessages' => Url::to(['my-chat/search-messages']),
    ],
];

$this->registerJs('
    window.chatConfig = ' . json_encode($chatData) . ';
', \yii\web\View::POS_HEAD);

// JavaScript functionality
$this->registerJs('
$(document).ready(function() {
    var lastMessageId = window.chatConfig.lastMessageId;
    var totalMessages = window.chatConfig.totalMessages;
    var loadedMessages = window.chatConfig.loadedMessages;
    var chatId = window.chatConfig.chatId;
    var isLoading = false;
    var isSending = false;
    var attachedFiles = [];
    var replyToMessageId = null;
    
    // Auto-scroll to bottom
    function scrollToBottom(smooth = true) {
        var container = $("#messages-container");
        if (container.length === 0) return;
        
        var scrollTop = container[0].scrollHeight - container.height();
        
        if (smooth) {
            container.animate({ scrollTop: scrollTop }, 300);
        } else {
            container.scrollTop(scrollTop);
        }
    }
    
    setTimeout(function() {
        scrollToBottom(false);
    }, 100);
    
    // Date formatting function
    function formatMessageDate(dateInput) {
        var date;
        if (!isNaN(dateInput) && dateInput > 1000000000) {
            date = new Date(parseInt(dateInput) * 1000);
        } else if (!isNaN(dateInput)) {
            date = new Date(parseInt(dateInput));
        } else {
            date = new Date(dateInput);
        }
        
        if (isNaN(date.getTime())) {
            return dateInput;
        }
        
        var month = date.getMonth() + 1;
        var day = date.getDate();
        var year = date.getFullYear().toString().substr(-2);
        var hours = date.getHours().toString().padStart(2, "0");
        var minutes = date.getMinutes().toString().padStart(2, "0");
        
        return month + "/" + day + "/" + year + " " + hours + ":" + minutes;
    }
    
    // Create message HTML
    function createMessageHtml(messageData) {
        var messageClass = messageData.is_own ? "own" : "other";
        var messageHtml = \'<div class="message \' + messageClass + \'" data-message-id="\' + messageData.id + \'">\';
        
        messageHtml += \'<div class="message-bubble">\';
        
        if (messageData.reply_to) {
            messageHtml += \'<div class="reply-info">\';
            messageHtml += \'<div class="reply-sender">\' + escapeHtml(messageData.reply_to.sender_name) + \'</div>\';
            messageHtml += \'<div class="reply-text">\';
            if (messageData.reply_to.has_attachments) {
                messageHtml += \'<i class="fas fa-paperclip"></i> Attachment \';
            }
            if (messageData.reply_to.text) {
                messageHtml += escapeHtml(messageData.reply_to.text);
            }
            messageHtml += \'</div></div>\';
        }
        
        if (messageData.text) {
            messageHtml += \'<div class="message-text" id="message-text-\' + messageData.id + \'">\';
            messageHtml += escapeHtml(messageData.text).replace(/\\n/g, "<br>");
            messageHtml += \'</div>\';
        }
        
        if (messageData.attachments && messageData.attachments.length > 0) {
            messageHtml += \'<div class="message-attachments">\';
            messageData.attachments.forEach(function(attachment) {
                if (attachment.is_image) {
                    messageHtml += \'<div class="attachment-image-preview">\';
                    messageHtml += \'<img src="\' + attachment.url + \'" alt="\' + escapeHtml(attachment.original_name) + \'" \';
                    messageHtml += \'class="attachment-image" onclick="openImageModal(\\\'\' + attachment.url + \'\\\', \\\'\' + escapeHtml(attachment.original_name) + \'\\\')">\';
                    messageHtml += \'<div class="attachment-image-info">\';
                    messageHtml += \'<div class="attachment-name">\' + escapeHtml(attachment.original_name) + \'</div>\';
                    messageHtml += \'<div class="attachment-size">\' + attachment.file_size + \'</div>\';
                    messageHtml += \'</div></div>\';
                } else {
                    var downloadUrl = window.chatConfig.urls.downloadAttachment.replace("id=0", "id=" + attachment.id);
                    messageHtml += \'<a href="\' + downloadUrl + \'" class="attachment-item" target="_blank">\';
                    messageHtml += \'<div class="attachment-icon"><i class="\' + attachment.icon + \'"></i></div>\';
                    messageHtml += \'<div class="attachment-info">\';
                    messageHtml += \'<div class="attachment-name">\' + escapeHtml(attachment.original_name) + \'</div>\';
                    messageHtml += \'<div class="attachment-size">\' + attachment.file_size + \'</div>\';
                    messageHtml += \'</div></a>\';
                }
            });
            messageHtml += \'</div>\';
        }
        
        messageHtml += \'<div class="message-info">\';
        if (!messageData.is_own) {
            messageHtml += \'<strong>\' + escapeHtml(messageData.sender_name) + \'</strong><br>\';
        }
        var formattedDate = messageData.created_at_timestamp ? 
            formatMessageDate(messageData.created_at_timestamp) : 
            formatMessageDate(messageData.created_at);
        messageHtml += formattedDate;
        if (messageData.is_edited) {
            messageHtml += \' <span class="message-edited">(edited)</span>\';
        }
        messageHtml += \'</div>\';
        
        messageHtml += \'</div>\';
        
        messageHtml += \'<div class="message-actions">\';
        messageHtml += \'<button type="button" class="btn btn-sm btn-outline-secondary reply-btn" \' +
            \'onclick="replyToMessage(\' + messageData.id + \', \\\'\' + escapeHtml(messageData.sender_name) + \'\\\', \' +
            \'\\\'\' + escapeHtml((messageData.text || \'Attachment\').substring(0, 50)) + \'\\\', \' + 
            (messageData.has_attachments ? \'true\' : \'false\') + \')" title="Reply">\' +
            \'<i class="fas fa-reply"></i></button>\';
        
        if (messageData.can_edit && messageData.text) {
            messageHtml += \'<button type="button" class="btn btn-sm btn-outline-primary edit-btn" \' +
                \'onclick="editMessage(\' + messageData.id + \', \\\'\' + escapeHtml(messageData.text) + \'\\\')" title="Edit">\' +
                \'<i class="fas fa-edit"></i></button>\';
        }
        
        if (messageData.attachments && messageData.attachments.length > 0) {
            messageHtml += \'<div class="dropdown">\';
            messageHtml += \'<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" \' +
                \'data-toggle="dropdown" title="Attachments"><i class="fas fa-paperclip"></i></button>\';
            messageHtml += \'<div class="dropdown-menu">\';
            messageHtml += \'<h6 class="dropdown-header">Attachments</h6>\';
            messageData.attachments.forEach(function(attachment) {
                var downloadUrl = window.chatConfig.urls.downloadAttachment.replace("id=0", "id=" + attachment.id);
                messageHtml += \'<a class="dropdown-item" href="\' + downloadUrl + \'" target="_blank">\' +
                    \'<i class="\' + attachment.icon + \'"></i> \' + escapeHtml(attachment.original_name) + \'</a>\';
            });
            messageHtml += \'</div></div>\';
        }
        
        messageHtml += \'</div>\';
        messageHtml += \'</div>\';
        
        return messageHtml;
    }
    
    // Escape HTML
    function escapeHtml(text) {
        return $("<div>").text(text).html();
    }
    
    // Handle file attachment selection
    $("#attachment-input").change(function() {
        var files = Array.from(this.files);
        if (files.length === 0) return;
        
        files.forEach(function(file) {
            if (file.size > 10 * 1024 * 1024) {
                alert("File " + file.name + " is too large. Maximum size is 10MB.");
                return;
            }
            
            var exists = attachedFiles.some(function(f) { 
                return f.name === file.name && f.size === file.size; 
            });
            if (!exists) {
                attachedFiles.push(file);
            }
        });
        
        updateAttachmentPreview();
        this.value = \'\';
    });
    
    // Update attachment preview
    function updateAttachmentPreview() {
        var container = $("#attachment-preview-container");
        var list = $("#attachment-preview-list");
        
        if (attachedFiles.length === 0) {
            container.hide();
            return;
        }
        
        list.empty();
        attachedFiles.forEach(function(file, index) {
            var fileSize = formatFileSize(file.size);
            var icon = getFileIcon(file.type);
            
            var item = $(\'<div class="attachment-preview-item">\' +
                \'<div class="attachment-icon"><i class="\' + icon + \'"></i></div>\' +
                \'<div class="attachment-preview-info">\' +
                    \'<div class="attachment-preview-name">\' + escapeHtml(file.name) + \'</div>\' +
                    \'<div class="attachment-preview-size">\' + fileSize + \'</div>\' +
                \'</div>\' +
                \'<button type="button" class="btn btn-sm btn-outline-danger remove-attachment-btn" \' +
                    \'onclick="removeAttachment(\' + index + \')">\' +
                    \'<i class="fas fa-times"></i>\' +
                \'</button>\' +
            \'</div>\');
            
            list.append(item);
        });
        
        container.show();
    }
    
    // Remove attachment
    window.removeAttachment = function(index) {
        attachedFiles.splice(index, 1);
        updateAttachmentPreview();
    };
    
    // Clear all attachments
    window.clearAttachments = function() {
        attachedFiles = [];
        updateAttachmentPreview();
    };
    
    // Format file size
    function formatFileSize(bytes) {
        var units = [\'B\', \'KB\', \'MB\', \'GB\'];
        for (var i = 0; bytes > 1024 && i < units.length - 1; i++) {
            bytes /= 1024;
        }
        return Math.round(bytes * 10) / 10 + \' \' + units[i];
    }
    
    // Get file icon
    function getFileIcon(type) {
        if (type.startsWith(\'image/\')) return \'fas fa-image\';
        if (type === \'application/pdf\') return \'fas fa-file-pdf\';
        if (type.includes(\'word\') || type.includes(\'document\')) return \'fas fa-file-word\';
        return \'fas fa-file\';
    }
    
    // Reply to message
    window.replyToMessage = function(messageId, senderName, messageText, hasAttachments) {
        replyToMessageId = messageId;
        $("#reply-to-message-id").val(messageId);
        
        var previewText = messageText;
        if (hasAttachments) {
            previewText = \'<i class="fas fa-paperclip"></i> Attachment\' + (messageText ? \' • \' + previewText : \'\');
        }
        
        $("#reply-sender-name").text(senderName);
        $("#reply-preview-text").html(previewText);
        $("#reply-preview").show();
        $("#message-input").focus();
    };
    
    // Cancel reply
    window.cancelReply = function() {
        replyToMessageId = null;
        $("#reply-to-message-id").val(\'\');
        $("#reply-preview").hide();
    };
    
    // Edit message
    window.editMessage = function(messageId, currentText) {
        $("#edit-message-id").val(messageId);
        $("#edit-message-text").val(currentText);
        $("#editMessageModal").modal(\'show\');
    };
    
    // Save edit
    $("#save-edit-btn").click(function() {
        var messageId = $("#edit-message-id").val();
        var newText = $("#edit-message-text").val().trim();
        
        if (!messageId || !newText) return;
        
        $(this).prop("disabled", true).html(\'<i class="fas fa-spinner fa-spin"></i> Saving...\');
        
        $.ajax({
            url: window.chatConfig.urls.editMessage,
            type: "POST",
            data: {
                message_id: messageId,
                message_text: newText
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#message-text-" + messageId).html(escapeHtml(newText).replace(/\\n/g, "<br>"));
                    
                    var messageInfo = $(\'[data-message-id="\' + messageId + \'"] .message-info\');
                    if (messageInfo.find(\'.message-edited\').length === 0) {
                        messageInfo.append(\' <span class="message-edited">(edited)</span>\');
                    }
                    
                    $("#editMessageModal").modal(\'hide\');
                    showNotification("Message updated successfully", "success");
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || "Failed to update message");
            },
            complete: function() {
                $("#save-edit-btn").prop("disabled", false).html(\'Save Changes\');
            }
        });
    });
    
    // Copy message text
    window.copyMessage = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification("Message copied to clipboard", "success");
            });
        } else {
            var textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand(\'copy\');
            document.body.removeChild(textArea);
            showNotification("Message copied to clipboard", "success");
        }
    };
    
    // Send message function
    function sendMessage() {
        if (isSending || isLoading) return false;
        
        var messageText = $("#message-input").val().trim();
        
        if (!messageText && attachedFiles.length === 0) {
            return false;
        }
        
        if (messageText.length > 1000) {
            alert("Message is too long (maximum 1000 characters)");
            return false;
        }
        
        isSending = true;
        $("#send-button").prop("disabled", true).html(\'<i class="fas fa-spinner fa-spin"></i>\');
        
        var formData = new FormData();
        formData.append(\'message_text\', messageText);
        formData.append(\'message_type\', $("#message-type").val());
        if (replyToMessageId) {
            formData.append(\'reply_to_message_id\', replyToMessageId);
        }
        
        attachedFiles.forEach(function(file) {
            formData.append(\'attachments[]\', file);
        });
        
        $.ajax({
            url: window.chatConfig.urls.sendMessage,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#no-messages").remove();
                    var messageHtml = createMessageHtml(response.messageData);
                    $("#messages-list").append(messageHtml);
                    
                    $("#message-input").val("").trigger("input");
                    clearAttachments();
                    cancelReply();
                    
                    lastMessageId = response.messageData.id;
                    scrollToBottom();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function(xhr) {
                var errorMessage = "Failed to send message. Please try again.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                alert(errorMessage);
            },
            complete: function() {
                isSending = false;
                $("#send-button").prop("disabled", false).html(\'<i class="fas fa-paper-plane"></i>\');
            }
        });
        
        return false;
    }
    
    // Load more messages
    function loadMoreMessages() {
        if (isLoading || loadedMessages >= totalMessages) return;
        
        isLoading = true;
        $("#load-more-btn").prop("disabled", true).html(\'<i class="fas fa-spinner fa-spin"></i> Loading...\');
        $("#loading-indicator").show();
        
        var currentScrollHeight = $("#messages-container")[0].scrollHeight;
        
        $.ajax({
            url: window.chatConfig.urls.loadMoreMessages,
            type: "GET",
            data: { offset: loadedMessages },
            dataType: "json",
            success: function(response) {
                if (response.success && response.messages.length > 0) {
                    var messagesHtml = "";
                    response.messages.forEach(function(messageData) {
                        messagesHtml += createMessageHtml(messageData);
                    });
                    
                    $("#messages-list").prepend(messagesHtml);
                    loadedMessages += response.messages.length;
                    
                    var newScrollHeight = $("#messages-container")[0].scrollHeight;
                    var scrollDiff = newScrollHeight - currentScrollHeight;
                    $("#messages-container").scrollTop(scrollDiff);
                    
                    if (!response.hasMore) {
                        $("#load-more-btn").hide();
                    }
                } else {
                    $("#load-more-btn").hide();
                }
            },
            error: function() {
                console.log("Failed to load more messages");
            },
            complete: function() {
                isLoading = false;
                $("#load-more-btn").prop("disabled", false).html(\'<i class="fas fa-chevron-up"></i> Load earlier messages\');
                $("#loading-indicator").hide();
            }
        });
    }
    
    // Character counter
    function updateCharCounter() {
        var length = $("#message-input").val().length;
        var remaining = 1000 - length;
        var counter = $("#char-counter");
        
        if (remaining < 100) {
            counter.text(remaining + " characters remaining").show();
            if (remaining < 0) {
                counter.removeClass("warning").addClass("danger");
                $("#send-button").prop("disabled", true);
            } else if (remaining < 50) {
                counter.removeClass("danger").addClass("warning");
                $("#send-button").prop("disabled", false);
            } else {
                counter.removeClass("danger warning");
                $("#send-button").prop("disabled", false);
            }
        } else {
            counter.hide();
            $("#send-button").prop("disabled", false);
        }
    }
    
    // Show notification
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
    
    // Event handlers
    $("#load-more-btn").click(loadMoreMessages);
    $("#send-button").click(function(e) { e.preventDefault(); sendMessage(); });
    
    $("#message-input").keydown(function(e) {
        if (e.ctrlKey && e.keyCode === 13) {
            e.preventDefault();
            sendMessage();
        }
    }).on("input", function() {
        this.style.height = "auto";
        this.style.height = Math.min(this.scrollHeight, 100) + "px";
        updateCharCounter();
    });
    
    updateCharCounter();
    
        // Escape HTML function (make it globally available)
    window.escapeHtml = function(text) {
        return $("<div>").text(text).html();
    };
    
     window.openImageModal = function(imageUrl, imageName) {
            $(\'#imageModal\').remove();
            var modalHtml =
                \'<div class="modal fade" id="imageModal" tabindex="-1" role="dialog">\' +
                \'<div class="modal-dialog modal-lg modal-dialog-centered" role="document">\' +
                \'<div class="modal-content bg-dark">\' +
                \'<div class="modal-header border-0">\' +
                \'<h5 class="modal-title text-white">\' + window.escapeHtml(imageName) + \'</h5>\' +
                \'<button type="button" class="close text-white" data-dismiss="modal">\' +
                \'<span>&times;</span>\' +
                \'</button>\' +
                \'</div>\' +
                \'<div class="modal-body text-center p-0">\' +
                \'<img src="\' + imageUrl + \'" class="img-fluid" alt="\' + window.escapeHtml(imageName) + \'" style="max-height: 70vh; width: auto;">\' +
                \'</div>\' +
                \'<div class="modal-footer border-0 justify-content-center">\' +
                \'<a href="\' + imageUrl + \'" class="btn btn-outline-light btn-sm" target="_blank">\' +
                \'<i class="fas fa-external-link-alt"></i> Open in new tab\' +
                \'</a>\' +
                \'</div>\' +
                \'</div>\' +
                \'</div>\' +
                \'</div>\';
            $(\'body\').append(modalHtml);
            $(\'#imageModal\').modal(\'show\');
            $(\'#imageModal\').on(\'hidden.bs.modal\', function() {
                $(this).remove();
            });
        }        
        
        // Search functionality
        // Search functionality
        var originalMessages = null;
        var isSearchMode = false;

        // Toggle search container
        $(\'.search-toggle-btn\').click(function() {
            var container = $(\'.search-container\');
            var isVisible = container.is(\':visible\');
            
            console.log(\'Toggle clicked, visible:\', isVisible);

            if (isVisible) {
                container.slideUp(200);
                if (isSearchMode) {
                    cancelSearch();
                }
            } else {
                container.slideDown(200, function() {
                    $(\'.search-input\').focus();
                });
            }
        });

        // Search when typing
        $(\'.search-input\').on(\'input\', function() {
            var query = $(this).val().trim();
            if (query.length >= 2) {
                performSearch(query);
            } else if (query.length === 0 && isSearchMode) {
                cancelSearch();
            }
        });

        // Search button click
        $(\'.search-btn\').click(function() {
            var query = $(\'.search-input\').val().trim();
            if (query.length >= 2) {
                performSearch(query);
            } else if (query.length === 0) {
                alert(\'Please enter search text\');
            } else {
                alert(\'Please enter at least 2 characters\');
            }
        });

        // Search on Enter key
        $(\'.search-input\').keypress(function(e) {
            if (e.which === 13) {
                var query = $(this).val().trim();
                if (query.length >= 2) {
                    performSearch(query);
                }
            }
        });

        // Clear search button - FIXED LIKE EMPLOYEE CHAT
        $(\'.clear-search-btn\').click(function() {
            console.log(\'Clear button clicked, search mode:\', isSearchMode);
            
            if (isSearchMode) {
                // Cancel search and restore messages
                cancelSearch();
            } else {
                // Just clear input and close container
                $(\'.search-input\').val(\'\');
                $(\'.search-container\').slideUp(200);
            }
        });

        // Perform search
        function performSearch(query) {
            console.log(\'Searching for:\', query);
            
            // Save original messages if first search
            if (!isSearchMode) {
                originalMessages = $(\'#messages-list\').html();
            }
            
            var searchUrl = window.chatConfig.urls.searchMessages || "/crm-panel/my-chat/search-messages";
            var searchData = { q: query, mode: \'full\' };
            
            // Show loading
            $(\'#messages-list\').html(\'<div class="text-center p-4"><i class="fas fa-spinner fa-spin"></i> Searching...</div>\');
            
            $.ajax({
                url: searchUrl,
                type: \'GET\',
                data: searchData,
                dataType: \'json\',
                success: function(response) {
                    console.log(\'Search response:\', response);
                    if (response.success && response.results.length > 0) {
                        displaySearchMessages(response.results, query);
                        enterSearchMode();
                    } else {
                        showNoSearchResults(query);
                        enterSearchMode();
                    }
                },
                error: function() {
                    $(\'#messages-list\').html(\'<div class="alert alert-danger">Search failed. Please try again.</div>\');
                }
            });
        }

        // Display search results as messages
        function displaySearchMessages(results, query) {
            var html = \'\';
            
            results.forEach(function(result) {
                var highlightedText = highlightSearchTerms(result.text, query);
                var messageClass = result.is_own ? \'own\' : \'other\';
                
                html += \'<div class="message \' + messageClass + \'" data-message-id="\' + result.id + \'">\';
                html += \'<div class="message-bubble">\';
                html += \'<div class="message-text">\' + highlightedText + \'</div>\';
                html += \'<div class="message-info">\';
                if (!result.is_own) {
                    html += \'<strong>\' + escapeHtml(result.sender_name) + \'</strong><br>\';
                }
                html += result.created_at;
                if (result.has_attachments) {
                    html += \' <i class="fas fa-paperclip" title="Has attachments"></i>\';
                }
                html += \'</div>\';
                html += \'</div></div>\';
            });
            
            $(\'#messages-list\').html(html);
            scrollToBottom(false);
        }

        // Show no results message
        function showNoSearchResults(query) {
            var html = \'<div class="no-search-results">\';
            html += \'<i class="fas fa-search"></i>\';
            html += \'<h5>No messages found</h5>\';
            html += \'<p>No messages containing "<strong>\' + escapeHtml(query) + \'</strong>" were found in this chat.</p>\';
            html += \'</div>\';
            
            $(\'#messages-list\').html(html);
        }

        // Enter search mode
        function enterSearchMode() {
            console.log(\'Entering search mode\');
            isSearchMode = true;
            $(\'.chat-header\').addClass(\'search-mode\');
            $(\'.clear-search-btn\').html(\'<i class="fas fa-times"></i>\').attr(\'title\', \'Cancel search\');
            
            // Hide load more button
            $(\'#load-more-btn\').hide();
        }

        // Cancel search and restore original messages
        function cancelSearch() {
            console.log(\'Canceling search...\');
            
            // Restore original messages
            if (originalMessages) {
                $(\'#messages-list\').html(originalMessages);
                scrollToBottom(false);
            }
            
            // Exit search mode
            isSearchMode = false;
            $(\'.chat-header\').removeClass(\'search-mode\');
            $(\'.search-input\').val(\'\');
            $(\'.search-container\').slideUp(200);
            $(\'.clear-search-btn\').html(\'<i class="fas fa-times"></i>\').attr(\'title\', \'Clear\');
            
            // Show load more button if needed
            if (loadedMessages < totalMessages) {
                $(\'#load-more-btn\').show();
            }
            
            originalMessages = null;
            
            console.log(\'Search canceled\');
        }

        // Highlight search terms
        function highlightSearchTerms(text, query) {
            var escapedText = escapeHtml(text);
            var escapedQuery = escapeHtml(query);
            var regex = new RegExp(\'(\' + escapedQuery.replace(/[.*+?^${}()|[\\]\\\\]/g, \'\\\\$&\') + \')\', \'gi\');
            return escapedText.replace(regex, \'<span class="search-highlight">$1</span>\');
        }


        
});
', \yii\web\View::POS_READY);