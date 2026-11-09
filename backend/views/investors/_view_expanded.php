<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Investor */
?>

<div class="investor-view">
    <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-bordered detail-view'],
        'attributes' => [
            'id',
            [
                'attribute' => 'first_name',
                'value' => $model->getFullName(),
                'label' => 'Full Name',
            ],
            'email:email',
            'address',
            [
                'attribute' => 'net_value',
                'format' => ['decimal', 2],
            ],
            [
                'attribute' => 'investor_type',
                'value' => $model->getTypeName(),
            ],
            'comment:ntext',
            [
                'attribute' => 'created_by',
                'value' => $model->creator ? $model->creator->getFullName() : 'N/A',
                'label' => 'Created By',
            ],
            [
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:Y-m-d H:i:s'],
            ],
            [
                'attribute' => 'updated_at',
                'format' => ['datetime', 'php:Y-m-d H:i:s'],
            ],
            [
                'label' => 'SMTP Host',
                'value' => $model->smtp_host ?: '—',
            ],
            [
                'label' => 'SMTP Port / Encryption',
                'value' => $model->smtp_host
                    ? $model->smtp_port . ' / ' . $model->getSmtpEncryptionName()
                    : '—',
            ],
            [
                'label' => 'SMTP Username',
                'value' => $model->smtp_username ?: '—',
            ],
            [
                'label' => 'SMTP From Email',
                'value' => $model->smtp_host ? $model->getSmtpFromEmail() : '—',
            ],
            [
                'label' => 'SMTP Password',
                'value' => $model->smtp_password ? '••••••••' : '—',
            ],
        ],
    ]) ?>

    <?php if (!empty($model->employees)): ?>
        <h5 class="mt-3">Assigned Employees</h5>
        <table class="table table-sm table-bordered">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($model->employees as $employee): ?>
                <tr>
                    <td><?= Html::encode($employee->getFullName()) ?></td>
                    <td><?= Html::encode($employee->email) ?></td>
                    <td><?= Html::encode($employee->phone_number) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-muted mt-3">No employees assigned to this investor.</p>
    <?php endif; ?>

    <div class="mt-3">
        <?php if (Yii::$app->user->can('assignInvestorToEmployee')): ?>
            <?= Html::button('<i class="fas fa-users"></i> Assign Employees', [
                'class' => 'btn btn-primary assign-employees-btn',
                'data-id' => $model->id,
            ]) ?>
        <?php endif; ?>

        <?= Html::button('Close', [
            'class' => 'btn btn-secondary cancel-action ml-2'
        ]) ?>
    </div>
</div>