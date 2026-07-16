<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $message common\models\CorporateEmailMessage|common\models\ExternalEmailMessage */
/* @var $messageType string */

$toEmails = is_array($message->getToEmailsArray()) ? $message->getToEmailsArray() : [];
$ccEmails = is_array($message->getCcEmailsArray()) ? $message->getCcEmailsArray() : [];
?>

    <div class="card" style="border-left: 3px solid #6c757d;">
        <div class="card-header">
            <h5><?= Html::encode($message->subject ?: '(No Subject)') ?></h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>From:</strong> <?= Html::encode($message->from_name ?: $message->from_email) ?>
                        <small class="text-muted">&lt;<?= Html::encode($message->from_email) ?>&gt;</small>
                    </p>
                    <p><strong>To:</strong> <?= Html::encode(implode(', ', $toEmails)) ?></p>
                    <?php if (!empty($ccEmails)): ?>
                        <p><strong>CC:</strong> <?= Html::encode(implode(', ', $ccEmails)) ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-right">
                    <p><strong>Date:</strong> <?= Yii::$app->formatter->asDatetime($message->received_at) ?></p>
                    <?php if ($message->has_attachments): ?>
                        <p>
                            <i class="fas fa-paperclip"></i>
                            <strong><?= count($message->attachments) ?> attachment(s)</strong>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <hr>

            <!-- Email Body -->
            <div class="email-body">
                <?php if ($message->body_html): ?>
                    <iframe
                            srcdoc="<?= htmlspecialchars($message->body_html) ?>"
                            style="width: 100%; min-height: 400px; border: 1px solid #dee2e6; border-radius: 4px;"
                            sandbox="allow-same-origin">
                    </iframe>
                <?php else: ?>
                    <pre style="white-space: pre-wrap; word-wrap: break-word; font-family: inherit;"><?= Html::encode($message->body_text) ?></pre>
                <?php endif; ?>
            </div>

            <!-- Attachments -->
            <?php if ($message->has_attachments && count($message->attachments) > 0): ?>
                <hr>
                <h6><strong>Attachments:</strong></h6>
                <ul class="list-unstyled">
                    <?php foreach ($message->attachments as $attachment): ?>
                        <li class="mb-2">
                            <i class="fas fa-file"></i>
                            <?= Html::a(
                                Html::encode($attachment->filename) . ' (' . $attachment->getFormattedSize() . ')',
                                ['download-attachment', 'id' => $attachment->id, 'type' => $messageType],
                                ['target' => '_blank']
                            ) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <!-- Actions -->
            <hr>
            <div class="mt-3">
                <?= Html::a(
                    '<i class="fas fa-reply"></i> Reply',
                    ['reply', 'id' => $message->id, 'type' => $messageType],
                    [
                        'class' => 'btn btn-primary btn-sm',
                        'onclick' => 'showReplyForm(event, ' . $message->id . ', "' . $messageType . '"); return false;'
                    ]
                ) ?>

                <?= Html::a(
                    '<i class="fas fa-share"></i> Forward',
                    ['forward', 'id' => $message->id, 'type' => $messageType],
                    [
                        'class' => 'btn btn-secondary btn-sm',
                        'onclick' => 'showForwardForm(event, ' . $message->id . ', "' . $messageType . '"); return false;'
                    ]
                ) ?>

                <?php if ($messageType == 'corporate'): ?>
                    <?php if ($message->is_read): ?>
                        <?= Html::a(
                            '<i class="fas fa-envelope"></i> Mark Unread',
                            '#',
                            [
                                'class' => 'btn btn-outline-secondary btn-sm mark-unread-btn',
                                'data-id' => $message->id,
                                'data-type' => $messageType,
                            ]
                        ) ?>
                    <?php else: ?>
                        <?= Html::a(
                            '<i class="fas fa-envelope-open"></i> Mark Read',
                            '#',
                            [
                                'class' => 'btn btn-outline-secondary btn-sm mark-read-btn',
                                'data-id' => $message->id,
                                'data-type' => $messageType,
                            ]
                        ) ?>
                    <?php endif; ?>
                <?php else: ?>
                    <?php
                    $readStatus = \common\models\ExternalEmailReadStatus::findOne([
                        'message_id' => $message->id,
                        'user_id' => Yii::$app->user->id,
                    ]);
                    ?>

                    <?php if ($readStatus && $readStatus->is_flagged): ?>
                        <?= Html::a(
                            '<i class="fas fa-star"></i> Unflag',
                            '#',
                            [
                                'class' => 'btn btn-warning btn-sm mark-unflagged-btn',
                                'data-id' => $message->id,
                                'data-type' => $messageType,
                            ]
                        ) ?>
                    <?php else: ?>
                        <?= Html::a(
                            '<i class="far fa-star"></i> Flag',
                            '#',
                            [
                                'class' => 'btn btn-outline-warning btn-sm mark-flagged-btn',
                                'data-id' => $message->id,
                                'data-type' => $messageType,
                            ]
                        ) ?>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($message->direction == 'incoming' && $message->folder != 'Archive'): ?>
                    <?= Html::a(
                        '<i class="fas fa-archive"></i> Archive',
                        '#',
                        [
                            'class' => 'btn btn-outline-secondary btn-sm archive-btn',
                            'data-id' => $message->id,
                            'data-type' => $messageType,
                        ]
                    ) ?>
                <?php endif; ?>

                <?php
                $userRoles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
                if (isset($userRoles['super-administrator']) || isset($userRoles['administrator'])):
                    ?>
                    <?= Html::a(
                    '<i class="fas fa-trash"></i> Delete',
                    '#',
                    [
                        'class' => 'btn btn-danger btn-sm delete-email-btn',
                        'data-id' => $message->id,
                        'data-type' => $messageType,
                    ]
                ) ?>
                <?php endif; ?>

                <?= Html::a(
                    '<i class="fas fa-times"></i> Close',
                    '#',
                    ['class' => 'btn btn-secondary btn-sm cancel-action']
                ) ?>
            </div>
        </div>
    </div>

<?php
$replyUrl = Url::to(['reply']);
$forwardUrl = Url::to(['forward']);

$js = <<<JS
function showReplyForm(event, messageId, messageType) {
    event.preventDefault();
    
    var modal = $('#composeEmailModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Reply');
    modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '$replyUrl',
        type: 'GET',
        data: { id: messageId, type: messageType },
        success: function(html) {
            modalBody.html(html);
        },
        error: function() {
            modalBody.html('<div class="alert alert-danger">Failed to load reply form</div>');
        }
    });
}

function showForwardForm(event, messageId, messageType) {
    event.preventDefault();
    
    var modal = $('#composeEmailModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Forward');
    modalBody.html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '$forwardUrl',
        type: 'GET',
        data: { id: messageId, type: messageType },
        success: function(html) {
            modalBody.html(html);
        },
        error: function() {
            modalBody.html('<div class="alert alert-danger">Failed to load forward form</div>');
        }
    });
}
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>