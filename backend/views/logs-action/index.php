<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\widgets\Pjax;
use kartik\datetime\DateTimePicker;
use backend\models\ActionLogSearch;

/* @var $this yii\web\View */
/* @var $tabsData array */

$this->title = 'Action Logs';
$this->params['breadcrumbs'][] = $this->title;

$script = "
function showLogDetails(event, logId) {
    event.preventDefault();
    event.stopPropagation();

    var \$clickedRow = $(event.target).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.log-details-row');

    if (existingDetailsRow.length) {
        existingDetailsRow.find('.log-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }

    $('.log-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.log-details').slideUp(700, function() {
            \$this.remove();
        });
    });

    var detailsRow = $('<tr class=\"log-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"log-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    \$clickedRow.after(detailsRow);

    $.ajax({
        url: '" . Url::to(['view']) . "',
        type: 'GET',
        data: { id: logId },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                detailsRow.find('.content').html(response.tpl);
                detailsRow.find('.log-details').hide().slideDown(700);
            }
        },
        error: function() {
            detailsRow.find('.content').html('Failed to load details');
            detailsRow.find('.log-details').hide().slideDown(700);
        }
    });
}

$(document).on('click', '.cancel-action', function(e) {
    e.preventDefault();
    $(this).closest('.log-details-row').find('.log-details').slideUp(700, function() {
        $(this).closest('.log-details-row').remove();
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
                    <div class="card card-secondary card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="logs-tabs" role="tablist">
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
                            <div class="tab-content" id="logs-tabs-content">
                                <?php foreach ($tabsData as $tab): ?>
                                    <div class="tab-pane fade <?= $tab['active'] ? 'show active' : '' ?>"
                                         id="<?= $tab['id'] ?>"
                                         role="tabpanel"
                                         aria-labelledby="<?= $tab['id'] ?>-tab">

                                        <?php Pjax::begin(['id' => 'pjax-' . $tab['id'], 'timeout' => 5000]); ?>

                                        <?= GridView::widget([
                                            'dataProvider' => $tab['dataProvider'],
                                            'filterModel' => $tab['searchModel'],
                                            'tableOptions' => ['class' => 'table table-striped table-bordered'],
                                            'columns' => [
                                                ['class' => 'yii\grid\SerialColumn'],

                                                [
                                                    'attribute' => 'userName',
                                                    'label' => 'User',
                                                    'value' => function($model) {
                                                        return $model->user ? $model->user->getFullName() : '-';
                                                    },
                                                ],

                                                [
                                                    'attribute' => 'action',
                                                    'value' => function($model) {
                                                        return $model->getActionName();
                                                    },
                                                    'filter' => ActionLogSearch::getActionFilterList(),
                                                ],

                                                [
                                                    'attribute' => 'created_at',
                                                    'label' => 'Date',
                                                    'format'    => 'raw',
                                                    'value'     => function ($model) {
                                                        return date('m/d/Y H:i', $model->created_at);
                                                    },

                                                    'filter' => '<div style="display:flex;align-items:center;gap:4px;">' .
                                                        DateTimePicker::widget([
                                                            'model' => $tab['searchModel'],
                                                            'attribute' => 'dateFrom',
                                                            'pluginOptions' => [
                                                                'autoclose' => true,
                                                                'format' => 'yyyy-mm-dd',
                                                                'minView' => 2,
                                                            ],
                                                            'options' => [
                                                                'placeholder' => 'From',
                                                                'class' => 'form-control form-control-sm',
                                                                'style' => 'width:110px',
                                                            ],
                                                        ]) .
                                                        '<span style="flex-shrink:0;">—</span>' .
                                                        DateTimePicker::widget([
                                                            'model' => $tab['searchModel'],
                                                            'attribute' => 'dateTo',
                                                            'pluginOptions' => [
                                                                'autoclose' => true,
                                                                'format' => 'yyyy-mm-dd',
                                                                'minView' => 2,
                                                            ],
                                                            'options' => [
                                                                'placeholder' => 'To',
                                                                'class' => 'form-control form-control-sm',
                                                                'style' => 'width:110px',
                                                            ],
                                                        ]) .
                                                        '</div>',
                                                ],

                                                [
                                                    'class' => 'yii\grid\ActionColumn',
                                                    'template' => '{view}',
                                                    'buttons' => [
                                                        'view' => function($url, $model, $key) {
                                                            return Html::a(
                                                                '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                                                '#',
                                                                [
                                                                    'title' => 'View Details',
                                                                    'onclick' => 'showLogDetails(event, ' . $model->id . '); return false;',
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
                                            ],
                                        ]); ?>

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