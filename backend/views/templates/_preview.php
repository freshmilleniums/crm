<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Template */
/* @var $user backend\models\User */
/* @var $subject string */
/* @var $body string */
?>

<div class="template-preview">
    <div class="alert alert-info">
        <p><strong>Preview for:</strong> <?= Html::encode($user->getFullName()) ?> (<?= Html::encode($user->email) ?>)</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><?= Html::encode($model->title) ?></h5>
        </div>
        <div class="card-body">
            <?php if ($subject): ?>
                <p><strong>Subject:</strong> <?= Html::encode($subject) ?></p>
                <hr>
            <?php endif; ?>

            <div class="border rounded p-3" style="background-color: white;">
                <?= $body ?>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <?= Html::button('Close', [
            'class' => 'btn btn-secondary',
            'data-dismiss' => 'modal'
        ]) ?>
    </div>
</div>