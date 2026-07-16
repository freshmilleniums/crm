<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Url;
use backend\widgets\tinymce\TinyMceWidget;

/* @var $this yii\web\View */
/* @var $model common\models\CallCenterScript */
/* @var $isModal bool - true when opened in modal (create), false when inline row (edit) */

$isModal = $model->isNewRecord;

$formId = $isModal ? 'script-create-form' : 'script-ajax-form';
$submitLabel = $isModal ? 'Create Script' : 'Save';

$action = $model->isNewRecord
    ? Url::to(['/call-center-settings/create-script'])
    : Url::to(['/call-center-settings/update-script', 'id' => $model->id]);
?>

<div class="script-form">
    <?php $form = ActiveForm::begin([
        'id'      => $formId,
        'action'  => $action,
        'options' => ['data-pjax' => false],
    ]); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => 255]) ?>

    <?= $form->field($model, 'content')->widget(TinyMceWidget::class, [
        'options' => ['rows' => 10],
        'clientOptions' => [
            'height'         => 350,
            'menubar'        => false,
            'plugins'        => [
                'advlist', 'autolink', 'lists', 'link', 'charmap',
                'searchreplace', 'visualblocks', 'code',
                'insertdatetime', 'table', 'help', 'wordcount'
            ],
            'toolbar'        => 'undo redo | blocks | bold italic underline | bullist numlist | removeformat | help',
            'toolbar_sticky' => false,
            'content_style'  => 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
        ],
    ]) ?>

    <?= $form->field($model, 'is_active')->checkbox() ?>

    <div class="form-group <?= $isModal ? 'text-right' : '' ?>">
        <?php if (!$isModal): ?>
            <?= Html::a(
                '<i class="fas fa-times"></i> Cancel',
                '#',
                ['class' => 'btn btn-secondary mr-2 cancel-action']
            ) ?>
        <?php else: ?>
            <?= Html::button('Cancel', [
                'class'        => 'btn btn-secondary mr-2',
                'type'         => 'button',
                'data-dismiss' => 'modal',
            ]) ?>
        <?php endif; ?>

        <?= Html::button($submitLabel, [
            'class' => 'btn btn-success script-form-submit',
            'type'  => 'submit',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>