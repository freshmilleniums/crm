<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use common\models\Template;

/* @var $this yii\web\View */
/* @var $tabsData array */

$this->title = 'Templates';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss('
.template-modal .modal-dialog {
    max-width: 900px;
}

.template-modal .modal-body {
    max-height: 80vh;
    overflow-y: auto;
}

.nav-tabs .nav-link {
    color: #495057;
}

.nav-tabs .nav-link.active {
    font-weight: bold;
}

.template-body-preview {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
');

$script = "
function showCreateTemplateModal(event) {
    event.preventDefault();
    event.stopPropagation();
    
    var modal = $('#templateModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Create Template');
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
            modalBody.html('<div class=\"alert alert-danger\">Failed to load template form</div>');
        }
    });
}

function showTemplateDetails(event, templateId) {
    event.preventDefault();
    event.stopPropagation();
    
    var \$clickedRow = $(event.target).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.template-details-row');
    
    if (existingDetailsRow.length) {
        existingDetailsRow.find('.template-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }
    
    $('.template-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.template-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    var detailsRow = $('<tr class=\"template-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"template-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    $.ajax({
        url: '" . Url::to(['view']) . "',
        type: 'GET',
        data: { id: templateId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.template-details').hide().slideDown(700);
            }
        },
        error: function(xhr, status, error) {
            detailsRow.find('.content').html('Failed to load template details');
            detailsRow.find('.template-details').hide().slideDown(700);
        }
    });
}

function showTemplateEditForm(event, templateId) {
    event.preventDefault();
    event.stopPropagation();
    
    var \$clickedRow = $(event.target).closest('tr');
    
    if (!\$clickedRow.length) {
        \$clickedRow = $(event.target).closest('.template-details-row').prev('tr');
    }
    
    var existingDetailsRow = \$clickedRow.next('.template-details-row');
    
    if (existingDetailsRow.length) {
        existingDetailsRow.find('.template-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
    }
    
    $('.template-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.template-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    var detailsRow = $('<tr class=\"template-details-row\"><td colspan=\"' + (\$clickedRow.find('td').length || 7) + '\"><div class=\"template-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    $.ajax({
        url: '" . Url::to(['update']) . "',
        type: 'GET',
        data: { id: templateId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.template-details').hide().slideDown(700);
            }
        },
        error: function(xhr, status, error) {
            detailsRow.find('.content').html('Failed to load edit form');
            detailsRow.find('.template-details').hide().slideDown(700);
        }
    });
}

function showSendTemplateForm(event, templateId) {
    event.preventDefault();
    event.stopPropagation();
    
    var modal = $('#templateModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Send Template');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '" . Url::to(['show-send-form']) . "',
        type: 'GET',
        data: { id: templateId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                modalBody.html(response.tpl);
            }
        },
        error: function(xhr, status, error) {
            modalBody.html('<div class=\"alert alert-danger\">Failed to load send form</div>');
        }
    });
}

$(document).on('submit', '#templates-form-ajax', function (e){
    e.preventDefault();
    
    let form = $(this);
    let button = form.find('.template-submit-btn');
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
                    $('#templateModal').modal('hide');
                    location.reload();
                } else {                   
                    if (typeof response.tpl != 'undefined') {
                        $('#templateModal .modal-body').html(response.tpl);                     
                    } else if (response.message) {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while creating template');
        },
        complete: function() {
            button.prop('disabled', false).text('Create Template');
        }
    });
});

$(document).on('submit', '#templates-form-update', function (e){
    e.preventDefault();
    
    let form = $(this);
    let button = form.find('.template-update-btn');
    let formData = new FormData(form[0]);
    
    button.prop('disabled', true).text('Saving...');
    
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
                    
                    $('.template-details-row').find('.template-details').slideUp(700, function() {
                        $('.template-details-row').remove();
                    });
                    
                    location.reload();
                } else {                   
                    if (typeof response.tpl != 'undefined') {
                        var detailsRow = $('.template-details-row');
                        detailsRow.find('.content').html(response.tpl);
                    } else if (response.message) {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while updating template');
        },
        complete: function() {
            button.prop('disabled', false).text('Save');
        }
    });
});

$(document).on('submit', '#send-template-form', function (e){
    e.preventDefault();
    
    let form = $(this);
    let button = form.find('.send-submit-btn');
    let data = form.serialize();
    
    button.prop('disabled', true).text('Sending...');
    
    $.ajax({
        type: 'POST',
        url: form.prop('action'),
        data: data,
        success: function (response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if(response.success == true) {                
                    toastr.success(response.message);
                    $('#templateModal').modal('hide');
                } else {                   
                    toastr.error(response.message);
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while sending template');
        },
        complete: function() {
            button.prop('disabled', false).text('Send');
        }
    });
});

$(document).on('click', '.cancel-action', function (e){
    e.preventDefault();
    $(this).closest('.template-details-row').find('.template-details').slideUp(700, function() {
        $(this).closest('.template-details-row').remove();
    });
});

$(document).on('click', '.edit-template-btn', function(e){
    e.preventDefault();
    var templateId = $(this).data('template-id');
    showTemplateEditForm(e, templateId);
});

$(document).on('click', '.kv-file-remove', function(e){
    var filePreview = $(this).closest('.file-input').find('.fileinput-remove');
    if(filePreview){
        filePreview[0].click();
    }
});

$('#templateModal').on('hidden.bs.modal', function () {
    if (typeof tinymce !== 'undefined') {
        tinymce.remove();
    }
});
";

$this->registerJs($script, \yii\web\View::POS_END);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <?= Html::a('Create Template', '#', [
                            'class' => 'btn btn-success',
                            'onclick' => 'showCreateTemplateModal(event); return false;'
                        ]) ?>
                    </div>

                    <div class="card card-secondary card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="custom-tabs-templates" role="tablist">
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
                            <div class="tab-content" id="custom-tabs-templates-tabContent">
                                <?php foreach ($tabsData as $tab): ?>
                                    <div class="tab-pane fade <?= $tab['active'] ? 'show active' : '' ?>"
                                         id="<?= $tab['id'] ?>"
                                         role="tabpanel"
                                         aria-labelledby="<?= $tab['id'] ?>-tab">

                                        <?= GridView::widget([
                                            'dataProvider' => $tab['dataProvider'],
                                            'tableOptions' => ['class' => 'table table-striped table-bordered'],
                                            'columns' => [
                                                ['class' => 'yii\grid\SerialColumn'],
                                                [
                                                    'attribute' => 'title',
                                                    'format' => 'raw',
                                                    'value' => function ($model) {
                                                        return Html::encode($model->title);
                                                    },
                                                ],
                                                [
                                                    'attribute' => 'subject',
                                                    'format' => 'raw',
                                                    'value' => function ($model) {
                                                        return $model->subject ? Html::encode($model->subject) : '<span class="text-muted">No subject</span>';
                                                    },
                                                ],
                                                [
                                                    'label' => 'Documents',
                                                    'format' => 'raw',
                                                    'value' => function ($model) {
                                                        $count = count($model->documents);
                                                        if ($count > 0) {
                                                            return  $count . ' file(s)';
                                                        }
                                                        return '<span class="text-muted">No files</span>';
                                                    },
                                                    'filter' => false,
                                                ],
                                                [
                                                    'attribute' => 'created_by',
                                                    'format' => 'raw',
                                                    'value' => function ($model) {
                                                        return $model->getCreatorName() ?? '<span class="text-muted">Unknown</span>';
                                                    },
                                                    'filter' => false,
                                                ],
                                                [
                                                    'attribute' => 'created_at',
                                                    'format'    => 'raw',
                                                    'value'     => function ($model) {
                                                        return date('m/d/Y H:i', $model->created_at);
                                                    },
                                                    'filter'    => false,
                                                ],
                                                [
                                                    'class' => 'yii\grid\ActionColumn',
                                                    'template' => '{view} {update} {send} {delete}',
                                                    'buttons' => [
                                                        'view' => function ($url, $model, $key) {
                                                            return Html::a(
                                                                '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                                                '#',
                                                                [
                                                                    'title' => Yii::t('app', 'View'),
                                                                    'onclick' => 'showTemplateDetails(event, ' . $model->id . '); return false;',
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
                                                                    'onclick' => 'showTemplateEditForm(event, ' . $model->id . '); return false;',
                                                                    'data-pjax' => '0',
                                                                ]
                                                            );
                                                        },
                                                        'send' => function ($url, $model, $key) {
                                                            return Html::a(
                                                                '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M476 3.2L12.5 270.6c-18.1 10.4-15.8 35.6 2.2 43.2L121 358.4l287.3-253.2c5.5-4.9 13.3 2.6 8.6 8.3L176 407v80.5c0 23.6 28.5 32.9 42.5 15.8L282 426l124.6 52.2c14.2 6 30.4-2.9 33-18.2l72-432C515 7.8 493.3-6.8 476 3.2z"></path></svg>',
                                                                '#',
                                                                [
                                                                    'title' => Yii::t('app', 'Send Template'),
                                                                    'onclick' => 'showSendTemplateForm(event, ' . $model->id . '); return false;',
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
                                                                    'data-confirm' => Yii::t('app', 'Are you sure you want to delete this template?'),
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
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade template-modal" id="templateModal" tabindex="-1" role="dialog" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalLabel"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>