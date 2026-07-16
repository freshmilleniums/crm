<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\Template */
/* @var $employees array */
?>

<div class="send-template-form">

    <?php $form = ActiveForm::begin([
        'id' => 'send-template-form',
        'action' => Url::to(['send-to-employee']),
    ]); ?>

    <?= Html::hiddenInput('template_id', $model->id) ?>

    <div class="alert alert-secondary">
        <p><strong>Template:</strong> <?= Html::encode($model->title) ?></p>
        <?php if ($model->subject): ?>
            <p class="mb-0"><strong>Subject:</strong> <?= Html::encode($model->subject) ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="employee_id">Select Employee</label>
        <?= Html::dropDownList('employee_id', null, $employees, [
            'class' => 'form-control',
            'prompt' => 'Select employee...',
            'id' => 'employee_id'
        ]) ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Send Template', [
            'class' => 'btn btn-success send-submit-btn'
        ]) ?>
        <?= Html::button('Cancel', [
            'class' => 'btn btn-secondary',
            'data-dismiss' => 'modal'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>