<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use common\models\Task;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\TasksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Tasks';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss('
.task-modal .modal-dialog {
    max-width: 800px;
}

.task-modal .modal-body {
    max-height: 80vh;
    overflow-y: auto;
}

.task-modal .documents_item {
    margin-bottom: 15px;
}
');

$script = "
function showCreateTaskModal(event) {
    event.preventDefault();
    event.stopPropagation();
    
    var modal = $('#taskModal');
    var modalBody = modal.find('.modal-body');
    
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
            modalBody.html('<div class=\"alert alert-danger\">Failed to load task form</div>');
        }
    });
}

function showTaskDetails(event, taskId) {
    event.preventDefault();
    event.stopPropagation();
    
    var \$clickedRow = $(event.target).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.task-details-row');
    
    if (existingDetailsRow.length) {
        existingDetailsRow.find('.task-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }
    
    $('.task-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.task-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    var detailsRow = $('<tr class=\"task-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"task-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    $.ajax({
        url: '" . Url::to(['view']) . "',
        type: 'GET',
        data: { id: taskId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.task-details').hide().slideDown(700);
            }
        },
        error: function(xhr, status, error) {
            detailsRow.find('.content').html('Failed to load task details');
            detailsRow.find('.task-details').hide().slideDown(700);
        }
    });
}

// Handle task form submission
$(document).on('submit', '#tasks-form-ajax', function (e){
    e.preventDefault();
    
    let form = $(this);
    let button = form.find('.task-submit-btn');
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
                    $('#taskModal').modal('hide');
                    location.reload();
                } else {                   
                    if (typeof response.tpl != 'undefined') {
                        $('#taskModal .modal-body').html(response.tpl);                     
                    } else if (response.message) {
                        toastr.error(response.message);
                    }
                }
            }
        },
        error: function() {
            toastr.error('An error occurred while creating task');
        },
        complete: function() {
            button.prop('disabled', false).text('Create Task');
        }
    });
});

$(document).on('click', '.kv-file-remove', function(e){
    var filePreview = $(this).closest('.file-input').find('.fileinput-remove');
    if(filePreview){
        filePreview[0].click();
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
                    <div class="mb-2">
                        <?= Html::a('Create Task', '#', [
                            'class' => 'btn btn-success',
                            'onclick' => 'showCreateTaskModal(event); return false;'
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
                                'attribute' => 'title',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $title = Html::encode($model->title);
                                    if ($model->is_training) {
                                        $title .= ' <span class="badge badge-warning ml-1">Training</span>';
                                    }
                                    return $title;
                                },
                            ],
                            [
                                'attribute' => 'subject',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return Html::encode($model->subject);
                                },
                            ],
                            [
                                'attribute' => 'assigned_to',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return $model->getAssignedUserName() ?? '<span class="text-muted">Not assigned</span>';
                                },
                                'filter' => false,
                            ],
                            [
                                'attribute' => 'priority',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return $model->priority ? Html::encode($model->getPriorityName()) : '<span class="text-muted">Not set</span>';
                                },
                                'filter' => Task::getPriorityList(),
                            ],
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return $model->status ? Html::encode($model->getStatusName()) : '<span class="text-muted">Not set</span>';
                                },
                                'filter' => Task::getStatusList(),
                            ],
                            [
                                'attribute' => 'due_date',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if (!empty($model->due_date)) {
                                        $timestamp = is_numeric($model->due_date) ? $model->due_date : strtotime($model->due_date);
                                        return date('m/d/Y H:i', $timestamp);
                                    }
                                    return '';
                                },
                                'filter' => false,
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{view} {update} {delete}',
                                'buttons' => [
                                    'view' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                            '#',
                                            [
                                                'title' => Yii::t('app', 'View'),
                                                'onclick' => 'showTaskDetails(event, ' . $model->id . '); return false;',
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
                                                'data-pjax' => '0',
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

<!-- Task Modal  -->
<div class="modal fade task-modal" id="taskModal" tabindex="-1" role="dialog" aria-labelledby="taskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskModalLabel"><?= Yii::t('app', 'Create Task') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>