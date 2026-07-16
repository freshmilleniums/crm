<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\CallCenterScript */
?>

<div class="card" style="border-left: 3px solid #6c757d;">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Title:</strong> <?= Html::encode($model->title) ?></p>
                <p><strong>Status:</strong>
                    <?php if ($model->is_active): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Inactive</span>
                    <?php endif; ?>
                </p>
                <p><strong>Created At:</strong> <?= date('m/d/Y H:i', $model->created_at) ?></p>
            </div>
        </div>

        <div class="mt-3">
            <p><strong>Content:</strong></p>
            <div class="border rounded p-3" style="background-color: white; max-height: 400px; overflow-y: auto;">
                <?= $model->content ?>
            </div>
        </div>

        <div class="mt-3">
            <?= Html::a(
                '<i class="fas fa-times"></i> Close',
                '#',
                [
                    'class'   => 'btn btn-secondary',
                    'onclick' => '$(this).closest(".script-details-row").find(".script-details").slideUp(700, function() { $(this).closest(".script-details-row").remove(); }); return false;',
                ]
            ) ?>
        </div>
    </div>
</div>