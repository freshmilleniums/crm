<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $investors array */

$this->title = 'My Investors';
$this->params['breadcrumbs'][] = $this->title;

// Создаём DataProvider из массива
$dataProvider = new ArrayDataProvider([
    'allModels' => $investors,
    'pagination' => [
        'pageSize' => 20,
    ],
]);
?>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <?php if (empty($investors)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No investors assigned to you yet.
                    </div>
                <?php else: ?>
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'layout' => "{items}\n{summary}\n{pager}",
                        'tableOptions' => ['class' => 'table table-striped table-bordered'],
                        'rowOptions' => function ($model) {
                            return [
                                'class' => 'investor-row',
                                'data-id' => $model->id,
                            ];
                        },
                        'columns' => [
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
                                    return $model->getTypeName();
                                },
                                'label' => 'Type',
                            ],
                            [
                                'attribute' => 'net_value',
                                'value' => function($model) {
                                    return $model->net_value ? number_format($model->net_value, 2) : 'N/A';
                                },
                                'label' => 'Net Value',
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
                                                'class' => 'view-investor-btn',
                                                'data-id' => $model->id,
                                                'title' => 'View',
                                            ]
                                        );
                                    },
                                ],
                            ],
                        ],
                    ]); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php
$viewInvestorUrl = Url::to(['view-investor']);

$this->registerJs("
// Expandable row logic
$(document).on('click', '.view-investor-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var investorId = $(this).data('id');
    var \$clickedRow = $(this).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.investor-details-row');
    
    // Close if already open
    if (existingDetailsRow.length) {
        existingDetailsRow.find('.investor-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }
    
    // Close other expanded rows
    $('.investor-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.investor-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    // Create details row
    var detailsRow = $('<tr class=\"investor-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"investor-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    // Load content via AJAX
    $.ajax({
        url: '$viewInvestorUrl',
        type: 'GET',
        data: { id: investorId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.success != 'undefined' && response.success && typeof response.content != 'undefined') {
                detailsRow.find('.content').html(response.content);
                detailsRow.find('.investor-details').hide().slideDown(700);
            } else {
                detailsRow.find('.content').html('Failed to load investor details');
                detailsRow.find('.investor-details').hide().slideDown(700);
            }
        },
        error: function(xhr, status, error) {
            detailsRow.find('.content').html('Failed to load investor details');
            detailsRow.find('.investor-details').hide().slideDown(700);
        }
    });
});
", \yii\web\View::POS_END);
?>