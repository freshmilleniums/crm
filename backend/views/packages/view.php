<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\Packages;

/* @var $this yii\web\View */
/* @var $model common\models\Packages */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Packages', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">

                        <?= DetailView::widget([
                            'model' => $model,
                            'options' => ['class' => 'table table-bordered detail-view'],
                            'attributes' => [
                                'name',
                                'description',
                                'address',
                                [
                                    'attribute' => 'courier_id',
                                    'label' => 'Courier',
                                    'value' => $model->getCourierName() ?: 'Not assigned',
                                ],
                                [
                                    'attribute' => 'status',
                                    'value' => $model->getStatusName(),
                                ],
                                [
                                    'attribute' => 'created_at',
                                    'value' => date('d.m.Y H:i', $model->created_at),
                                ],
                                /*[
                                    'attribute' => 'delivered_at',
                                    'value' => $model->delivered_at ? date('d.m.Y H:i', $model->delivered_at) : null,
                                ],*/
                                [
                                    'attribute' => 'delivery_date',
                                    'value' => $model->delivery_date ? date('d.m.Y H:i', strtotime($model->delivery_date)) : null,
                                ],
                                'track',
                                [
                                    'attribute' => 'post',
                                    'value' => $model->getPostName(),
                                ],
                                'weight',
                                'comment',
                            ],
                        ]) ?>

                        <!-- Package Documents Section (if delivered or completed) -->
                        <?php if ($model->documents && ($model->status == Packages::STATUS_DELIVERED || $model->status == Packages::STATUS_COMPLETED)): ?>
                            <div class="mt-4">
                                <h5>Uploaded Documents</h5>
                                <div class="row">
                                    <?php foreach ($model->documents as $document): ?>
                                        <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3">
                                            <div class="card shadow-sm" style="height: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 8px;">
                                                <div class="card-body p-1 d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 180px;">
                                                    <?php if ($document->isImage()): ?>
                                                        <a href="<?= $document->getUrl() ?>" target="_blank">
                                                            <img src="<?= $document->getUrl() ?>"
                                                                 class="img-thumbnail mb-2"
                                                                 style="max-width: 100%;"
                                                                 alt="Document">
                                                        </a>
                                                    <?php elseif ($document->isPdf()): ?>
                                                        <i class="fas fa-file-pdf fa-2x text-danger mb-1"></i>
                                                        <div class="small text-truncate" style="max-width: 100%">
                                                            <?= Html::encode($document->getFileName()) ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <i class="fas fa-file fa-2x text-secondary mb-1"></i>
                                                        <div class="small text-truncate" style="max-width: 100%">
                                                            <?= Html::encode($document->getFileName()) ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="mt-1">
                                                        <?= Html::a(
                                                            '<i class="fas fa-download"></i>',
                                                            ['download-document', 'id' => $document->id],
                                                            ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'Download']
                                                        ) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <?php if ($model->status == Packages::STATUS_DELIVERED): ?>

                            <?= Html::a(
                                '<i class="fas fa-undo"></i> Return to Work',
                                ['return-to-work', 'id' => $model->id],
                                [
                                    'class' => 'btn btn-warning mr-2',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to return this package to work? The courier will be able to modify it again.',
                                        'method' => 'post',
                                    ],
                                ]
                            ) ?>

                            <?= Html::a(
                                '<i class="fas fa-check-circle"></i> Complete Package',
                                ['complete-package', 'id' => $model->id],
                                [
                                    'class' => 'btn btn-success',
                                    'data' => [
                                        'confirm' => 'Are you sure you want to complete this package? This action cannot be undone.',
                                        'method' => 'post',
                                    ],
                                ]
                            ) ?>

                        <?php endif; ?>

                    </div>
                    <!--.col-md-12-->
                </div>
                <!--.row-->
            </div>
            <!--.card-body-->
        </div>
        <!--.card-->
    </div>

<?php
$css = <<<'EOD'
.img-thumbnail {
    cursor: pointer;
}

.card .card-body {
    min-height: 180px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.btn-group-sm > .btn, .btn-sm {
    margin: 2px;
}
EOD;

$this->registerCss($css);
?>