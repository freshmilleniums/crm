<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $employees array */
?>

<div class="bulk-assign-form">

    <?php $form = ActiveForm::begin([
        'id' => 'bulk-assign-form',
        'options' => [
            'data-pjax' => false,
        ]
    ]); ?>

    <p>Select an employee to assign the selected investors to:</p>

    <div class="form-group">
        <label>Employee</label>
        <?= Select2::widget([
            'name' => 'employee_id',
            'data' => $employees,
            'options' => [
                'placeholder' => 'Select employee...',
                'id' => 'bulk-assign-employee-select',
            ],
            'pluginOptions' => [
                'allowClear' => true,
            ],
        ]) ?>
    </div>

    <div class="form-group text-right">
        <?= Html::button('Assign', [
            'type' => 'submit',
            'class' => 'btn btn-success bulk-assign-submit-btn'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>