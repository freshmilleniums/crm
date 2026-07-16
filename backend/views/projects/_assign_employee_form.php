<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Project */
/* @var $employees array */
?>

<div class="assign-employee-form">

    <?php $form = ActiveForm::begin([
        'id' => 'assign-employee-form',
        'action' => ['projects/assign-employee', 'id' => $model->id],
    ]); ?>

    <div class="form-group">
        <label for="employee_id">Select Employee</label>
        <?= Html::dropDownList('employee_id', $model->employee_id, $employees, [
            'class' => 'form-control',
            'prompt' => 'Select employee...'
        ]) ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Assign', [
            'class' => 'btn btn-success assign-submit-btn'
        ]) ?>
        <?= Html::button('Cancel', [
            'class' => 'btn btn-secondary',
            'data-dismiss' => 'modal'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>