<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use backend\models\ChatMessage;
use backend\models\Chat;

/* @var $selectedChat backend\models\Chat */
/* @var $messages backend\models\ChatMessage[] */
/* @var $currentUser backend\models\User */
/* @var $totalMessages int */

?>

<div class="chat-header mobile-with-back">
    <!-- Mobile Back Button - only show when inside a specific chat -->
    <?php /*if ($selectedChat): ?>
        <a href="<?= Url::to(['/chat']) ?>" class="mobile-back-btn d-block d-md-none" title="Back to chats">
            <i class="fas fa-arrow-left"></i>
        </a>
    <?php endif;*/ ?>

    <div class="chat-header-top">
        <div class="chat-title">
            <i class="fas fa-<?= Chat::getChatIcon($selectedChat->type) ?>"></i>
            <?= Html::encode($selectedChat->title ?: Chat::getDefaultChatTitle($selectedChat)) ?>
        </div>

        <div class="chat-status">
            <?php /*if ($selectedChat->type === Chat::TYPE_EMPLOYEE_GROUP): ?>
                <div class="chat-participants">
                    <?php
                    $participants = $selectedChat->activeParticipants;
                    $participantCount = count($participants);
                    ?>

                    <div class="participants-toggle" onclick="toggleParticipants('<?= $selectedChat->id ?>')">
                        <i class="fas fa-users"></i>
                        <span class="participants-count"><?= $participantCount ?> participant<?= $participantCount == 1 ? '' : 's' ?></span>
                        <i class="fas fa-chevron-down expand-icon" id="expand-icon-<?= $selectedChat->id ?>"></i>
                    </div>
                </div>
            <?php endif;*/ ?>
        </div>

        <div class="chat-search">
            <div class="search-toggle">
                <button type="button" class="btn btn-sm btn-outline-light search-toggle-btn" title="Search messages">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <?php if ($selectedChat->type === Chat::TYPE_EMPLOYEE &&
            (Yii::$app->user->can('administrator') || Yii::$app->user->can('super-administrator'))): ?>

            <div class="chat-action-buttons">
                <?= Html::a('<i class="fas fa-tasks"></i>', '#', [
                    'class' => 'btn btn-sm btn-outline-secondary add-task-btn',
                    'title' => 'Add Task',
                    'data-id' => $selectedChat->employee_id,
                ]) ?>
                <?= Html::a('<i class="fas fa-hand-holding-usd"></i>', '#', [
                    'class' => 'btn btn-sm btn-outline-secondary add-investor-btn',
                    'title' => 'Add Investor',
                    'data-id' => $selectedChat->employee_id,
                ]) ?>
                <?= Html::a('<i class="fas fa-project-diagram"></i>', '#', [
                    'class' => 'btn btn-sm btn-outline-secondary add-project-btn',
                    'title' => 'Add Project',
                    'data-id' => $selectedChat->employee_id,
                ]) ?>
                <?= Html::a('<i class="fas fa-file-alt"></i>', '#', [
                    'class' => 'btn btn-sm btn-outline-secondary send-template-btn',
                    'title' => 'Send Template',
                    'data-id' => $selectedChat->employee_id,
                ]) ?>
                <?php
                $employee = $selectedChat->employee;
                $isArchived = $employee && $employee->status === \backend\models\User::STATUS_DELETED;
                ?>

                <?php if ($isArchived): ?>
                    <?= Html::a('<i class="fas fa-undo"></i>', '#', [
                        'class' => 'btn btn-sm btn-outline-success restore-employer-btn',
                        'title' => 'Restore Employee',
                        'data-id' => $selectedChat->employee_id,
                    ]) ?>
                <?php else: ?>
                    <?= Html::a('<i class="fas fa-trash-alt"></i>', '#', [
                        'class' => 'btn btn-sm btn-outline-danger remove-employer-btn',
                        'title' => 'Archive Employee',
                        'data-id' => $selectedChat->employee_id,
                    ]) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Search Container -->
    <div class="search-container" style="display: none;">
        <div class="input-group input-group-sm">
            <input type="text" class="form-control search-input" placeholder="Search messages..." maxlength="100">
            <div class="input-group-append">
                <button class="btn btn-outline-light search-btn" type="button" title="Search">
                    <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-outline-light clear-search-btn" type="button" title="Clear search">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php /*if ($selectedChat->type === Chat::TYPE_EMPLOYEE_GROUP): ?>
    <?php
    $participants = $selectedChat->activeParticipants;
    ?>

    <div class="participants-dropdown" id="participants-<?= $selectedChat->id ?>" style="display: none;">
        <div class="participants-header">
            <strong>Chat Participants</strong>
            <button class="btn btn-sm btn-outline-primary add-participant-btn"
                    onclick="showAddParticipantModal('<?= $selectedChat->id ?>')">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>

        <div class="participants-list">
            <?php foreach ($participants as $participant): ?>
                <div class="participant-item" data-user-id="<?= $participant->id ?>">
                    <div class="participant-info">
                        <div class="participant-details">
                            <div class="participant-name">
                                <?= Html::encode(trim($participant->first_name . ' ' . $participant->last_name)) ?>
                            </div>
                            <div class="participant-role">
                                <?php
                                $userRoles = Yii::$app->authManager->getRolesByUser($participant->id);
                                echo !empty($userRoles) ? Html::encode(ucfirst(key($userRoles))) : 'Employee';
                                ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($participant->id != Yii::$app->user->id): ?>
                        <button class="btn btn-sm btn-outline-danger remove-participant-btn"
                                onclick="removeParticipant('<?= $selectedChat->id ?>', <?= $participant->id ?>, '<?= Html::encode($participant->first_name . ' ' . $participant->last_name) ?>')">
                            <i class="fas fa-times"></i>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; */ ?>

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

    <!-- Messages List -->
    <div id="messages-list">
        <?php if (empty($messages)): ?>
            <div class="no-messages" id="no-messages">
                <i class="fas fa-comment-alt fa-3x"></i>
                <h5>No messages yet</h5>
                <p>Start the conversation!</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <?= $this->render('_message_item', [
                    'message' => $message,
                    'currentUser' => $currentUser
                ]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
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
        'options' => ['data-pjax' => false, 'enctype' => 'multipart/form-data'],
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
        <div class="col-1 text-center">
            <!-- Attachment Button -->
            <label for="attachment-input" class="btn btn-light attachment-btn" title="Add attachments">
                <i class="fas fa-paperclip"></i>
                <input type="file" id="attachment-input" name="attachments[]" multiple
                       accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.odt" style="display: none;">
            </label>
        </div>
        <div class="col-9">
            <?= Html::textarea('message_text', '', [
                'class' => 'form-control message-input',
                'placeholder' => 'Type your message...',
                'rows' => 1,
                'id' => 'message-input',
                'maxlength' => 1000,
            ]) ?>
        </div>
        <div class="col-2 text-center">
            <?= Html::button('<i class="fas fa-paper-plane"></i>', [
                'class' => 'btn btn-primary send-button',
                'id' => 'send-button',
                'title' => 'Send message (Ctrl+Enter)',
            ]) ?>
        </div>
    </div>

    <?= Html::hiddenInput('chat_id', $selectedChat->id, ['id' => 'chat-id']) ?>
    <?= Html::hiddenInput('message_type', ChatMessage::MESSAGE_TYPE_TEXT, ['id' => 'message-type']) ?>
    <?= Html::hiddenInput('reply_to_message_id', '', ['id' => 'reply-to-message-id']) ?>

    <div class="form-footer">
        <small class="text-muted">Press Ctrl+Enter to send</small>
        <small class="char-counter" id="char-counter"></small>
    </div>

    <?php ActiveForm::end(); ?>
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

<?php if ($selectedChat->type === Chat::TYPE_EMPLOYEE &&
    (Yii::$app->user->can('administrator') || Yii::$app->user->can('super-administrator'))): ?>

    <!-- Modal for employee actions -->
    <div class="modal fade" id="chatEmployeeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="chatEmployeeModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body"></div>
            </div>
        </div>
    </div>

    <?php
    $addTaskUrl = \yii\helpers\Url::to(['/users/add-task']);
    $addInvestorUrl = \yii\helpers\Url::to(['/users/add-investor']);
    $addProjectUrl = \yii\helpers\Url::to(['/users/add-project']);
    $sendTemplateUrl = \yii\helpers\Url::to(['/users/send-template']);
    $removeEmployerUrl = \yii\helpers\Url::to(['/users/remove-employer']);
    $restoreEmployerUrl = \yii\helpers\Url::to(['/users/restore-employer']);

    $this->registerJs("
var chatModal = \$('#chatEmployeeModal');
var chatModalBody = chatModal.find('.modal-body');
var chatModalTitle = chatModal.find('.modal-title');

\$(document).on('click', '.add-task-btn', function(e) {
    e.preventDefault();
    var employeeId = \$(this).data('id');
    chatModalTitle.text('Create Task');
    chatModalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div></div>');
    chatModal.modal('show');
    \$.ajax({
        url: '$addTaskUrl', type: 'GET', data: { id: employeeId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') { chatModalBody.html(response.tpl); }
        },
        error: function() { chatModalBody.html('<div class=\"alert alert-danger\">Failed to load form</div>'); }
    });
});

\$(document).on('submit', '#add-task-form', function(e) {
    e.preventDefault();
    var form = \$(this);
    var submitButton = form.find('.add-task-submit-btn');
    var formData = new FormData(form[0]);
    submitButton.prop('disabled', true).text('Creating...');
    \$.ajax({
        type: 'POST', url: form.prop('action'), data: formData,
        processData: false, contentType: false,
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) { toastr.success(response.message); chatModal.modal('hide'); }
            else if (response.tpl) { chatModalBody.html(response.tpl); }
            else { toastr.error(response.message); }
        },
        error: function() { toastr.error('An error occurred'); },
        complete: function() { submitButton.prop('disabled', false).text('Create Task'); }
    });
});

\$(document).on('click', '.add-investor-btn', function(e) {
    e.preventDefault();
    var employeeId = \$(this).data('id');
    chatModalTitle.text('Create Investor');
    chatModalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div></div>');
    chatModal.modal('show');
    \$.ajax({
        url: '$addInvestorUrl', type: 'GET', data: { id: employeeId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') { chatModalBody.html(response.tpl); }
        },
        error: function() { chatModalBody.html('<div class=\"alert alert-danger\">Failed to load form</div>'); }
    });
});

\$(document).on('submit', '#add-investor-form', function(e) {
    e.preventDefault();
    var form = \$(this);
    var submitButton = form.find('.add-investor-submit-btn');
    submitButton.prop('disabled', true).text('Creating...');
    \$.ajax({
        type: 'POST', url: form.prop('action'), data: form.serialize(),
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) { toastr.success(response.message); chatModal.modal('hide'); }
            else if (response.tpl) { chatModalBody.html(response.tpl); }
            else { toastr.error(response.message); }
        },
        error: function() { toastr.error('An error occurred'); },
        complete: function() { submitButton.prop('disabled', false).text('Create Investor'); }
    });
});

\$(document).on('click', '.add-project-btn', function(e) {
    e.preventDefault();
    var employeeId = \$(this).data('id');
    chatModalTitle.text('Create Project');
    chatModalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div></div>');
    chatModal.modal('show');
    \$.ajax({
        url: '$addProjectUrl', type: 'GET', data: { id: employeeId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') { chatModalBody.html(response.tpl); }
        },
        error: function() { chatModalBody.html('<div class=\"alert alert-danger\">Failed to load form</div>'); }
    });
});

\$(document).on('submit', '#add-project-form', function(e) {
    e.preventDefault();
    var form = \$(this);
    var submitButton = form.find('.add-project-submit-btn');
    submitButton.prop('disabled', true).text('Creating...');
    \$.ajax({
        type: 'POST', url: form.prop('action'), data: form.serialize(),
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) { toastr.success(response.message); chatModal.modal('hide'); }
            else if (response.tpl) { chatModalBody.html(response.tpl); }
            else { toastr.error(response.message); }
        },
        error: function() { toastr.error('An error occurred'); },
        complete: function() { submitButton.prop('disabled', false).text('Create Project'); }
    });
});

\$(document).on('click', '.send-template-btn', function(e) {
    e.preventDefault();
    var employeeId = \$(this).data('id');
    chatModalTitle.text('Send Template');
    chatModalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div></div>');
    chatModal.modal('show');
    \$.ajax({
        url: '$sendTemplateUrl', type: 'GET', data: { id: employeeId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') { chatModalBody.html(response.tpl); }
        },
        error: function() { chatModalBody.html('<div class=\"alert alert-danger\">Failed to load form</div>'); }
    });
});

\$(document).on('submit', '#send-template-form', function(e) {
    e.preventDefault();
    var form = \$(this);
    var submitButton = form.find('.send-template-submit-btn');
    submitButton.prop('disabled', true).text('Sending...');
    \$.ajax({
        type: 'POST', url: form.prop('action'), data: form.serialize(),
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) { 
                toastr.success(response.message);
                chatModal.modal('hide');
    
                if (response.chat && window.chatConfig.selectedChatId) {
                    \$.ajax({
                        url: window.chatConfig.urls.loadMoreMessages,
                        type: 'GET',
                        data: { chat_id: window.chatConfig.selectedChatId, offset: 0 },
                        dataType: 'json',
                        success: function(res) {
                            if (res.success && res.messages.length > 0) {
                                var last = res.messages[res.messages.length - 1];
                                \$('#messages-list').append(window.createMessageHtml(last));
                                \$('#no-messages').remove();
                                var container = \$('#messages-container');
                                container.animate({ scrollTop: container[0].scrollHeight }, 300);
                            }
                        }
                    });
                }            
            }
            else { toastr.error(response.message); }
        },
        error: function() { toastr.error('An error occurred'); },
        complete: function() { submitButton.prop('disabled', false).text('Send Template'); }
    });
});

\$(document).on('click', '.remove-employer-btn', function(e) {
    e.preventDefault();
    if (!confirm('Are you sure you want to archive this employee?')) return;
    var userId = \$(this).data('id');
    \$.ajax({
        url: '$removeEmployerUrl', type: 'POST', data: { id: userId },
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) { toastr.success(response.message); window.location.href = window.chatConfig.urls.chatIndex; }
            else { toastr.error(response.message); }
        },
        error: function() { toastr.error('An error occurred'); }
    });
});

\$(document).on('click', '.restore-employer-btn', function(e) {
    e.preventDefault();
    if (!confirm('Are you sure you want to restore this employee?')) return;
    var userId = \$(this).data('id');
    \$.ajax({
        url: '$restoreEmployerUrl', type: 'POST', data: { id: userId },
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) { toastr.success(response.message); window.location.href = window.chatConfig.urls.chatIndex; }
            else { toastr.error(response.message); }
        },
        error: function() { toastr.error('An error occurred'); }
    });
});
", \yii\web\View::POS_READY);
    ?>

<?php endif; ?>