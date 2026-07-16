<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\web\JqueryAsset;
use yii\data\ArrayDataProvider;
use backend\models\TrainingModuleQuestion;

/* @var $this yii\web\View */
/* @var $module backend\models\TrainingModule */
/* @var $questions backend\models\TrainingModuleQuestion[] */

$this->title = 'Questions: ' . $module->title;
$this->params['breadcrumbs'][] = ['label' => 'Training Modules', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

JqueryAsset::register($this);
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js', [
    'depends' => [JqueryAsset::class]
]);

$this->registerCss('
.sortable-ghost {
    opacity: 0.4;
}
.drag-handle {
    cursor: move;
    color: #999;
}
');

$dataProvider = new ArrayDataProvider([
    'allModels' => $questions,
    'pagination' => false,
]);
?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <?= Html::a('<i class="fas fa-arrow-left"></i> Back to Modules', ['index'], ['class' => 'btn btn-default']) ?>
                            <?= Html::a(' Add Question', ['create-question', 'moduleId' => $module->id], ['class' => 'btn btn-success']) ?>
                        </div>

                        <?php if (empty($questions)): ?>
                            <div class="alert alert-secondary">
                                <p class="mb-0">No questions found. <?= Html::a('Create first question', ['create-question', 'moduleId' => $module->id]) ?></p>
                            </div>
                        <?php else: ?>
                            <?= GridView::widget([
                                'dataProvider' => $dataProvider,
                                'tableOptions' => ['class' => 'table table-striped table-bordered'],
                                'rowOptions' => function ($model) {
                                    return [
                                        'data-id' => $model->id,
                                        'data-sort' => $model->sort,
                                    ];
                                },
                                'columns' => [
                                    [
                                        'label' => 'Sort',
                                        'format' => 'raw',
                                        'value' => function ($model) {
                                            return '<i class="fas fa-bars drag-handle"></i> <span class="sort-number">' . $model->sort . '</span>';
                                        },
                                        'headerOptions' => ['width' => '50', 'class' => 'text-center'],
                                        'contentOptions' => ['class' => 'text-center'],
                                    ],
                                    [
                                        'attribute' => 'question_text',
                                        'format' => 'raw',
                                        'value' => function ($model) {
                                            return Html::encode($model->question_text);
                                        },
                                    ],
                                    [
                                        'label' => 'Type',
                                        'format' => 'raw',
                                        'value' => function ($model) {
                                            return '<span class="badge badge-secondary">' . $model->getTypeName() . '</span>';
                                        },
                                        'headerOptions' => ['width' => '120'],
                                    ],
                                    [
                                        'label' => 'Options',
                                        'format' => 'raw',
                                        'value' => function ($model) {
                                            if (in_array($model->type, [TrainingModuleQuestion::TYPE_RADIO, TrainingModuleQuestion::TYPE_CHECKBOX])) {
                                                return count($model->options);
                                            }
                                            return '-';
                                        },
                                        'headerOptions' => ['width' => '100', 'class' => 'text-center'],
                                        'contentOptions' => ['class' => 'text-center'],
                                    ],
                                    [
                                        'label' => 'Check Answer',
                                        'format' => 'raw',
                                        'value' => function ($model) {
                                            if ($model->has_correct_answer) {
                                                return '<span class="badge badge-success">Yes</span>';
                                            }
                                            return '<span class="badge badge-secondary">No</span>';
                                        },
                                        'headerOptions' => ['width' => '120', 'class' => 'text-center'],
                                        'contentOptions' => ['class' => 'text-center'],
                                    ],
                                    [
                                        'class' => 'yii\grid\ActionColumn',
                                        'template' => '{update} {delete}',
                                        'buttons' => [
                                            'update' => function ($url, $model, $key) {
                                                return Html::a(
                                                    '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:1em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M498 142l-46 46c-5 5-13 5-17 0L324 77c-5-5-5-12 0-17l46-46c19-19 49-19 68 0l60 60c19 19 19 49 0 68zm-214-42L22 362 0 484c-3 16 12 30 28 28l122-22 262-262c5-5 5-13 0-17L301 100c-4-5-12-5-17 0zM124 340c-5-6-5-14 0-20l154-154c6-5 14-5 20 0s5 14 0 20L144 340c-6 5-14 5-20 0zm-36 84h48v36l-64 12-32-31 12-65h36v48z"></path></svg>',
                                                    ['update-question', 'id' => $model->id],
                                                    ['title' => 'Edit', 'data-pjax' => '0']
                                                );
                                            },
                                            'delete' => function ($url, $model, $key) {
                                                return Html::a(
                                                    '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:.875em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9-19a24 24 0 00-22-13H167a24 24 0 00-22 13l-9 19H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z"></path></svg>',
                                                    ['delete-question', 'id' => $model->id],
                                                    [
                                                        'title' => 'Delete',
                                                        'data-confirm' => 'Are you sure you want to delete this question?',
                                                        'data-method' => 'post',
                                                    ]
                                                );
                                            },
                                        ],
                                        'headerOptions' => ['width' => '100', 'class' => 'text-center'],
                                        'contentOptions' => ['class' => 'text-center'],
                                    ],
                                ],
                                'summaryOptions' => ['class' => 'summary mb-2'],
                            ]); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$updateSortUrl = Url::to(['update-question-sort']);
$this->registerJs("
    var tableBody = $('table tbody').get(0);
    if (tableBody) {
        var sortable = new Sortable(tableBody, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                var rows = $('table tbody tr');
                var ids = [];
                
                rows.each(function(index) {
                    var id = $(this).data('id');
                    ids.push(id);
                    // Update visual sort number (1-based)
                    $(this).find('.sort-number').text(index + 1);
                });
                
                $.ajax({
                    url: '{$updateSortUrl}',
                    type: 'POST',
                    data: {ids: ids},
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Sort order updated');
                        }
                    }
                });
            }
        });
    }
");
?>