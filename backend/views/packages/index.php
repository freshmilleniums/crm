<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use common\models\Packages;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\PackagesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Packages';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss('
/* Task Modal Styles */
.task-modal .modal-dialog {
    max-width: 800px;
}

.task-modal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.task-modal .labels_item {
    margin-bottom: 15px;
}

.badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem !important;
}
.tracking-status .table td {
    padding: 0.25rem 0.5rem;
    vertical-align: top;
}
.tracking-status .table td:first-child {
    width: 40%;
    font-weight: 500;
}
.btn-track {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

');

$script = "
function showCreateTaskModal(event, packageId) {
    event.preventDefault();
    event.stopPropagation();
    
    var modal = $('#taskModal');
    var modalBody = modal.find('.modal-body');
    
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '" . Url::to(['tasks/create-for-package']) . "',
        type: 'GET',
        data: { package_id: packageId },
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

function showTrackingStatus(event, packageId, trackNumber) {
    event.preventDefault();
    event.stopPropagation();
    
    var modal = $('#trackingModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Tracking Status: ' + trackNumber);
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '" . Url::to(['get-tracking-status']) . "',
        type: 'GET',
        data: { id: packageId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined') {
                if (response.success == true) {
                    if (typeof response.tpl != 'undefined') {
                        modalBody.html(response.tpl);
                    }
                } else {
                    modalBody.html('<div class=\"alert alert-danger\"><i class=\"fas fa-exclamation-triangle\"></i> ' + response.message + '</div>');
                }
            }
        },
        error: function(xhr, status, error) {
            modalBody.html('<div class=\"alert alert-danger\"><i class=\"fas fa-exclamation-triangle\"></i> Error loading tracking status: ' + error + '</div>');
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
";

$this->registerJs($script, \yii\web\View::POS_END);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <?php if (Yii::$app->user->can('createPackage')): ?>
                                <?= Html::a('Create Package', ['create'], ['class' => 'btn btn-success']) ?>
                            <?php endif; ?>
                            <?= Html::button('<i class="fas fa-filter"></i> Filters', ['class' => 'btn btn-primary mobile-filter-btn']) ?>
                        </div>
                    </div>

                    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'tableOptions' => ['class' => 'table table-striped table-bordered'],
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],
                            [
                                'attribute' => 'delivery_date',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    if (!empty($model->delivery_date)) {
                                        return date('m/d/Y H:i', is_numeric($model->delivery_date) ? $model->delivery_date : strtotime($model->delivery_date));
                                    }
                                    return '';
                                },
                            ],
                            [
                                'label' => 'Track, Post',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $result = '';

                                    // Track
                                    if (!empty($model->track)) {
                                        $trackButton = Html::button(
                                            '<i class="fas fa-search"></i>',
                                            [
                                                'class' => 'btn btn-outline-primary btn-sm btn-track ml-2',
                                                'title' => 'Check tracking status',
                                                'onclick' => "showTrackingStatus(event, {$model->id}, '{$model->track}')",
                                            ]
                                        );
                                        $result .= Html::encode($model->track) . ' ' . $trackButton;
                                    }

                                    // Tracking Status and Age
                                    if ($model->track_status !== null) {
                                        $statusAge = $model->getTrackStatusAge();

                                        $result .= '<br><span >' . Html::encode($model->track_status) . '</span>';

                                        if ($statusAge) {
                                            $result .= '<br><small class="text-muted">' . Html::encode($statusAge) . '</small>';
                                        }
                                    }

                                    $result .= '<br>';

                                    // Post
                                    if ($model->post != \common\models\Packages::POST_NONE) {
                                        $result .= Html::encode($model->getPostName());
                                    }

                                    return $result;
                                },
                                'filter' => false,
                            ],
                            [
                                'label' => 'Weight, Name, Description',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $result = '';

                                    // Weight
                                    if (!empty($model->weight)) {
                                        $result .= number_format($model->weight, 2) ;
                                    }
                                    $result .= '<br>';

                                    // Name on Package
                                    $result .= Html::encode($model->name) . '<br>';

                                    // Description
                                    if (!empty($model->description)) {
                                        $result .= Html::encode($model->description);
                                    }

                                    return $result;
                                },
                                'filter' => false,
                            ],
                            [
                                'label' => 'Courier, Comment',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $result = '';

                                    // Courier
                                    $courierName = $model->getCourierName();
                                    if (!empty($courierName)) {
                                        $result .= Html::encode($courierName);
                                    }

                                    $result .= '<br>';

                                    // Comment
                                    if (!empty($model->comment)) {
                                        $result .= Html::encode($model->comment);
                                    }

                                    return $result;
                                },
                                'filter' => false,
                            ],
                            //'address',
                            [
                                'attribute' => 'status',
                                'value' => function ($model) {
                                    return $model->getStatusName();
                                },
                                'filter' => \common\models\Packages::getStatusList(),
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'visibleButtons' => [
                                    'view' => Yii::$app->user->can('viewPackage'),
                                    'update' => Yii::$app->user->can('updatePackage'),
                                    'delete' => Yii::$app->user->can('deletePackage'),
                                ],
                                'template' => '{view} {update} {create-task} {delete}',
                                'buttons' => [
                                    'view' => function ($url, $model, $key) {
                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                            $url,
                                            [
                                                'title' => Yii::t('app', 'View'),
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
                                    'create-task' => function ($url, $model, $key) {
                                        if ($model->status !== Packages::STATUS_DELIVERED && $model->status !== Packages::STATUS_COMPLETED) {
                                            return '';
                                        }

                                        return Html::a(
                                            '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm144 276c0 6.6-5.4 12-12 12h-92v92c0 6.6-5.4 12-12 12h-56c-6.6 0-12-5.4-12-12v-92h-92c-6.6 0-12-5.4-12-12v-56c0-6.6 5.4-12 12-12h92v-92c0-6.6 5.4-12 12-12h56c6.6 0 12 5.4 12 12v92h92c6.6 0 12 5.4 12 12v56z"></path></svg>',
                                            '#',
                                            [
                                                'title' => Yii::t('app', 'Create Task'),
                                                'onclick' => 'showCreateTaskModal(event, ' . $model->id . '); return false;',
                                                'data-pjax' => '0',
                                                'class' => 'text-success'
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
                <!--.card-body-->
            </div>
            <!--.card-->
        </div>
        <!--.col-md-12-->
    </div>
    <!--.row-->
</div>

<!-- Task Modal -->
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

<!-- Tracking Modal -->
<div class="modal fade task-modal" id="trackingModal" tabindex="-1" role="dialog" aria-labelledby="trackingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trackingModalLabel"><?= Yii::t('app', 'Package Tracking') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>