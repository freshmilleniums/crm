<?php

/** @var yii\web\View $this */
/** @var array $trainingProgress */
/** @var array $taskStats */
/** @var array $investorProject */
/** @var \backend\models\User $user */

use yii\helpers\Html;
use backend\models\User;

$this->title = 'Dashboard';

/*$isInTraining = in_array($user->substatus, [
    User::SUBSTATUS_TRAINING_IN_PROGRESS,
    User::SUBSTATUS_FINAL_ASSIGNMENT,
    User::SUBSTATUS_UNCOMPLETED_TASK,
]);*/

$isInTraining = true;

$isActiveEmployee = $user->substatus === User::SUBSTATUS_ACTIVE_EMPLOYEE;
?>

<style>
    .stats-table {
        background: white;
        border-radius: 0.375rem;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .stats-table .table-header {
        background: #6c757d;
        color: white;
        padding: 1rem 1.5rem;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .stats-table .table {
        margin-bottom: 0;
    }

    .stats-table .table thead th {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        color: #495057;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.75rem;
        vertical-align: middle;
    }

    .stats-table .table tbody td {
        padding: 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
    }

    .stats-table .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .summary-row {
        background: #f8f9fa !important;
        border-top: 2px solid #6c757d !important;
    }

    .summary-row td {
        color: #495057;
        font-weight: 600;
        border-bottom: none !important;
    }

    .training-progress-bar {
        height: 25px;
        border-radius: 8px;
    }

    .module-current-block {
        background: #f0f5ff;
        border-left: 4px solid #6c757d;
        border-radius: 0.375rem;
        padding: 1rem 1.25rem;
    }

    .project-card {
        border-left: 4px solid #6c757d;
        padding: 1rem 1.25rem;
        background: white;
        border-radius: 0.375rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .project-card .project-name {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .project-meta {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .module-current-block.completed {
        background: #f0f9f4;
        border-left-color: #28a745;
    }

</style>

<div class="site-index">
    <div class="body-content">

        <?php if ($isInTraining): ?>
            <!-- Training Progress -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="stats-table">
                        <div class="table-header">
                            <i class="fas fa-graduation-cap"></i> My Training Progress
                        </div>
                        <div class="card-body">
                            <?php if ($trainingProgress['completed'] >= $trainingProgress['total']): ?>
                                <!-- Training Completed -->
                                <div class="module-current-block completed">
                                    <span class="badge badge-success mr-2"><i class="fas fa-check"></i> Completed</span>
                                    <strong>Training completed</strong>
                                </div>
                            <?php elseif ($trainingProgress['current_module']): ?>

                                <!-- Progress Bar -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Completed: <strong><?= $trainingProgress['completed'] ?></strong> of <strong><?= $trainingProgress['total'] ?></strong> modules</span>
                                        <strong><?= $trainingProgress['percent'] ?>%</strong>
                                    </div>
                                    <div class="progress training-progress-bar">
                                        <div class="progress-bar bg-success"
                                             role="progressbar"
                                             style="width: <?= $trainingProgress['percent'] ?>%"
                                             aria-valuenow="<?= $trainingProgress['percent'] ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>

                                <!-- Current Module -->
                                <div class="module-current-block">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge badge-secondary mr-2">In Progress</span>
                                            <strong><?= Html::encode($trainingProgress['current_module']->title) ?></strong>
                                        </div>
                                        <div>
                                            <?= Html::a(
                                                '<i class="fas fa-book-open"></i> Continue',
                                                ['/personal/module', 'id' => $trainingProgress['current_module']->id],
                                                ['class' => 'btn btn-secondary btn-sm']
                                            ) ?>
                                        </div>
                                    </div>

                                    <?php if ($trainingProgress['attempts'] > 0): ?>
                                        <div class="mt-2 text-muted" style="font-size: 0.85rem;">
                                            Attempts: <?= $trainingProgress['attempts'] ?>
                                            <?php if ($trainingProgress['last_score'] > 0): ?>
                                                &nbsp;&bull;&nbsp; Last score: <?= $trainingProgress['last_score'] ?>%
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php else: ?>
                                <p class="text-muted mb-0">Training not started yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isActiveEmployee): ?>

            <!-- My Tasks -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3"><i class="fas fa-tasks"></i> My Tasks</h5>
                </div>

                <div class="col-lg-3 col-6 dashboard-info-box-col">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-plus"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">New</span>
                            <span class="info-box-number"><?= $taskStats['today']['new'] ?></span>
                            <div class="stats-rate">today</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6 dashboard-info-box-col">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">In Progress</span>
                            <span class="info-box-number"><?= $taskStats['today']['in_progress'] ?></span>
                            <div class="stats-rate">today</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6 dashboard-info-box-col">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-check"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Completed</span>
                            <span class="info-box-number"><?= $taskStats['today']['completed'] ?></span>
                            <div class="stats-rate">today</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6 dashboard-info-box-col">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-calendar-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total This Month</span>
                            <span class="info-box-number"><?= $taskStats['month']['total'] ?></span>
                            <div class="stats-rate">this month</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Investors & Project -->
            <div class="row mb-4">

                <!-- Investors -->
                <div class="col-lg-6 col-12 mb-4">
                    <div class="stats-table">
                        <div class="table-header">
                            <i class="fas fa-hand-holding-usd"></i> My Investors
                            <?php if (!empty($investorProject['investors'])): ?>
                                <span class="badge badge-light ml-2"><?= count($investorProject['investors']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th class="text-right">Net Value</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($investorProject['investors'])): ?>
                                    <tr>
                                        <td colspan="3" class="text-muted text-center">No investors assigned yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($investorProject['investors'] as $investor): ?>
                                        <tr>
                                            <td><?= Html::encode($investor->getFullName()) ?></td>
                                            <td><?= Html::encode($investor->email) ?></td>
                                            <td class="text-right">
                                                <?= $investor->net_value
                                                    ? '$' . number_format($investor->net_value, 2)
                                                    : '—' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Current Project -->
                <div class="col-lg-6 col-12 mb-4">
                    <div class="stats-table">
                        <div class="table-header">
                            <i class="fas fa-project-diagram"></i> My Project
                        </div>
                        <div class="card-body">
                            <?php if ($investorProject['project']): ?>
                                <?php $project = $investorProject['project']; ?>
                                <div class="project-card">
                                    <div class="project-name mb-2">
                                        <?= Html::encode($project->name) ?>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="project-meta">
                                                <strong>Type:</strong> <?= Html::encode($project->getTypeName()) ?>
                                            </div>
                                            <div class="project-meta">
                                                <strong>Status:</strong> <?= Html::encode($project->getStatusName()) ?>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="project-meta">
                                                <strong>Net Worth:</strong>
                                                <?= $project->net_worth
                                                    ? '$' . number_format($project->net_worth, 2)
                                                    : '—' ?>
                                            </div>
                                            <div class="project-meta">
                                                <strong>ROI:</strong>
                                                <?= $project->roi ? $project->roi . '%' : '—' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($project->comment): ?>
                                        <div class="project-meta mt-2">
                                            <strong>Comment:</strong> <?= Html::encode($project->comment) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No project assigned yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>