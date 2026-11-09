<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\Investor */
/* @var $form yii\bootstrap4\ActiveForm */

$isNewRecord = $model->isNewRecord;
?>

<div class="investor-form">

    <?php $form = ActiveForm::begin([
        'id' => $isNewRecord ? 'investors-form-ajax' : 'update-investor-form-' . $model->id,
        'action' => $isNewRecord ? Url::to(['create-ajax']) : Url::to(['update', 'id' => $model->id]),
        'enableAjaxValidation' => false,
        'options' => [
            'enctype' => 'multipart/form-data',
            'data-pjax' => false,
        ]
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'first_name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'last_name')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'type' => 'email']) ?>

    <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'investor_type')->dropdownList(
                \common\models\Investor::getTypeList(),
                ['prompt' => 'Select Type...']
            ) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'net_value')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        </div>
    </div>

    <?= $form->field($model, 'comment')->textarea(['rows' => 4, 'maxlength' => true]) ?>

    <hr>
    <h5>Investor SMTP Settings</h5>
    <p class="text-muted small">Configure the outgoing email account for this investor. Leave blank if not needed.</p>

    <?= $form->field($model, 'smtp_host')->textInput(['maxlength' => true, 'placeholder' => 'e.g. smtp.gmail.com']) ?>

    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'smtp_port')->textInput(['type' => 'number', 'min' => 1, 'max' => 65535]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'smtp_encryption')->dropdownList(
                \common\models\Investor::getSmtpEncryptionList(),
                ['prompt' => 'Select...']
            ) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'smtp_username')->textInput(['maxlength' => true, 'placeholder' => 'Login or identifier']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'plainSmtpPassword')->passwordInput([
                'autocomplete' => 'new-password',
                'placeholder'  => $model->isNewRecord ? 'Password' : 'Leave blank to keep current',
            ])->hint($model->isNewRecord ? '' : 'Fill only if you want to change the password') ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'smtp_from_email')->textInput([
                'maxlength'   => true,
                'type'        => 'email',
                'placeholder' => 'e.g. noreply@investor.com',
            ])->hint('From address in outgoing emails. If empty, SMTP username or investor email is used.') ?>
        </div>
    </div>

    <div class="form-group text-right">
        <?= Html::button($isNewRecord ? 'Create Investor' : 'Save', [
            'type' => $isNewRecord ? 'submit' : 'button',
            'class' => $isNewRecord ? 'btn btn-success investor-submit-btn' : 'btn btn-success update-investor-send'
        ]) ?>

        <?php if (!$isNewRecord): ?>
            <?= Html::button('Cancel', [
                'class' => 'btn btn-secondary cancel-action ml-2'
            ]) ?>
        <?php endif; ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>