<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $employee backend\models\User */
/* @var $templatesList array */
?>

<div class="send-template-form">

    <?php $form = ActiveForm::begin([
        'id'     => 'send-template-form',
        'action' => Url::to(['templates/send-to-employee']),
        'options'=> ['data-pjax' => false],
    ]); ?>

    <?= Html::hiddenInput('employee_id', $employee->id) ?>

    <div class="form-group">
        <label class="control-label">Employee</label>
        <input type="text"
               class="form-control"
               value="<?= Html::encode($employee->getFullName()) ?>"
               disabled>
    </div>

    <div class="form-group">
        <label class="control-label">Template</label>
        <?= Select2::widget([
            'name'          => 'template_id',
            'data'          => $templatesList,
            'options'       => [
                'placeholder' => 'Select template...',
                'id'          => 'send-template-select',
            ],
            'pluginOptions' => [
                'allowClear' => true,
            ],
        ]) ?>
    </div>

    <div class="form-group text-right">
        <?= Html::button('Send Template', [
            'class' => 'btn btn-success send-template-submit-btn',
            'type'  => 'submit',
        ]) ?>
        <?= Html::button('Cancel', [
            'class'        => 'btn btn-secondary ml-2',
            'data-dismiss' => 'modal',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>