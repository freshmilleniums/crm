<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\bootstrap4\ActiveForm;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use common\models\Task;
use common\models\TasksDocuments;
use kartik\file\FileInput;
use backend\widgets\dynamicForm\DynamicFormWidget;

/* @var $this yii\web\View */
/* @var $task common\models\Task */
/* @var $employee backend\models\User */
/* @var $documents TasksDocuments[] */
/* @var $sourceTask common\models\Task|null */
/* @var $sourceDocumentIds int[] */

$sourceTask        = $sourceTask ?? null;
$sourceDocumentIds = $sourceDocumentIds ?? [];

if (count($documents) == 0) {
    $documents = [new TasksDocuments()];
}

// Result row in task search dropdown: title + assignee + created date
$taskResultTemplate = new JsExpression('function(item) {
    if (item.loading) {
        return item.text;
    }
    var $row = $("<div></div>");
    $("<div class=\"font-weight-bold\"></div>").text(item.text).appendTo($row);
    var meta = (item.assignee ? item.assignee : "Unassigned") + " · " + item.created;
    if (item.is_training) {
        meta += " · Training task";
    }
    $("<small class=\"text-muted task-search-meta\"></small>").text(meta).appendTo($row);
    return $row;
}');
?>

    <div class="add-task-form"
         data-task-data-url="<?= Url::to(['tasks/get-task-data']) ?>"
         data-employee-id="<?= $employee->id ?>">

        <div class="card card-secondary card-tabs mb-0">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active"
                           data-toggle="pill"
                           href="#add-task-tab-create"
                           role="tab"
                           aria-controls="add-task-tab-create"
                           aria-selected="true">Create Task</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           data-toggle="pill"
                           href="#add-task-tab-assign"
                           role="tab"
                           aria-controls="add-task-tab-assign"
                           aria-selected="false">Assign Existing</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">

                    <!-- Create Task tab -->
                    <div class="tab-pane fade show active" id="add-task-tab-create" role="tabpanel">

                        <?php $form = ActiveForm::begin([
                            'id'     => 'add-task-form',
                            'action' => ['users/add-task', 'id' => $employee->id],
                            'options'=> ['enctype' => 'multipart/form-data', 'data-pjax' => false],
                        ]); ?>

                        <?= $this->render('@backend/views/tasks/_template_clone', [
                            'sourceTask'        => $sourceTask,
                            'sourceDocumentIds' => $sourceDocumentIds,
                        ]) ?>

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
                                    Task::getPriorityList(),
                                    ['prompt' => 'Select priority...']
                                ) ?>
                            </div>
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <div style="flex: 1;">
                                <?= $form->field($task, 'status')->dropDownList(
                                    Task::getStatusList(),
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
                        <?= $this->render('@backend/views/tasks/_template_clone_documents', [
                            'sourceTask'        => $sourceTask,
                            'sourceDocumentIds' => $sourceDocumentIds,
                        ]) ?>

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

                    <!-- Assign Existing tab -->
                    <div class="tab-pane fade" id="add-task-tab-assign" role="tabpanel">

                        <?= Html::beginForm(['users/assign-existing-task', 'id' => $employee->id], 'post', [
                            'id'                 => 'assign-existing-task-form',
                            'data-employee-name' => $employee->getFullName(),
                        ]) ?>

                        <div class="form-group">
                            <label class="control-label">Task</label>
                            <?= Select2::widget([
                                'name'          => 'task_id',
                                'options'       => [
                                    'id'          => 'assign-task-select',
                                    'placeholder' => 'Search task...',
                                ],
                                'pluginOptions' => [
                                    'allowClear'         => true,
                                    'minimumInputLength' => 0,
                                    'dropdownParent'     => new JsExpression('$("#assign-task-select").closest(".modal")'),
                                    'ajax'               => [
                                        'url'      => Url::to(['tasks/search-tasks']),
                                        'dataType' => 'json',
                                        'delay'    => 250,
                                        'data'     => new JsExpression('function(params) { return {q: params.term, is_template: 0}; }'),
                                    ],
                                    'templateResult' => $taskResultTemplate,
                                ],
                            ]) ?>
                        </div>

                        <div id="assign-task-preview" style="display: none;">

                            <div class="alert alert-warning assign-task-reassign-warning" style="display: none;">
                                This task is currently assigned to <strong class="assign-task-current-assignee"></strong>.
                                After saving it will be assigned to <strong><?= Html::encode($employee->getFullName()) ?></strong>
                                and the current employee will be unlinked from it.
                            </div>

                            <div class="alert alert-danger assign-task-same-warning" style="display: none;">
                                This task is already assigned to this employee.
                            </div>

                            <div class="alert alert-info assign-task-training-notice" style="display: none;">
                                This is a training task. It should be assigned only to an employee who is going through training.
                            </div>

                            <!-- Definition list instead of table: global mobile .table styles would break it -->
                            <div class="border rounded p-2 mb-3">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Title</dt>
                                    <dd class="col-sm-8 assign-task-title"></dd>

                                    <dt class="col-sm-4">Subject</dt>
                                    <dd class="col-sm-8 assign-task-subject"></dd>

                                    <dt class="col-sm-4">Description</dt>
                                    <dd class="col-sm-8 assign-task-description" style="white-space: pre-wrap;"></dd>

                                    <dt class="col-sm-4">Priority</dt>
                                    <dd class="col-sm-8 assign-task-priority"></dd>

                                    <dt class="col-sm-4">Current Assignee</dt>
                                    <dd class="col-sm-8 assign-task-assignee"></dd>

                                    <dt class="col-sm-4">Due Date</dt>
                                    <dd class="col-sm-8 assign-task-due-date"></dd>

                                    <dt class="col-sm-4">Documents</dt>
                                    <dd class="col-sm-8 mb-0 assign-task-documents"></dd>
                                </dl>
                            </div>

                            <div class="form-group">
                                <label class="control-label" for="assign-task-status">Status</label>
                                <?= Html::dropDownList('status', null, Task::getStatusList(), [
                                    'id'    => 'assign-task-status',
                                    'class' => 'form-control',
                                ]) ?>
                            </div>

                        </div>

                        <div class="form-group text-right">
                            <?= Html::button('Assign Task', [
                                'class'    => 'btn btn-success assign-task-submit-btn',
                                'type'     => 'submit',
                                'disabled' => true,
                            ]) ?>
                            <?= Html::button('Cancel', [
                                'class'        => 'btn btn-secondary ml-2',
                                'data-dismiss' => 'modal',
                            ]) ?>
                        </div>

                        <?= Html::endForm() ?>

                    </div>

                </div>
            </div>
        </div>

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

var $addTaskWrapper   = $('.add-task-form');
var taskDataUrl       = $addTaskWrapper.data('task-data-url');
var addTaskEmployeeId = parseInt($addTaskWrapper.data('employee-id'), 10);

// Icons for non-image documents preview (keep in sync with $documentIcons in PHP above)
var documentIcons = {
    pdf:      'fas fa-file-pdf text-danger',
    document: 'fas fa-file text-secondary',
    other:    'fas fa-file text-secondary'
};

function loadTaskData(taskId, callback) {
    $.getJSON(taskDataUrl, { id: taskId, is_template: 0 }, function(response) {
        if (!response.success) {
            toastr.error(response.message);
            return;
        }
        callback(response);
    }).fail(function() {
        toastr.error('Failed to load task data');
    });
}

function buildDocumentPreview(taskDocument) {
    var $link = $('<a target="_blank" class="task-document-preview"></a>')
        .attr('href', taskDocument.url)
        .attr('title', taskDocument.name);

    if (taskDocument.type === 'image') {
        $('<img alt="">').attr('src', taskDocument.url).appendTo($link);
    } else {
        $('<i></i>').addClass(documentIcons[taskDocument.type] || documentIcons.other).appendTo($link);
    }

    return $link;
}


// ===== Assign Existing tab =====
var $assignForm = $('#assign-existing-task-form');

function resetAssignPreview() {
    $('#assign-task-preview').hide();
    $assignForm.data('current-assignee', '');
    $assignForm.find('.assign-task-submit-btn').prop('disabled', true);
}

$('#assign-task-select').on('select2:select', function(e) {
    var isTraining = !!e.params.data.is_training;

    loadTaskData(e.params.data.id, function(response) {
        var task     = response.task;
        var $preview = $('#assign-task-preview');

        $preview.find('.assign-task-title').text(task.title);
        $preview.find('.assign-task-subject').text(task.subject || '—');
        $preview.find('.assign-task-description').text(task.description || '—');
        $preview.find('.assign-task-priority').text(task.priority ? task.priority_name : '—');
        $preview.find('.assign-task-assignee').text(task.assignee || 'Unassigned');
        $preview.find('.assign-task-due-date').text(task.due_date || '—');

        var $documentsCell = $preview.find('.assign-task-documents').empty();
        if (response.documents.length) {
            var $tiles = $('<div class="task-document-tiles"></div>').appendTo($documentsCell);

            response.documents.forEach(function(taskDocument) {
                var $tile = $('<div class="task-document-tile"></div>');
                $tile.append(buildDocumentPreview(taskDocument));
                $('<div class="task-document-name"></div>')
                    .attr('title', taskDocument.name)
                    .text(taskDocument.name)
                    .appendTo($tile);
                $tiles.append($tile);
            });
        } else {
            $documentsCell.text('—');
        }

        if (task.status) {
            $('#assign-task-status').val(task.status);
        }

        var isSameEmployee   = parseInt(task.assigned_to, 10) === addTaskEmployeeId;
        var hasOtherAssignee = !isSameEmployee && !!task.assignee;

        $preview.find('.assign-task-current-assignee').text(task.assignee || '');
        $preview.find('.assign-task-reassign-warning').toggle(hasOtherAssignee);
        $preview.find('.assign-task-same-warning').toggle(isSameEmployee);
        $preview.find('.assign-task-training-notice').toggle(isTraining);

        $assignForm.data('current-assignee', hasOtherAssignee ? task.assignee : '');
        $assignForm.find('.assign-task-submit-btn').prop('disabled', isSameEmployee);

        $preview.show();
    });
});

$('#assign-task-select').on('select2:clear', function() {
    resetAssignPreview();
});
EOD;

$this->registerJs($js, \yii\web\View::POS_END);
?>