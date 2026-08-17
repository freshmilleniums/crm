<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use common\models\EmailAccount;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\EmailAccountSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Email Accounts';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss('
.email-account-modal .modal-dialog {
    max-width: 900px;
}

.email-account-modal .modal-body {
    max-height: 80vh;
    overflow-y: auto;
}
');

$viewUrl = Url::to(['view']);
$updateUrl = Url::to(['update']);
$createUrl = Url::to(['create-ajax']);
$testConnectionUrl = Url::to(['test-connection']);
$assignAdminsUrl   = Url::to(['assign-admins']);

$script = "
// Create email account (modal)
$(document).on('click', '#create-email-account-btn', function(e) {
    e.preventDefault();
    
    var modal = $('#emailAccountModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Create Email Account');
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

// View email account (expandable row)
$(document).on('click', '.view-email-account-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var accountId = $(this).data('id');
    var \$clickedRow = $(this).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.email-account-details-row');
    
    if (existingDetailsRow.length) {
        existingDetailsRow.find('.email-account-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }
    
    $('.email-account-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.email-account-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    var detailsRow = $('<tr class=\"email-account-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"email-account-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    $.ajax({
        url: '$viewUrl',
        type: 'GET',
        data: { id: accountId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.email-account-details').hide().slideDown(700);
            }
        },
        error: function() {
            detailsRow.find('.content').html('Failed to load data');
            detailsRow.find('.email-account-details').hide().slideDown(700);
        }
    });
});

// Update email account from grid (open expandable row with update form)
$(document).on('click', '.update-email-account-inline-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var accountId = $(this).data('id');
    var \$clickedRow = $(this).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.email-account-details-row');
    
    if (existingDetailsRow.length) {
        existingDetailsRow.find('.email-account-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }
    
    $('.email-account-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.email-account-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    var detailsRow = $('<tr class=\"email-account-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"email-account-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    $.ajax({
        url: '$updateUrl',
        type: 'GET',
        data: { id: accountId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.email-account-details').hide().slideDown(700);
            }
        },
        error: function() {
            detailsRow.find('.content').html('Failed to load form');
            detailsRow.find('.email-account-details').hide().slideDown(700);
        }
    });
});

// Update email account from expandable row (Edit button inside view)
$(document).on('click', '.update-email-account-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var accountId = $(this).data('id');
    var \$clickedRow = $(this).closest('tr.email-account-details-row').prev('tr');
    var existingDetailsRow = \$clickedRow.next('.email-account-details-row');
    
    if (existingDetailsRow.length) {
        var contentDiv = existingDetailsRow.find('.content');
        
        contentDiv.fadeOut(200, function() {
            $(this).html('Loading...');
            $(this).fadeIn(200);
            
            $.ajax({
                url: '$updateUrl',
                type: 'GET',
                data: { id: accountId },
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
    }
});

// Test connection
$(document).on('click', '.test-connection-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var accountId = $(this).data('id');
    var button = $(this);
    var originalHtml = button.html();
    
    button.html('<i class=\"fas fa-spinner fa-spin\"></i> Testing...').prop('disabled', true);
    
    $.ajax({
        url: '$testConnectionUrl',
        type: 'POST',
        data: { id: accountId },
        success: function(response) {
            response = JSON.parse(response);
            
            var message = '';
            if (response.imap) {
                message += 'IMAP: ' + response.imap.message + '<br>';
            }
            if (response.smtp) {
                message += 'SMTP: ' + response.smtp.message;
            }
            
            if (response.success) {
                toastr.success(message);
            } else {
                toastr.error(message);
            }
        },
        error: function() {
            toastr.error('Failed to test connection');
        },
        complete: function() {
            button.html(originalHtml).prop('disabled', false);
        }
    });
});

// Submit create form
$(document).on('submit', '#email-account-form-ajax', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var button = form.find('.submit-btn');
    var data = form.serialize();
    
    button.prop('disabled', true).text('Creating...');
    
    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: data,
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    toastr.success(response.message);
                    $('#emailAccountModal').modal('hide');
                    location.reload();
                } else {
                    if (typeof response.tpl != 'undefined') {
                        $('#emailAccountModal .modal-body').html(response.tpl);
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
            button.prop('disabled', false).text('Create Email Account');
        }
    });
});

// Submit update form
$(document).on('click', '.update-email-account-btn-save', function(e) {
    e.preventDefault();
    
    var button = $(this);
    var form = button.closest('form');
    var data = form.serialize();
    
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
                    $('.email-account-details-row').find('.email-account-details').slideUp(700, function() {
                        $('.email-account-details-row').remove();
                    });
                    location.reload();
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
            toastr.error('An error occurred');
        },
        complete: function() {
            button.prop('disabled', false).text('Save');
        }
    });
});

// Cancel action (close expandable row)
$(document).on('click', '.cancel-action', function(e) {
    e.preventDefault();
    $(this).closest('.email-account-details-row').find('.email-account-details').slideUp(700, function() {
        $(this).closest('.email-account-details-row').remove();
    });
});

// Open assign admins modal
$(document).on('click', '.assign-admins-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();

    var accountId  = $(this).data('id');
    var modal      = $('#emailAccountModal');
    var modalBody  = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');

    modalTitle.text('Assign Administrators');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');

    $.ajax({
        url: '$assignAdminsUrl',
        type: 'GET',
        data: { id: accountId },
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

// Submit assign admins form
$(document).on('submit', '#assign-admins-form', function(e) {
    e.preventDefault();

    var form         = $(this);
    var submitButton = form.find('.assign-admins-submit-btn');
    var data         = form.serialize();

    submitButton.prop('disabled', true).text('Saving...');

    $.ajax({
        type: 'POST',
        url:  form.prop('action'),
        data: data,
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                toastr.success(response.message);
                $('#emailAccountModal').modal('hide');
                // Close expandable row so next open will reload fresh data
                $('.email-account-details-row').find('.email-account-details').slideUp(700, function() {
                    $('.email-account-details-row').remove();
                });
            } else {
                toastr.error(response.message);
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
";

$this->registerJs($script, \yii\web\View::POS_END);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Email Accounts Management</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?= Html::a('Create Email Account', '#', [
                                'class' => 'btn btn-success',
                                'id' => 'create-email-account-btn'
                            ]) ?>
                        </div>
                    </div>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'tableOptions' => ['class' => 'table table-striped table-bordered'],
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],
                            [
                                'attribute' => 'email',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return Html::encode($model->email);
                                },
                            ],
                            [
                                'attribute' => 'label',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return $model->label ? Html::encode($model->label) : '<span class="text-muted">No label</span>';
                                },
                            ],
                            [
                                'attribute' => 'imap_host',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return Html::encode($model->imap_host) . ':' . $model->imap_port;
                                },
                                'label' => 'IMAP',
                            ],
                            [
                                'attribute' => 'smtp_host',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return Html::encode($model->smtp_host) . ':' . $model->smtp_port;
                                },
                                'label' => 'SMTP',
                            ],
                            /*[
                                'attribute' => 'is_corporate',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if ($model->is_corporate) {
                                        return '<span class="badge badge-success">Yes</span>';
                                    }
                                    return '<span class="badge badge-secondary">No</span>';
                                },
                                'filter' => [0 => 'No', 1 => 'Yes'],
                            ],*/
                            [
                                'attribute' => 'is_active',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if ($model->is_active) {
                                        return '<span class="badge badge-success">Active</span>';
                                    }
                                    return '<span class="badge badge-danger">Inactive</span>';
                                },
                                'filter' => [0 => 'Inactive', 1 => 'Active'],
                            ],
                            [
                                'attribute' => 'created_at',
                                'format'    => ['date', 'php:m/d/Y H:i'],
                                'filter'    => false,
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{view} {update} {delete}',
                                'buttons' => [
                                    'view' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                            'javascript:void(0);',
                                            [
                                                'class' => 'view-email-account-btn',
                                                'data-id' => $model->id,
                                                'title' => Yii::t('app', 'View'),
                                            ]
                                        );
                                    },
                                    'update' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M498 142l-46 46c-5 5-13 5-17 0L324 77c-5-5-5-12 0-17l46-46c19-19 49-19 68 0l60 60c19 19 19 49 0 68zm-214-42L22 362 0 484c-3 16 12 30 28 28l122-22 262-262c5-5 5-13 0-17L301 100c-4-5-12-5-17 0zM124 340c-5-6-5-14 0-20l154-154c6-5 14-5 20 0s5 14 0 20L144 340c-6 5-14 5-20 0zm-36 84h48v36l-64 12-32-31 12-65h36v48z"></path></svg>',
                                            'javascript:void(0);',
                                            [
                                                'class' => 'update-email-account-inline-btn',
                                                'data-id' => $model->id,
                                                'title' => Yii::t('app', 'Update'),
                                            ]
                                        );
                                    },
                                    'delete' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:.875em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9-19a24 24 0 00-22-13H167a24 24 0 00-22 13l-9 19H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z"></path></svg>',
                                            $url,
                                            [
                                                'title' => Yii::t('app', 'Delete'),
                                                'data-confirm' => Yii::t('app', 'Are you sure you want to delete this email account?'),
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

<div class="modal fade email-account-modal" id="emailAccountModal" tabindex="-1" role="dialog" aria-labelledby="emailAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailAccountModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>