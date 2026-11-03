<?php
use yii\helpers\Html;
use yii\helpers\Url;
use backend\models\ChatMessageReadStatus;
?>

<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Logo -->
    <a href="<?= \yii\helpers\Url::to(['/site/index']) ?>" class="navbar-brand">
        <img src="<?= $assetDir ?>/img/AdminLTELogo.png" alt="Logo"
             class="brand-image img-circle elevation-3" style="opacity:.8; width:33px;">
        <span class="brand-text font-weight-light">Employers CRM</span>
    </a>

    <!-- Left navbar links -->
    <ul class="navbar-nav">

        <?php if (
            Yii::$app->user->can('phone-operator') ||
            Yii::$app->user->can('administrator') ||
            Yii::$app->user->can('super-administrator')
        ): ?>
            <li class="nav-item">
                <a href="<?= Url::to(['/call-center']) ?>" class="nav-link">
                    <i class="fas fa-phone-alt mr-1"></i> Call Center
                </a>
            </li>
        <?php endif; ?>

        <?php if (
            Yii::$app->user->can('super-administrator') ||
            Yii::$app->user->can('administrator') ||
            Yii::$app->user->can('phone-operator') ||
            Yii::$app->user->can('email-task-operator')
        ): ?>
            <li class="nav-item">
                <a href="<?= Url::to(['/users']) ?>" class="nav-link">
                    <i class="fas fa-users mr-1"></i> Users
                </a>
            </li>
        <?php endif; ?>

        <?php if (
            Yii::$app->user->can('email-task-operator') ||
            Yii::$app->user->can('administrator') ||
            Yii::$app->user->can('super-administrator')
        ): ?>
            <li class="nav-item">
                <a href="<?= Url::to(['/tasks']) ?>" class="nav-link">
                    <i class="fas fa-tasks mr-1"></i> Tasks
                </a>
            </li>
        <?php endif; ?>

        <?php if (
            Yii::$app->user->can('administrator') ||
            Yii::$app->user->can('super-administrator')
        ): ?>
            <li class="nav-item">
                <a href="<?= Url::to(['/projects']) ?>" class="nav-link">
                    <i class="fas fa-project-diagram mr-1"></i> Projects
                </a>
            </li>
        <?php endif; ?>

        <?php if (Yii::$app->user->can('viewTemplatesList')): ?>
            <li class="nav-item">
                <a href="<?= Url::to(['/templates']) ?>" class="nav-link">
                    <i class="fas fa-file-alt mr-1"></i> Templates
                </a>
            </li>
        <?php endif; ?>

        <?php if (Yii::$app->user->can('viewInvestorsList')): ?>
            <li class="nav-item">
                <a href="<?= Url::to(['/investors']) ?>" class="nav-link">
                    <i class="fas fa-hand-holding-usd mr-1"></i> Investors
                </a>
            </li>
        <?php endif; ?>

        <li class="nav-item">
            <?php $unreadEmailCount = \common\services\EmailUnreadService::getUnreadCount(Yii::$app->user->id); ?>
            <a href="<?= Url::to(['/email']) ?>" class="nav-link">
                <i class="fas fa-envelope mr-1"></i> Email
                <?php if ($unreadEmailCount > 0): ?>
                    <span class="badge badge-danger navbar-badge">
                        <?= $unreadEmailCount > 99 ? '99+' : $unreadEmailCount ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>

        <?php if (Yii::$app->user->can('viewEmailAccountsList')): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fas fa-cog mr-1"></i> Settings
                </a>
                <div class="dropdown-menu">
                    <?php if (
                        Yii::$app->user->can('super-administrator') ||
                        Yii::$app->user->can('administrator')
                    ): ?>
                        <a href="<?= Url::to(['/training']) ?>" class="dropdown-item">
                            <i class="fas fa-chalkboard-teacher mr-2"></i> Training Modules
                        </a>
                        <a href="<?= Url::to(['/call-center-settings']) ?>" class="dropdown-item">
                            <i class="fas fa-headset mr-2"></i> Call Center Settings
                        </a>
                        <a href="<?= Url::to(['/users/archive']) ?>" class="dropdown-item">
                            <i class="fas fa-user-times mr-2"></i> Users Archive
                        </a>
                        <a href="<?= Url::to(['/logs-action']) ?>" class="dropdown-item">
                            <i class="fas fa-scroll mr-2"></i> Action Logs
                        </a>
                        <a href="<?= Url::to(['/reminders']) ?>" class="dropdown-item">
                            <i class="fas fa-bell mr-2"></i> Reminders
                        </a>
                        <div class="dropdown-divider"></div>
                    <?php endif; ?>
                    <a href="<?= Url::to(['/email-accounts']) ?>" class="dropdown-item">
                        <i class="fas fa-envelope-open-text mr-2"></i> Email Accounts
                    </a>
                </div>
            </li>
        <?php endif; ?>

    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

        <!-- Notifications -->
        <li class="nav-item dropdown">
            <?= \backend\widgets\notificationDropdown\NotificationDropdownWidget::widget() ?>
        </li>

        <?php
        $userRoles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
        $chatUrl = isset($userRoles['employee'])
            ? Url::to(['/my-chat'])
            : Url::to(['/chat']);

        if (
            isset($userRoles['employee']) ||
            isset($userRoles['administrator']) ||
            isset($userRoles['super-administrator']) ||
            isset($userRoles['email-task-operator'])
        ):

        $unreadCount = \backend\models\ChatMessageReadStatus::getUnreadCount(); ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= $chatUrl ?>">
                <i class="far fa-comments"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge badge-danger navbar-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item d-flex align-items-center mr-3 ml-3">
            <div class="info">
                <span class="text-dark">
                    <i class="far fa-user mr-1"></i>
                    <?= Yii::$app->user->identity->first_name ?? '' ?>
                    <?= Yii::$app->user->identity->last_name ?? '' ?>
                </span>
            </div>
        </li>

        <li class="nav-item">
            <?= Html::a('<i class="fas fa-sign-out-alt"></i>', ['/site/logout'], [
                'data-method' => 'post',
                'class' => 'nav-link',
                'title' => 'Logout'
            ]) ?>
        </li>

        <!-- Control Sidebar -->
        <li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                <i class="fas fa-th-large"></i>
            </a>
        </li>
    </ul>
</nav>