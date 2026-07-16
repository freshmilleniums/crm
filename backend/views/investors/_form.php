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