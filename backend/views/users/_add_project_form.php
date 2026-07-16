<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $project common\models\Project */
/* @var $employee backend\models\User */
?>

<div class="add-project-form">

    <?php $form = ActiveForm::begin([
        'id'     => 'add-project-form',
        'action' => ['users/add-project', 'id' => $employee->id],
        'options'=> ['data-pjax' => false],
    ]); ?>

    <?= $form->field($project, 'employee_id')->hiddenInput()->label(false) ?>

    <?= $form->field($project, 'name')->textInput(['maxlength' => true]) ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($project, 'type')->dropDownList(
                \common\models\Project::getTypeList(),
                ['prompt' => 'Select type...']
            ) ?>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="control-label">Assigned Employee</label>
                <input type="text"
                       class="form-control"
                       value="<?= Html::encode($employee->getFullName()) ?>"
                       disabled>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($project, 'net_worth')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($project, 'roi')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        </div>
    </div>

    <?= $form->field($project, 'comment')->textarea(['rows' => 3, 'maxlength' => 5000]) ?>

    <div class="form-group text-right">
        <?= Html::button('Create Project', [
            'class' => 'btn btn-success add-project-submit-btn',
            'type'  => 'submit',
        ]) ?>
        <?= Html::button('Cancel', [
            'class'        => 'btn btn-secondary ml-2',
            'data-dismiss' => 'modal',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>