<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Url;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $accountId int */
/* @var $accountType string */
/* @var $templates array */
?>

<div class="compose-template-form">

    <?php $form = ActiveForm::begin([
        'id'     => 'compose-template-form',
        'action' => Url::to(['send-template']),
    ]); ?>

    <?= Html::hiddenInput('account_id', $accountId) ?>
    <?= Html::hiddenInput('account_type', $accountType) ?>

    <div class="form-group">
        <label for="template_id">Template <span class="text-danger">*</span></label>
        <?= Select2::widget([
            'name'          => 'template_id',
            'data'          => $templates,
            'options'       => [
                'id'          => 'template_id',
                'placeholder' => 'Select template...',
            ],
            'pluginOptions' => [
                'allowClear' => true,
            ],
        ]) ?>
    </div>

    <div class="form-group">
        <label for="to">To <span class="text-danger">*</span></label>
        <input type="text"
               class="form-control"
               id="to"
               name="to"
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
        <button type="submit" class="btn btn-success compose-template-submit-btn">
            <i class="fas fa-paper-plane"></i> Send Template
        </button>
        <button type="button" class="btn btn-secondary ml-2" data-dismiss="modal">Cancel</button>
    </div>

    <?php ActiveForm::end(); ?>

</div>