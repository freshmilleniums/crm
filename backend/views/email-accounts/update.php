<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Url;
use common\models\EmailAccount;

/* @var $this yii\web\View */
/* @var $model common\models\EmailAccount */
/* @var $form yii\bootstrap4\ActiveForm */
?>

<div class="email-account-form">

    <?php $form = ActiveForm::begin([
        'id' => 'email-account-form-update',
        'action' => Url::to(['update', 'id' => $model->id]),
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'label')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'plainPassword')->passwordInput(['maxlength' => true, 'placeholder' => 'Leave empty to keep current password']) ?>
        </div>
    </div>

    <hr>
    <h5>IMAP Settings (Receiving Emails)</h5>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'imap_host')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'imap_port')->textInput(['type' => 'number']) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'imap_encryption')->dropDownList(EmailAccount::getImapEncryptionList()) ?>
        </div>
    </div>

    <hr>
    <h5>SMTP Settings (Sending Emails)</h5>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'smtp_host')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'smtp_port')->textInput(['type' => 'number']) ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'smtp_encryption')->dropDownList(EmailAccount::getSmtpEncryptionList()) ?>
        </div>
    </div>

    <hr>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'is_corporate')->checkbox() ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'is_active')->checkbox() ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::button('Save', [
            'class' => 'btn btn-success update-email-account-btn-save'
        ]) ?>
        <?= Html::button('Cancel', [
            'class' => 'btn btn-secondary',
            'data-dismiss' => 'modal'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>