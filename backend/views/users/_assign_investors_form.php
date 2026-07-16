<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $investorsList array */
?>

<div class="assign-investors-form">

    <?php $form = ActiveForm::begin([
        'id'      => 'assign-investors-form',
        'action'  => Url::to(['assign-investors']),
        'options' => ['data-pjax' => false],
    ]); ?>

    <div class="form-group">
        <label>Investors</label>
        <?= Select2::widget([
            'name'          => 'investor_ids',
            'data'          => $investorsList,
            'options'       => [
                'placeholder' => 'Select investors...',
                'multiple'    => true,
                'id'          => 'assign-investors-select',
            ],
            'pluginOptions' => [
                'allowClear' => true,
            ],
        ]) ?>
    </div>

    <div class="form-group text-right">
        <?= Html::button('Assign', [
            'class' => 'btn btn-success assign-investors-submit-btn',
            'type'  => 'submit',
        ]) ?>
        <?= Html::button('Cancel', [
            'class'        => 'btn btn-secondary ml-2',
            'data-dismiss' => 'modal',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>