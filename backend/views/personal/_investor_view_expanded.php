<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Investor */
?>

<div class="investor-details-content p-3" style="background-color: #f8f9fa;">
    <div class="row">
        <div class="col-md-6">
            <p><strong>Full Name:</strong> <?= Html::encode($model->getFullName()) ?></p>
            <p><strong>Email:</strong> <?= Html::encode($model->email) ?></p>
            <p><strong>Address:</strong> <?= $model->address ? Html::encode($model->address) : 'N/A' ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Type:</strong> <?= Html::encode($model->getTypeName()) ?></p>
            <p><strong>Net Value:</strong> <?= $model->net_value ? number_format($model->net_value, 2) : 'N/A' ?></p>
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