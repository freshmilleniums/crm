<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\InvestorsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $employeesList array */

$this->title = 'Investors';
$this->params['breadcrumbs'][] = $this->title;
?>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="mb-2">
                    <?php if (Yii::$app->user->can('createInvestor')): ?>
                        <?= Html::a('Create Investor', '#', [
                            'class' => 'btn btn-success',
                            'id' => 'create-investor-btn'
                        ]) ?>
                    <?php endif; ?>

                    <?php if (Yii::$app->user->can('assignInvestorToEmployee')): ?>
                        <?= Html::button('Assign to employee', [
                            'id' => 'bulk-assign-btn',
                            'class' => 'btn btn-primary ml-2',
                            'disabled' => true
                        ]) ?>
                    <?php endif; ?>
                </div>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'layout' => "{items}\n{summary}\n{pager}",
                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                    'rowOptions' => function ($model) {
                        return [
                            'class' => 'investor-row',
                            'data-id' => $model->id,
                        ];
                    },
                    'columns' => [
                        [
                            'class' => 'yii\grid\CheckboxColumn',
                            'checkboxOptions' => function ($model, $key, $index, $column) {
                                return [
                                    'value' => $model->id,
                                    'class' => 'investor-checkbox',
                                ];
                            },
                            'visible' => Yii::$app->user->can('assignInvestorToEmployee'),
                        ],
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'attribute' => 'first_name',
                            'value' => function($model) {
                                return $model->getFullName();
                            },
                            'label' => 'Full Name',
                        ],
                        'email:email',
                        [
                            'attribute' => 'investor_type',
                            'value' => function($model) {
                                return $model->investor_type ? $model->getTypeName() : 'Not set';
                            },
                            'filter' => \common\models\Investor::getTypeList(),
                        ],
                        [
                            'attribute' => 'net_value',
                            'format' => ['decimal', 2],
                        ],
                        [
                            'attribute' => 'employee_filter',
                            'value' => function($model) {
                                if (empty($model->employees)) {
                                    return '<span class="text-muted">Not assigned</span>';
                                }

                                $employeeNames = [];
                                foreach ($model->employees as $employee) {
                                    $employeeNames[] = Html::encode($employee->getFullName());
                                }

                                return implode('<br>', $employeeNames);
                            },
                            'filter' => Select2::widget([
                                'model' => $searchModel,
                                'attribute' => 'employee_filter',
                                'data' => $employeesList,
                                'options' => [
                                    'placeholder' => 'Filter by employee...',
                                ],
                                'pluginOptions' => [
                                    'allowClear' => true,
                                ],
                            ]),
                            'format' => 'raw',
                            'label' => 'Assigned Employees',
                        ],
                        [
                            'attribute' => 'created_at',
                            'format' => ['date', 'php:Y-m-d'],
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view} {update} {assign-employees} {delete}',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('viewInvestor')) {
                                        return '';
                                    }
                                    return Html::a(
                                        '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                        'javascript:void(0);',
                                        [
                                            'class' => 'view-investor-btn',
                                            'data-id' => $model->id,
                                            'title' => 'View',
                                        ]
                                    );
                                },
                                'update' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('updateInvestor')) {
                                        return '';
                                    }
                                    return Html::a(
                                        '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M498 142l-46 46c-5 5-13 5-17 0L324 77c-5-5-5-12 0-17l46-46c19-19 49-19 68 0l60 60c19 19 19 49 0 68zm-214-42L22 362 0 484c-3 16 12 30 28 28l122-22 262-262c5-5 5-13 0-17L301 100c-4-5-12-5-17 0zM124 340c-5-6-5-14 0-20l154-154c6-5 14-5 20 0s5 14 0 20L144 340c-6 5-14 5-20 0zm-36 84h48v36l-64 12-32-31 12-65h36v48z"></path></svg>',
                                        'javascript:void(0);',
                                        [
                                            'class' => 'update-investor-btn',
                                            'data-id' => $model->id,
                                            'title' => 'Update',
                                        ]
                                    );
                                },
                                'assign-employees' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('assignInvestorToEmployee')) {
                                        return '';
                                    }
                                    return Html::a(
                                        '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.28em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M96 224c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm448 0c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm32 32h-64c-17.6 0-33.5 7.1-45.1 18.6 40.3 22.1 68.9 62 75.1 109.4h66c17.7 0 32-14.3 32-32v-32c0-35.3-28.7-64-64-64zm-256 0c61.9 0 112-50.1 112-112S381.9 32 320 32 208 82.1 208 144s50.1 112 112 112zm76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C179.6 288 128 339.6 128 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2zm-223.7-13.4C161.5 263.1 145.6 256 128 256H64c-35.3 0-64 28.7-64 64v32c0 17.7 14.3 32 32 32h65.9c6.3-47.4 34.9-87.3 75.2-109.4z"></path></svg>',
                                        'javascript:void(0);',
                                        [
                                            'class' => 'assign-employees-btn',
                                            'data-id' => $model->id,
                                            'title' => 'Assign Employees',
                                        ]
                                    );
                                },
                                'delete' => function ($url, $model, $key) {
                                    if (!Yii::$app->user->can('deleteInvestor')) {
                                        return '';
                                    }
                                    return Html::a(
                                        '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:.875em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9-19a24 24 0 00-22-13H167a24 24 0 00-22 13l-9 19H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z"></path></svg>',
                                        $url,
                                        [
                                            'title' => 'Delete',
                                            'data-confirm' => 'Are you sure you want to delete this investor?',
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
                ]) ?>

            </div>
        </div>
    </div>

    <!-- Investor Modal -->
    <div class="modal fade" id="investorModal" tabindex="-1" role="dialog" aria-labelledby="investorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="investorModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                </div>
            </div>
        </div>
    </div>

<?php
$viewUrl = Url::to(['view']);
$updateUrl = Url::to(['update']);
$createUrl = Url::to(['create-ajax']);
$assignEmployeesUrl = Url::to(['assign-employees']);
$bulkAssignUrl = Url::to(['bulk-assign-employee']);

$this->registerJs("
var selectedInvestors = [];

// Checkbox selection handling
$(document).on('change', '.investor-checkbox', function() {
    var investorId = parseInt($(this).val());
    
    if ($(this).is(':checked')) {
        if (!selectedInvestors.includes(investorId)) {
            selectedInvestors.push(investorId);
        }
    } else {
        selectedInvestors = selectedInvestors.filter(function(id) {
            return id !== investorId;
        });
    }
    
    var button = $('#bulk-assign-btn');
    if (selectedInvestors.length > 0) {
        button.prop('disabled', false);
        button.text('Assign ' + selectedInvestors.length + ' investor(s) to employee');
    } else {
        button.prop('disabled', true);
        button.text('Assign to employee');
    }
});

// View investor (expandable row)
$(document).on('click', '.view-investor-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var investorId = $(this).data('id');
    var \$clickedRow = $(this).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.investor-details-row');
    
    if (existingDetailsRow.length) {
        var currentAction = existingDetailsRow.data('current-action');
        
        if (currentAction === 'view') {
            existingDetailsRow.find('.investor-details').slideUp(500, function() {
                existingDetailsRow.remove();
            });
            return;
        }
        
        var contentDiv = existingDetailsRow.find('.content');
        existingDetailsRow.data('current-action', 'view');
        
        contentDiv.fadeOut(200, function() {
            $(this).html('Loading...');
            $(this).fadeIn(200);
            
            $.ajax({
                url: '$viewUrl',
                type: 'GET',
                data: { id: investorId },
                success: function(response) {
                    response = JSON.parse(response);
                    if (typeof response.tpl != 'undefined') {
                        contentDiv.fadeOut(200, function() {
                            $(this).html(response.tpl);
                            $(this).fadeIn(300);
                        });
                    }
                },
                error: function() {
                    contentDiv.fadeOut(200, function() {
                        $(this).html('Failed to load data');
                        $(this).fadeIn(200);
                    });
                }
            });
        });
        
        return;
    }
    
    $('.investor-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.investor-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    var detailsRow = $('<tr class=\"investor-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"investor-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    detailsRow.data('current-action', 'view');
    
    \$clickedRow.after(detailsRow);
    
    $.ajax({
        url: '$viewUrl',
        type: 'GET',
        data: { id: investorId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.investor-details').hide().slideDown(700);
            }
        },
        error: function() {
            detailsRow.find('.content').html('Failed to load data');
            detailsRow.find('.investor-details').hide().slideDown(700);
        }
    });
});

// Update investor (expandable row)
$(document).on('click', '.update-investor-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var investorId = $(this).data('id');
    var \$clickedRow = $(this).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.investor-details-row');
    
    if (existingDetailsRow.length) {
        var currentAction = existingDetailsRow.data('current-action');
        
        if (currentAction === 'update') {
            existingDetailsRow.find('.investor-details').slideUp(500, function() {
                existingDetailsRow.remove();
            });
            return;
        }
        
        var contentDiv = existingDetailsRow.find('.content');
        existingDetailsRow.data('current-action', 'update');
        
        contentDiv.fadeOut(200, function() {
            $(this).html('Loading...');
            $(this).fadeIn(200);
            
            $.ajax({
                url: '$updateUrl',
                type: 'GET',
                data: { id: investorId },
                success: function(response) {
                    response = JSON.parse(response);
                    if (typeof response.tpl != 'undefined') {
                        contentDiv.fadeOut(200, function() {
                            $(this).html(response.tpl);
                            $(this).fadeIn(300);
                        });
                    }
                },
                error: function() {
                    contentDiv.fadeOut(200, function() {
                        $(this).html('Failed to load data');
                        $(this).fadeIn(200);
                    });
                }
            });
        });
        
        return;
    }
    
    $('.investor-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.investor-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    var detailsRow = $('<tr class=\"investor-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"investor-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    detailsRow.data('current-action', 'update');
    
    \$clickedRow.after(detailsRow);
    
    $.ajax({
        url: '$updateUrl',
        type: 'GET',
        data: { id: investorId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.investor-details').hide().slideDown(700);
            }
        },
        error: function() {
            detailsRow.find('.content').html('Failed to load data');
            detailsRow.find('.investor-details').hide().slideDown(700);
        }
    });
});

// Create investor (modal)
$(document).on('click', '#create-investor-btn', function(e) {
    e.preventDefault();
    
    var modal = $('#investorModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Create Investor');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '$createUrl',
        type: 'GET',
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                modalBody.html(response.tpl);
            }
        },
        error: function() {
            modalBody.html('<div class=\"alert alert-danger\">Failed to load form</div>');
        }
    });
});

// Assign employees (modal)
$(document).on('click', '.assign-employees-btn', function(e) {
    e.preventDefault();
    
    var investorId = $(this).data('id');
    var modal = $('#investorModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Assign Employees');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '$assignEmployeesUrl',
        type: 'GET',
        data: { id: investorId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                modalBody.html(response.tpl);
            }
        },
        error: function() {
            modalBody.html('<div class=\"alert alert-danger\">Failed to load form</div>');
        }
    });
});

// Bulk assign (modal)
$(document).on('click', '#bulk-assign-btn', function(e) {
    e.preventDefault();
    
    if (selectedInvestors.length === 0) {
        toastr.warning('Please select at least one investor');
        return;
    }
    
    var modal = $('#investorModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Assign Investors to Employee');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '$bulkAssignUrl',
        type: 'GET',
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                modalBody.html(response.tpl);
            }
        },
        error: function() {
            modalBody.html('<div class=\"alert alert-danger\">Failed to load form</div>');
        }
    });
});

// Submit create form
$(document).on('submit', '#investors-form-ajax', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var button = form.find('.investor-submit-btn');
    var formData = new FormData(form[0]);
    
    button.prop('disabled', true).text('Creating...');
    
    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#investorModal').modal('hide');
                    location.reload();
                } else {
                    if (typeof response.tpl != 'undefined') {
                        $('#investorModal .modal-body').html(response.tpl);
                    } else if (response.message) {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred');
        },
        complete: function() {
            button.prop('disabled', false).text('Create Investor');
        }
    });
});

// Submit update form
$(document).on('click', '.update-investor-send', function(e) {
    e.preventDefault();
    
    var button = $(this);
    var form = button.closest('form');
    var data = form.serialize();
    
    if (form.find('.has-error').length) {
        return false;
    }
    
    button.prop('disabled', true).text('Saving...');
    
    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: data,
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('.investor-details-row').find('.investor-details').slideUp(700, function() {
                        $('.investor-details-row').remove();
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
            toastr.error('An error occurred');
        },
        complete: function() {
            button.prop('disabled', false).text('Save');
        }
    });
});

// Submit assign employees form
$(document).on('submit', '#assign-employees-form', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var button = form.find('.assign-submit-btn');
    var data = form.serialize();
    
    button.prop('disabled', true).text('Assigning...');
    
    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: data,
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#investorModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            }
        },
        error: function() {
            toastr.error('An error occurred');
        },
        complete: function() {
            button.prop('disabled', false).text('Assign');
        }
    });
});

// Submit bulk assign form
$(document).on('submit', '#bulk-assign-form', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var button = form.find('.bulk-assign-submit-btn');
    var employeeId = form.find('[name=\"employee_id\"]').val();
    
    if (!employeeId) {
        toastr.warning('Please select an employee');
        return;
    }
    
    button.prop('disabled', true).text('Assigning...');
    
    $.ajax({
        type: 'POST',
        url: '$bulkAssignUrl',
        data: {
            investor_ids: selectedInvestors,
            employee_id: employeeId
        },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#investorModal').modal('hide');
                    selectedInvestors = [];
                    $('.investor-checkbox').prop('checked', false);
                    $('#bulk-assign-btn').prop('disabled', true).text('Assign to employee');
                    location.reload();
                } else {
                    if (response.message) {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred');
        },
        complete: function() {
            button.prop('disabled', false).text('Assign');
        }
    });
});

// Cancel action (close expandable row)
$(document).on('click', '.cancel-action', function(e) {
    e.preventDefault();
    $(this).closest('.investor-details-row').find('.investor-details').slideUp(700, function() {
        $(this).closest('.investor-details-row').remove();
    });
});
", \yii\web\View::POS_END);
?>