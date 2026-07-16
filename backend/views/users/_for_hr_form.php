<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use \yii\helpers\ArrayHelper;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model backend\models\User */
/* @var $form yii\bootstrap4\ActiveForm */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin([
        'id' => 'user-update-form-' . $model->id,
        'action' => Url::to(['employees/for-hr-update', 'id' => $model->id]),
        'enableAjaxValidation' => true,
        'enableClientValidation' => true,
        'validationUrl' => Url::toRoute(['employees/ajax-validation', 'id' => $model->id]),
        'options' => [
            'data-pjax' => false,
        ]
    ]); ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'first_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'last_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'address')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'phone_number')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'city')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'state')->dropdownList($model->getStates(),['prompt' => 'Choose a state']) ?>

    <?= $form->field($model, 'zip_code')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::button('Save', [
            'class' => 'btn btn-success update-employee-send',
            'id' => 'update-employee-send-' . $model->id
        ]) ?>
        <?= Html::button('Cancel', [
            'class' => 'btn btn-secondary cancel-action'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>