<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\EmailAccount */
/* @var $admins array */
/* @var $assignedIds array */
?>

<div class="assign-admins-form">
    <p>Assign administrators to email account: <strong><?= Html::encode($model->label ?: $model->email) ?></strong></p>

    <?php $form = ActiveForm::begin([
        'id' => 'assign-admins-form',
        'action' => Url::to(['email-accounts/save-admins']),
        'options' => ['data-pjax' => false],
    ]); ?>

    <?= Html::hiddenInput('id', $model->id) ?>

    <div class="form-group">
        <label>Administrators</label>
        <?= Select2::widget([
            'name'  => 'user_ids',
            'data'  => $admins,
            'value' => $assignedIds,
            'options' => [
                'placeholder' => 'Select administrators...',
                'multiple'    => true,
                'id'          => 'assign-admins-select',
            ],
            'pluginOptions' => [
                'allowClear' => true,
            ],
        ]) ?>
    </div>

    <div class="form-group text-right">
        <?= Html::button('Assign', [
            'class' => 'btn btn-success assign-admins-submit-btn',
            'type'  => 'submit',
        ]) ?>
        <?= Html::button('Cancel', [
            'class'        => 'btn btn-secondary cancel-action',
            'data-dismiss' => 'modal',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>