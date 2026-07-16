<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Notifications';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">

                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'columns' => [
                            [
                                'attribute' => 'created_at',
                                'label' => 'Sent',
                                'value' => function ($model) {
                                    $timestamp = $model->resent_at ?: $model->created_at;
                                    return date('d.m.Y H:i', $timestamp);
                                },
                                'headerOptions' => ['width' => '180', 'style' => 'white-space: nowrap;'],
                                'contentOptions' => ['style' => 'white-space: nowrap; vertical-align: top;'],
                            ],
                            [
                                'attribute' => 'text',
                                'label' => 'Notification Text',
                                'value' => function ($model) {
                                    return $model->text;
                                },
                                'format' => 'raw',
                                'contentOptions' => [
                                    'style' => 'word-wrap: break-word; word-break: break-word; white-space: normal; vertical-align: top;',
                                ],
                            ],
                        ],
                        'tableOptions' => ['class' => 'table table-striped table-bordered', 'style' => 'table-layout: fixed; width: 100%;'],
                        'pager' => [
                            'class' => 'yii\bootstrap4\LinkPager',
                        ],
                    ]); ?>

                </div>
            </div>
        </div>
    </div>
</div>