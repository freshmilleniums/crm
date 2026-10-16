<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $projectsList array */
?>

<div class="assign-to-employees-form">

    <?php $form = ActiveForm::begin([
        'id'      => 'assign-to-employees-form',
        'action'  => Url::to(['projects/assign-to-employees']),
        'options' => ['data-pjax' => false],
    ]); ?>

    <div class="form-group">
        <label>Projects</label>
        <?= Select2::widget([
            'name'          => 'project_ids',
            'data'          => $projectsList,
            'options'       => [
                'placeholder' => 'Select projects...',
                'multiple'    => true,
                'id'          => 'assign-projects-select',
            ],
            'pluginOptions' => [
                'allowClear' => true,
            ],
        ]) ?>
    </div>

    <div class="form-group text-right">
        <?= Html::button('Assign', [
            'class' => 'btn btn-success assign-to-employees-submit-btn',
            'type'  => 'submit',
        ]) ?>
        <?= Html::button('Cancel', [
            'class'        => 'btn btn-secondary ml-2',
            'data-dismiss' => 'modal',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>