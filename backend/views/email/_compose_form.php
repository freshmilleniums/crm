<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Url;
use backend\widgets\tinymce\TinyMceWidget;

/* @var $this yii\web\View */
/* @var $account common\models\UserCorporateEmail|common\models\EmailAccount */
/* @var $accountType string */
/* @var $replyTo common\models\CorporateEmailMessage|common\models\ExternalEmailMessage|null */
/* @var $forwardMessage common\models\CorporateEmailMessage|common\models\ExternalEmailMessage|null */

$isReply = isset($replyTo);
$isForward = isset($forwardMessage);

if ($isReply) {
    $toEmail = $replyTo->from_email;
    $subject = 'Re: ' . $replyTo->subject;
    $body = "\n\n--- Original Message ---\n" .
        "From: " . $replyTo->from_email . "\n" .
        "Date: " . Yii::$app->formatter->asDatetime($replyTo->received_at) . "\n" .
        "Subject: " . $replyTo->subject . "\n\n" .
        strip_tags($replyTo->body_html ?: $replyTo->body_text);
} elseif ($isForward) {
    $toEmail = '';
    $subject = 'Fwd: ' . $forwardMessage->subject;
    $body = "\n\n--- Forwarded Message ---\n" .
        "From: " . $forwardMessage->from_email . "\n" .
        "Date: " . Yii::$app->formatter->asDatetime($forwardMessage->received_at) . "\n" .
        "Subject: " . $forwardMessage->subject . "\n\n" .
        strip_tags($forwardMessage->body_html ?: $forwardMessage->body_text);
} else {
    $toEmail = '';
    $subject = '';
    $body = '';
}
?>

    <div class="compose-email-form">
        <?php $form = ActiveForm::begin([
            'id' => 'compose-email-form',
            'action' => Url::to(['send']),
            'options' => ['enctype' => 'multipart/form-data']
        ]); ?>

        <?= Html::hiddenInput('account_id', $account->id) ?>
        <?= Html::hiddenInput('account_type', $accountType) ?>

        <div class="form-group">
            <label>From</label>
            <input type="text" class="form-control" value="<?= Html::encode($account->email) ?>" disabled>
        </div>

        <div class="form-group">
            <label for="to">To <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control"
                   id="to"
                   name="to"
                   value="<?= Html::encode($toEmail) ?>"
                   placeholder="recipient@example.com"
                   required>
            <small class="form-text text-muted">Separate multiple recipients with commas</small>
        </div>

        <div class="form-group">
            <label for="cc">CC</label>
            <input type="text"
                   class="form-control"
                   id="cc"
                   name="cc"
                   placeholder="cc@example.com">
        </div>

        <div class="form-group">
            <label for="bcc">BCC</label>
            <input type="text"
                   class="form-control"
                   id="bcc"
                   name="bcc"
                   placeholder="bcc@example.com">
        </div>

        <div class="form-group">
            <label for="subject">Subject <span class="text-danger">*</span></label>
            <input type="text"
                   class="form-control"
                   id="subject"
                   name="subject"
                   value="<?= Html::encode($subject) ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="body">Message <span class="text-danger">*</span></label>
            <?= TinyMceWidget::widget([
                'name' => 'body',
                'value' => $body,
                'options' => ['rows' => 12, 'id' => 'compose-body'],
                'clientOptions' => [
                    'height' => 400,
                    'menubar' => false,
                    'plugins' => [
                        'advlist', 'autolink', 'lists', 'link', 'charmap',
                        'searchreplace', 'visualblocks', 'code',
                        'insertdatetime', 'table', 'help', 'wordcount'
                    ],
                    'toolbar' => 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | removeformat | help',
                    'content_style' => 'body { font-family: Arial, sans-serif; font-size: 14px; }',
                ]
            ]) ?>
        </div>

        <div class="form-group">
            <label>Attachments</label>
            <input type="file"
                   class="form-control-file"
                   name="attachments[]"
                   multiple
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.zip">
            <small class="form-text text-muted">Max 5 files, 10MB each</small>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary send-email-btn">
                <i class="fas fa-paper-plane"></i> Send Email
            </button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

<?php
$js = <<<JS
$(document).on('submit', '#compose-email-form', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var button = form.find('.send-email-btn');
    var formData = new FormData(form[0]);
    
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
    
    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#composeEmailModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('Failed to send email');
        },
        complete: function() {
            button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Email');
        }
    });
});

$('#composeEmailModal').on('hidden.bs.modal', function() {
    if (typeof tinymce !== 'undefined') {
        tinymce.remove();
    }
});
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>