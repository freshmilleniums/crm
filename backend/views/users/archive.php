<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $tabsData array */
/* @var $countries array */

$this->title = 'Archive';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$restoreEmployerUrl = Url::to(['restore-employer']);
$viewUrl = Url::to(['view']);

$script = "

function showArchiveUserDetails(event, userId) {
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

    $.ajax({
        url: '$viewUrl',
        type: 'GET',
        data: { id: userId },
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

$(document).on('click', '.restore-employer-btn', function(e) {
    e.preventDefault();

    if (!confirm('Are you sure you want to restore this user?')) {
        return;
    }

    var userId = $(this).data('id');

    $.ajax({
        url: '$restoreEmployerUrl',
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

$(document).on('click', '.cancel-action', function(e) {
    e.preventDefault();
    $(this).closest('.action-details-row').find('.action-details').slideUp(700, function() {
        $(this).closest('.action-details-row').remove();
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
                            <ul class="nav nav-tabs" id="archive-tabs" role="tablist">
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
                            <div class="tab-content" id="archive-tabContent">
                                <?php foreach ($tabsData as $tab): ?>
                                    <div class="tab-pane fade <?= $tab['active'] ? 'show active' : '' ?>"
                                         id="<?= $tab['id'] ?>"
                                         role="tabpanel"
                                         aria-labelledby="<?= $tab['id'] ?>-tab">

                                        <?= GridView::widget([
                                            'dataProvider' => $tab['dataProvider'],
                                            'filterModel'  => $tab['filterModel'],
                                            'tableOptions' => ['class' => 'table table-striped table-bordered'],
                                            'columns'      => [
                                                ['class' => 'yii\grid\SerialColumn'],
                                                'email:email',
                                                'first_name',
                                                'last_name',
                                                'phone_number',
                                                [
                                                    'attribute' => 'city',
                                                    'label'     => 'Location',
                                                    'value'     => function($model) use ($countries) {
                                                        $locationParts = array_filter([
                                                            $model->city,
                                                            $model->state,
                                                        ]);
                                                        if ($model->country) {
                                                            $locationParts[] = $countries[$model->country] ?? $model->country;
                                                        }
                                                        return implode(', ', $locationParts);
                                                    },
                                                ],
                                                [
                                                    'attribute' => 'created_at',
                                                    'format'    => ['date', 'php:Y-m-d'],
                                                ],
                                                [
                                                    'class'    => 'yii\grid\ActionColumn',
                                                    'template' => '{view} {restore}',
                                                    'buttons'  => [
                                                        'view' => function($url, $model, $key) {
                                                            return Html::a(
                                                                '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1.125em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M573 241C518 136 411 64 288 64S58 136 3 241a32 32 0 000 30c55 105 162 177 285 177s230-72 285-177a32 32 0 000-30zM288 400a144 144 0 11144-144 144 144 0 01-144 144zm0-240a95 95 0 00-25 4 48 48 0 01-67 67 96 96 0 1092-71z"></path></svg>',
                                                                '#',
                                                                [
                                                                    'title'     => Yii::t('app', 'View'),
                                                                    'onclick'   => 'showArchiveUserDetails(event, ' . $model->id . '); return false;',
                                                                    'data-pjax' => '0',
                                                                ]
                                                            );
                                                        },
                                                        'restore' => function($url, $model, $key) {
                                                            return Html::a(
                                                                '<i class="fas fa-undo"></i>',
                                                                '#',
                                                                [
                                                                    'title'     => 'Restore',
                                                                    'class'     => 'restore-employer-btn',
                                                                    'data-id'   => $model->id,
                                                                    'data-pjax' => '0',
                                                                ]
                                                            );
                                                        },
                                                    ],
                                                ],
                                            ],
                                            'summaryOptions' => ['class' => 'summary mb-2'],
                                            'pager'          => ['class' => 'yii\bootstrap4\LinkPager'],
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