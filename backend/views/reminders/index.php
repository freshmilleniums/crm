<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Reminders';
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
                                'attribute' => 'code',
                                'headerOptions' => ['width' => '100'],
                            ],
                            [
                                'label' => 'Description',
                                'value' => function ($model) {
                                    return $model->getCodeDescription();
                                },
                                'headerOptions' => ['width' => '500'],
                            ],
                            [
                                'attribute' => 'text',
                                'value' => function ($model) {
                                    return $model->text ?: '';
                                },
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{update}',
                                'headerOptions' => ['width' => '80'],
                            ],
                        ],
                    ]); ?>

                </div>
            </div>
        </div>
    </div>
</div>