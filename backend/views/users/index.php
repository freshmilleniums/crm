<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $tabsData array */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
$assignInvestorsUrl = Url::to(['assign-investors']);
$addProjectUrl = Url::to(['add-project']);
$addTaskUrl = Url::to(['add-task']);
$sendTemplateUrl = Url::to(['send-template']);
$removeEmployerUrl = Url::to(['remove-employer']);
$quickSearchUrl    = Url::to(['quick-search']);
$assignProjectUrl     = Url::to(['projects/assign-to-employees']);
$saveCommentUrl       = Url::to(['save-comment']);
$templateHistoryUrl   = Url::to(['template-history']);

$css = "
.table-danger-soft {
    background-color: #f8d7da !important;
    color: #721c24;
}
.table-danger-soft:hover {
    background-color: #f5c6cb !important;
}
.qs-wrap { position: relative; display: inline-block; vertical-align: middle; width: 320px; }
.qs-dropdown {
    position: absolute; top: 100%; left: 0; right: 0; z-index: 9999;
    background: #fff; border: 1px solid #ced4da;
    border-top: none; border-radius: 0 0 4px 4px;
    max-height: 200px; overflow-y: auto; display: none;
    box-shadow: 0 4px 6px rgba(0,0,0,.1);
}
.qs-item { padding: 7px 12px; cursor: pointer; font-size: 13px; border-bottom: 1px solid #f0f0f0; }
.qs-item:last-child { border-bottom: none; }
.qs-item:hover { background: #f5f7ff; }
.qs-item small { color: #888; display: block; }
.qs-no-results { padding: 8px 12px; color: #999; font-size: 13px; }

/* Compact employee table */
.employee-table th,
.employee-table td {
    padding: 4px 6px !important;
    font-size: 12px !important;
}
/* Keep action icons at normal size */
.employee-table td svg {
    height: 14px !important;
    width: auto !important;
    font-size: 14px !important;
}
.employee-table td i.fas,
.employee-table td i.far {
    font-size: 14px !important;
}
.employee-table td a {
    margin-right: 3px;
}
.employee-table tr.action-details-row > td {
    padding: 0 !important;
}
.employee-table tr.action-details-row .content {
    font-size: 14px !important;
}
.employee-table tr.action-details-row .content td,
.employee-table tr.action-details-row .content th {
    padding: 8px 12px !important;
    font-size: 14px !important;
}
/* Comment cell */
.comment-cell { min-width: 130px; }
.comment-cell .comment-text {
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: inline-block;
    vertical-align: middle;
    font-size: 12px;
}
.comment-cell .comment-edit textarea { font-size: 12px; min-width: 140px; }
.comment-cell .btn-link { font-size: 11px; padding: 0; }
/* Last template cell */
.last-template-cell {
    cursor: pointer;
    text-decoration: underline dotted;
    font-size: 12px;
}
";

$this->registerCss($css);

// Page-specific JS for user actions
$script = "

var selectedEmployeeIds = [];
var anchorIndex = null;
var anchorState = null;
var lastShiftRange = null;
var expandDirection = null;

function getActionUrl(action) {
    var url = '';
    if (action === 'view') {
        url = '" . Url::to(['view']) . "';       
    } else if (action === 'update') {
        url = '" . Url::to(['update']) . "';       
    } else if (action === 'change-password') {
        url = '" . Url::to(['change-password']) . "';       
    }
    return url;
}

function showActionDetails(event, action, userId) {
    event.preventDefault();
    event.stopPropagation();
    
    var \$clickedRow = $(event.target).closest('tr');
    var existingActionRow = \$clickedRow.next('.action-details-row');    
   
    if (existingActionRow.length) {
        var currentAction = existingActionRow.data('current-action');        
      
        if (currentAction === action) {
            existingActionRow.find('.action-details').slideUp(500, function() {
                existingActionRow.remove();
            });
            return;
        }        
       
        var url = getActionUrl(action);
        var contentDiv = existingActionRow.find('.content');
        
        existingActionRow.data('current-action', action);        
        
        contentDiv.fadeOut(200, function() {
            $(this).html('Loading...');
            $(this).fadeIn(200);            
         
            if (action === 'change-password') {
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: { id: userId },             
                    success: function(response) {
                        response = JSON.parse(response);
                        if (typeof response.tpl != 'undefined') {
                            contentDiv.fadeOut(200, function() {
                                $(this).html(response.tpl);
                                $(this).fadeIn(300);
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        contentDiv.fadeOut(200, function() {
                            $(this).html('Failed to load data');
                            $(this).fadeIn(200);
                        });
                    }
                });
                return;
            }
            
            $.ajax({
                url: url,
                type: 'GET',
                data: { id: userId },             
                success: function(response) {
                    response = JSON.parse(response);
                    if (typeof response.tpl != 'undefined') {
                        contentDiv.fadeOut(200, function() {
                            $(this).html(response.tpl);
                            $(this).fadeIn(300);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    contentDiv.fadeOut(200, function() {
                        $(this).html('Failed to load data');
                        $(this).fadeIn(200);
                    });
                }
            });
        });
        
        return;
    }    
   
    $('.action-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.action-details').slideUp(700, function() {
            \$this.remove();
        });
    });    
   
    var actionRow = $('<tr class=\"action-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"action-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    actionRow.data('current-action', action);
    
    \$clickedRow.after(actionRow);
    
    var url = getActionUrl(action);
    
    $.ajax({
        url: url,
        type: 'GET',
        data: { id: userId },    
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                actionRow.find('.content').html(response.tpl);
                actionRow.find('.action-details').hide().slideDown(700);
            }
        },
        error: function(xhr, status, error) {          
            actionRow.find('.content').html('Failed to load data');
            actionRow.find('.action-details').hide().slideDown(700);
        }
    });
}

$(document).on('click', '.update-employee-send', function (e){
    e.preventDefault();

    let button = $(this);
    let form   = button.closest('form');
    let action = form.prop('action');
    let formData = new FormData(form[0]);

    if (form.find('.has-error').length) {
        return false;
    }

    button.prop('disabled', true).text('Saving...');

    $.ajax({
        type        : 'POST',
        url         : action,
        data        : formData,
        processData : false,
        contentType : false,
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('.action-details-row').find('.action-details').slideUp(700, function() {
                        $('.action-details-row').remove();
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

$(document).on('submit', '#change-password-form', function (e){
    e.preventDefault();
    
    let form = $(this);
    let button = form.find('.change-password-submit');
    let action = form.prop('action');
    let data = form.serialize();    
   
    button.prop('disabled', true).text('Changing...');
    
    $.ajax({
        type: 'POST',
        url: action,
        data: data,
        success: function (response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if(response.success == true) {                
                    toastr.success(response.message);                    
                   
                    $('.action-details-row').find('.action-details').slideUp(700, function() {
                        $('.action-details-row').remove();
                    });
                } else {                   
                    if (typeof response.tpl != 'undefined') {
                        button.closest('.content').html(response.tpl);
                    } else if (response.message) {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while changing password');
        },
        complete: function() {          
            if (button.length) {
                button.prop('disabled', false).text('Change Password');
            }
        }
    });
});

$(document).on('click', '.cancel-action', function (e){
    e.preventDefault();
    $(this).closest('.action-details-row').find('.action-details').slideUp(700, function() {
        $(this).closest('.action-details-row').remove();
    });
});

// Employee checkbox selection with Shift support (Gmail-style)
$(document).on('click', '.employee-checkbox', function(e) {
    var checkboxes = $('.employee-checkbox');
    var currentIndex = checkboxes.index(this);

    if (e.shiftKey && anchorIndex !== null) {
        setTimeout(function() {
            var newFrom, newTo;

            var insideRange = lastShiftRange &&
                currentIndex >= lastShiftRange.from &&
                currentIndex <= lastShiftRange.to;

            var isShrink = !!insideRange;

            if (isShrink) {
                var shrinkFrom, shrinkTo;
                var shrinkRight = expandDirection === 'right';
            
                if (shrinkRight) {
                    shrinkFrom = currentIndex;
                    shrinkTo   = lastShiftRange.to;
                    newFrom    = lastShiftRange.from;
                    newTo      = currentIndex - 1;
                } else {
                    shrinkFrom = lastShiftRange.from;
                    shrinkTo   = currentIndex;
                    newFrom    = currentIndex + 1;
                    newTo      = lastShiftRange.to;
                }            
               
                for (var i = shrinkFrom; i <= shrinkTo; i++) {
                    checkboxes.eq(i).prop('checked', false);                   
                }
            
                if (newFrom <= newTo) {
                    lastShiftRange = { from: newFrom, to: newTo };
                    expandDirection = newFrom < anchorIndex ? 'left' : 'right';
                    for (var i = newFrom; i <= newTo; i++) {
                        checkboxes.eq(i).prop('checked', true);
                    }
                } else {
                    lastShiftRange = null;
                    expandDirection = null;
                }
            
            } else {
                newFrom = Math.min(anchorIndex, currentIndex);
                newTo   = Math.max(anchorIndex, currentIndex);
            
                if (lastShiftRange) {
                    newFrom = Math.min(newFrom, lastShiftRange.from);
                    newTo   = Math.max(newTo, lastShiftRange.to);
                }
            
                expandDirection = currentIndex >= anchorIndex ? 'right' : 'left';
            
                for (var i = newFrom; i <= newTo; i++) {
                    checkboxes.eq(i).prop('checked', true);                   
                }
            
                lastShiftRange = { from: newFrom, to: newTo };
            }

            selectedEmployeeIds = [];
            checkboxes.each(function() {
                if ($(this).is(':checked')) {
                    selectedEmployeeIds.push(parseInt($(this).val()));
                }
            });
            
            updateBulkButton();
        }, 0);

    } else {
        var isChecked = $(this).is(':checked');
        var employeeId = parseInt($(this).val());

        anchorIndex = currentIndex;
        anchorState = isChecked;
        lastShiftRange = null;
        expandDirection = null;

        if (isChecked) {
            if (!selectedEmployeeIds.includes(employeeId)) selectedEmployeeIds.push(employeeId);
        } else {
            selectedEmployeeIds = selectedEmployeeIds.filter(function(id) { return id !== employeeId; });
        }

        updateBulkButton();
    }
});

function updateBulkButton() {
    var bulkAssignButton = $('#bulk-assign-investors-btn');
    var bulkAssignProjectButton = $('#bulk-assign-project-btn');
    if (selectedEmployeeIds.length > 0) {
        bulkAssignButton.prop('disabled', false);
        bulkAssignButton.text('Assign Investors (' + selectedEmployeeIds.length + ')');
        bulkAssignProjectButton.prop('disabled', false);
        bulkAssignProjectButton.text('Assign Project (' + selectedEmployeeIds.length + ')');
    } else {
        bulkAssignButton.prop('disabled', true);
        bulkAssignButton.text('Assign Investors');
        bulkAssignProjectButton.prop('disabled', true);
        bulkAssignProjectButton.text('Assign Project');
    }
}

// Open assign investors modal
$(document).on('click', '#bulk-assign-investors-btn', function(e) {
    e.preventDefault();

    if (selectedEmployeeIds.length === 0) {
        toastr.warning('Please select at least one employee');
        return;
    }

    var modal = $('#usersModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');

    modalTitle.text('Assign Investors');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div></div>');
    modal.modal('show');

    $.ajax({
        url: '$assignInvestorsUrl',
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

// Submit assign investors form
$(document).on('submit', '#assign-investors-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var submitButton = form.find('.assign-investors-submit-btn');
    var formData = form.serialize();

    if (selectedEmployeeIds.length === 0) {
        toastr.warning('Please select at least one employee');
        return;
    }

    submitButton.prop('disabled', true).text('Assigning...');

    $.ajax({
        type: 'POST',
        url: '$assignInvestorsUrl',
        data: formData + '&' + $.param({ employee_ids: selectedEmployeeIds }),
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#usersModal').modal('hide');
                    selectedEmployeeIds = [];
                    $('.employee-checkbox').prop('checked', false);
                    $('#bulk-assign-investors-btn').prop('disabled', true).text('Assign Investors');
                } else {
                    toastr.error(response.message);
                }
            }
        },
        error: function() {
            toastr.error('An error occurred');
        },
        complete: function() {
            submitButton.prop('disabled', false).text('Assign Investors');
        }
    });
});

// Open assign project modal
$(document).on('click', '#bulk-assign-project-btn', function(e) {
    e.preventDefault();

    if (selectedEmployeeIds.length === 0) {
        toastr.warning('Please select at least one employee');
        return;
    }

    var modal = $('#usersModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');

    modalTitle.text('Assign Project');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div></div>');
    modal.modal('show');

    $.ajax({
        url: '$assignProjectUrl',
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

// Submit assign project form
$(document).on('submit', '#assign-to-employees-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var submitButton = form.find('.assign-to-employees-submit-btn');
    var formData = form.serialize();

    if (selectedEmployeeIds.length === 0) {
        toastr.warning('Please select at least one employee');
        return;
    }

    submitButton.prop('disabled', true).text('Assigning...');

    $.ajax({
        type: 'POST',
        url: '$assignProjectUrl',
        data: formData + '&' + $.param({ employee_ids: selectedEmployeeIds }),
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#usersModal').modal('hide');
                    selectedEmployeeIds = [];
                    $('.employee-checkbox').prop('checked', false);
                    $('#bulk-assign-project-btn').prop('disabled', true).text('Assign Project');
                } else {
                    toastr.error(response.message);
                }
            }
        },
        error: function() {
            toastr.error('An error occurred');
        },
        complete: function() {
            submitButton.prop('disabled', false).text('Assign');
        }
    });
});

// Open add project modal
$(document).on('click', '.add-project-btn', function(e) {
    e.preventDefault();

    var employeeId = $(this).data('id');
    var modal = $('#usersModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');

    modalTitle.text('Add Project');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div></div>');
    modal.modal('show');

    $.ajax({
        url: '$addProjectUrl',
        type: 'GET',
        data: { id: employeeId },
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

// Submit add project form
$(document).on('submit', '#add-project-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var submitButton = form.find('.add-project-submit-btn');
    var formData = form.serialize();

    submitButton.prop('disabled', true).text('Creating...');

    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: formData,
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#usersModal').modal('hide');
                } else {
                    if (typeof response.tpl != 'undefined') {
                        $('#usersModal .modal-body').html(response.tpl);
                    } else {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred');
        },
        complete: function() {
            submitButton.prop('disabled', false).text('Create Project');
        }
    });
});

// Submit assign existing project form
$(document).on('submit', '#assign-existing-project-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var submitButton = form.find('.assign-project-submit-btn');

    if (!form.find('[name=\"project_id\"]').val()) {
        toastr.warning('Please select a project');
        return;
    }

    submitButton.prop('disabled', true).text('Assigning...');

    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: form.serialize(),
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#usersModal').modal('hide');
                } else {
                    toastr.error(response.message);
                }
            }
        },
        error: function() {
            toastr.error('An error occurred');
        },
        complete: function() {
            submitButton.prop('disabled', false).text('Assign Project');
        }
    });
});

// Open add task modal
$(document).on('click', '.add-task-btn', function(e) {
    e.preventDefault();

    var employeeId = $(this).data('id');
    var modal = $('#usersModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');

    modalTitle.text('Add Task');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div></div>');
    modal.modal('show');

    $.ajax({
        url: '$addTaskUrl',
        type: 'GET',
        data: { id: employeeId },
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

// Submit add task form
$(document).on('submit', '#add-task-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var submitButton = form.find('.add-task-submit-btn');
    var formData = new FormData(form[0]);

    submitButton.prop('disabled', true).text('Creating...');

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
                    $('#usersModal').modal('hide');
                } else {
                    if (typeof response.tpl != 'undefined') {
                        $('#usersModal .modal-body').html(response.tpl);
                    } else {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred');
        },
        complete: function() {
            submitButton.prop('disabled', false).text('Add Task');         
        }
    });
});

// Submit assign existing task form
$(document).on('submit', '#assign-existing-task-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var submitButton = form.find('.assign-task-submit-btn');
    var currentAssignee = form.data('current-assignee');
    var employeeName = form.data('employee-name');

    if (!form.find('[name=\"task_id\"]').val()) {
        toastr.warning('Please select a task');
        return;
    }

    if (currentAssignee && !confirm('This task is currently assigned to ' + currentAssignee + '.\\n\\nIt will be reassigned to ' + employeeName + ' and ' + currentAssignee + ' will be unlinked from it. Continue?')) {
        return;
    }

    submitButton.prop('disabled', true).text('Assigning...');

    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: form.serialize(),
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#usersModal').modal('hide');
                } else {
                    toastr.error(response.message);
                }
            }
        },
        error: function() {
            toastr.error('An error occurred');
        },
        complete: function() {
            submitButton.prop('disabled', false).text('Assign Task');
        }
    });
});

// Open send template modal
$(document).on('click', '.send-template-btn', function(e) {
    e.preventDefault();

    var employeeId = $(this).data('id');
    var modal = $('#usersModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');

    modalTitle.text('Send Template');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div></div>');
    modal.modal('show');

    $.ajax({
        url: '$sendTemplateUrl',
        type: 'GET',
        data: { id: employeeId },
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

// Submit send template form
$(document).on('submit', '#send-template-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var submitButton = form.find('.send-template-submit-btn');
    var formData = form.serialize();

    submitButton.prop('disabled', true).text('Sending...');

    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: formData,
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#usersModal').modal('hide');
                } else {
                    toastr.error(response.message);
                }
            }
        },
        error: function() {
            toastr.error('An error occurred');
        },
        complete: function() {
            submitButton.prop('disabled', false).text('Send Template');
        }
    });
});

$(document).on('click', '.remove-employer-btn', function(e) {
    e.preventDefault();
    
    var isHigh = $(this).data('high') == '1';
    var role   = $(this).data('role');
    
    if (isHigh) {
        if (!confirm('⚠️ WARNING: You are about to archive a user with role \"' + role + '\".\\n\\nAre you absolutely sure? This action cannot be easily undone.')) {
            return;
        }
    } else {
        if (!confirm('Are you sure you want to archive this user?')) {
            return;
        }
    }

    var userId = $(this).data('id');

    $.ajax({
        url: '$removeEmployerUrl',
        type: 'POST',
        data: { id: userId },
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('An error occurred');
        }
    });
});

// ===== Employee Quick Search =====
(function() {
    var input     = document.getElementById('qs-input');
    var dropdown  = document.getElementById('qs-dropdown');
    var btnSearch = document.getElementById('qs-btn-search');
    var btnReset  = document.getElementById('qs-btn-reset');

    if (!input) return;

    var timer          = null;
    var quickSearchUrl = '" . $quickSearchUrl . "';

    // Restore input value if quick search is active (after page reload)
    var urlParams = new URLSearchParams(window.location.search);
    var activeQ   = urlParams.get('UserSearch[q]') || '';
    if (activeQ) {
        input.value = activeQ;
        btnReset.style.display = 'inline-block';
    }

    // Fetch suggestions while typing
    input.addEventListener('input', function() {
        clearTimeout(timer);
        var term = this.value.trim();

        if (term.length < 2) {
            closeDropdown();
            return;
        }

        timer = setTimeout(function() {
            fetchSuggestions(term);
        }, 300);
    });

    // Search button — reload page with UserSearch[q] param
    btnSearch.addEventListener('click', function() {
        var term = input.value.trim();
        if (!term) return;
        closeDropdown();
        applyServerFilter(term);
    });

    // Enter key — same as Search button
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') btnSearch.click();
    });

    // Reset — clear filter and reload
    btnReset.addEventListener('click', function() {
        input.value = '';
        closeDropdown();

        var url = new URL(window.location.href);
        url.searchParams.delete('UserSearch[q]');
        window.location.href = url.toString();
    });

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.qs-wrap')) closeDropdown();
    });

    function fetchSuggestions(term) {
        \$.getJSON(quickSearchUrl, { q: term }, function(data) {
            if (!data.items || !data.items.length) {
                dropdown.innerHTML = '<div class=\"qs-no-results\">No results found</div>';
                openDropdown();
                return;
            }

            var html = '';
            data.items.forEach(function(item) {
                var sub = [item.email, item.phone].filter(Boolean).join(' &middot; ');
                html += '<div class=\"qs-item\" data-text=\"' + esc(item.text) + '\">' +
                        '<strong>' + esc(item.text) + '</strong>' +
                        (sub ? '<small>' + sub + '</small>' : '') +
                        '</div>';
            });
            dropdown.innerHTML = html;
            openDropdown();
        });
    }

    // Click on suggestion — use full name as search term
    \$(document).on('click', '.qs-item', function() {
        var text = \$(this).data('text');
        input.value = text;
        closeDropdown();
        applyServerFilter(text);
    });

    // Reload page with UserSearch[q] param — filterModel handles the rest
    function applyServerFilter(term) {
        var url = new URL(window.location.href);
        url.searchParams.delete('UserSearch[q]');
        if (term) {
            url.searchParams.set('UserSearch[q]', term);
        }
        window.location.href = url.toString();
    }

    function openDropdown()  { dropdown.style.display = 'block'; }
    function closeDropdown() { dropdown.style.display = 'none';  }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }
})();

";

$this->registerJs($script, \yii\web\View::POS_END);

// JS for employee tab: inline comments, template history popup, inactive toggle
$scriptExtra = "
\$(document).on('click', '.btn-edit-comment', function(e) {
    e.preventDefault();
    var cell = \$(this).closest('.comment-cell');
    cell.find('.comment-textarea').val('');
    cell.find('.comment-edit').show().find('.comment-textarea').focus();
});

\$(document).on('click', '.btn-cancel-comment', function() {
    var cell = \$(this).closest('.comment-cell');
    cell.find('.comment-textarea').val('');
    cell.find('.comment-edit').hide();
});

\$(document).on('input', '.comment-textarea', function() {
    \$(this).closest('.comment-cell').find('.comment-error').hide();
});

\$(document).on('click', '.btn-save-comment', function() {
    var btn    = \$(this);
    var cell   = btn.closest('.comment-cell');
    var userId = btn.data('user-id');
    var text   = cell.find('.comment-textarea').val().trim();

    if (!text) {
        cell.find('.comment-error').text('Comment cannot be empty').show();
        return;
    }
    cell.find('.comment-error').hide();

    btn.prop('disabled', true).text('Saving...');

    \$.ajax({
        url  : '" . $saveCommentUrl . "',
        type : 'POST',
        data : { id: userId, text: text, _csrf: yii.getCsrfToken() },
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                toastr.success('Comment saved');
                var escaped = \$('<div>').text(text).html();
                var now = new Date();
                var dateStr = now.getFullYear() + '-' +
                    String(now.getMonth()+1).padStart(2,'0') + '-' +
                    String(now.getDate()).padStart(2,'0') + ' ' +
                    String(now.getHours()).padStart(2,'0') + ':' +
                    String(now.getMinutes()).padStart(2,'0');
                var entry = '<hr style=\"margin:3px 0;border-color:#ddd;\">'
                    + '<div style=\"font-size:12px;margin-bottom:2px;\">'
                    + '<span class=\"comment-text\">' + escaped + '</span>'
                    + '<small class=\"text-muted d-block\" style=\"font-size:10px;\">' + dateStr + '</small>'
                    + '</div>';
                var list = cell.find('.comments-list');
                if (list.length) {
                    list.append(entry);
                } else {
                    cell.prepend('<div class=\"comments-list\"><div style=\"font-size:12px;margin-bottom:2px;\"><span class=\"comment-text\">'
                        + escaped + '</span><small class=\"text-muted d-block\" style=\"font-size:10px;\">' + dateStr + '</small></div></div>');
                }
                cell.find('.comment-textarea').val('');
                cell.find('.comment-edit').hide();
            } else {
                cell.find('.comment-error').text(response.message || 'Error saving comment').show();
            }
        },
        error: function() { cell.find('.comment-error').text('An error occurred').show(); },
        complete: function() { btn.prop('disabled', false).text('Save'); }
    });
});

// ===== Template send history (double-click on cell) =====
\$(document).on('dblclick', '.last-template-cell', function() {
    var userId = \$(this).data('user-id');
    var modal  = \$('#templateHistoryModal');
    modal.find('.modal-body').html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"></div></div>');
    modal.modal('show');
    \$.ajax({
        url  : '" . $templateHistoryUrl . "',
        type : 'GET',
        data : { id: userId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl !== 'undefined') {
                modal.find('.modal-body').html(response.tpl);
            }
        },
        error: function() {
            modal.find('.modal-body').html('<div class=\"alert alert-danger\">Failed to load history</div>');
        }
    });
});

// ===== Inactive employees toggle =====
$(document).on('change', '#show-inactive-toggle', function() {
    var url = new URL(window.location.href);
    url.searchParams.set('UserSearch[hide_inactive]', $(this).is(':checked') ? '1' : '0');
    window.location.href = url.toString();
});
";
$this->registerJs($scriptExtra, \yii\web\View::POS_END);
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="card card-secondary card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                <?php foreach ($tabsData as $tab): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $tab['active'] ? 'active' : '' ?>"
                                        id="<?= $tab['id'] ?>-tab"
                                        data-toggle="pill"
                                        href="#<?= $tab['id'] ?>"
                                        role="tab"
                                        aria-controls="<?= $tab['id'] ?>"
                                        aria-selected="<?= $tab['active'] ? 'true' : 'false' ?>">
                                        <?= $tab['label'] ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="custom-tabs-one-tabContent">
                                <?php foreach ($tabsData as $tab): ?>
                                    <div class="tab-pane fade <?= $tab['active'] ? 'show active' : '' ?>"
                                            id="<?= $tab['id'] ?>"
                                            role="tabpanel"
                                            aria-labelledby="<?= $tab['id'] ?>-tab">
                                            <div class="row mb-2">
                                                <div class="col-md-12">
                                                    <?php if (Yii::$app->user->can('createUser')): ?>
                                                        <?= Html::a('Create ' . $tab['label'], ['create', 'role' => $tab['role']], ['class' => 'btn btn-success']) ?>
                                                    <?php endif; ?>

                                                    <?php if ($tab['role'] === 'employee' && Yii::$app->user->can('assignInvestorToEmployee')): ?>
                                                        <?= Html::button('Assign Investors', [
                                                            'id'       => 'bulk-assign-investors-btn',
                                                            'class'    => 'btn btn-primary ml-2',
                                                            'disabled' => true,
                                                        ]) ?>
                                                    <?php endif; ?>
                                                    <?php if ($tab['role'] === 'employee' && Yii::$app->user->can('assignEmployeeToProject')): ?>
                                                        <?= Html::button('Assign Project', [
                                                            'id'       => 'bulk-assign-project-btn',
                                                            'class'    => 'btn btn-primary ml-2',
                                                            'disabled' => true,
                                                        ]) ?>
                                                    <?php endif; ?>

                                                    <?php
                                                    $currentUserRoles = array_keys(Yii::$app->authManager->getRolesByUser(Yii::$app->user->id));
                                                    $canUseQuickSearch = !empty(array_intersect($currentUserRoles, ['super-administrator', 'administrator', 'phone-operator']));
                                                    ?>
                                                    <?php if ($tab['role'] === 'employee' && $canUseQuickSearch): ?>
                                                        <div class="d-inline-flex align-items-center mt-2 mb-1 ml-2" style="gap: 4px; vertical-align: middle;">
                                                            <div class="qs-wrap">
                                                                <input type="text"
                                                                       id="qs-input"
                                                                       class="form-control"
                                                                       placeholder="Search by name, email or phone..."
                                                                       autocomplete="off">
                                                                <div class="qs-dropdown" id="qs-dropdown"></div>
                                                            </div>
                                                            <button class="btn btn-primary" id="qs-btn-search">
                                                                <i class="fas fa-search"></i> Search
                                                            </button>
                                                            <button class="btn btn-default" id="qs-btn-reset" >
                                                                <i class="fas fa-times"></i> Reset
                                                            </button>
                                                        </div>
                                                    <?php endif; ?>

                                                </div>
                                            </div>

                                        <?php if ($tab['role'] === 'employee'): ?>
                                            <div class="mb-2 ml-1 mt-1">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="show-inactive-toggle"
                                                        <?= $hideInactive ? 'checked' : '' ?>>
                                                    <label class="custom-control-label" for="show-inactive-toggle">
                                                        Hide inactive (&gt;1 month)
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?= GridView::widget([
                                            'dataProvider' => $tab['dataProvider'],
                                            'filterModel'  => $tab['filterModel'] ?? null,
                                            'tableOptions' => ['class' => 'table table-striped table-bordered' . ($tab['role'] === 'employee' ? ' employee-table' : '')],
                                            'columns' => [
                                                [
                                                    'class'           => 'yii\grid\CheckboxColumn',
                                                    'checkboxOptions' => function($model, $key, $index, $column) {
                                                        return [
                                                            'value' => $model->id,
                                                            'class' => 'employee-checkbox',
                                                        ];
                                                    },
                                                    'visible' => $tab['role'] === 'employee'
                                                        && (Yii::$app->user->can('assignInvestorToEmployee') || Yii::$app->user->can('assignEmployeeToProject')),
                                                ],
                                                ['class' => 'yii\grid\SerialColumn'],
                                                [
                                                    'attribute'      => 'email',
                                                    'format'         => 'email',
                                                    'headerOptions'  => $tab['role'] === 'employee' ? ['style' => 'width:140px;'] : [],
                                                    'contentOptions' => $tab['role'] === 'employee'
                                                        ? ['style' => 'max-width:130px; word-break:break-all; overflow-wrap:break-word;']
                                                        : [],
                                                ],
                                                [
                                                    'attribute'      => 'first_name',
                                                    'headerOptions'  => $tab['role'] === 'employee' ? ['style' => 'width:90px;'] : [],
                                                    'contentOptions' => $tab['role'] === 'employee'
                                                        ? ['style' => 'max-width:80px; word-break:break-word; overflow-wrap:break-word;']
                                                        : [],
                                                ],
                                                'last_name',
                                                [
                                                    'attribute'      => 'phone_number',
                                                    'headerOptions'  => $tab['role'] === 'employee' ? ['style' => 'width:100px;'] : [],
                                                    'contentOptions' => $tab['role'] === 'employee'
                                                        ? ['style' => 'max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;']
                                                        : [],
                                                ],
                                                [
                                                    'attribute' => 'city',
                                                    'value' => function($model) use ($countries) {
                                                        $parts = array_filter([
                                                            $model->city,
                                                            $model->state,
                                                        ]);

                                                        // Add country name instead of code
                                                        if ($model->country) {
                                                            $countryName = $countries[$model->country] ?? $model->country;
                                                            $parts[] = $countryName;
                                                        }

                                                        return implode(', ', $parts);
                                                    },
                                                    'label' => 'Location',
                                                ],
                                                [
                                                    'attribute' => 'substatus',
                                                    'value' => function($model) {
                                                        return $model->getSubstatusLabel();
                                                    },
                                                    'label' => 'Status',
                                                    'visible' => $tab['role'] === 'employee',
                                                    'headerOptions' => ['style' => 'width:85px;'],
                                                    'filter'             => \backend\models\User::getSubstatusLabels(),
                                                   // 'filterInputOptions' => ['class' => 'form-control', 'prompt' => '- All -'],
                                                    'contentOptions' => function($model) {
                                                        switch ($model->substatus) {
                                                            case \backend\models\User::SUBSTATUS_ACTIVE_EMPLOYEE: // 8 - Active Employee
                                                                return ['class' => 'table-success'];
                                                            case \backend\models\User::SUBSTATUS_CONTRACT_SENT: // 5 - Contract Sent
                                                                return ['class' => 'table-warning'];
                                                            case \backend\models\User::SUBSTATUS_CONTRACT_REFUSED: // 9 - Contract Refused
                                                                return ['class' => 'table-danger-soft'];
                                                            default:
                                                                return [];
                                                        }
                                                    },
                                                ],
                                                [
                                                    'label'   => 'Current Projects',
                                                    'format'  => 'raw',
                                                    'visible' => $tab['role'] === 'employee',
                                                    'value'   => function($model) {
                                                        if (empty($model->currentProjects)) {
                                                            return '<span class="text-muted">Not assigned</span>';
                                                        }

                                                        $projectNames = [];
                                                        foreach ($model->currentProjects as $project) {
                                                            $projectNames[] = Html::encode($project->name);
                                                        }

                                                        return implode('<br>', $projectNames);
                                                    },
                                                ],
                                                [
                                                    'label'   => 'Current Tasks',
                                                    'format'  => 'raw',
                                                    'visible' => $tab['role'] === 'employee',
                                                    'value'   => function($model) {
                                                        if (empty($model->currentTasks)) {
                                                            return '<span class="text-muted">—</span>';
                                                        }
                                                        $items = [];
                                                        foreach ($model->currentTasks as $task) {
                                                            $items[] = Html::encode($task->title);
                                                        }
                                                        return implode('<br>', $items);
                                                    },
                                                ],
                                                [
                                                    'attribute' => 'last_activity',
                                                    'label'     => 'Last Activity',
                                                    'value'     => function($model) {
                                                        if (!$model->last_activity) {
                                                            return 'Never';
                                                        }
                                                        if ($model->isOnline()) {
                                                            return '<span style="color: green; font-weight: bold;">Online</span>';
                                                        }
                                                        return date('Y-m-d H:i:s', $model->last_activity);
                                                    },
                                                    'format'  => 'raw',
                                                    'visible' => $tab['role'] === 'employee',
                                                ],
                                                [
                                                    'label'   => 'Company',
                                                    'format'  => 'raw',
                                                    'visible' => $tab['role'] === 'employee',
                                                    'value'   => function($model) {
                                                        if (!$model->company) {
                                                            return '<span class="text-muted">—</span>';
                                                        }
                                                        return Html::encode($model->company->name);
                                                    },
                                                ],
                                                [
                                                    'label'   => 'Manager',
                                                    'format'  => 'raw',
                                                    'visible' => $tab['role'] === 'employee',
                                                    'value'   => function($model) {
                                                        if (!$model->administrator) {
                                                            return '<span class="text-muted">—</span>';
                                                        }
                                                        $name = trim(
                                                            ($model->administrator->first_name ?? '') . ' ' .
                                                            ($model->administrator->last_name  ?? '')
                                                        );
                                                        return Html::encode($name ?: $model->administrator->email);
                                                    },
                                                ],
                                                [
                                                    'label'   => 'Template',
                                                    'format'  => 'raw',
                                                    'visible' => $tab['role'] === 'employee',
                                                    'value'   => function($model) use ($tab) {
                                                        $tpl = $tab['lastTemplates'][$model->id] ?? null;
                                                        if (!$tpl) {
                                                            return '<span class="text-muted">—</span>';
                                                        }
                                                        $name = $tpl->template->title ?? ('Template #' . $tpl->template_id);
                                                        return '<span class="last-template-cell" data-user-id="' . $model->id . '" title="Double-click to view send history">'
                                                            . Html::encode($name) . '</span>';
                                                    },
                                                ],
                                                [
                                                    'label'   => 'Comment',
                                                    'format'  => 'raw',
                                                    'visible' => $tab['role'] === 'employee' && !in_array('employee', $currentUserRoles),
                                                    'value'   => function($model) use ($tab) {
                                                        $comments = $tab['comments'][$model->id] ?? [];

                                                        $result = '<div class="comment-cell">';

                                                        // Show all existing comments
                                                        if (!empty($comments)) {
                                                            $result .= '<div class="comments-list">';
                                                            foreach ($comments as $index => $comment) {
                                                                if ($index > 0) {
                                                                    $result .= '<hr style="margin:3px 0;border-color:#ddd;">';
                                                                }
                                                                $escaped = Html::encode($comment->comment ?? '');
                                                                $result .= '<div style="font-size:12px;margin-bottom:2px;">'
                                                                    . '<span class="comment-text">' . $escaped . '</span>'
                                                                    . '<small class="text-muted d-block" style="font-size:10px;">'
                                                                    .   date('Y-m-d H:i', $comment->created_at)
                                                                    . '</small>'
                                                                    . '</div>';
                                                            }
                                                            $result .= '</div>';
                                                        }

                                                        // Add new comment form (always empty)
                                                        $result .= '<div class="comment-edit" style="display:none;">'
                                                            . '<textarea class="form-control comment-textarea" rows="2" placeholder="New comment..."></textarea>'
                                                            . '<div class="comment-error text-danger small mt-1" style="display:none;"></div>'
                                                            . '<div class="mt-1">'
                                                            .   '<button class="btn btn-xs btn-primary btn-save-comment" data-user-id="' . $model->id . '">Save</button> '
                                                            .   '<button class="btn btn-xs btn-secondary btn-cancel-comment">Cancel</button>'
                                                            . '</div>'
                                                            . '</div>';

                                                        // Always show the add button
                                                        $result .= '<button class="btn btn-xs btn-link btn-edit-comment p-0" title="Add comment">'
                                                            . '<i class="fas fa-plus"></i>'
                                                            . '</button>';

                                                        $result .= '</div>';
                                                        return $result;
                                                    },
                                                ],
                                                [
                                                    'attribute' => 'created_at',
                                                    'label'     => 'Registered',
                                                    'format'    => ['date', 'php:Y-m-d'],
                                                ],
                                                [
                                                    'class' => 'yii\grid\ActionColumn',
                                                    'headerOptions' => ['style' => 'min-width:90px;'],
                                                    'visibleButtons' => [
                                                        'view' => Yii::$app->user->can('viewUser'),
                                                        'update' => Yii::$app->user->can('updateUser'),
                                                        'delete' => Yii::$app->user->can('deleteUser'),
                                                    ],
                                                    'template' => $tab['role'] === 'employee'
                                                        ? '{view} {update} {change-password} {delete} <br> {add-project} {add-task} {send-template}'
                                                        : '{view} {update} {change-password} {delete}',
                                                    'buttons' => [
                                                        'view' => function ($url, $model, $key) {
                                                            return Html::a(
                                                                '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                                                '#',
                                                                [
                                                                    'title' => Yii::t('app', 'View'),
                                                                    'onclick' => 'showActionDetails(event, "view", ' . $model->id . '); return false;',
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
                                                                    'onclick' => 'showActionDetails(event, "update", ' . $model->id . '); return false;',
                                                                    'data-pjax' => '0',
                                                                ]
                                                            );
                                                        },
                                                        'change-password' => function ($url, $model, $key) {
                                                            if (!Yii::$app->user->can('super-administrator')) {
                                                                return '';
                                                            }

                                                            return Html::a(
                                                                '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M512 176.001C512 273.203 433.202 352 336 352c-11.22 0-22.19-1.062-32.827-3.069l-24.012 27.014A23.999 23.999 0 01261.223 384H224v40c0 13.255-10.745 24-24 24h-40v40c0 13.255-10.745 24-24 24H24c-13.255 0-24-10.745-24-24v-78.059c0-6.365 2.529-12.47 7.029-16.971l161.802-161.802C163.108 213.814 160 195.271 160 176.001 160 78.798 238.797.001 335.999 0 433.488-.001 512 78.511 512 176.001zM336 128c0 26.51 21.49 48 48 48s48-21.49 48-48-21.49-48-48-48-48 21.49-48 48z"></path></svg>',
                                                                '#',
                                                                [
                                                                    'title' => Yii::t('app', 'Change Password'),
                                                                    'onclick' => 'showActionDetails(event, "change-password", ' . $model->id . '); return false;',
                                                                    'data-pjax' => '0',
                                                                ]
                                                            );
                                                        },
                                                        'add-project' => function($url, $model, $key) {
                                                            return Html::a(
                                                                '<i class="fas fa-project-diagram"></i>',
                                                                '#',
                                                                [
                                                                    'title'     => 'Add Project',
                                                                    'class'     => 'add-project-btn',
                                                                    'data-id'   => $model->id,
                                                                    'data-pjax' => '0',
                                                                ]
                                                            );
                                                        },
                                                        'add-task' => function($url, $model, $key) {
                                                            return Html::a(
                                                                '<i class="fas fa-tasks"></i>',
                                                                '#',
                                                                [
                                                                    'title'     => 'Add Task',
                                                                    'class'     => 'add-task-btn',
                                                                    'data-id'   => $model->id,
                                                                    'data-pjax' => '0',
                                                                ]
                                                            );
                                                        },
                                                        'send-template' => function($url, $model, $key) {
                                                            return Html::a(
                                                                '<i class="fas fa-file-alt"></i>',
                                                                '#',
                                                                [
                                                                    'title'     => 'Send Template',
                                                                    'class'     => 'send-template-btn',
                                                                    'data-id'   => $model->id,
                                                                    'data-pjax' => '0',
                                                                ]
                                                            );
                                                        },
                                                        'delete' => function($url, $model, $key) {
                                                            $isHighRole = in_array($model->role, [/*'administrator',*/ 'super-administrator']);
                                                            return Html::a(
                                                                '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:.875em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9-19a24 24 0 00-22-13H167a24 24 0 00-22 13l-9 19H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z"></path></svg>',
                                                                '#',
                                                                [
                                                                    'title'     => 'Archive',
                                                                    'class'     => 'remove-employer-btn',
                                                                    'data-id'   => $model->id,
                                                                    'data-role'    => $model->role,
                                                                    'data-high'    => $isHighRole ? '1' : '0',
                                                                    'data-pjax' => '0',
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
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal for user actions -->
<div class="modal fade" id="usersModal" tabindex="-1" role="dialog" aria-labelledby="usersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="usersModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>
<!-- Template send history modal -->
<div class="modal fade" id="templateHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Send History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>