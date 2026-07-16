<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

/* @var $investor common\models\Investor */
/* @var $employee backend\models\User */
?>

<div class="add-investor-form">

    <?php $form = ActiveForm::begin([
        'id'     => 'add-investor-form',
        'action' => ['users/add-investor', 'id' => $employee->id],
        'options'=> ['data-pjax' => false],
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($investor, 'first_name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($investor, 'last_name')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <?= $form->field($investor, 'email')->textInput(['maxlength' => true, 'type' => 'email']) ?>

    <?= $form->field($investor, 'address')->textInput(['maxlength' => true]) ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($investor, 'investor_type')->dropDownList(
                \common\models\Investor::getTypeList(),
                ['prompt' => 'Select Type...']
            ) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($investor, 'net_value')->textInput(['type' => 'number', 'step' => '0.01']) ?>
        </div>
    </div>

    <?= $form->field($investor, 'comment')->textarea(['rows' => 4, 'maxlength' => true]) ?>

    <div class="form-group">
        <label class="control-label">Assigned Employee</label>
        <input type="text" class="form-control"
               value="<?= Html::encode($employee->getFullName()) ?>" disabled>
    </div>

    <div class="form-group text-right">
        <?= Html::button('Create Investor', [
            'class' => 'btn btn-success add-investor-submit-btn',
            'type'  => 'submit',
        ]) ?>
        <?= Html::button('Cancel', [
            'class'        => 'btn btn-secondary ml-2',
            'data-dismiss' => 'modal',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>