<?php
use yii\helpers\Html;
use yii\helpers\Url;
use backend\models\ChatMessageReadStatus;
?>

<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
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