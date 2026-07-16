<?php

use yii\helpers\Html;
use backend\models\ChatMessage;

/* @var $message backend\models\ChatMessage */
/* @var $currentUser backend\models\User */

$isOwnMessage = $message->sender_id == $currentUser->id;
$canEdit = $message->canBeEditedBy($currentUser->id);

?>

<div class="message <?= $isOwnMessage ? 'own' : 'other' ?>" data-message-id="<?= $message->id ?>">
    <div class="message-bubble">
        <!-- Reply Information -->
        <?php if ($message->isReply() && $message->replyToMessage): ?>
            <div class="reply-info">
                <div class="reply-sender">
                    <?= Html::encode($message->replyToMessage->sender ?
                        trim($message->replyToMessage->sender->first_name . ' ' . $message->replyToMessage->sender->last_name) : 'Unknown') ?>
                </div>
                <div class="reply-text">
                    <?php if ($message->replyToMessage->hasAttachments()): ?>
                        <i class="fas fa-paperclip"></i>
                        <?= $message->replyToMessage->getAttachmentsCount() ?> attachment<?= $message->replyToMessage->getAttachmentsCount() > 1 ? 's' : '' ?>
                    <?php endif; ?>
                    <?php if (!empty($message->replyToMessage->message_text)): ?>
                        <?= Html::encode(mb_substr($message->replyToMessage->message_text, 0, 100)) ?>
                        <?= mb_strlen($message->replyToMessage->message_text) > 100 ? '...' : '' ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Message Text -->
        <?php if (!empty($message->message_text)): ?>
            <div class="message-text" id="message-text-<?= $message->id ?>">
                <?= nl2br(Html::encode($message->message_text)) ?>
            </div>
        <?php endif; ?>

        <!-- Attachments -->
        <?php if ($message->hasAttachments()): ?>
            <div class="message-attachments">
                <?php foreach ($message->attachments as $attachment): ?>
                    <?php if ($attachment->isImage()): ?>
                        <!-- Image preview -->
                        <div class="attachment-image-preview">
                            <img src="<?= $attachment->getUrl() ?>"
                                 alt="<?= Html::encode($attachment->original_name) ?>"
                                 class="attachment-image"
                                 onclick="openImageModal('<?= $attachment->getUrl() ?>', '<?= Html::encode($attachment->original_name) ?>')">
                            <div class="attachment-image-info">
                                <div class="attachment-name"><?= Html::encode($attachment->original_name) ?></div>
                                <div class="attachment-size"><?= $attachment->getFormattedFileSize() ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- File attachment -->
                        <a href="<?= \yii\helpers\Url::to(['chat/download-attachment', 'id' => $attachment->id]) ?>"
                           class="attachment-item" target="_blank">
                            <div class="attachment-icon">
                                <i class="<?= $attachment->getFileIcon() ?>"></i>
                            </div>
                            <div class="attachment-info">
                                <div class="attachment-name"><?= Html::encode($attachment->original_name) ?></div>
                                <div class="attachment-size"><?= $attachment->getFormattedFileSize() ?></div>
                            </div>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Message Info -->
        <div class="message-info">
            <?php if (!$isOwnMessage): ?>
                <strong><?= Html::encode($message->sender ?
                        trim($message->sender->first_name . ' ' . $message->sender->last_name) : 'Support') ?></strong><br>
            <?php endif; ?>
            <?= Yii::$app->formatter->asDatetime($message->created_at, 'short') ?>
            <?php if ($message->isEdited()): ?>
                <span class="message-edited">(edited)</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Message Actions -->
    <div class="message-actions">
        <!-- Reply Button -->
        <button type="button" class="btn btn-sm btn-outline-secondary reply-btn"
                onclick="replyToMessage(<?= $message->id ?>, '<?= Html::encode($message->sender ?
                    trim($message->sender->first_name . ' ' . $message->sender->last_name) : 'Support') ?>',
                        '<?= Html::encode(mb_substr($message->message_text ?: 'Attachment', 0, 50)) ?>', <?= $message->hasAttachments() ? 'true' : 'false' ?>)"
                title="Reply">
            <i class="fas fa-reply"></i>
        </button>

        <!-- Edit Button (only for own messages) -->
        <?php if ($canEdit && !empty($message->message_text)): ?>
            <button type="button" class="btn btn-sm btn-outline-primary edit-btn"
                    onclick="editMessage(<?= $message->id ?>, '<?= Html::encode($message->message_text) ?>')"
                    title="Edit">
                <i class="fas fa-edit"></i>
            </button>
        <?php endif; ?>

        <!-- More Actions Dropdown (only if has attachments) -->
        <?php if ($message->hasAttachments()): ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                        data-toggle="dropdown" title="Attachments">
                    <i class="fas fa-paperclip"></i>
                </button>
                <div class="dropdown-menu">
                    <h6 class="dropdown-header">Attachments</h6>
                    <?php foreach ($message->attachments as $attachment): ?>
                        <a class="dropdown-item"
                           href="<?= \yii\helpers\Url::to(['chat/download-attachment', 'id' => $attachment->id]) ?>"
                           target="_blank">
                            <i class="<?= $attachment->getFileIcon() ?>"></i>
                            <?= Html::encode($attachment->original_name) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>