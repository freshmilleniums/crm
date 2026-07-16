<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\widgets\Pjax;
use backend\models\User;
use common\models\UrgentCall;

$this->title = 'HR Section';

use yii\web\JsExpression;

$this->registerCss('
.user-details {
    display: none;
    background: #f8f9fa;
    padding: 15px;
    border-left: 3px solid #007bff;
    margin: 5px 0;
}
.expand-row {
    cursor: pointer;
}
.expand-row:hover {
    background-color: #f5f5f5;
}
.action-details {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    margin: 10px 0;
    border-left: 3px solid #6c757d;
}

.urgent-tab-highlight {
    animation: pulse-gray 2s infinite;
}

@keyframes pulse-gray {
    0% { background-color: transparent; }
    50% { background-color: rgba(108, 117, 125, 0.2); }
    100% { background-color: transparent; }
}

.urgent-calls-table .table td {
    vertical-align: middle;
}

.status-need-call {
    background-color: #f8f9fa !important;
    border-left: 3px solid #6c757d;
}

.status-called {
    background-color: #d1ecf1 !important;
}

/* HR Urgent Calls Highlighting */
.hr-needs-confirmation {
    background-color: #fff3cd !important;
    border-left: 3px solid #ffc107;
}

.hr-needs-confirmation:hover {
    background-color: #ffeaa7 !important;
}

/* Tab highlighting for HR notifications */
.hr-urgent-tab-highlight:not(.active) {
    background-color: #ffc107 !important;
    color: #1f2d3d !important;
    animation: none !important;
}

.hr-urgent-tab-highlight:not(.active):hover {
    background-color: #e0a800 !important;
    color: #1f2d3d !important;
}

');

$script = "

function getActionUrl(action) {
    var url = '';
    if (action === 'view') {
        url = '" . Url::to(['employees/for-hr-view']) . "';       
    } else if (action === 'update') {
        url = '" . Url::to(['employees/for-hr-update']) . "';       
    } else if (action === 'create-urgent-call') {
        url = '" . Url::to(['urgent-call/create']) . "';       
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
            
            var requestData = { id: userId };
            if (action === 'create-urgent-call') {
                requestData = { userId: userId };
            }
            
            $.ajax({
                url: url,
                type: 'GET',
                data: requestData,
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
   
    $('.action-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.action-details').slideUp(700, function() {
            \$this.remove();
        });
    });    
    
    var url = getActionUrl(action);
    
    var actionRow = $('<tr class=\"action-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"action-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    actionRow.data('current-action', action);
    
    \$clickedRow.after(actionRow);
    
    var requestData = { id: userId };
    if (action === 'create-urgent-call') {
        requestData = { userId: userId };
    }
    
    $.ajax({
        url: url,
        type: 'GET',
        data: requestData,
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                actionRow.find('.content').html(response.tpl);
                actionRow.find('.action-details').hide().slideDown(700);
            }
        },
        error: function() {
            actionRow.find('.content').html('Failed to load data');
            actionRow.find('.action-details').hide().slideDown(700);
        }
    });
}

function showUrgentCallAction(event, action, urgentCallId) {
    event.preventDefault();
    event.stopPropagation();
    
    var \$clickedRow = $(event.target).closest('tr');
    var existingActionRow = \$clickedRow.next('.action-details-row');    
    
    if (existingActionRow.length) {
        existingActionRow.find('.action-details').slideUp(500, function() {
            existingActionRow.remove();
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
    \$clickedRow.after(actionRow);
    
    if (action === 'confirm') {
        actionRow.find('.content').html('<div class=\"alert alert-light border\"><p>Are you sure you want to confirm this urgent call?</p><button class=\"btn btn-success confirm-urgent-call-btn\" data-id=\"' + urgentCallId + '\">Yes, Confirm</button> <button class=\"btn btn-secondary cancel-action\">Cancel</button></div>');
    } else if (action === 'delete') {
        actionRow.find('.content').html('<div class=\"alert alert-light border\"><p>Are you sure you want to delete this urgent call?</p><button class=\"btn btn-danger delete-urgent-call-btn\" data-id=\"' + urgentCallId + '\">Yes, Delete</button> <button class=\"btn btn-secondary cancel-action\">Cancel</button></div>');
    }
    
    actionRow.find('.action-details').hide().slideDown(700);
}

// Handle urgent call form submission (create and mark-as-called)
$(document).on('submit', '.urgent-call-form', function (e){
    e.preventDefault();
    
    let form = $(this);
    let button = form.find('.create-urgent-call-btn, .mark-as-called-btn');
    let action = form.prop('action');
    let data = form.serialize();
    let originalText = button.text();
    
    button.prop('disabled', true).text('Processing...');
    
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
                    
                    // Remember active tab
                    var activeTabId = $('.tab-pane.active').attr('id');
                    
                    // First update active tab content
                    if (activeTabId) {
                        $.pjax.reload({
                            container: '#pjax-' + activeTabId,
                            timeout: 10000
                        }).done(function() {
                            // After content update completion, update headers
                            $.pjax.reload({
                                container: '#pjax-tab-headers',
                                timeout: 10000
                            }).done(function() {
                                // Restore active tab after headers update
                                if (activeTabId) {
                                    $('#' + activeTabId + '-tab').tab('show');
                                }
                            });
                        });
                    }
                } else {
                    toastr.error(response.message);
                    if (typeof response.tpl != 'undefined') {
                        button.closest('.content').html(response.tpl);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while processing request');
        },
        complete: function() {
            button.prop('disabled', false).text(originalText);
        }
    });
});

// Handle create urgent call button click (trigger form submission)
$(document).on('click', '.create-urgent-call-btn', function (e){
    e.preventDefault();
    
    let button = $(this);
    let form = button.closest('form');
    
    // Validate form before submission
    let hrComment = form.find('#hr_comment, [name=\"hr_comment\"]').val();
    if (!hrComment || hrComment.trim().length === 0) {
        toastr.error('HR comment is required');
        return false;
    }
    
    // Trigger form submission which will be handled by the submit event above
    form.trigger('submit');
});

// Handle urgent call confirmation
$(document).on('click', '.confirm-urgent-call-btn', function (e){
    e.preventDefault();
    
    let button = $(this);
    let urgentCallId = button.data('id');
    let originalText = button.text();
    
    button.prop('disabled', true).text('Confirming...');
    
    $.ajax({
        type: 'POST',
        url: '" . Url::to(['urgent-call/confirm']) . "',
        data: { id: urgentCallId },
        success: function (response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if(response.success == true) {
                    toastr.success(response.message);
                    
                    // Close action details
                    $('.action-details-row').find('.action-details').slideUp(700, function() {
                        $('.action-details-row').remove();
                    });
                    
                    // Remember active tab
                    var activeTabId = $('.tab-pane.active').attr('id');
                    
                    // First update active tab content
                    if (activeTabId) {
                        $.pjax.reload({
                            container: '#pjax-' + activeTabId,
                            timeout: 10000
                        }).done(function() {
                            // After content update completion, update headers
                            $.pjax.reload({
                                container: '#pjax-tab-headers',
                                timeout: 10000
                            }).done(function() {
                                // Restore active tab after headers update
                                if (activeTabId) {
                                    $('#' + activeTabId + '-tab').tab('show');
                                }
                            });
                        });
                    }
                } else {
                    toastr.error(response.message);
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while confirming call');
        },
        complete: function() {
            button.prop('disabled', false).text(originalText);
        }
    });
});

// Handle urgent call deletion
$(document).on('click', '.delete-urgent-call-btn', function (e){
    e.preventDefault();
    
    let button = $(this);
    let urgentCallId = button.data('id');
    let originalText = button.text();
    
    button.prop('disabled', true).text('Deleting...');
    
    $.ajax({
        type: 'POST',
        url: '" . Url::to(['urgent-call/delete']) . "',
        data: { id: urgentCallId },
        success: function (response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if(response.success == true) {
                    toastr.success(response.message);
                    
                    $('.action-details-row').find('.action-details').slideUp(700, function() {
                        $('.action-details-row').remove();
                    });
                    
                    // Remember active tab
                    var activeTabId = $('.tab-pane.active').attr('id');
                    
                    // First update active tab content
                    if (activeTabId) {
                        $.pjax.reload({
                            container: '#pjax-' + activeTabId,
                            timeout: 10000
                        }).done(function() {
                            // After content update completion, update headers
                            $.pjax.reload({
                                container: '#pjax-tab-headers',
                                timeout: 10000
                            }).done(function() {
                                // Restore active tab after headers update
                                if (activeTabId) {
                                    $('#' + activeTabId + '-tab').tab('show');
                                }
                            });
                        });
                    }
                } else {
                    toastr.error(response.message);
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while deleting call');
        },
        complete: function() {
            button.prop('disabled', false).text(originalText);
        }
    });
});

// Handle employee updates
$(document).on('click', '.update-employee-send', function (e){
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
                   
                    $('.action-details-row').find('.action-details').slideUp(700, function() {
                        $('.action-details-row').remove();
                    });                    
                  
                    var activeTab = $('.tab-pane.active').attr('id');
                    if (activeTab) {
                        $.pjax.reload({
                            container: '#pjax-' + activeTab,
                            timeout: 10000
                        });
                    }
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

$(document).on('click', '.edit-comment-btn', function(e) {
    e.stopPropagation();
    
    let commentItem = $(this).closest('.comment-item');
    let displayDiv = commentItem.find('.comment-display');
    let editDiv = commentItem.find('.comment-edit-form');    
   
    displayDiv.hide();
    editDiv.show();    
  
    editDiv.find('textarea').focus();
});

$(document).on('click', '.cancel-edit-btn', function(e) {
    e.stopPropagation();
    
    let commentItem = $(this).closest('.comment-item');
    let displayDiv = commentItem.find('.comment-display');
    let editDiv = commentItem.find('.comment-edit-form');    
   
    displayDiv.show();
    editDiv.hide();
});

$(document).on('click', '.delete-comment-btn', function(e) {
    e.stopPropagation();
    
    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }
    
    let commentId = $(this).data('comment-id');
    let commentItem = $(this).closest('.comment-item');
    let originalText = $(this).text();    
   
    $(this).prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin\"></i> Deleting...');    
    
    let deleteForm = $('<form>', {
        method: 'POST',
        action: $('.comment-form').attr('action')
    });
    
    deleteForm.append($('<input>', {
        type: 'hidden',
        name: 'comment_id',
        value: commentId
    }));
    
    deleteForm.append($('<input>', {
        type: 'hidden',
        name: 'action',
        value: 'delete'
    }));
    
    $.ajax({
        type: 'POST',
        url: deleteForm.attr('action'),
        data: deleteForm.serialize(),
        dataType: 'json',
        success: function(response) {
            if (response && response.success === true) {
                let successMessage = response.message || 'Comment deleted successfully';
                toastr.success(successMessage);                
            
                commentItem.fadeOut(300, function() {
                    $(this).remove();                    
                 
                    if ($('.comment-item').length === 0) {
                        $('.comments-list').replaceWith('<p class=\"text-muted\">No comments yet.</p>');
                    }
                });
            } else {
                let errorMessage = response.message || 'Error deleting comment';
                toastr.error(errorMessage);
            }
        },
        error: function(xhr, status, error) {
            console.error('Delete AJAX Error:', error);
            toastr.error('An error occurred while deleting the comment');
        },
        complete: function() {         
            if (commentItem.is(':visible')) {
                commentItem.find('.delete-comment-btn').prop('disabled', false).html(' Delete');
            }
        }
    });
});

$(document).on('click', '.comment-form [type=\"submit\"], .edit-comment-form [type=\"submit\"]', function (e){
    e.preventDefault();
    
    let submitButton = $(this);
    let form = submitButton.closest('form');
    let data = form.serialize();
    let originalText = submitButton.text();    
   
    let action = form.attr('action');    
    
    console.log('Form ID:', form.prop('id'));
    console.log('Form Action:', action);
    console.log('Form Data:', data);    
    
    data += '&' + submitButton.attr('name') + '=' + encodeURIComponent(submitButton.val());    
   
    let actionType = submitButton.val() || 'add';
    let loadingText = actionType === 'add' ? 'Adding...' : 
                     actionType === 'update' ? 'Updating...' : 
                     actionType === 'delete' ? 'Deleting...' : 'Processing...';    
   
    if (form.find('.has-error').length) {
        console.log('Validation errors found');
        return false;
    }    
   
    if (actionType === 'delete') {
        if (!confirm('Are you sure you want to delete this comment?')) {
            return false;
        }
    }    
   
    submitButton.prop('disabled', true).text(loadingText);
    
    $.ajax({
        type: 'POST',
        url: action,
        data: data,
        dataType: 'json',
        success: function (response) {
            console.log('AJAX Success Response:', response);
            
            if (response && typeof response.success !== 'undefined') {
                if (response.success === true) {                  
                    let successMessage = response.message || 
                                       (actionType === 'add' ? 'Comment added successfully' :
                                        actionType === 'update' ? 'Comment updated successfully' :
                                        actionType === 'delete' ? 'Comment deleted successfully' :
                                        'Operation completed successfully');
                    
                    toastr.success(successMessage);                    
                   
                    if (response.tpl) {                      
                        let container = submitButton.closest('.action-details-row').find('.content');
                        if (container.length === 0) {                          
                            container = submitButton.closest('.col-md-5, .card-body');
                        }
                        if (container.length > 0) {
                            container.html(response.tpl);
                        } else {
                            console.warn('Container for update not found');                          
                            location.reload();
                        }
                    }
                } else {                   
                    if (response.tpl) {
                        let container = submitButton.closest('.content');
                        if (container.length === 0) {
                            container = submitButton.closest('.card-body');
                        }
                        if (container.length > 0) {
                            container.html(response.tpl);
                        }
                    }
                    
                    let errorMessage = response.message || 'Validation errors occurred';
                    toastr.error(errorMessage);
                    
                    if (response.errors) {
                        console.log('Validation errors:', response.errors);
                    }
                }
            } else {
                console.error('Unexpected response format:', response);
                toastr.error('Unexpected response format');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                url: action
            });
            
            let errorMessage = 'An error occurred while ' + 
                             (actionType === 'add' ? 'adding' :
                              actionType === 'update' ? 'updating' :
                              actionType === 'delete' ? 'deleting' : 'processing') + 
                             ' the comment';
            toastr.error(errorMessage);
        },
        complete: function() {
            submitButton.prop('disabled', false).text(originalText);
        }
    });
});

// Handle status change
$(document).on('click', '.change-status-btn', function (e){
    e.preventDefault();
    
    let button = $(this);
    let userId = button.data('user-id');
    let newStatus = button.data('new-status');    
   
    button.prop('disabled', true);
    let originalText = button.text();
    button.text('Processing...');
    
    $.ajax({
        type: 'POST',
        url: '" . Url::to(['employees/change-status']) . "',
        data: { 
            id: userId,
            substatus: newStatus
        },
        success: function (response) {
            try {
                response = JSON.parse(response);
                if (typeof response.success != 'undefined') {
                    if(response.success == true) {                      
                        if (response.message) {
                            toastr.success(response.message);
                        } else {
                            toastr.success('Status updated successfully');
                        }                        
                     
                        if (typeof response.tpl != 'undefined') {
                            button.closest('.action-details-row').find('.content').html(response.tpl);
                        }
                        
                        // Remember active tab
                        var activeTabId = $('.tab-pane.active').attr('id');
                        
                        if (activeTabId) {
                            // First update active tab content
                            $.pjax.reload({
                                container: '#pjax-' + activeTabId,
                                timeout: 10000,                               
                            }).done(function() {
                                // After content update completion, update headers
                                $.pjax.reload({
                                    container: '#pjax-tab-headers',
                                    timeout: 10000,                                  
                                }).done(function() {
                                    // Restore active tab after headers update
                                    if (activeTabId) {
                                        $('#' + activeTabId + '-tab').tab('show');
                                    }
                                });
                            });
                        }                        
                     
                        setTimeout(function() {
                            $('.action-details-row').find('.action-details').slideUp(700, function() {
                                $('.action-details-row').remove();
                            });
                        }, 1000);
                        
                    } else {                      
                        if (response.message) {
                            toastr.error(response.message);
                        } else {
                            toastr.error('Failed to change status');
                        }
                    }
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                toastr.error('An error occurred while processing the response');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            toastr.error('An error occurred while changing status');
        },
        complete: function() {
            button.prop('disabled', false).text(originalText);
        }
    });
});

$(document).on('change', '.status-dropdown', function (e){
    let dropdown = $(this);
    let userId = dropdown.data('user-id');
    let newStatus = dropdown.val();
    
    if (!newStatus) return;    
   
    dropdown.prop('disabled', true);
    
    $.ajax({
        type: 'POST',
        url: '" . Url::to(['employees/change-status']) . "',
        data: { 
            id: userId,
            substatus: newStatus
        },
        success: function (response) {
            try {
                response = JSON.parse(response);
                if (typeof response.success != 'undefined') {
                    if(response.success == true) {                     
                        if (response.message) {
                            toastr.success(response.message);
                        } else {
                            toastr.success('Status updated successfully');
                        }                        
                      
                        if (typeof response.tpl != 'undefined') {
                            dropdown.closest('.action-details-row').find('.content').html(response.tpl);
                        }
                        
                        // Remember active tab
                        var activeTabId = $('.tab-pane.active').attr('id');
                        
                        if (activeTabId) {
                            // Store tab to restore after reload
                            $('body').data('restore-active-tab', activeTabId);
                            
                            $.pjax.reload({
                                container: '#pjax-' + activeTabId,
                                timeout: 10000,                               
                            }).done(function() {                               
                               
                            });
                        }                        
                     
                        setTimeout(function() {
                            $('.action-details-row').find('.action-details').slideUp(700, function() {
                                $('.action-details-row').remove();
                            });
                        }, 1000);
                        
                    } else {                     
                        dropdown.val('');
                        if (response.message) {
                            toastr.error(response.message);
                        } else {
                            toastr.error('Failed to change status');
                        }
                    }
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                dropdown.val('');
                toastr.error('An error occurred while processing the response');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            dropdown.val('');
            toastr.error('An error occurred while changing status');
        },
        complete: function() {
            dropdown.prop('disabled', false);
        }
    });
});

// Handle archive with reason
$(document).on('click', '.archive-with-reason-btn', function (e){
    e.preventDefault();
    
    let button = $(this);
    let userId = button.data('user-id');
    let newStatus = button.data('new-status');
    let rejectionReason = $('#rejection-reason').val().trim();
    
    if (rejectionReason.length < 20) {
        toastr.error('Rejection reason must be at least 20 characters long');
        return false;
    }
    
    button.prop('disabled', true);
    let originalText = button.text();
    button.text('Processing...');
    
    $.ajax({
        type: 'POST',
        url: '" . Url::to(['employees/archive-with-reason']) . "',
        data: { 
            id: userId,
            substatus: newStatus,
            rejection_reason: rejectionReason
        },
        success: function (response) {
            try {
                response = JSON.parse(response);
                if (typeof response.success != 'undefined') {
                    if(response.success == true) {
                        toastr.success(response.message);
                        
                        $('.action-details-row').find('.action-details').slideUp(700, function() {
                            $('.action-details-row').remove();
                        });
                        
                        // Remember active tab
                        var activeTabId = $('.tab-pane.active').attr('id');
                        
                        if (activeTabId) {
                            // First update active tab content
                            $.pjax.reload({
                                container: '#pjax-' + activeTabId,
                                timeout: 10000
                            }).done(function() {
                                // After content update completion, update headers
                                $.pjax.reload({
                                    container: '#pjax-tab-headers',
                                    timeout: 10000
                                }).done(function() {
                                    // Restore active tab after headers update
                                    if (activeTabId) {
                                        $('#' + activeTabId + '-tab').tab('show');
                                    }
                                });
                            });
                        }
                    } else {
                        toastr.error(response.message);
                    }
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                toastr.error('An error occurred while processing the response');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            toastr.error('An error occurred while archiving user');
        },
        complete: function() {
            button.prop('disabled', false).text(originalText);
        }
    });
});

$(document).on('input', '#rejection-reason', function() {
    var length = $(this).val().length;
    var minLength = 20;

    if (length < minLength) {
        $(this).removeClass('is-valid').addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid').addClass('is-valid');
    }
});

$(document).on('click', '.resend-notification-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    let button = $(this);
    let notificationId = button.data('notification-id');
    let userId = button.data('user-id');
    let originalText = button.text();
    
    // Confirm action
    if (!confirm('Are you sure you want to resend this notification?')) {
        return;
    }
    
    // Disable button and show loading state
    button.prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin\"></i> Resending...');
    
    $.ajax({
        type: 'POST',
        url: '" . Url::to(['employees/for-hr-view']) . "' + '?id=' + userId,
        data: {
            action: 'resend_notification',
            notification_id: notificationId
        },
        dataType: 'json',
        success: function(response) {
            if (response && response.success === true) {
                let successMessage = response.message || 'Notification resent successfully';
                toastr.success(successMessage);                
               
                if (response.tpl) {
                    let container = button.closest('.action-details-row').find('.content');
                    if (container.length === 0) {
                        container = button.closest('.card-body');
                    }
                    if (container.length > 0) {
                        container.html(response.tpl);
                    }
                }
            } else {
                let errorMessage = response.message || 'Failed to resend notification';
                toastr.error(errorMessage);
            }
        },
        error: function(xhr, status, error) {
            console.error('Resend notification AJAX Error:', error);
            toastr.error('An error occurred while resending the notification');
        },
        complete: function() {
            button.prop('disabled', false).text(originalText);
        }
    });
});

$(document).on('pjax:complete', '#pjax-tab-headers', function() {
    var tabToRestore = $('body').data('restore-active-tab');
    if (tabToRestore) {
        setTimeout(function() {         
            $('.nav-tabs .nav-link').removeClass('active').attr('aria-selected', 'false');
            $('.tab-pane').removeClass('active show');            
         
            $('#' + tabToRestore + '-tab').addClass('active').attr('aria-selected', 'true');
            $('#' + tabToRestore).addClass('active show');
            
            console.log('Tab restored via pjax:complete:', tabToRestore);            
           
            $('body').removeData('restore-active-tab');
        }, 50);
    }
});

$(document).on('click', '.cancel-action', function (e){
    e.preventDefault();
    $(this).closest('.action-details-row').find('.action-details').slideUp(700, function() {
        $(this).closest('.action-details-row').remove();
    });
});

$(document).on('shown.bs.tab', 'a[data-toggle=\"pill\"]', function (e) {
    var target = $(e.target).attr('href');
    var tabId = target.replace('#', '');    
    
    if ($('#pjax-' + tabId).length) {
        $.pjax.reload({
            container: '#pjax-' + tabId,
            timeout: 10000
        });
    }
});

$(document).on('pjax:start', function() {    
    $('.pjax-loading').show();
});

$(document).on('pjax:end', function() {    
    $('.pjax-loading').hide();
    $('.action-details-row').remove();
});

";

$this->registerJs($script, \yii\web\View::POS_END);
?>

<div class="site-index">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card card-secondary card-tabs">
                            <div class="card-header p-0 pt-1">
                                <?php Pjax::begin([
                                    'id' => 'pjax-tab-headers',
                                    'timeout' => 10000,
                                    'enablePushState' => false,
                                    'enableReplaceState' => false,
                                ]); ?>
                                <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                                    <?php foreach ($tabsData as $tab): ?>
                                        <li class="nav-item">
                                            <a
                                                    class="nav-link <?= $tab['active'] ? 'active' : '' ?> <?= isset($tab['labelClass']) ? $tab['labelClass'] : '' ?>"
                                                    id="<?= $tab['id'] ?>-tab"
                                                    data-toggle="pill"
                                                    href="#<?= $tab['id'] ?>"
                                                    role="tab"
                                                    aria-controls="<?= $tab['id'] ?>"
                                                    aria-selected="<?= $tab['active'] ? 'true' : 'false' ?>">
                                                <?= $tab['label'] ?>
                                                <span class="pjax-loading" style="display: none;">
                                                    <i class="fa fa-spinner fa-spin"></i>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php Pjax::end(); ?>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="custom-tabs-one-tabContent">
                                    <?php foreach ($tabsData as $tab): ?>
                                        <div
                                                class="tab-pane fade <?= $tab['active'] ? 'show active' : '' ?>"
                                                id="<?= $tab['id'] ?>"
                                                role="tabpanel"
                                                aria-labelledby="<?= $tab['id'] ?>-tab">

                                            <?php Pjax::begin([
                                                'id' => 'pjax-' . $tab['id'],
                                                'timeout' => 10000,
                                                'enablePushState' => false,
                                                'enableReplaceState' => false,
                                                'clientOptions' => [
                                                    'skipOuterContainers' => true,
                                                ],
                                            ]); ?>

                                            <?php if ($tab['group'] === 'urgent_calls'): ?>
                                                <!-- Urgent Calls Tab Content -->
                                                <?= GridView::widget([
                                                    'dataProvider' => $tab['dataProvider'],
                                                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                                                    'rowOptions' => function ($model, $key, $index, $grid) {
                                                        $class = '';
                                                        // Highlight only calls created by current HR user and with CALLED status
                                                        if ($model->status == UrgentCall::STATUS_CALLED &&
                                                            $model->hr_employee_id == Yii::$app->user->id) {
                                                            $class = 'hr-needs-confirmation';
                                                        }
                                                        return ['class' => $class];
                                                    },
                                                    'columns' => [
                                                        ['class' => 'yii\grid\SerialColumn'],
                                                        [
                                                            'attribute' => 'user.first_name',
                                                            'label' => 'First Name',
                                                            'value' => function($model) {
                                                                return $model->user ? $model->user->first_name : 'N/A';
                                                            }
                                                        ],
                                                        [
                                                            'attribute' => 'user.last_name',
                                                            'label' => 'Last Name',
                                                            'value' => function($model) {
                                                                return $model->user ? $model->user->last_name : 'N/A';
                                                            }
                                                        ],
                                                        [
                                                            'attribute' => 'user.phone_number',
                                                            'label' => 'Phone Number',
                                                            'value' => function($model) {
                                                                return $model->user ? $model->user->phone_number : 'N/A';
                                                            }
                                                        ],
                                                        [
                                                            'attribute' => 'status',
                                                            'label' => 'Call Status',
                                                            'value' => function($model) {
                                                                return $model->getStatusLabel();
                                                            },
                                                            'filter' => UrgentCall::getStatusOptions(),
                                                        ],
                                                        [
                                                            'label' => 'HR Comment',
                                                            'value' => function($model) {
                                                                return $model->hr_comment;
                                                            },
                                                            'format' => 'ntext',
                                                            'contentOptions' => ['style' => 'min-width: 200px; max-width: 300px; word-wrap: break-word;'],
                                                        ],
                                                        [
                                                            'label' => 'Call Center Comment',
                                                            'value' => function($model) {
                                                                return $model->call_center_comment ?: '-';
                                                            },
                                                            'format' => 'ntext',
                                                            'contentOptions' => ['style' => 'min-width: 200px; max-width: 300px; word-wrap: break-word;'],
                                                        ],
                                                        [
                                                            'attribute' => 'created_at',
                                                            'format' => ['date', 'php:Y-m-d H:i'],
                                                            'label' => 'Created At'
                                                        ],
                                                        [
                                                            'class' => 'yii\grid\ActionColumn',
                                                            'template' => '{confirm} {delete}',
                                                            'buttons' => [
                                                                'confirm' => function ($url, $model, $key) {
                                                                    if ($model->status !== UrgentCall::STATUS_CALLED) {
                                                                        return '';
                                                                    }
                                                                    return Html::a(
                                                                        '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z"></path></svg>',
                                                                        $url,
                                                                        [
                                                                            'title' => 'Confirm Call',
                                                                            'onclick' => 'showUrgentCallAction(event, "confirm", ' . $model->id . '); return false;',
                                                                            'data-pjax' => '0',
                                                                        ]
                                                                    );
                                                                },
                                                                'delete' => function ($url, $model, $key) {
                                                                    if ($model->status !== UrgentCall::STATUS_NEED_TO_CALL) {
                                                                        return '';
                                                                    }
                                                                    return Html::a(
                                                                        '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9.4-18.7A24 24 0 00281.1 0H166.8a23.72 23.72 0 00-21.4 13.3L136 32H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z"></path></svg>',
                                                                        $url,
                                                                        [
                                                                            'title' => 'Delete Call',
                                                                            'onclick' => 'showUrgentCallAction(event, "delete", ' . $model->id . '); return false;',
                                                                            'data-pjax' => '0',
                                                                        ]
                                                                    );
                                                                },
                                                            ],
                                                        ],
                                                    ],
                                                ]) ?>
                                            <?php else: ?>
                                                <!-- Regular Courier Tabs Content -->
                                                <?= GridView::widget([
                                                    'dataProvider' => $tab['dataProvider'],
                                                    'filterModel' => isset($tab['filterModel']) ? $tab['filterModel'] : null,
                                                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                                                    'columns' => [
                                                        ['class' => 'yii\grid\SerialColumn'],
                                                        'first_name',
                                                        'last_name',
                                                        'phone_number',
                                                        'email:email',
                                                        [
                                                            'attribute' => 'address',
                                                            'value' => function($model) {
                                                                return $model->address . ', ' . $model->city . ' ' . $model->zip_code;
                                                            },
                                                        ],
                                                        [
                                                            'attribute' => 'substatus',
                                                            'label' => 'Courier Status',
                                                            'value' => function($model) {
                                                                return $model->getSubstatusLabel();
                                                            },
                                                            'filter' => User::getFilteredSubstatusLabels($tab['group'] ?? null),
                                                        ],
                                                        [
                                                            'label' => 'Comments',
                                                            'value' => function($model) use ($tab) {
                                                                $userComments = $tab['comments'][$model->id] ?? [];

                                                                if (empty($userComments)) {
                                                                    return '-';
                                                                }

                                                                $result = '';
                                                                foreach ($userComments as $index => $comment) {
                                                                    $commentText = nl2br(Html::encode($comment->comment));
                                                                    $createdAt = date('Y-m-d H:i:s', $comment->created_at);

                                                                    if ($index > 0) {
                                                                        $result .= '<hr style="margin: 0; border: 1px solid #ddd;">';
                                                                    }

                                                                    $result .= '<div style="font-size: 13px;">';
                                                                    $result .= '<div>' . $commentText . '</div>';
                                                                    $result .= '<small class="text-muted" style="font-size: 11px; color: #666;">' . $createdAt . '</small>';
                                                                    $result .= '</div>';
                                                                }

                                                                return $result;
                                                            },
                                                            'format' => 'html',
                                                            'contentOptions' => ['style' => 'min-width: 200px; max-width: 300px; word-wrap: break-word;'],
                                                        ],
                                                        [
                                                            'attribute' => 'created_at',
                                                            'format' => ['date', 'php:Y-m-d'],
                                                        ],
                                                        [
                                                            'class' => 'yii\grid\ActionColumn',
                                                            'visibleButtons' => [
                                                                'view' => Yii::$app->user->can('viewEmployee'),
                                                                'update' => Yii::$app->user->can('updateEmployee'),
                                                                'delete' => false,
                                                            ],
                                                            'template' => '{view} {update} {create-urgent-call}',
                                                            'buttons' => [
                                                                'view' => function ($url, $model, $key) {
                                                                    return Html::a(
                                                                        '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                                                        $url,
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
                                                                        $url,
                                                                        [
                                                                            'title' => Yii::t('app', 'Update'),
                                                                            'onclick' => 'showActionDetails(event, "update", ' . $model->id . '); return false;',
                                                                            'data-pjax' => '0',
                                                                        ]
                                                                    );
                                                                },
                                                                'create-urgent-call' => function ($url, $model, $key) {
                                                                    return Html::a(
                                                                        '<i class="fas fa-phone-alt" title="Create Urgent Call"></i>',
                                                                        '#',
                                                                        [
                                                                            'title' => 'Create Urgent Call',
                                                                            'onclick' => 'showActionDetails(event, "create-urgent-call", ' . $model->id . '); return false;',
                                                                            'data-pjax' => '0',
                                                                        ]
                                                                    );
                                                                },
                                                            ],
                                                        ],
                                                    ],
                                                ]) ?>
                                            <?php endif; ?>

                                            <?php Pjax::end(); ?>
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

    <div class="body-content">
        <div class="row">
        </div>
    </div>
</div>