<?php
use yii\helpers\Html;
use common\models\Template;
use backend\helpers\DocumentHelper;

/* @var $this yii\web\View */
/* @var $model common\models\Template */
?>

<div class="card" style="border-left: 3px solid #6c757d;">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Title:</strong> <?= Html::encode($model->title) ?></p>
                <p><strong>Category:</strong> <?= Html::encode($model->getCategoryName()) ?></p>
                <p><strong>Subject:</strong> <?= $model->subject ? Html::encode($model->subject) : '<span class="text-muted">No subject</span>' ?></p>
                <p><strong>Created By:</strong> <?= $model->getCreatorName() ?? '<span class="text-muted">Unknown</span>' ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Created At:</strong> <?= date('m/d/Y H:i', $model->created_at) ?></p>
                <p><strong>Updated At:</strong> <?= date('m/d/Y H:i', $model->updated_at) ?></p>
            </div>
        </div>

        <div class="mt-3">
            <p><strong>Body:</strong></p>
            <div class="border rounded p-3" style="background-color: white; max-height: 400px; overflow-y: auto;">
                <?= $model->body ?>
            </div>
        </div>

        <?php if ($model->documents): ?>
            <div class="mt-3">
                <h6><strong>Attached Files:</strong></h6>
                <div class="row">
                    <?php foreach ($model->documents as $document): ?>
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3">
                            <div class="card shadow-sm" style="height: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 8px;">
                                <div class="card-body p-1 d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 150px;">

                                    <?= DocumentHelper::renderPreview($document) ?>

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
            <?php /* Html::button(
                '<i class="fas fa-edit"></i> Edit Template',
                [
                    'class' => 'btn btn-primary edit-template-btn',
                    'data-template-id' => $model->id
                ]
            ) */?>
            <?= Html::a(
                '<i class="fas fa-times"></i> Close',
                '#',
                [
                    'class' => 'btn btn-secondary',
                    'onclick' => '$(this).closest(".template-details-row").find(".template-details").slideUp(700, function() { $(this).closest(".template-details-row").remove(); }); return false;'
                ]
            ) ?>
        </div>
    </div>
</div>