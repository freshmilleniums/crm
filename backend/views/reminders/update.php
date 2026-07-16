<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Reminders */

$this->title = 'Update Reminder: ' . $model->code;
$this->params['breadcrumbs'][] = ['label' => 'Reminders', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Update';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">

                    <div class="alert alert-secondary">
                        <strong>Code:</strong> <?= Html::encode($model->code) ?><br>
                        <strong>Description:</strong> <?= Html::encode($model->getCodeDescription()) ?>
                    </div>

                    <?php $form = ActiveForm::begin(); ?>

                    <?= $form->field($model, 'text')->textarea([
                        'rows' => 10,
                        'placeholder' => 'Enter reminder text...'
                    ]) ?>

                    <div class="form-group">
                        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
                        <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-secondary']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                </div>
            </div>
        </div>
        <!--.card-body-->
    </div>
    <!--.card-->
</div>