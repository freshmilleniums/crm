<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Project */
/* @var $employees array */
/* @var $form yii\bootstrap4\ActiveForm */
?>

<div class="projects-form">

    <?php $form = ActiveForm::begin([
        'id' => 'projects-form-ajax',
        'action' => ['projects/create-ajax'],
    ]); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'type')->dropDownList($model::getTypeList(), [
                'prompt' => 'Select type...'
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'employee_id')->dropDownList($employees, [
                'prompt' => 'Select employee...'
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'net_worth')->textInput(['type' => 'number']) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'roi')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        </div>
    </div>

    <?= $form->field($model, 'comment')->textarea(['rows' => 4, 'maxlength' => 5000]) ?>

    <div class="form-group">
        <?= Html::submitButton('Create Project', [
            'class' => 'btn btn-success project-submit-btn'
        ]) ?>
        <?= Html::button('Cancel', [
            'class' => 'btn btn-secondary',
            'data-dismiss' => 'modal'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>