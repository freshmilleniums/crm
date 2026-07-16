<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\datetime\DateTimePicker;
use common\models\TasksDocuments;
use kartik\file\FileInput;
use backend\widgets\dynamicForm\DynamicFormWidget;

/* @var $this yii\web\View */
/* @var $task common\models\Task */
/* @var $employee backend\models\User */
/* @var $documents TasksDocuments[] */

if (count($documents) == 0) {
    $documents = [new TasksDocuments()];
}
?>

    <div class="add-task-form">

        <?php $form = ActiveForm::begin([
            'id'     => 'add-task-form',
            'action' => ['users/add-task', 'id' => $employee->id],
            'options'=> ['enctype' => 'multipart/form-data', 'data-pjax' => false],
        ]); ?>

        <?= $form->field($task, 'assigned_to')->hiddenInput()->label(false) ?>

        <?= $form->field($task, 'title')->textInput(['maxlength' => true]) ?>

        <?= $form->field($task, 'subject')->textInput(['maxlength' => true]) ?>

        <?= $form->field($task, 'description')->textarea(['rows' => 4, 'maxlength' => 5000]) ?>

        <div style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <div class="form-group">
                    <label class="control-label">Assigned To</label>
                    <input type="text"
                           class="form-control"
                           value="<?= Html::encode($employee->getFullName()) ?>"
                           disabled>
                </div>
            </div>
            <div style="flex: 1;">
                <?= $form->field($task, 'priority')->dropDownList(
                    \common\models\Task::getPriorityList(),
                    ['prompt' => 'Select priority...']
                ) ?>
            </div>
        </div>

        <div style="display: flex; gap: 15px;">
            <div style="flex: 1;">
                <?= $form->field($task, 'status')->dropDownList(
                    \common\models\Task::getStatusList(),
                    ['prompt' => 'Select status...']
                ) ?>
            </div>
            <div style="flex: 1;">
                <?= $form->field($task, 'due_date_formatted')->widget(DateTimePicker::class, [
                    'options' => [
                        'placeholder' => 'Select due date and time',
                        'readonly'    => true,
                        'class'       => 'form-control',
                    ],
                    'pluginOptions' => [
                        'format'              => 'yyyy-mm-dd hh:ii',
                        'autoclose'           => true,
                        'todayHighlight'      => true,
                        'keyboardNavigation'  => false,
                    ]
                ]) ?>
            </div>
        </div>

        <?php DynamicFormWidget::begin([
            'widgetContainer' => 'documents_dynamic_form_wrapper',
            'widgetBody'      => '.documents_container-items',
            'widgetItem'      => '.documents_item',
            'min'             => 0,
            'insertButton'    => '.add-document',
            'deleteButton'    => '.remove-document',
            'model'           => $documents[0],
            'formId'          => 'add-task-form',
            'formFields'      => ['file'],
        ]); ?>

        <div class="panel panel-default">
            <div class="panel-heading" style="background-color: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; border-bottom: none;">
                <h5 class="panel-title mb-0 d-inline-block"><?= Yii::t('app', 'Documents') ?></h5>
                <button type="button" class="btn btn-success btn-sm add-document float-right">
                    <i class="fa fa-plus"></i> <?= Yii::t('app', 'Add Document') ?>
                </button>
                <div class="clearfix"></div>
            </div>
            <div class="panel-body documents_container-items" style="border: 1px solid #dee2e6; padding: 15px; margin-bottom: 20px;">
                <?php foreach ($documents as $index => $document): ?>
                    <div class="documents_item card mb-3 border">
                        <div class="card-header bg-white">
                            <span class="panel-title-document font-weight-bold"><?= Yii::t('app', 'Document') ?>: <?= ($index + 1) ?></span>
                            <button type="button" class="btn btn-danger btn-sm remove-document float-right">
                                <i class="fa fa-minus"></i>
                            </button>
                            <div class="clearfix"></div>
                        </div>
                        <div class="card-body">
                            <?= $form->field($document, "[{$index}]file")->widget(FileInput::class, [
                                'pluginOptions' => [
                                    'showUpload'         => false,
                                    'overwriteInitial'   => true,
                                    'allowedFileExtensions' => ['jpg', 'gif', 'png', 'jpeg', 'pdf', 'doc', 'docx', 'odt'],
                                    'fileActionSettings' => ['showZoom' => true],
                                ],
                            ]) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php DynamicFormWidget::end(); ?>

        <div class="form-group text-right">
            <?= Html::button('Create Task', [
                'class' => 'btn btn-success add-task-submit-btn',
                'type'  => 'submit',
            ]) ?>
            <?= Html::button('Cancel', [
                'class'        => 'btn btn-secondary ml-2',
                'data-dismiss' => 'modal',
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

<?php
$js = <<<'EOD'
$(".documents_dynamic_form_wrapper").on("afterInsert", function(e, item) {
    $(".documents_dynamic_form_wrapper .panel-title-document").each(function(index) {
        $(this).html("Document: " + (index + 1));
    });
    $(item).find('.fileinput-remove').click();
});

$(".documents_dynamic_form_wrapper").on("afterDelete", function(e, item) {
    $(".documents_dynamic_form_wrapper .panel-title-document").each(function(index) {
        $(this).html("Document: " + (index + 1));
    });
});
EOD;

$this->registerJs($js, \yii\web\View::POS_END);
?>