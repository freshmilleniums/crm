<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Project */
?>

<div class="card" style="border-left: 3px solid #6c757d;">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Project Name:</strong> <?= Html::encode($model->name) ?></p>
                <p><strong>Type:</strong> <?= $model->type ? Html::encode($model->getTypeName()) : '<span class="text-muted">Not set</span>' ?></p>
                <p><strong>Net Worth:</strong> <?= $model->net_worth ? number_format($model->net_worth, 2) : '<span class="text-muted">Not set</span>' ?></p>
                <p><strong>ROI:</strong> <?= $model->roi ? Html::encode($model->roi) . '%' : '<span class="text-muted">Not set</span>' ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> <?= $model->status ? Html::encode($model->getStatusName()) : '<span class="text-muted">Not set</span>' ?></p>
                <p><strong>Employee:</strong> <?= $model->getEmployeeName() ?? '<span class="text-muted">Not assigned</span>' ?></p>
                <p><strong>Created By:</strong> <?= $model->getCreatorName() ?? '<span class="text-muted">Unknown</span>' ?></p>
                <p><strong>Created At:</strong> <?= date('m/d/Y H:i', $model->created_at) ?></p>
                <p><strong>Updated At:</strong> <?= date('m/d/Y H:i', $model->updated_at) ?></p>
            </div>
        </div>

        <?php if (!empty($model->comment)): ?>
            <div class="mt-3">
                <p><strong>Comment:</strong></p>
                <div class="border rounded p-2" style="background-color: white;">
                    <?= nl2br(Html::encode($model->comment)) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($model->documents): ?>
            <div class="mt-3">
                <h6><strong>Files:</strong></h6>
                <div class="row">
                    <?php foreach ($model->documents as $document): ?>
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
                                        <div class="small text-truncate" style="max-width: 100%">
                                            <?= Html::encode($document->getFileName()) ?>
                                        </div>
                                    <?php else: ?>
                                        <i class="fas fa-file fa-3x text-secondary mb-2"></i>
                                        <div class="small text-truncate" style="max-width: 100%">
                                            <?= Html::encode($document->getFileName()) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-2">
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
        <?php else: ?>
            <div class="mt-3">
                <p class="text-muted">No documents attached</p>
            </div>
        <?php endif; ?>

        <div class="mt-3">
            <?= Html::a(
                '<i class="fas fa-user-plus"></i> Assign Employee',
                '#',
                [
                    'class' => 'btn btn-success',
                    'onclick' => 'showAssignEmployeeModal(event, ' . $model->id . '); return false;'
                ]
            ) ?>
            <?= Html::a(
                '<i class="fas fa-times"></i> Close',
                '#',
                [
                    'class' => 'btn btn-secondary',
                    'onclick' => '$(this).closest(".project-details-row").find(".project-details").slideUp(500, function() { $(this).closest(".project-details-row").remove(); }); return false;'
                ]
            ) ?>
        </div>
    </div>
</div>