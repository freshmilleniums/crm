<?php

/** @var yii\web\View $this */
/** @var array $employeeStatusCounts */
/** @var array $taskStats */
/** @var array $trainingSummary */
/** @var array $unassignedResources */

use yii\helpers\Html;

$this->title = 'Dashboard Administrator';

$totalEmployees = array_sum(array_column($employeeStatusCounts, 'count'));
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

    .stuck-badge {
        background-color: #ffc107;
        color: #212529;
    }

    .summary-row {
        background: #f8f9fa !important;
        border-top: 2px solid #6c757d !important;
        font-weight: 600;
    }

    .summary-row td {
        color: #495057;
        font-weight: 600;
        border-bottom: none !important;
    }
</style>

<div class="site-index">
    <div class="body-content">

        <!-- My Employees by Status -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"><i class="fas fa-users"></i> My Employees by Status</h5>
            </div>

            <?php if ($totalEmployees === 0): ?>
                <div class="col-12">
                    <div class="alert alert-secondary">No employees assigned to you yet.</div>
                </div>
            <?php endif; ?>

            <?php foreach ($employeeStatusCounts as $groupKey => $data): ?>
                <div class="col-lg-3 col-6 dashboard-info-box-col">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-user"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= $data['label'] ?></span>
                            <span class="info-box-number"><?= $data['count'] ?></span>
                            <?php if ($totalEmployees > 0): ?>
                                <div class="stats-rate"><?= round($data['count'] / $totalEmployees * 100, 1) ?>% of total</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row mb-4">
            <!-- Task Statistics -->
            <div class="col-lg-6 col-12 mb-4">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-tasks"></i> Tasks for My Employees
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Period</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">New</th>
                                <th class="text-center">In Progress</th>
                                <th class="text-center">Completed</th>
                                <th class="text-center">On Hold</th>
                                <th class="text-center">Cancelled</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach (['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $period => $label): ?>
                                <tr>
                                    <td><strong><?= $label ?></strong></td>
                                    <td class="text-center"><?= $taskStats[$period]['total'] ?></td>
                                    <td class="text-center"><?= $taskStats[$period]['new'] ?></td>
                                    <td class="text-center"><?= $taskStats[$period]['in_progress'] ?></td>
                                    <td class="text-center"><?= $taskStats[$period]['completed'] ?></td>
                                    <td class="text-center"><?= $taskStats[$period]['on_hold'] ?></td>
                                    <td class="text-center"><?= $taskStats[$period]['cancelled'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Training Progress -->
            <div class="col-lg-6 col-12 mb-4">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-graduation-cap"></i> My Employees in Training
                        <?php if ($trainingSummary['stuck_count'] > 0): ?>
                            <span class="badge stuck-badge ml-2"><?= $trainingSummary['stuck_count'] ?> stuck (24h+)</span>
                        <?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Module</th>
                                <th class="text-center">Employees</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($trainingSummary['total_in_training'] === 0): ?>
                                <tr>
                                    <td colspan="2" class="text-muted text-center">No Employees currently in training</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($trainingSummary['by_module'] as $module): ?>
                                    <?php if ($module['count'] > 0): ?>
                                        <tr>
                                            <td><?= Html::encode($module['title']) ?></td>
                                            <td class="text-center"><?= $module['count'] ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <tr class="summary-row">
                                    <td><strong>Total</strong></td>
                                    <td class="text-center"><strong><?= $trainingSummary['total_in_training'] ?></strong></td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unassigned Resources -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"> Resources Needing Attention</h5>
            </div>
            <div class="col-lg-3 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-hand-holding-usd"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Unassigned Investors</span>
                        <span class="info-box-number"><?= $unassignedResources['unassigned_investors'] ?></span>
                        <div class="stats-rate">of <?= $unassignedResources['total_investors'] ?> total</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-project-diagram"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Unassigned Projects</span>
                        <span class="info-box-number"><?= $unassignedResources['unassigned_projects'] ?></span>
                        <div class="stats-rate">of <?= $unassignedResources['total_projects'] ?> total</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>