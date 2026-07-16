<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Project */
?>

<div class="project-details-content p-3" style="background-color: #f8f9fa;">
    <div class="row">
        <div class="col-md-6">
            <p><strong>Project Name:</strong> <?= Html::encode($model->name) ?></p>
            <p><strong>Type:</strong> <?= Html::encode($model->getTypeName()) ?></p>
            <p><strong>Net Worth:</strong> <?= $model->net_worth ? number_format($model->net_worth, 2) : 'N/A' ?></p>
            <p><strong>ROI:</strong> <?= $model->roi ? Html::encode($model->roi) . '%' : 'N/A' ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Status:</strong> <?= Html::encode($model->getStatusName()) ?></p>
            <p><strong>Created:</strong> <?= Yii::$app->formatter->asDatetime($model->created_at) ?></p>
            <p><strong>Updated:</strong> <?= Yii::$app->formatter->asDatetime($model->updated_at) ?></p>
        </div>
    </div>

    <?php if ($model->comment): ?>
        <div class="mt-3">
            <p><strong>Comment:</strong></p>
            <div class="p-2 border rounded" style="background-color: white;">
                <?= nl2br(Html::encode($model->comment)) ?>
            </div>
        </div>
    <?php endif; ?>

</div>