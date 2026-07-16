<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */

$this->title = 'Email';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-envelope-open-text fa-5x text-muted mb-4"></i>
                    <h3>No Email Accounts Available</h3>
                    <p class="text-muted">
                        You don't have any email accounts configured yet.
                    </p>

                    <?php
                    $userRoles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
                    if (isset($userRoles['super-administrator']) || isset($userRoles['administrator'])):
                        ?>
                        <p>
                            <?= Html::a(
                                '<i class="fas fa-plus"></i> Add Email Account',
                                ['email-accounts/index'],
                                ['class' => 'btn btn-primary']
                            ) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-muted">
                            Please contact your administrator to set up an email account.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>