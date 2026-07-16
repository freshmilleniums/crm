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