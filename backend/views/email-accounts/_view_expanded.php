<?php
use yii\helpers\Html;
use common\models\EmailAccount;

/* @var $this yii\web\View */
/* @var $model common\models\EmailAccount */
?>

<div class="card" style="border-left: 3px solid #6c757d;">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Email:</strong> <?= Html::encode($model->email) ?></p>
                <p><strong>Display Label:</strong> <?= $model->label ? Html::encode($model->label) : '<span class="text-muted">No label</span>' ?></p>
                <p><strong>Username:</strong> <?= Html::encode($model->username) ?></p>
                <?php /*
                <p><strong>Available for Employees:</strong>
                    <?php if ($model->is_corporate): ?>
                        <span class="badge badge-success">Yes</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">No</span>
                    <?php endif; ?>
                </p>
                */?>
                <p><strong>Status:</strong>
                    <?php if ($model->is_active): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Inactive</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-6">
                <p><strong>Created At:</strong> <?= date('m/d/Y H:i', $model->created_at) ?></p>
                <p><strong>Updated At:</strong> <?= date('m/d/Y H:i', $model->updated_at) ?></p>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6">
                <h6><strong>IMAP Settings (Receiving)</strong></h6>
                <p><strong>Host:</strong> <?= Html::encode($model->imap_host) ?></p>
                <p><strong>Port:</strong> <?= $model->imap_port ?></p>
                <p><strong>Encryption:</strong>
                    <span class="badge badge-info"><?= $model->getImapEncryptionName() ?></span>
                </p>
            </div>
            <div class="col-md-6">
                <h6><strong>SMTP Settings (Sending)</strong></h6>
                <p><strong>Host:</strong> <?= Html::encode($model->smtp_host) ?></p>
                <p><strong>Port:</strong> <?= $model->smtp_port ?></p>
                <p><strong>Encryption:</strong>
                    <span class="badge badge-info"><?= $model->getSmtpEncryptionName() ?></span>
                </p>
            </div>
        </div>

        <hr>
        <div class="row">
            <div class="col-md-12">
                <h6><strong>Assigned Administrators</strong></h6>
                <?php if (empty($assignedAdmins)): ?>
                    <span class="text-muted">No administrators assigned</span>
                <?php else: ?>
                    <?php foreach ($assignedAdmins as $admin): ?>
                        <span class="badge badge-secondary mr-1">
                    <?= Html::encode($admin['first_name'] . ' ' . $admin['last_name']) ?>
                </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-3">
            <?= Html::a(
                '<i class="fas fa-edit"></i> Edit Account',
                '#',
                [
                    'class' => 'btn btn-primary update-email-account-btn',
                    'data-id' => $model->id,
                ]
            ) ?>
            <?= Html::a(
                '<i class="fas fa-check-circle"></i> Test Connection',
                '#',
                [
                    'class' => 'btn btn-success test-connection-btn',
                    'data-id' => $model->id,
                ]
            ) ?>
            <?= Html::a(
                '<i class="fas fa-user-cog"></i> Assign Administrators',
                '#',
                [
                    'class' => 'btn btn-success assign-admins-btn',
                    'data-id' => $model->id,
                ]
            ) ?>
            <?= Html::a(
                '<i class="fas fa-times"></i> Close',
                '#',
                [
                    'class' => 'btn btn-secondary cancel-action',
                ]
            ) ?>
        </div>
    </div>
</div>