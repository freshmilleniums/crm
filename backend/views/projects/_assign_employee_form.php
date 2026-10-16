<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model common\models\Project */
/* @var $employees array */
/* @var $currentEmployeeIds array */
?>

<div class="assign-employee-form">

    <?php $form = ActiveForm::begin([
        'id'     => 'assign-employee-form',
        'action' => ['projects/assign-employee', 'id' => $model->id],
    ]); ?>

    <div class="form-group">
        <label>Employees</label>
        <?= Select2::widget([
            'name'          => 'employee_ids',
            'value'         => $currentEmployeeIds,
            'data'          => $employees,
            'options'       => [
                'placeholder' => 'Select employees...',
                'multiple'    => true,
                'id'          => 'assign-employee-select',
            ],
            'pluginOptions' => [
                'allowClear' => true,
            ],
        ]) ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Assign', [
            'class' => 'btn btn-success assign-submit-btn',
        ]) ?>
        <?= Html::button('Cancel', [
            'class'        => 'btn btn-secondary',
            'data-dismiss' => 'modal',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>