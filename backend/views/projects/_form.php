<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\Project */
/* @var $employees array */
/* @var $form yii\bootstrap4\ActiveForm */
?>

<div class="projects-form">

    <?php $form = ActiveForm::begin([
        'id' => 'project-form-' . $model->id,
        'action' => Url::to(['projects/update', 'id' => $model->id]),
        'enableAjaxValidation' => false,
        'enableClientValidation' => true,
        'options' => [
            'data-pjax' => false,
        ]
    ]); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <div style="display: flex; gap: 15px;">
        <div style="flex: 1;">
            <?= $form->field($model, 'type')->dropDownList($model::getTypeList(), [
                'prompt' => 'Select type...'
            ]) ?>
        </div>
        <div style="flex: 1;">
            <?= $form->field($model, 'employee_id')->dropDownList($employees, [
                'prompt' => 'Select employee...'
            ]) ?>
        </div>
    </div>

    <div style="display: flex; gap: 15px;">
        <div style="flex: 1;">
            <?= $form->field($model, 'net_worth')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        </div>
        <div style="flex: 1;">
            <?= $form->field($model, 'roi')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        </div>
    </div>

    <?= $form->field($model, 'status')->dropDownList($model::getStatusList(), [
        'prompt' => 'Select status...'
    ]) ?>

    <?= $form->field($model, 'comment')->textarea(['rows' => 4, 'maxlength' => 5000]) ?>

    <div class="form-group">
        <?= Html::button('Save', [
            'class' => 'btn btn-success update-project-send',
            'id' => 'update-project-send-' . $model->id
        ]) ?>
        <?= Html::button('Cancel', [
            'class' => 'btn btn-secondary cancel-action'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>