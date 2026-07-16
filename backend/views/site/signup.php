<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap4\ActiveForm $form */
/** @var \common\models\SignupForm $model */

use yii\bootstrap4\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;

$this->title = 'Signup';
?>

<div class="auth-card-header">
    <h1><?= Html::encode($this->title) ?></h1>
</div>

<div class="auth-card-body">
    <p>Please fill out the following fields to signup:</p>

    <?php $form = ActiveForm::begin([
        'id'      => 'form-signup',
        'options' => ['enctype' => 'multipart/form-data'],
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'first_name')->textInput([
                'autofocus' => true,
                'placeholder' => 'First Name'
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'last_name')->textInput([
                'placeholder' => 'Last Name'
            ]) ?>
        </div>
    </div>

    <?= $form->field($model, 'email')->textInput([
        'placeholder' => 'Email'
    ]) ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'phone_number')->textInput([
                'placeholder' => 'Phone Number'
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'home_phone')->textInput([
                'placeholder' => 'Home Phone'
            ]) ?>
        </div>
    </div>

    <?= $form->field($model, 'position_title')->textInput([
        'placeholder' => 'Position Title'
    ]) ?>

    <?= $form->field($model, 'address')->textInput([
        'placeholder' => 'Address'
    ]) ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'city')->textInput([
                'placeholder' => 'City'
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'state')->textInput([
                'placeholder' => 'State/Region'
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'country')->widget(Select2::class, [
                'data' => \common\models\User::getCountries(),
                'options' => [
                    'placeholder' => 'Select country...',
                    'value' => 'US'
                ],
                'pluginOptions' => [
                    'allowClear' => false,
                ],
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'zip_code')->textInput([
                'placeholder' => 'ZIP Code'
            ]) ?>
        </div>
    </div>

    <?= $form->field($model, 'hr_source')->textInput([
        'placeholder' => 'HR Source (optional)'
    ]) ?>

    <div class="form-group">
        <label>Documents</label>
        <div class="mb-2">
            <input type="file" name="documents[]" class="form-control-file"
                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.rtf">
            <small class="text-muted">Contract, identity or other documents</small>
        </div>
        <div class="mb-2">
            <input type="file" name="documents[]" class="form-control-file"
                   accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt,.rtf">
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'password')->passwordInput([
                'placeholder' => 'Password'
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'password_repeat')->passwordInput([
                'placeholder' => 'Repeat Password'
            ]) ?>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Signup', [
            'class' => 'btn btn-primary btn-block',
            'name' => 'signup-button'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <div class="auth-links">
        Already have an account? <?= Html::a('Login here', ['/site/login']) ?>
    </div>
</div>