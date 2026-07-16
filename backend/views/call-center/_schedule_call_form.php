<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Url;
use kartik\datetime\DateTimePicker;

/* @var $this yii\web\View */
/* @var $model common\models\ScheduledCall */
/* @var $candidate backend\models\User */
/* @var $scheduledCalls common\models\ScheduledCall[] */
?>

<div class="schedule-call-form">

    <div class="mb-3">
        <strong>Candidate:</strong> <?= Html::encode($candidate->getFullName()) ?>
        &nbsp;|&nbsp;
        <strong>Phone:</strong> <?= Html::encode($candidate->phone_number) ?>
    </div>

    <?php $form = ActiveForm::begin([
        'id'      => 'schedule-call-ajax-form',
        'action'  => Url::to(['/call-center/schedule-call', 'candidateId' => $candidate->id]),
        'options' => ['data-pjax' => false],
    ]); ?>

    <?= Html::activeHiddenInput($model, 'candidate_id') ?>

    <?= $form->field($model, 'scheduled_at_formatted')->widget(DateTimePicker::class, [
        'options' => [
            'placeholder' => 'Select date and time',
            'readonly'    => true,
            'class'       => 'form-control',
        ],
        'pluginOptions' => [
            'format'             => 'yyyy-mm-dd hh:ii',
            'autoclose'          => true,
            'todayHighlight'     => true,
            'keyboardNavigation' => false,
            'startDate'          => date('Y-m-d H:i'),
        ],
    ])->label('Date & Time') ?>

    <?= $form->field($model, 'comment')->textarea([
        'rows'        => 3,
        'maxlength'   => 500,
        'placeholder' => 'Optional comment...',
    ]) ?>

    <div class="form-group text-right">
        <?= Html::button('Cancel', [
            'class'        => 'btn btn-secondary mr-2',
            'type'         => 'button',
            'data-dismiss' => 'modal',
        ]) ?>
        <?= Html::button('Schedule', [
            'class' => 'btn btn-success schedule-call-submit',
            'type'  => 'submit',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>