<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\widgets\Pjax;
use common\models\Task;

/* @var $model backend\models\User */
/* @var $countries array */
/* @var $isEmployee bool */
/* @var $documentsProvider yii\data\ActiveDataProvider|null */
/* @var $sentTemplatesProvider yii\data\ActiveDataProvider|null */
/* @var $projectsProvider yii\data\ActiveDataProvider|null */
/* @var $investorsProvider yii\data\ActiveDataProvider|null */
/* @var $tasksProvider yii\data\ActiveDataProvider|null */
?>

<div class="container-fluid">

    <?php if (!$isEmployee): ?>

        <div class="card">
            <div class="card-body">
                <?= DetailView::widget([
                    'model'   => $model,
                    'options' => ['class' => 'table table-bordered detail-view'],
                    'attributes' => [
                        'email:email',
                        'first_name',
                        'last_name',
                        'phone_number',
                        'home_phone',
                        'address',
                        'city',
                        'state',
                        [
                            'attribute' => 'country',
                            'value'     => function($model) use ($countries) {
                                return $countries[$model->country] ?? $model->country;
                            },
                        ],
                        'zip_code',
                        [
                            'label'  => 'Online Status',
                            'value'  => $model->isOnline()
                                ? '<span class="badge badge-success">Online</span>'
                                : '<span class="badge badge-secondary">Offline</span>'
                                . ($model->getOfflineDurationFormatted()
                                    ? ' <small class="text-muted">(absent ' . $model->getOfflineDurationFormatted() . ')</small>'
                                    : ''),
                            'format' => 'raw',
                        ],
                        [
                            'label' => 'Time Online Today',
                            'value' => $model->getTotalTimeTodayFormatted(),
                        ],
                        [
                            'label' => 'Total Time Online',
                            'value' => $model->getTotalTimeAllFormatted(),
                        ],
                        [
                            'attribute' => 'last_activity',
                            'value'     => $model->last_activity ? date('Y-m-d H:i:s', $model->last_activity) : 'Never',
                            'label'     => 'Last Activity',
                        ],
                        [
                            'attribute' => 'created_at',
                            'format'    => ['datetime', 'php:Y-m-d H:i:s'],
                        ],
                    ],
                ]) ?>
            </div>
        </div>

    <?php else: ?>

        <div class="card card-secondary card-tabs">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="user-view-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" href="#tab-main" role="tab">Main</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab-documents" role="tab">Documents</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab-templates" role="tab">Sent Templates</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab-projects" role="tab">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab-investors" role="tab">Investors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab-tasks" role="tab">Tasks</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">

                    <!-- Main Tab -->
                    <div class="tab-pane fade show active" id="tab-main" role="tabpanel">
                        <?= DetailView::widget([
                            'model'   => $model,
                            'options' => ['class' => 'table table-bordered detail-view'],
                            'attributes' => [
                                'sequential_number',
                                'email:email',
                                'first_name',
                                'last_name',
                                'phone_number',
                                'home_phone',
                                'position_title',
                                'address',
                                'city',
                                'state',
                                [
                                    'attribute' => 'country',
                                    'value'     => function($model) use ($countries) {
                                        return $countries[$model->country] ?? $model->country;
                                    },
                                ],
                                'zip_code',
                                'hr_source',
                                [
                                    'attribute' => 'substatus',
                                    'value'     => $model->getSubstatusLabel(),
                                    'label'     => 'Status',
                                ],
                                [
                                    'attribute' => 'administrator_id',
                                    'value'     => $model->administrator ? $model->administrator->getFullName() : 'Not assigned',
                                    'label'     => 'Administrator',
                                ],
                                [
                                    'attribute' => 'call_center_operator_id',
                                    'value'     => $model->callCenterOperator ? $model->callCenterOperator->getFullName() : 'Not assigned',
                                    'label'     => 'Phone Operator',
                                    'visible'   => Yii::$app->user->can('super-administrator') || Yii::$app->user->can('administrator'),
                                ],
                                [
                                    'label'  => 'Online Status',
                                    'value'  => $model->isOnline()
                                        ? '<span class="badge badge-success">Online</span>'
                                        : '<span class="badge badge-secondary">Offline</span>'
                                        . ($model->getOfflineDurationFormatted()
                                            ? ' <small class="text-muted">(absent ' . $model->getOfflineDurationFormatted() . ')</small>'
                                            : ''),
                                    'format' => 'raw',
                                ],
                                [
                                    'label' => 'Time Online Today',
                                    'value' => $model->getTotalTimeTodayFormatted(),
                                ],
                                [
                                    'label' => 'Total Time Online',
                                    'value' => $model->getTotalTimeAllFormatted(),
                                ],
                                [
                                    'attribute' => 'last_activity',
                                    'value'     => $model->last_activity ? date('Y-m-d H:i:s', $model->last_activity) : 'Never',
                                    'label'     => 'Last Activity',
                                ],
                                [
                                    'attribute' => 'created_at',
                                    'format'    => ['datetime', 'php:Y-m-d H:i:s'],
                                ],
                            ],
                        ]) ?>
                    </div>

                    <!-- Documents Tab -->
                    <div class="tab-pane fade" id="tab-documents" role="tabpanel">
                        <?php
                        $documents = $documentsProvider->getModels();
                        if (empty($documents)):
                            ?>
                            <p class="text-muted">No documents uploaded.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($documents as $document): ?>
                                    <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3">
                                        <div class="card shadow-sm" style="height: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 8px;">
                                            <div class="card-body p-1 d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 150px;">

                                                <?php if ($document->isImage()): ?>
                                                    <a href="<?= $document->getUrl() ?>" target="_blank">
                                                        <img src="<?= $document->getUrl() ?>"
                                                             class="img-thumbnail mb-2"
                                                             style="max-width: 100%; max-height: 100px;"
                                                             alt="Document">
                                                    </a>
                                                <?php elseif ($document->isPdf()): ?>
                                                    <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-file fa-3x text-secondary mb-2"></i>
                                                <?php endif; ?>

                                                <div class="small text-truncate w-100 mb-1" title="<?= Html::encode($document->getFileName()) ?>">
                                                    <?= Html::encode($document->getFileName()) ?>
                                                </div>

                                                <div class="mt-auto">
                                                    <div class="mb-2">
                                                        <span class="badge badge-secondary" style="font-size: 0.8rem; padding: 4px 8px;">
                                                            <?= Html::encode($document->getDocumentTypeName()) ?>
                                                        </span>
                                                    </div>
                                                    <?= Html::a(
                                                        '<i class="fas fa-download"></i>',
                                                        $document->getUrl(),
                                                        [
                                                            'class'    => 'btn btn-sm btn-outline-primary',
                                                            'title'    => 'Download',
                                                            'download' => $document->getFileName(),
                                                            'target'   => '_blank',
                                                        ]
                                                    ) ?>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sent Templates Tab -->
                    <div class="tab-pane fade" id="tab-templates" role="tabpanel">
                        <?php Pjax::begin(['id' => 'pjax-templates', 'enablePushState' => false, 'timeout' => 5000]) ?>
                        <?= GridView::widget([
                            'dataProvider' => $sentTemplatesProvider,
                            'tableOptions' => ['class' => 'table table-sm table-bordered table-striped'],
                            'columns'      => [
                                ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'attribute' => 'template_name',
                                    'label'     => 'Template Name',
                                ],
                                [
                                    'attribute' => 'subject',
                                    'label'     => 'Subject',
                                ],
                                [
                                    'attribute' => 'sent_at',
                                    'label'     => 'Sent At',
                                    'value'     => function($st) {
                                        return $st->getSentAtFormatted();
                                    },
                                ],
                                [
                                    'attribute' => 'sent_by',
                                    'label'     => 'Sent By',
                                    'value'     => function($st) {
                                        return $st->sender ? Html::encode($st->sender->getFullName()) : '—';
                                    },
                                    'format' => 'raw',
                                ],
                            ],
                            'pager' => ['class' => 'yii\bootstrap4\LinkPager'],
                        ]) ?>
                        <?php Pjax::end() ?>
                    </div>

                    <!-- Projects Tab -->
                    <div class="tab-pane fade" id="tab-projects" role="tabpanel">
                        <?php Pjax::begin(['id' => 'pjax-projects', 'enablePushState' => false, 'timeout' => 5000]) ?>
                        <?= GridView::widget([
                            'dataProvider' => $projectsProvider,
                            'tableOptions' => ['class' => 'table table-sm table-bordered table-striped'],
                            'columns'      => [
                                ['class' => 'yii\grid\SerialColumn'],
                                ['attribute' => 'name'],
                                [
                                    'attribute' => 'type',
                                    'value'     => function($p) { return $p->getTypeName(); },
                                ],
                                [
                                    'attribute' => 'net_worth',
                                    'label'     => 'Net Worth',
                                ],
                                ['attribute' => 'roi', 'label' => 'ROI'],
                                [
                                    'attribute' => 'status',
                                    'value'     => function($p) { return $p->getStatusName(); },
                                ],
                            ],
                            'pager' => ['class' => 'yii\bootstrap4\LinkPager'],
                        ]) ?>
                        <?php Pjax::end() ?>
                    </div>

                    <!-- Investors Tab -->
                    <div class="tab-pane fade" id="tab-investors" role="tabpanel">
                        <?php Pjax::begin(['id' => 'pjax-investors', 'enablePushState' => false, 'timeout' => 5000]) ?>
                        <?= GridView::widget([
                            'dataProvider' => $investorsProvider,
                            'tableOptions' => ['class' => 'table table-sm table-bordered table-striped'],
                            'columns'      => [
                                ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'label'  => 'Full Name',
                                    'value'  => function($ie) {
                                        return $ie->investor ? Html::encode($ie->investor->getFullName()) : '—';
                                    },
                                    'format' => 'raw',
                                ],
                                [
                                    'label'  => 'Email',
                                    'value'  => function($ie) {
                                        return $ie->investor ? Html::encode($ie->investor->email) : '—';
                                    },
                                    'format' => 'raw',
                                ],
                                [
                                    'label'  => 'Type',
                                    'value'  => function($ie) {
                                        return $ie->investor ? Html::encode($ie->investor->getTypeName()) : '—';
                                    },
                                    'format' => 'raw',
                                ],
                                [
                                    'label'  => 'Net Value',
                                    'value'  => function($ie) {
                                        return $ie->investor ? $ie->investor->net_value : '—';
                                    },
                                ],
                                [
                                    'attribute' => 'assigned_at',
                                    'label'     => 'Assigned At',
                                    'value'     => function($ie) {
                                        return $ie->assigned_at ? date('Y-m-d H:i', $ie->assigned_at) : '—';
                                    },
                                ],
                            ],
                            'pager' => ['class' => 'yii\bootstrap4\LinkPager'],
                        ]) ?>
                        <?php Pjax::end() ?>
                    </div>

                    <!-- Tasks Tab -->
                    <div class="tab-pane fade" id="tab-tasks" role="tabpanel">
                        <?php Pjax::begin(['id' => 'pjax-tasks', 'enablePushState' => false, 'timeout' => 5000]) ?>
                        <?= GridView::widget([
                            'dataProvider' => $tasksProvider,
                            'tableOptions' => ['class' => 'table table-sm table-bordered table-striped'],
                            'columns'      => [
                                ['class' => 'yii\grid\SerialColumn'],
                                ['attribute' => 'title'],
                                ['attribute' => 'subject'],
                                [
                                    'attribute' => 'priority',
                                    'value'     => function($t) { return $t->getPriorityName(); },
                                    'filter'    => Task::getPriorityList(),
                                ],
                                [
                                    'attribute' => 'status',
                                    'value'     => function($t) { return $t->getStatusName(); },
                                    'filter'    => Task::getStatusList(),
                                ],
                                [
                                    'attribute' => 'due_date',
                                    'label'     => 'Due Date',
                                    'value'     => function($t) {
                                        return $t->due_date ? date('Y-m-d H:i', $t->due_date) : '—';
                                    },
                                ],
                            ],
                            'pager' => ['class' => 'yii\bootstrap4\LinkPager'],
                        ]) ?>
                        <?php Pjax::end() ?>
                    </div>

                </div>
            </div>
        </div>

    <?php endif; ?>
</div>