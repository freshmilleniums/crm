<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use common\models\Project;

$this->title = 'My Projects';
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
                            'class' => 'project-row',
                            'data-id' => $model->id,
                        ];
                    },
                    'columns' => [
                        [
                            'attribute' => 'name',
                            'label' => 'Project Name',
                        ],
                        [
                            'attribute' => 'type',
                            'value' => function ($model) {
                                return $model->getTypeName();
                            },
                            'filter' => Project::getTypeList(),  // ✅ Вместо getTypeLabels()
                        ],
                        [
                            'attribute' => 'status',
                            'value' => function ($model) {
                                return $model->getStatusName();
                            },
                            'filter' => Project::getStatusList(),  // ✅ Вместо getStatusLabels()
                        ],
                        [
                            'attribute' => 'net_worth',
                            'value' => function ($model) {
                                return $model->net_worth ? number_format($model->net_worth, 2) : 'N/A';
                            },
                            'filter' => false,
                        ],
                        [
                            'attribute' => 'roi',
                            'value' => function ($model) {
                                return $model->roi ? $model->roi . '%' : 'N/A';
                            },
                            'label' => 'ROI',
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
                                            'class' => 'view-project-btn',
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
$viewProjectUrl = Url::to(['view-project']);

// Register JavaScript
$this->registerJs("
// Expandable row logic
$(document).on('click', '.view-project-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var projectId = $(this).data('id');
    var \$clickedRow = $(this).closest('tr');
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
    
    // Create details row
    var detailsRow = $('<tr class=\"project-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"project-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    // Load content via AJAX
    $.ajax({
        url: '$viewProjectUrl',
        type: 'GET',
        data: { id: projectId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined' && response.success && typeof response.content != 'undefined') {
                detailsRow.find('.content').html(response.content);
                detailsRow.find('.project-details').hide().slideDown(700);
            } else {
                detailsRow.find('.content').html('Failed to load project details');
                detailsRow.find('.project-details').hide().slideDown(700);
            }
        },
        error: function(xhr, status, error) {
            detailsRow.find('.content').html('Failed to load project details');
            detailsRow.find('.project-details').hide().slideDown(700);
        }
    });
});
", \yii\web\View::POS_END);
?>