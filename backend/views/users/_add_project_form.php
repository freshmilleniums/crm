<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use common\models\Project;

/* @var $this yii\web\View */
/* @var $project common\models\Project */
/* @var $employee backend\models\User */

// Result row in project search dropdown: name + status + assigned employees count
$projectResultTemplate = new JsExpression('function(item) {
    if (item.loading) {
        return item.text;
    }
    var $row = $("<div></div>");
    $("<div class=\"font-weight-bold\"></div>").text(item.text).appendTo($row);
    var meta = item.status_name + " · " + item.employees_count + (item.employees_count === 1 ? " employee" : " employees");
    $("<small class=\"text-muted project-search-meta\"></small>").text(meta).appendTo($row);
    return $row;
}');
?>

    <div class="add-project-form"
         data-project-data-url="<?= Url::to(['users/get-project-data']) ?>"
         data-employee-id="<?= $employee->id ?>">

        <div class="card card-secondary card-tabs mb-0">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active"
                           data-toggle="pill"
                           href="#add-project-tab-create"
                           role="tab"
                           aria-controls="add-project-tab-create"
                           aria-selected="true">Create Project</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           data-toggle="pill"
                           href="#add-project-tab-assign"
                           role="tab"
                           aria-controls="add-project-tab-assign"
                           aria-selected="false">Assign Existing</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">

                    <!-- Create Project tab -->
                    <div class="tab-pane fade show active" id="add-project-tab-create" role="tabpanel">

                        <?php $form = ActiveForm::begin([
                            'id'     => 'add-project-form',
                            'action' => ['users/add-project', 'id' => $employee->id],
                            'options'=> ['data-pjax' => false],
                        ]); ?>

                        <?= $form->field($project, 'name')->textInput(['maxlength' => true]) ?>

                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($project, 'type')->dropDownList(
                                    Project::getTypeList(),
                                    ['prompt' => 'Select type...']
                                ) ?>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Assigned Employee</label>
                                    <input type="text"
                                           class="form-control"
                                           value="<?= Html::encode($employee->getFullName()) ?>"
                                           disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($project, 'net_worth')->textInput(['type' => 'number', 'step' => '0.01']) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($project, 'roi')->textInput(['type' => 'number', 'step' => '0.01']) ?>
                            </div>
                        </div>

                        <?= $form->field($project, 'comment')->textarea(['rows' => 3, 'maxlength' => 5000]) ?>

                        <div class="form-group text-right">
                            <?= Html::button('Create Project', [
                                'class' => 'btn btn-success add-project-submit-btn',
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
                    <div class="tab-pane fade" id="add-project-tab-assign" role="tabpanel">

                        <?= Html::beginForm(['users/assign-existing-project', 'id' => $employee->id], 'post', [
                            'id' => 'assign-existing-project-form',
                        ]) ?>

                        <div class="form-group">
                            <label class="control-label">Project</label>
                            <?= Select2::widget([
                                'name'          => 'project_id',
                                'options'       => [
                                    'id'          => 'assign-project-select',
                                    'placeholder' => 'Search by project name...',
                                ],
                                'pluginOptions' => [
                                    'allowClear'         => true,
                                    'minimumInputLength' => 0,
                                    'dropdownParent'     => new JsExpression('$("#assign-project-select").closest(".modal")'),
                                    'ajax'               => [
                                        'url'      => Url::to(['users/search-projects']),
                                        'dataType' => 'json',
                                        'delay'    => 250,
                                        'data'     => new JsExpression('function(params) { return {q: params.term}; }'),
                                    ],
                                    'templateResult' => $projectResultTemplate,
                                ],
                            ]) ?>
                        </div>

                        <div id="assign-project-preview" style="display: none;">

                            <div class="alert alert-warning assign-project-same-warning" style="display: none;">
                                This project is already assigned to this employee.
                            </div>

                            <div class="alert alert-warning assign-project-closed-warning" style="display: none;">
                                This project is <strong class="assign-project-closed-status"></strong>.
                                Make sure you really want to assign an employee to it.
                            </div>

                            <div class="alert alert-warning assign-project-shared-notice" style="display: none;">
                                This project is already assigned to other employees. They will stay assigned,
                                and <strong><?= Html::encode($employee->getFullName()) ?></strong> will be added.
                            </div>

                            <!-- Definition list instead of table: global mobile .table styles would break it -->
                            <div class="border rounded p-2 mb-3">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Name</dt>
                                    <dd class="col-sm-8 assign-project-name"></dd>

                                    <dt class="col-sm-4">Type</dt>
                                    <dd class="col-sm-8 assign-project-type"></dd>

                                    <dt class="col-sm-4">Status</dt>
                                    <dd class="col-sm-8 assign-project-status"></dd>

                                    <dt class="col-sm-4">Net Worth</dt>
                                    <dd class="col-sm-8 assign-project-net-worth"></dd>

                                    <dt class="col-sm-4">ROI (%)</dt>
                                    <dd class="col-sm-8 assign-project-roi"></dd>

                                    <dt class="col-sm-4">Comment</dt>
                                    <dd class="col-sm-8 assign-project-comment" style="white-space: pre-wrap;"></dd>

                                    <dt class="col-sm-4">Assigned Employees</dt>
                                    <dd class="col-sm-8 assign-project-employees"></dd>

                                    <dt class="col-sm-4">Documents</dt>
                                    <dd class="col-sm-8 mb-0 assign-project-documents"></dd>
                                </dl>
                            </div>

                        </div>

                        <div class="form-group text-right">
                            <?= Html::button('Assign Project', [
                                'class'    => 'btn btn-success assign-project-submit-btn',
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
var $addProjectWrapper   = $('.add-project-form');
var projectDataUrl       = $addProjectWrapper.data('project-data-url');
var addProjectEmployeeId = parseInt($addProjectWrapper.data('employee-id'), 10);
var $assignProjectForm   = $('#assign-existing-project-form');

// Icons for non-image documents preview (same set as in add task form)
var projectDocumentIcons = {
    pdf:      'fas fa-file-pdf text-danger',
    document: 'fas fa-file text-secondary',
    other:    'fas fa-file text-secondary'
};

function buildProjectDocumentPreview(projectDocument) {
    var $link = $('<a target="_blank" class="task-document-preview"></a>')
        .attr('href', projectDocument.url)
        .attr('title', projectDocument.name);

    if (projectDocument.type === 'image') {
        $('<img alt="">').attr('src', projectDocument.url).appendTo($link);
    } else {
        $('<i></i>').addClass(projectDocumentIcons[projectDocument.type] || projectDocumentIcons.other).appendTo($link);
    }

    return $link;
}

function resetAssignProjectPreview() {
    $('#assign-project-preview').hide();
    $assignProjectForm.find('.assign-project-submit-btn').prop('disabled', true);
}

$('#assign-project-select').on('select2:select', function(e) {
    $.getJSON(projectDataUrl, { id: e.params.data.id }, function(response) {
        if (!response.success) {
            toastr.error(response.message);
            resetAssignProjectPreview();
            return;
        }

        var project  = response.project;
        var $preview = $('#assign-project-preview');

        $preview.find('.assign-project-name').text(project.name);
        $preview.find('.assign-project-type').text(project.type_name || '—');
        $preview.find('.assign-project-status').text(project.status_name || '—');
        $preview.find('.assign-project-net-worth').text(project.net_worth !== null ? project.net_worth : '—');
        $preview.find('.assign-project-roi').text(project.roi !== null ? project.roi : '—');
        $preview.find('.assign-project-comment').text(project.comment || '—');

        var isAlreadyAssigned = false;
        var $employeesCell    = $preview.find('.assign-project-employees').empty();

        if (response.employees.length) {
            response.employees.forEach(function(projectEmployee) {
                if (parseInt(projectEmployee.id, 10) === addProjectEmployeeId) {
                    isAlreadyAssigned = true;
                }
                $('<div></div>').text(projectEmployee.name).appendTo($employeesCell);
            });
        } else {
            $employeesCell.text('—');
        }

        var $documentsCell = $preview.find('.assign-project-documents').empty();
        if (response.documents.length) {
            var $tiles = $('<div class="task-document-tiles"></div>').appendTo($documentsCell);

            response.documents.forEach(function(projectDocument) {
                var $tile = $('<div class="task-document-tile"></div>');
                $tile.append(buildProjectDocumentPreview(projectDocument));
                $('<div class="task-document-name"></div>')
                    .attr('title', projectDocument.name)
                    .text(projectDocument.name)
                    .appendTo($tile);
                $tiles.append($tile);
            });
        } else {
            $documentsCell.text('—');
        }

        var hasOtherEmployees = response.employees.length > (isAlreadyAssigned ? 1 : 0);

        $preview.find('.assign-project-closed-status').text(project.status_name || '');
        $preview.find('.assign-project-same-warning').toggle(isAlreadyAssigned);
        $preview.find('.assign-project-closed-warning').toggle(!isAlreadyAssigned && project.is_closed);
        $preview.find('.assign-project-shared-notice').toggle(!isAlreadyAssigned && hasOtherEmployees);

        $assignProjectForm.find('.assign-project-submit-btn').prop('disabled', isAlreadyAssigned);

        $preview.show();
    }).fail(function() {
        toastr.error('Failed to load project data');
        resetAssignProjectPreview();
    });
});

$('#assign-project-select').on('select2:clear', function() {
    resetAssignProjectPreview();
});
EOD;

$this->registerJs($js, \yii\web\View::POS_END);
?>