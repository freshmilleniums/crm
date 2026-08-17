<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\EmailAccount;

/* @var $this yii\web\View */
/* @var $model common\models\EmailAccount */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="email-account-form">

    <?php $form = ActiveForm::begin([
        'id' => 'email-account-form-ajax',
        'enableClientValidation' => false,
        'options' => ['autocomplete' => 'off'],
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'email')->textInput(['maxlength' => true])->label('Email Address') ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'label')->textInput(['maxlength' => true])->label('Display Label') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'plainPassword')->passwordInput()->label('Password') ?>
        </div>
    </div>

    <hr>
    <h5>IMAP Settings (Receiving Emails)</h5>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'imap_host')->textInput([
                'maxlength' => true,
                'placeholder' => 'imap.gmail.com'
            ])->label('IMAP Host') ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'imap_port')->textInput(['type' => 'number'])->label('IMAP Port') ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'imap_encryption')->dropDownList([
                EmailAccount::IMAP_ENCRYPTION_SSL => 'SSL',
                EmailAccount::IMAP_ENCRYPTION_TLS => 'TLS',
            ])->label('IMAP Encryption') ?>
        </div>
    </div>

    <hr>
    <h5>SMTP Settings (Sending Emails)</h5>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'smtp_host')->textInput([
                'maxlength' => true,
                'placeholder' => 'smtp.gmail.com'
            ])->label('SMTP Host') ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'smtp_port')->textInput(['type' => 'number'])->label('SMTP Port') ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'smtp_encryption')->dropDownList([
                EmailAccount::SMTP_ENCRYPTION_SSL => 'SSL',
                EmailAccount::SMTP_ENCRYPTION_TLS => 'TLS',
                EmailAccount::SMTP_ENCRYPTION_STARTTLS => 'STARTTLS',
            ])->label('SMTP Encryption') ?>
        </div>
    </div>

    <hr>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'is_active')->checkbox()->label('') ?>
        </div>
        <div class="col-md-6">
            <?php //= $form->field($model, 'is_corporate')->checkbox()->label('') ?>
        </div>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-success submit-btn">Create Email Account</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
    </div>

    <?php ActiveForm::end(); ?>

</div>