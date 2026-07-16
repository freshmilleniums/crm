<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user backend\models\User */
/* @var $progress backend\models\UserTrainingProgress */
/* @var $modules backend\models\TrainingModule[] */
/* @var $completedModules array */

$this->title = 'Training';
$this->params['breadcrumbs'][] = $this->title;

$totalModules = count($modules);
$completedCount = count($completedModules);
$progressPercentage = $totalModules > 0 ? ($completedCount / $totalModules) * 100 : 0;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <div class="alert alert-secondary">
                            <h4><i class="icon fas fa-secondary"></i> No Training Modules Available</h4>
                            <p>There are currently no training modules configured. Please check back later.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary mb-4">
                            <h5 class="mb-3"><i class="icon fas fa-graduation-cap"></i> Training Progress</h5>
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar"
                                     style="width: <?= $progressPercentage ?>%;"
                                     aria-valuenow="<?= $progressPercentage ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                    <?= round($progressPercentage, 1) ?>%
                                </div>
                            </div>
                            <p class="mb-0">
                                Completed: <strong><?= $completedCount ?></strong> of <strong><?= $totalModules ?></strong> modules
                            </p>
                        </div>

                        <div class="modules-list">
                            <?php foreach ($modules as $index => $module): ?>
                                <?php
                                $isCompleted = in_array($module->id, $completedModules);
                                $isCurrent = $module->id == $progress->current_module_id;
                                $isLocked = !$isCompleted && !$isCurrent;

                                $statusClass = $isCompleted ? 'completed' : ($isCurrent ? 'current' : 'locked');
                                $borderColor = $isCompleted ? '#28a745' : ($isCurrent ? '#007bff' : '#6c757d');
                                ?>

                                <div class="module-block <?= $statusClass ?>" style="border-left-color: <?= $borderColor ?>;">
                                    <div class="module-header mb-3">
                                        <h5 class="module-title">
                                            <span class="module-number badge badge-secondary mr-2"><?= $index + 1 ?></span>
                                            <?= Html::encode($module->title) ?>

                                            <?php if ($isCompleted): ?>
                                                <span class="badge badge-success ml-2">
                                                    <i class="fas fa-check"></i> Completed
                                                </span>
                                            <?php elseif ($isCurrent): ?>
                                                <span class="badge badge-secondary ml-2">
                                                    <i class="fas fa-play"></i> In Progress
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary ml-2">
                                                    <i class="fas fa-lock"></i> Locked
                                                </span>
                                            <?php endif; ?>
                                        </h5>
                                    </div>

                                    <div class="module-content">
                                        <?php if ($isCurrent): ?>
                                            <div class="module-actions">
                                                <?= Html::a(
                                                    '<i class="fas fa-book-open"></i> View Content',
                                                    ['module', 'id' => $module->id],
                                                    ['class' => 'btn btn-primary btn-sm mr-2']
                                                ) ?>

                                                <?php if ($module->is_final_task): ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-tasks"></i> Final task — check your tasks section
                                                    </span>
                                                <?php elseif ($module->getQuestionCount() > 0): ?>
                                                    <?= Html::a(
                                                        '<i class="fas fa-pencil-alt"></i> Take Test',
                                                        ['module-test', 'id' => $module->id],
                                                        ['class' => 'btn btn-primary btn-sm']
                                                    ) ?>
                                                <?php else: ?>
                                                    <span class="text-muted small">No test available</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($isCompleted): ?>
                                            <span class="text-success small">
                                                <i class="fas fa-check-circle"></i> Completed
                                            </span>
                                        <?php else: ?>
                                            <div class="text-muted">
                                                <i class="fas fa-lock"></i> Complete previous modules to unlock
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($index < count($modules) - 1): ?>
                                    <hr class="module-separator">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .module-block {
        background: #f4f4f4;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #6c757d;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .module-block.completed {
        background: #f0f9f4;
    }

    .module-block.current {
        background: #f0f5ff;
    }

    .module-block.locked {
        background: #f8f9fa;
        opacity: 0.7;
    }

    .module-title {
        color: #333;
        margin-bottom: 0;
        font-weight: 500;
    }

    .module-number {
        font-size: 0.875em;
    }

    .module-separator {
        margin: 20px 0;
        border-color: #dee2e6;
    }

    .progress {
        border-radius: 8px;
    }

    @media (max-width: 768px) {
        .module-block {
            padding: 15px;
            margin-bottom: 15px;
        }

        .module-title {
            font-size: 1.1rem;
        }

        .module-actions .btn {
            display: block;
            width: 100%;
            margin-bottom: 5px;
        }
    }
</style>