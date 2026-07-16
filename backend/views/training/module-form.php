<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use backend\widgets\tinymce\TinyMceWidget;

/* @var $this yii\web\View */
/* @var $model backend\models\TrainingModule */
/* @var $form yii\bootstrap4\ActiveForm */

$this->title = $model->isNewRecord ? 'Create Training Module' : 'Update Training Module: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Training Modules', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="training-module-form">
                        <?php $form = ActiveForm::begin([
                            'options' => ['enctype' => 'multipart/form-data'],
                        ]); ?>

                        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

                        <?= $form->field($model, 'content')->widget(TinyMceWidget::class, [
                            'options' => ['rows' => 10],
                            'clientOptions' => [
                                'height' => 400,
                                'menubar' => false,
                                'plugins' => [
                                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                                ],
                                'toolbar' => 'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | forecolor backcolor | removeformat | help',
                                'toolbar_sticky' => true,
                                'toolbar_mode' => 'sliding',
                                'paste_as_text' => false,
                                'content_style' => 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
                            ]
                        ]) ?>

                        <?= $form->field($model, 'passing_score')->textInput([
                            'type' => 'number',
                            'min' => 0,
                            'max' => 100,
                        ])->hint('Minimum percentage of correct answers required to pass (0-100)') ?>

                        <?= $form->field($model, 'is_active')->checkbox() ?>

                        <?= $form->field($model, 'is_final_task')->checkbox([
                            'id' => 'trainingmodule-is_final_task',
                        ]) ?>

                        <div id="final-task-fields" style="<?= $model->is_final_task ? '' : 'display:none;' ?>">
                            <div class="card card-warning mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Final Task Settings</h5>
                                </div>
                                <div class="card-body">
                                    <?= $form->field($model, 'task_title')->textInput(['maxlength' => true]) ?>
                                    <?= $form->field($model, 'task_subject')->textInput(['maxlength' => true]) ?>
                                    <?= $form->field($model, 'task_body')->textarea(['rows' => 6]) ?>
                                    <?= $form->field($model, 'task_deadline_hours')->textInput([
                                        'type' => 'number',
                                        'min' => 1,
                                    ])->hint('Deadline in hours from task start (leave empty for no deadline)') ?>

                                    <?= $form->field($model, 'file')->fileInput([
                                        'accept' => '.png,.jpg,.jpeg,.gif,.pdf,.doc,.docx,.odt',
                                    ])->hint($model->task_file ? 'Current file: ' . basename($model->task_file) . '. Upload new to replace.' : 'Attach a file to the task') ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <?= Html::submitButton( ($model->isNewRecord ? 'Create Module' : 'Save'), ['class' => 'btn btn-success']) ?>
                            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-secondary ml-2']) ?>
                        </div>

                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->registerJs("
    $('#trainingmodule-is_final_task').change(function() {
        if ($(this).is(':checked')) {
            $('#final-task-fields').show();
        } else {
            $('#final-task-fields').hide();
        }
    });
"); ?>