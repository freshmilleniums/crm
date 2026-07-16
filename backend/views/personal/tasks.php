<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use common\models\Task;

$this->title = 'My Tasks';
$this->params['breadcrumbs'][] = $this->title;
?>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'layout' => "{items}\n{summary}\n{pager}",
                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                    'rowOptions' => function ($model) {
                        return [
                            'class' => 'task-row',
                            'data-id' => $model->id,
                        ];
                    },
                    'columns' => [
                        [
                            'attribute' => 'title',
                            'label' => 'Task Name',
                        ],
                        [
                            'attribute' => 'status',
                            'value' => function ($model) {
                                return $model->getStatusName();
                            },
                            'filter' => Task::getStatusList(),
                        ],
                        [
                            'attribute' => 'priority',
                            'value' => function ($model) {
                                return $model->getPriorityName();
                            },
                            'filter' => Task::getPriorityList(),
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
                            'label' => 'Deadline',
                            'filter' => false,
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view}',
                            'buttons' => [
                                'view' => function ($url, $model) {
                                    return Html::a(
                                        '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                        'javascript:void(0);',
                                        [
                                            'class' => 'view-task-btn',
                                            'data-id' => $model->id,
                                            'title' => 'View',
                                        ]
                                    );
                                },
                            ],
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </div>

<?php
// Generate URLs first
$viewTaskUrl = Url::to(['view-task']);
$startTaskUrl = Url::to(['start-task']);
$completeTaskUrl = Url::to(['complete-task']);

// Use regular string with interpolation
$this->registerJs("
// Expandable row logic (similar to admin side)
$(document).on('click', '.view-task-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var taskId = $(this).data('id');
    var \$clickedRow = $(this).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.task-details-row');
    
    // Close if already open
    if (existingDetailsRow.length) {
        existingDetailsRow.find('.task-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }
    
    // Close other expanded rows
    $('.task-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.task-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    // Create details row
    var detailsRow = $('<tr class=\"task-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"task-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    // Load content via AJAX
    $.ajax({
        url: '$viewTaskUrl',
        type: 'GET',
        data: { id: taskId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined' && response.success && typeof response.content != 'undefined') {
                detailsRow.find('.content').html(response.content);
                detailsRow.find('.task-details').hide().slideDown(700);
            } else {
                detailsRow.find('.content').html('Failed to load task details');
                detailsRow.find('.task-details').hide().slideDown(700);
            }
        },
        error: function(xhr, status, error) {
            detailsRow.find('.content').html('Failed to load task details');
            detailsRow.find('.task-details').hide().slideDown(700);
        }
    });
});

// Start task
function startTask(event, taskId) {
    event.preventDefault();
    
    if (!confirm('Are you sure you want to start this task?')) {
        return;
    }
    
    $.ajax({
        url: '$startTaskUrl',
        type: 'POST',
        data: { id: taskId },
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message);
                }
                location.reload();
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(response.message || 'Error starting task');
                } else {
                    alert(response.message || 'Error starting task');
                }
            }
        },
        error: function() {
            if (typeof toastr !== 'undefined') {
                toastr.error('Error starting task');
            } else {
                alert('Error starting task');
            }
        }
    });
}

// Complete task
function completeTask(event, taskId) {
    event.preventDefault();
    
    if (!confirm('Are you sure you want to mark this task as completed?')) {
        return;
    }
    
    $.ajax({
        url: '$completeTaskUrl',
        type: 'POST',
        data: { id: taskId },
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message);
                }
                location.reload();
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(response.message || 'Error completing task');
                } else {
                    alert(response.message || 'Error completing task');
                }
            }
        },
        error: function() {
            if (typeof toastr !== 'undefined') {
                toastr.error('Error completing task');
            } else {
                alert('Error completing task');
            }
        }
    });
}
", \yii\web\View::POS_END);
?>