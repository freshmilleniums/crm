<?php
use backend\models\User;
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?= \yii\helpers\Url::to(['/site/index']) ?>" class="brand-link">
        <img src="<?=$assetDir?>/img/AdminLTELogo.png" alt="Employers CRM Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">Employers CRM</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            
            <div class="info">
                <a href="#" class="d-block">
                    <?= Yii::$app->user->identity->first_name ?? '' ?>
                    <?= Yii::$app->user->identity->last_name ?? '' ?>
                </a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <?php if (
                    Yii::$app->user->can('phone-operator') ||
                    Yii::$app->user->can('administrator') ||
                    Yii::$app->user->can('super-administrator')
                ): ?>
                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/call-center']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-phone-alt"></i>
                            <p>Call Center</p>
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
                        <a href="<?= \yii\helpers\Url::to(['/users']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Users</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- My Cabinet (for employee during training) -->
                <?php if (Yii::$app->user->can('employee')): ?>
                    <?php
                    $substatus = Yii::$app->user->identity->substatus;
                    $isActiveEmployee = ($substatus == User::SUBSTATUS_ACTIVE_EMPLOYEE);
                    ?>

                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/personal/training']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-graduation-cap"></i>
                            <p>Training</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/personal/tasks']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-tasks"></i>
                            <p>My Tasks</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/personal/projects']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-project-diagram"></i>
                            <p>My Projects</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/personal/investors']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-hand-holding-usd"></i>
                            <p>My Investors</p>
                        </a>
                    </li>


                    <?php if (!$isActiveEmployee): ?>

                    <?php endif; ?>
                <?php endif; ?>


                <?php
                if (
                    Yii::$app->user->can('email-task-operator') ||
                    Yii::$app->user->can('administrator') ||
                    Yii::$app->user->can('super-administrator')
                ) {  ?>
                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/tasks']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-tasks"></i>
                            <p>Tasks</p>
                        </a>
                    </li>
                <?php } ?>

                <?php
                if (
                    Yii::$app->user->can('administrator') ||
                    Yii::$app->user->can('super-administrator')
                ) { ?>
                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/projects']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-project-diagram"></i>
                            <p>Projects</p>
                        </a>
                    </li>
                <?php } ?>

                <?php if (Yii::$app->user->can('viewTemplatesList')): ?>
                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/templates']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>Templates</p>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (Yii::$app->user->can('viewInvestorsList')): ?>
                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/investors']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-hand-holding-usd"></i>
                            <p>Investors</p>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <?php $unreadEmailCount = \common\services\EmailUnreadService::getUnreadCount(Yii::$app->user->id); ?>
                    <a href="<?= \yii\helpers\Url::to(['/email']) ?>" class="nav-link">
                        <i class="nav-icon fas fa-envelope"></i>
                        <p>
                            Email
                            <?php if ($unreadEmailCount > 0): ?>
                                <span class="badge badge-danger right"><?= $unreadEmailCount > 99 ? '99+' : $unreadEmailCount ?></span>
                            <?php endif; ?>
                        </p>
                    </a>
                </li>



                <?php if (
                Yii::$app->user->can('employee') ||
                Yii::$app->user->can('administrator') ||
                Yii::$app->user->can('super-administrator') ||
                Yii::$app->user->can('email-task-operator')
                ): ?>
                <li class="nav-item">
                    <?php
                    $userRoles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
                    $chatUrl = isset($userRoles['employee'])
                        ? \yii\helpers\Url::to(['/my-chat'])
                        : \yii\helpers\Url::to(['/chat']);
                    $unreadCount = \backend\models\ChatMessageReadStatus::getUnreadCount();
                    ?>
                    <a href="<?= $chatUrl ?>" class="nav-link">
                        <i class="nav-icon far fa-comments"></i>
                        <p>
                            Messages
                            <?php if ($unreadCount > 0): ?>
                                <span class="badge badge-danger right"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                            <?php endif; ?>
                        </p>
                    </a>
                </li>
                <?php endif; ?>
                <!-- Email (for admins and email-task-operator) - COMMENTED UNTIL IMPLEMENTED -->
                <?php /*
                <?php if (
                    Yii::$app->user->can('super-administrator') ||
                    Yii::$app->user->can('administrator') ||
                    Yii::$app->user->can('email-task-operator')
                ): ?>
                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/email']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>Email</p>
                        </a>
                    </li>
                <?php endif; ?>
                */ ?>

                <!-- Settings Section -->
                <?php if (Yii::$app->user->can('viewEmailAccountsList')): ?>
                    <li class="nav-header">SETTINGS</li>

                    <?php if (
                        Yii::$app->user->can('super-administrator') ||
                        Yii::$app->user->can('administrator')
                    ): ?>
                        <li class="nav-item">
                            <a href="<?= \yii\helpers\Url::to(['/training']) ?>" class="nav-link">
                                <i class="nav-icon fas fa-chalkboard-teacher"></i>
                                <p>Training Modules</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= \yii\helpers\Url::to(['/call-center-settings']) ?>" class="nav-link">
                                <i class="nav-icon fas fa-headset"></i>
                                <p>Call Center Settings</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= \yii\helpers\Url::to(['/users/archive']) ?>" class="nav-link">
                                <i class="nav-icon fas fa-user-times"></i>
                                <p>Users Archive</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= \yii\helpers\Url::to(['/logs-action']) ?>" class="nav-link">
                                <i class="nav-icon fas fa-scroll "></i>
                                <p>Action Logs</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= \yii\helpers\Url::to(['/reminders']) ?>" class="nav-link">
                                <i class="nav-icon fas fa-bell"></i>
                                <p>Reminders</p>
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a href="<?= \yii\helpers\Url::to(['/email-accounts']) ?>" class="nav-link">
                            <i class="nav-icon fas fa-envelope-open-text"></i>
                            <p>Email Accounts</p>
                        </a>
                    </li>
                <?php endif; ?>


            </ul>
        </nav>
    </div>
</aside>