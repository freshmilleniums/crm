<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use common\models\Project;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\ProjectsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Projects';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss('
.project-modal .modal-dialog {
    max-width: 800px;
}

.project-modal .modal-body {
    max-height: 80vh;
    overflow-y: auto;
}

.inline-editing {
    background-color: #fff9e6 !important;
}

.editable-cell {
    display: block;
    min-height: 20px;
}
');

$script = "
// Store original row data for cancel
var originalRowData = {};

function showCreateProjectModal(event) {
    event.preventDefault();
    event.stopPropagation();
    
    var modal = $('#projectModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Create Project');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '" . Url::to(['create-ajax']) . "',
        type: 'GET',
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                modalBody.html(response.tpl);               
            }
        },
        error: function(xhr, status, error) {
            modalBody.html('<div class=\"alert alert-danger\">Failed to load project form</div>');
        }
    });
}

function showProjectDetails(event, projectId) {
    event.preventDefault();
    event.stopPropagation();
    
    var \$clickedRow = $(event.target).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.project-details-row');
    
    // Close if already open
    if (existingDetailsRow.length) {
        existingDetailsRow.find('.project-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }
    
    // Close other expanded rows
    $('.project-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.project-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    var detailsRow = $('<tr class=\"project-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"project-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    $.ajax({
        url: '" . Url::to(['view']) . "',
        type: 'GET',
        data: { id: projectId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.project-details').hide().slideDown(700);
            }
        },
        error: function(xhr, status, error) {
            detailsRow.find('.content').html('Failed to load project details');
            detailsRow.find('.project-details').hide().slideDown(700);
        }
    });
}

function showProjectEditForm(event, projectId) {
    event.preventDefault();
    event.stopPropagation();
    
    var \$clickedRow = $(event.target).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.project-details-row');
    
    // Close if already open (same behavior as View)
    if (existingDetailsRow.length) {
        existingDetailsRow.find('.project-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }
    
    // Close other expanded rows
    $('.project-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.project-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    var detailsRow = $('<tr class=\"project-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"project-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    $.ajax({
        url: '" . Url::to(['update']) . "',
        type: 'GET',
        data: { id: projectId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.project-details').hide().slideDown(700);
            }
        },
        error: function(xhr, status, error) {
            detailsRow.find('.content').html('Failed to load edit form');
            detailsRow.find('.project-details').hide().slideDown(700);
        }
    });
}

function showQuickEdit(event, projectId) {
    event.preventDefault();
    event.stopPropagation();
    
    var \$row = $(event.target).closest('tr');
    
    // If already in edit mode, do nothing
    if (\$row.hasClass('inline-editing')) {
        return;
    }
    
    // Load options via AJAX
    $.ajax({
        url: '" . Url::to(['get-edit-options']) . "',
        type: 'GET',
        data: { id: projectId },
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                convertRowToEditMode(\$row, projectId, response.options);
            }
        },
        error: function() {
            toastr.error('Failed to load edit options');
        }
    });
}

function convertRowToEditMode(\$row, projectId, options) {
    // Mark row as editing
    \$row.addClass('inline-editing');
    
    // Name
    var \$nameCell = \$row.find('[data-field=\"name\"]');
    var nameValue = \$nameCell.text().trim();
    \$nameCell.html('<input type=\"text\" class=\"form-control form-control-sm\" name=\"name\" value=\"' + nameValue + '\">');
    
    // Type
    var \$typeCell = \$row.find('[data-field=\"type\"]');
    var typeValue = \$typeCell.data('value');
    var typeOptions = '<option value=\"\">Select type...</option>';
    $.each(options.types, function(key, label) {
        var selected = (key == typeValue) ? 'selected' : '';
        typeOptions += '<option value=\"' + key + '\" ' + selected + '>' + label + '</option>';
    });
    \$typeCell.html('<select class=\"form-control form-control-sm\" name=\"type\">' + typeOptions + '</select>');
    
    // Net Worth
    var \$netWorthCell = \$row.find('[data-field=\"net_worth\"]');
    var netWorthValue = \$netWorthCell.data('value') || '';
    \$netWorthCell.html('<input type=\"number\" step=\"0.01\" class=\"form-control form-control-sm\" name=\"net_worth\" value=\"' + netWorthValue + '\">');
    
    // ROI
    var \$roiCell = \$row.find('[data-field=\"roi\"]');
    var roiValue = \$roiCell.data('value') || '';
    \$roiCell.html('<input type=\"number\" step=\"0.01\" class=\"form-control form-control-sm\" name=\"roi\" value=\"' + roiValue + '\">');
    
    // Status
    var \$statusCell = \$row.find('[data-field=\"status\"]');
    var statusValue = \$statusCell.data('value');
    var statusOptions = '<option value=\"\">Select status...</option>';
    $.each(options.statuses, function(key, label) {
        var selected = (key == statusValue) ? 'selected' : '';
        statusOptions += '<option value=\"' + key + '\" ' + selected + '>' + label + '</option>';
    });
    \$statusCell.html('<select class=\"form-control form-control-sm\" name=\"status\">' + statusOptions + '</select>');
    
    // Employee
    var \$employeeCell = \$row.find('[data-field=\"employee_id\"]');
    var employeeValue = \$employeeCell.data('value');
    var employeeOptions = '<option value=\"\">Select employee...</option>';
    $.each(options.employees, function(key, label) {
        var selected = (key == employeeValue) ? 'selected' : '';
        employeeOptions += '<option value=\"' + key + '\" ' + selected + '>' + label + '</option>';
    });
    \$employeeCell.html('<select class=\"form-control form-control-sm\" name=\"employee_id\">' + employeeOptions + '</select>');
    
    // Replace action buttons
    var \$actionsCell = \$row.find('td:last');
    \$actionsCell.html(
        '<button class=\"btn btn-sm btn-success mr-1\" onclick=\"saveInlineEdit(event, ' + projectId + ')\"><i class=\"fas fa-save\"></i></button>' +
        '<button class=\"btn btn-sm btn-secondary\" onclick=\"cancelInlineEdit(event, ' + projectId + ')\"><i class=\"fas fa-times\"></i></button>'
    );
}

function saveInlineEdit(event, projectId) {
    event.preventDefault();
    event.stopPropagation();
    
    var \$row = $(event.target).closest('tr');
    
    var data = {
        id: projectId,
        name: \$row.find('input[name=\"name\"]').val(),
        type: \$row.find('select[name=\"type\"]').val(),
        net_worth: \$row.find('input[name=\"net_worth\"]').val(),
        roi: \$row.find('input[name=\"roi\"]').val(),
        status: \$row.find('select[name=\"status\"]').val(),
        employee_id: \$row.find('select[name=\"employee_id\"]').val(),
    };
    
    $.ajax({
        url: '" . Url::to(['save-inline-edit']) . "',
        type: 'POST',
        data: data,
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to save');
            }
        },
        error: function() {
            toastr.error('An error occurred while saving');
        }
    });
}

function cancelInlineEdit(event, projectId) {
    event.preventDefault();
    event.stopPropagation();
    
    location.reload();
}

function showAssignEmployeeModal(event, projectId) {
    event.preventDefault();
    event.stopPropagation();
    
    var modal = $('#projectModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Assign Employee');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '" . Url::to(['assign-employee']) . "',
        type: 'GET',
        data: { id: projectId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                modalBody.html(response.tpl);
            }
        },
        error: function(xhr, status, error) {
            modalBody.html('<div class=\"alert alert-danger\">Failed to load assign form</div>');
        }
    });
}

// Handle project form submission (create)
$(document).on('submit', '#projects-form-ajax', function (e){
    e.preventDefault();
    
    let form = $(this);
    let button = form.find('.project-submit-btn');
    let formData = new FormData(form[0]);
    
    button.prop('disabled', true).text('Creating...');
    
    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if(response.success == true) {                
                    toastr.success(response.message);
                    $('#projectModal').modal('hide');
                    location.reload();
                } else {                   
                    if (typeof response.tpl != 'undefined') {
                        $('#projectModal .modal-body').html(response.tpl);                     
                    } else if (response.message) {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while creating project');
        },
        complete: function() {
            button.prop('disabled', false).text('Create Project');
        }
    });
});

// Handle project update form submission (full edit in expandable row)
$(document).on('click', '.update-project-send', function (e){
    e.preventDefault();
    
    let button = $(this);
    let form = button.closest('form');
    let action = form.prop('action');
    let data = form.serialize();
    
    if (form.find('.has-error').length) {
        return false;
    }
    
    button.prop('disabled', true).text('Saving...');
    
    $.ajax({
        type: 'POST',
        url: action,
        data: data,
        success: function (response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if(response.success == true) {
                    toastr.success(response.message);
                    
                    $('.project-details-row').find('.project-details').slideUp(700, function() {
                        $('.project-details-row').remove();
                    });
                    
                    location.reload();
                } else {
                    if (typeof response.tpl != 'undefined') {
                        button.closest('.content').html(response.tpl);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while saving');
        },
        complete: function() {
            button.prop('disabled', false).text('Save');
        }
    });
});

// Handle assign employee form submission
$(document).on('submit', '#assign-employee-form', function (e){
    e.preventDefault();
    
    let form = $(this);
    let button = form.find('.assign-submit-btn');
    let data = form.serialize();
    
    button.prop('disabled', true).text('Assigning...');
    
    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: data,
        success: function (response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if(response.success == true) {                
                    toastr.success(response.message);
                    $('#projectModal').modal('hide');
                    location.reload();
                } else {                   
                    toastr.error(response.message);
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while assigning employee');
        },
        complete: function() {
            button.prop('disabled', false).text('Assign');
        }
    });
});

// Cancel action - close expandable row
$(document).on('click', '.cancel-action', function (e){
    e.preventDefault();
    $(this).closest('.project-details-row').find('.project-details').slideUp(700, function() {
        $(this).closest('.project-details-row').remove();
    });
});
";

$this->registerJs($script, \yii\web\View::POS_END);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="mb-2">
                        <?= Html::a('Create Project', '#', [
                            'class' => 'btn btn-success',
                            'onclick' => 'showCreateProjectModal(event); return false;'
                        ]) ?>
                        <?= Html::button('<i class="fas fa-filter"></i> Filters', ['class' => 'btn btn-primary mobile-filter-btn']) ?>
                    </div>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'tableOptions' => ['class' => 'table table-striped table-bordered'],
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],
                            [
                                'attribute' => 'name',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return '<span class="editable-cell" data-field="name" data-id="' . $model->id . '">' . Html::encode($model->name) . '</span>';
                                },
                            ],
                            [
                                'attribute' => 'type',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $typeName = $model->type ? Html::encode($model->getTypeName()) : '<span class="text-muted">Not set</span>';
                                    return '<span class="editable-cell" data-field="type" data-id="' . $model->id . '" data-value="' . $model->type . '">' . $typeName . '</span>';
                                },
                                'filter' => Project::getTypeList(),
                            ],
                            [
                                'attribute' => 'net_worth',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $display = $model->net_worth ? $model->net_worth : '<span class="text-muted">Not set</span>';
                                    return '<span class="editable-cell" data-field="net_worth" data-id="' . $model->id . '" data-value="' . ($model->net_worth ?: '') . '">' . $display . '</span>';
                                },
                                'filter' => false,
                            ],
                            [
                                'attribute' => 'roi',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $display = $model->roi ? $model->roi : '<span class="text-muted">Not set</span>';
                                    return '<span class="editable-cell" data-field="roi" data-id="' . $model->id . '" data-value="' . ($model->roi ?: '') . '">' . $display . '</span>';
                                },
                                'filter' => false,
                            ],
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $statusName = $model->status ? Html::encode($model->getStatusName()) : '<span class="text-muted">Not set</span>';
                                    return '<span class="editable-cell" data-field="status" data-id="' . $model->id . '" data-value="' . $model->status . '">' . $statusName . '</span>';
                                },
                                'filter' => Project::getStatusList(),
                            ],
                            [
                                'attribute' => 'employee_id',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $employeeName = $model->getEmployeeName() ?? '<span class="text-muted">Not assigned</span>';
                                    return '<span class="editable-cell" data-field="employee_id" data-id="' . $model->id . '" data-value="' . $model->employee_id . '">' . $employeeName . '</span>';
                                },
                                'filter' => false,
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{view} {update} {quick-edit} {assign} {delete}',
                                'buttons' => [
                                    'view' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                            '#',
                                            [
                                                'title' => Yii::t('app', 'View'),
                                                'onclick' => 'showProjectDetails(event, ' . $model->id . '); return false;',
                                                'data-pjax' => '0',
                                            ]
                                        );
                                    },
                                    'update' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M498 142l-46 46c-5 5-13 5-17 0L324 77c-5-5-5-12 0-17l46-46c19-19 49-19 68 0l60 60c19 19 19 49 0 68zm-214-42L22 362 0 484c-3 16 12 30 28 28l122-22 262-262c5-5 5-13 0-17L301 100c-4-5-12-5-17 0zM124 340c-5-6-5-14 0-20l154-154c6-5 14-5 20 0s5 14 0 20L144 340c-6 5-14 5-20 0zm-36 84h48v36l-64 12-32-31 12-65h36v48z"></path></svg>',
                                            '#',
                                            [
                                                'title' => Yii::t('app', 'Update'),
                                                'onclick' => 'showProjectEditForm(event, ' . $model->id . '); return false;',
                                                'data-pjax' => '0',
                                            ]
                                        );
                                    },
                                    'quick-edit' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M402.3 344.9l32-32c5-5 13.7-1.5 13.7 5.7V464c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h273.5c7.1 0 10.7 8.6 5.7 13.7l-32 32c-1.5 1.5-3.5 2.3-5.7 2.3H48v352h352V350.5c0-2.1.8-4.1 2.3-5.6zm156.6-201.8L296.3 405.7l-90.4 10c-26.2 2.9-48.5-19.2-45.6-45.6l10-90.4L432.9 17.1c22.9-22.9 59.9-22.9 82.7 0l43.2 43.2c22.9 22.9 22.9 60 .1 82.8zM460.1 174L402 115.9 216.2 301.8l-7.3 65.3 65.3-7.3L460.1 174zm64.8-79.7l-43.2-43.2c-4.1-4.1-10.8-4.1-14.8 0L436 82l58.1 58.1 30.9-30.9c4-4.2 4-10.8-.1-14.9z"></path></svg>',
                                            '#',
                                            [
                                                'title' => Yii::t('app', 'Quick Edit'),
                                                'onclick' => 'showQuickEdit(event, ' . $model->id . '); return false;',
                                                'data-pjax' => '0',
                                                'class' => 'text-success'
                                            ]
                                        );
                                    },
                                    'assign' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.25em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4zM496 224c-44.2 0-80-35.8-80-80s35.8-80 80-80 80 35.8 80 80-35.8 80-80 80zm48 32h-3.8c-13.9 4.8-28.6 8-44.2 8s-30.3-3.2-44.2-8H448c-49.7 0-91.2 35.2-100.6 81.9 23.3 11.5 43.7 27.6 59.9 47.1h137.4c26.5 0 48-21.5 48-48 0-44.2-35.8-80-80-80z"></path></svg>',
                                            '#',
                                            [
                                                'title' => Yii::t('app', 'Assign Employee'),
                                                'onclick' => 'showAssignEmployeeModal(event, ' . $model->id . '); return false;',
                                                'data-pjax' => '0',
                                                'class' => 'text-primary'
                                            ]
                                        );
                                    },
                                    'delete' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:.875em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9-19a24 24 0 00-22-13H167a24 24 0 00-22 13l-9 19H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z"></path></svg>',
                                            $url,
                                            [
                                                'title' => Yii::t('app', 'Delete'),
                                                'data-confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                                'data-method' => 'post',
                                            ]
                                        );
                                    },
                                ],
                            ],
                        ],
                        'summaryOptions' => ['class' => 'summary mb-2'],
                        'pager' => [
                            'class' => 'yii\bootstrap4\LinkPager',
                        ]
                    ]); ?>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Project Modal (universal for create and assign) -->
<div class="modal fade project-modal" id="projectModal" tabindex="-1" role="dialog" aria-labelledby="projectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>