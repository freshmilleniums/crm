<?php
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $employeeStatusCounts */
/** @var array $taskStats */
/** @var array $administratorsOverview */
/** @var array $callCenterLoad */
/** @var array $trainingSummary */
/** @var array $unassignedResources */
/** @var \common\services\EmployersDashboardStatisticsService $statsService */

$this->title = 'Dashboard Super Administrator';

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

    .progress-bar-load {
        height: 6px;
        border-radius: 3px;
    }

    .change-indicator {
        font-size: 0.8rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        justify-content: center;
    }

    .change-indicator i {
        font-size: 0.7rem;
    }
</style>

<div class="site-index">
    <div class="body-content">

        <!-- Employee Status Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"><i class="fas fa-users"></i> Employees by Status</h5>
            </div>

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

        <!-- Task Statistics -->
        <div class="row mb-4">
            <div class="col-lg-6 col-12 mb-4">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-tasks"></i> Task Statistics
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

            <!-- Training Progress Summary -->
            <div class="col-lg-6 col-12 mb-4">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-graduation-cap"></i> Training Progress
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
                            <?php foreach ($trainingSummary['by_module'] as $module): ?>
                                <tr>
                                    <td><?= Html::encode($module['title']) ?></td>
                                    <td class="text-center"><?= $module['count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="summary-row">
                                <td><strong>Total in Training</strong></td>
                                <td class="text-center"><strong><?= $trainingSummary['total_in_training'] ?></strong></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Administrators Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-user-tie"></i> Administrators Overview
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Administrator</th>
                                <th class="text-center">Total</th>
                                <?php foreach ($administratorsOverview[0]['status_counts'] ?? [] as $key => $data): ?>
                                    <th class="text-center"><?= $data['label'] ?></th>
                                <?php endforeach; ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($administratorsOverview as $admin): ?>
                                <tr>
                                    <td><?= Html::encode($admin['name']) ?></td>
                                    <td class="text-center"><strong><?= $admin['total_employees'] ?></strong></td>
                                    <?php foreach ($admin['status_counts'] as $data): ?>
                                        <td class="text-center"><?= $data['count'] ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($administratorsOverview)): ?>
                                <tr><td colspan="100%" class="text-muted text-center">No administrators found</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call Center Operators Load -->
        <div class="row mb-4">
            <div class="col-lg-7 col-12 mb-4">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-headset"></i> Call Center Operators Load
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Operator</th>
                                <th class="text-center">Assigned</th>
                                <th class="text-center">% of Total</th>
                                <th>Load</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($callCenterLoad['operators'] as $operator): ?>
                                <tr>
                                    <td><?= Html::encode($operator['name']) ?></td>
                                    <td class="text-center"><?= $operator['assigned_count'] ?></td>
                                    <td class="text-center"><?= $operator['percent'] ?>%</td>
                                    <td>
                                        <div class="progress progress-bar-load">
                                            <div class="progress-bar bg-secondary" style="width: <?= $operator['percent'] ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="summary-row">
                                <td><strong>Total Employees</strong></td>
                                <td class="text-center"><strong><?= $callCenterLoad['total_candidates'] ?></strong></td>
                                <td class="text-center" colspan="2">
                                    <?php if ($callCenterLoad['unassigned'] > 0): ?>
                                        <span class="text-danger"><?= $callCenterLoad['unassigned'] ?> unassigned</span>
                                    <?php else: ?>
                                        <span class="text-success">All assigned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Unassigned Resources -->
            <div class="col-lg-5 col-12 mb-4">
                <div class="row">
                    <div class="col-6 dashboard-info-box-col">
                        <div class="info-box">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-hand-holding-usd"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Unassigned Investors</span>
                                <span class="info-box-number"><?= $unassignedResources['unassigned_investors'] ?></span>
                                <div class="stats-rate">of <?= $unassignedResources['total_investors'] ?> total</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 dashboard-info-box-col">
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

    </div>
</div>