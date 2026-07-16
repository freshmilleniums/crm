<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\Investor */
/* @var $employees array */
/* @var $currentEmployeeIds array */
?>

<div class="assign-employees-form">
    <p>Assign employees to investor: <strong><?= Html::encode($model->getFullName()) ?></strong></p>

    <?php $form = ActiveForm::begin([
        'id' => 'assign-employees-form',
        'action' => Url::to(['assign-employees', 'id' => $model->id]),
        'options' => ['data-pjax' => false],
    ]); ?>

    <div class="form-group">
        <label>Employees</label>
        <?= Select2::widget([
            'name' => 'employee_ids',
            'data' => $employees,
            'value' => $currentEmployeeIds,
            'options' => [
                'placeholder' => 'Select employees...',
                'multiple' => true,
                'id' => 'assign-employees-select',
            ],
            'pluginOptions' => [
                'allowClear' => true,
            ],
        ]) ?>
    </div>

    <div class="form-group text-right">
        <?= Html::button('Assign', [
            'class' => 'btn btn-success assign-submit-btn',
            'type' => 'submit'
        ]) ?>
        <?= Html::button('Cancel', [
            'class' => 'btn btn-secondary',
            'data-dismiss' => 'modal'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>