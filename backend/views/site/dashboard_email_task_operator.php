<?php

/** @var yii\web\View $this */
/** @var array $employeeStatusCounts */
/** @var array $taskStats */

$this->title = 'Dashboard Email/Task Operator';

$totalRelevant = array_sum(array_column($employeeStatusCounts, 'count'));
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
</style>

<div class="site-index">
    <div class="body-content">

        <!-- Employees by Status -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"><i class="fas fa-users"></i> Employees by Status</h5>
            </div>

            <?php if ($totalRelevant === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info">No employees in your work queue yet.</div>
                </div>
            <?php endif; ?>

            <?php foreach ($employeeStatusCounts as $data): ?>
                <div class="col-lg-2 col-6 dashboard-info-box-col">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-user"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= $data['label'] ?></span>
                            <span class="info-box-number"><?= $data['count'] ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Task Statistics -->
        <div class="row mb-4">
            <div class="col-lg-8 col-12">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-tasks"></i> My Tasks
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
        </div>

    </div>
</div>