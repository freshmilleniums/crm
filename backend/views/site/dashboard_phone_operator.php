<?php

/** @var yii\web\View $this */
/** @var array $employeeStatusCounts */
/** @var array $scheduledCalls */
/** @var \common\services\EmployersDashboardStatisticsService $statsService */

use yii\helpers\Html;

$this->title = 'Dashboard Phone Operator';

// Phone operator only works with these status groups
$relevantGroups = ['not_processed', 'new_applicants', 'primary_contact', 'interview_completed'];

$totalRelevant = 0;
foreach ($relevantGroups as $groupKey) {
    $totalRelevant += $employeeStatusCounts[$groupKey]['count'] ?? 0;
}

$completionRate = $scheduledCalls['today_total'] > 0
    ? round($scheduledCalls['today_done'] / $scheduledCalls['today_total'] * 100, 1)
    : 0;
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

    .completion-rate-cell {
        font-weight: 600;
    }

    .reminder-row.urgent {
        background-color: #fff3cd;
    }

    .reminder-time {
        font-weight: 600;
    }

    .reminder-bucket-badge {
        font-size: 0.75rem;
    }
</style>

<div class="site-index">
    <div class="body-content">

        <!-- My Work Queue -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"><i class="fas fa-headset"></i> My Work Queue</h5>
            </div>

            <?php foreach ($relevantGroups as $groupKey): ?>
                <?php $data = $employeeStatusCounts[$groupKey]; ?>
                <div class="col-lg-3 col-6 dashboard-info-box-col">
                    <div class="info-box">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-user"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text"><?= $data['label'] ?></span>
                            <span class="info-box-number"><?= $data['count'] ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($totalRelevant === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info">No employees assigned to you yet.</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row mb-4">
            <!-- Scheduled Calls Stats -->
            <div class="col-lg-6 col-12 mb-4">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-phone-alt"></i> Scheduled Calls - Today
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Metric</th>
                                <th class="text-center">Value</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Total Scheduled Today</td>
                                <td class="text-center"><?= $scheduledCalls['today_total'] ?></td>
                            </tr>
                            <tr>
                                <td>Completed Today</td>
                                <td class="text-center"><?= $scheduledCalls['today_done'] ?></td>
                            </tr>
                            <tr>
                                <td>Completion Rate</td>
                                <td class="text-center completion-rate-cell">
                                    <?= $completionRate ?>%
                                </td>
                            </tr>
                            <tr class="summary-row">
                                <td><strong>Total Pending (all time)</strong></td>
                                <td class="text-center"><strong><?= $scheduledCalls['total_pending'] ?></strong></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Upcoming Reminders -->
            <div class="col-lg-6 col-12 mb-4">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-bell"></i> Upcoming Calls (next 45 min)
                        <?php if (!empty($scheduledCalls['upcoming'])): ?>
                            <span class="badge badge-light ml-2"><?= count($scheduledCalls['upcoming']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Phone</th>
                                <th class="text-center">Scheduled At</th>
                                <th class="text-center">In</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($scheduledCalls['upcoming'])): ?>
                                <tr>
                                    <td colspan="4" class="text-muted text-center">No upcoming calls in the next 45 minutes</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($scheduledCalls['upcoming'] as $call): ?>
                                    <?php
                                    $minutesLeft = max(0, round(($call->scheduled_at - time()) / 60));
                                    $isUrgent = $minutesLeft <= 5;
                                    ?>
                                    <tr class="reminder-row <?= $isUrgent ? 'urgent' : '' ?>">
                                        <td>
                                            <?= $call->candidate
                                                ? Html::encode($call->candidate->getFullName())
                                                : '—' ?>
                                        </td>
                                        <td>
                                            <?= $call->candidate
                                                ? Html::encode($call->candidate->phone_number)
                                                : '—' ?>
                                        </td>
                                        <td class="text-center reminder-time">
                                            <?= date('H:i', $call->scheduled_at) ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge reminder-bucket-badge <?= $isUrgent ? 'badge-danger' : 'badge-warning' ?>">
                                                <?= $minutesLeft ?> min
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>