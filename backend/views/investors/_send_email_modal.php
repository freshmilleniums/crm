<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Url;
use yii\helpers\Json;
use kartik\select2\Select2;
use backend\widgets\tinymce\TinyMceWidget;
/* @var $this yii\web\View */
/* @var $investorsList array */
/* @var $templatesList array */
$getTemplateUrl = Url::to(['/email/get-template']);
?>
    <div class="investor-send-email-form">
        <?php $form = ActiveForm::begin([
            'id'      => 'investor-send-email-form',
            'action'  => Url::to(['send-email']),
            'options' => ['enctype' => 'multipart/form-data'],
        ]); ?>

        <div class="form-group">
            <label>Send From (Investor) <span class="text-danger">*</span></label>
            <?= Select2::widget([
                'name'          => 'investor_id',
                'data'          => $investorsList,
                'options'       => ['id' => 'investor-select', 'placeholder' => 'Select investor...'],
                'pluginOptions' => ['allowClear' => true],
            ]) ?>
        </div>

        <div class="form-group">
            <label>Template <small class="text-muted">(optional)</small></label>
            <?= Select2::widget([
                'name'          => 'template_id',
                'data'          => $templatesList,
                'options'       => ['id' => 'investor-template-select', 'placeholder' => 'Select template to prefill subject and body...'],
                'pluginOptions' => ['allowClear' => true],
            ]) ?>
            <div id="investor-template-attachments-info" class="mt-1" style="display:none;">
                <small class="text-primary"><i class="fas fa-paperclip"></i> <span id="investor-template-attachments-list"></span></small>
            </div>
        </div>

        <div class="form-group">
            <label>To (Employee) <span class="text-danger">*</span></label>
            <?= Select2::widget([
                'name'          => 'employee_id',
                'data'          => $employeesList,
                'options'       => ['id' => 'investor-employee-select', 'placeholder' => 'Select employee...'],
                'pluginOptions' => ['allowClear' => true],
            ]) ?>
        </div>

        <div class="form-group">
            <label for="investor-email-cc">CC</label>
            <input type="text" class="form-control" id="investor-email-cc" name="cc" placeholder="cc@example.com">
        </div>

        <div class="form-group">
            <label for="investor-email-bcc">BCC</label>
            <input type="text" class="form-control" id="investor-email-bcc" name="bcc" placeholder="bcc@example.com">
        </div>

        <div class="form-group">
            <label for="investor-email-subject">Subject <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="investor-email-subject" name="subject" placeholder="Email subject">
        </div>

        <div class="form-group">
            <label>Message</label>
            <div id="investor-body-wrap">
                <?= TinyMceWidget::widget([
                    'name'          => 'body',
                    'value'         => '',
                    'options'       => ['rows' => 12, 'id' => 'investor-email-body'],
                    'clientOptions' => [
                        'height'        => 400,
                        'menubar'       => false,
                        'plugins'       => ['advlist','autolink','lists','link','charmap','searchreplace','visualblocks','code','insertdatetime','table','help','wordcount'],
                        'toolbar'       => 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | removeformat | help',
                        'content_style' => 'body { font-family: Arial, sans-serif; font-size: 14px; }',
                    ],
                ]) ?>
            </div>
            <div id="investor-template-preview" class="border rounded p-3 bg-light" style="display:none; max-height:400px; overflow:auto;"></div>
        </div>

        <div class="form-group">
            <label>Attachments</label>
            <input type="file" class="form-control-file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.zip">
            <small class="form-text text-muted">Template documents are attached automatically when a template is selected.</small>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary investor-email-submit-btn">
                <i class="fas fa-paper-plane"></i> Send Email
            </button>
            <button type="button" class="btn btn-secondary ml-2" data-dismiss="modal">Cancel</button>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
<?php
$js = <<<JS
var getTemplateUrl = '$getTemplateUrl';

function resetInvestorTemplate() {
    \$('#investor-email-subject').val('').prop('readonly', false).css('background-color', '');
    \$('#investor-template-preview').hide().empty();
    \$('#investor-body-wrap').show();
    \$('#investor-template-attachments-info').hide();
}

function loadInvestorTemplate() {
    var templateId = $('#investor-template-select').val();
    if (!templateId) { resetInvestorTemplate(); return; }
    $.ajax({
        url: getTemplateUrl,
        type: 'GET',
        data: { template_id: templateId },
        dataType: 'json',
        success: function(response) {
            if (!response.success) { toastr.error(response.message || 'Failed to load template'); return; }
            \$('#investor-email-subject').val(response.subject || '').prop('readonly', true).css('background-color', '#f8f9fa');
            \$('#investor-body-wrap').hide();
            \$('#investor-template-preview').html(response.body).show();
            if (response.attachments && response.attachments.length > 0) {
                \$('#investor-template-attachments-list').text('Template attachments: ' + response.attachments.join(', '));
                \$('#investor-template-attachments-info').show();
            } else {
                \$('#investor-template-attachments-info').hide();
            }
        },
        error: function() { toastr.error('Failed to load template'); }
    });
}

$(document).off('change', '#investor-employee-select').on('change', '#investor-employee-select', function() {
    if ($('#investor-template-select').val()) { loadInvestorTemplate(); }
});

\$(document).off('change', '#investor-template-select').on('change', '#investor-template-select', loadInvestorTemplate);

\$(document).off('submit', '#investor-send-email-form').on('submit', '#investor-send-email-form', function(e) {
    e.preventDefault();
    var form   = \$(this);
    var button = form.find('.investor-email-submit-btn');
    if (typeof tinymce !== 'undefined') { tinymce.triggerSave(); }
    var formData = new FormData(form[0]);
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
    \$.ajax({
        type: 'POST', url: form.prop('action'), data: formData,
        processData: false, contentType: false, dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                \$('#investorModal').modal('hide');
            } else {
                toastr.error(response.message);
                button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Email');
            }
        },
        error: function() {
            toastr.error('Failed to send email');
            button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Send Email');
        }
    });
});

\$('#investorModal').off('hidden.bs.modal.investor-email').on('hidden.bs.modal.investor-email', function() {
    if (typeof tinymce !== 'undefined') { tinymce.remove('#investor-email-body'); }
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>