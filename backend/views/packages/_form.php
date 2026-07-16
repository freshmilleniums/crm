<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\datetime\DateTimePicker;
use common\models\PackagesLabels;
use kartik\file\FileInput;
use backend\widgets\dynamicForm\DynamicFormWidget;

/* @var $this yii\web\View */
/* @var $model common\models\Packages */
/* @var $packagesLabels PackagesLabels[]|null */
/* @var $form yii\bootstrap4\ActiveForm */

?>

<div class="packages-form">

    <?php $form = ActiveForm::begin(['id' => 'packages-form']); ?>

    <?= $form->field($model, 'courier_id')->widget(\kartik\select2\Select2::class, [
        'options' => [
            'placeholder' => 'Select courier ...',
            'value' => ($model->courier_id > 0) ? $model->courier_id : null
        ],
        'data' => $model->courier_id != 0
            ? [$model->courier_id => $model->courierName]
            : [],
        'initValueText' => $model->courierName,
        'pluginOptions' => [
            'allowClear' => true,
            'ajax' => [
                'url' => \yii\helpers\Url::to(['employees/get-couriers']),
                'dataType' => 'json',
                'data' => new \yii\web\JsExpression('function(params) { return {q: params.term}; }'),
                'processResults' => new \yii\web\JsExpression('function(data) {
                    return {
                        results: $.map(data, function(item) {
                            return {id: item.id, text: item.name};
                        })
                    };
                }'),
            ],
            'minimumInputLength' => 1,
            'templateSelection' => new \yii\web\JsExpression('function (data) {
                if (!data.id) {
                    return "Select courier ...";
                }
                return data.text || data.name || data.id;
            }'),
        ],
    ]); ?>

    <?= $form->field($model, 'delivery_date')->widget(DateTimePicker::class, [
        'options' => [
            'placeholder' => 'Select delivery date and time',
            'readonly' => true,
            'class' => 'form-control'
        ],
        'pluginOptions' => [
            'format' => 'yyyy-mm-dd hh:ii',
            'autoclose' => true,
            'todayHighlight' => true,
            'keyboardNavigation' => false,
        ]
    ]) ?>

    <?= $form->field($model, 'track')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'post')->dropDownList($model::getPostList()) ?>

    <?= $form->field($model, 'weight')->textInput(['type' => 'number']) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>

    <?= $form->field($model, 'comment')->textarea(['rows' => 3, 'maxlength' => 1000]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create Package' : 'Update Package', [
            'class' => 'btn btn-success d-none d-md-inline-block btn-lg'
        ]) ?>
        <?= Html::submitButton($model->isNewRecord ? 'Create Package' : 'Update Package', [
            'class' => 'btn btn-success d-block d-md-none btn-block btn-lg'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>