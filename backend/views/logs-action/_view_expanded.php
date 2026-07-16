<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\ActionLog */

$details = $model->details;
$differences = $details ? $details->getFormattedDifference($model->getEntityAttributeLabels()) : [];
?>

<div class="log-details-content" style="padding: 15px;">
    <div class="row">

        <!-- Left: Log info -->
        <div class="col-md-4">
            <table class="table table-sm table-bordered">
                <tbody>
                <tr>
                    <th>User</th>
                    <td><?= $model->user ? Html::encode($model->user->getFullName()) : '-' ?></td>
                </tr>
                <tr>
                    <th>Action</th>
                    <td>
                        <?php
                        $actionClass = match($model->action) {
                            'create' => 'badge badge-success',
                            'delete' => 'badge badge-danger',
                            default => 'badge badge-warning',
                        };
                        ?>
                        <span class="<?= $actionClass ?>">
                                <?= Html::encode($model->getActionName()) ?>
                            </span>
                    </td>
                </tr>
                <tr>
                    <th>Entity</th>
                    <td><?= Html::encode($model->getEntityTypeName()) ?> #<?= $model->entity_id ?></td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td><?= date('m/d/Y H:i', $model->created_at) ?></td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Right: Changes -->
        <div class="col-md-8">
            <?php if (!empty($differences)): ?>
                <table class="table table-sm table-bordered table-striped">
                    <thead>
                    <tr>
                        <th>Field</th>
                        <th>Was</th>
                        <th>Became</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($differences as $diff): ?>
                        <tr>
                            <td><strong><?= Html::encode($diff['label']) ?></strong></td>
                            <td class="text-danger"><?= $diff['old'] ?></td>
                            <td class="text-success"><?= $diff['new'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($details): ?>
                <p class="text-muted">No field changes recorded.</p>
            <?php else: ?>
                <p class="text-muted">No details available.</p>
            <?php endif; ?>
        </div>

    </div>

    <div class="row mt-2">
        <div class="col-md-12">
            <button class="btn btn-sm btn-secondary cancel-action">Close</button>
        </div>
    </div>
</div>