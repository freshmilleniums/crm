<?php

/** @var yii\web\View $this */
/** @var array $packageStats */
/** @var array $taskStats */
/** @var array $courierStats */
/** @var \common\services\DashboardStatisticsService $statisticsService */

$this->title = 'Dashboard Super Administrator22';
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

    .stats-rate {
        font-size: 0.8rem;
        color: #6c757d;
    }
</style>

<div class="site-index">
    <div class="body-content">

        <!-- Today's Package Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"><i class="fas fa-box"></i> Package Statistics - Today</h5>
            </div>
            <div class="col-lg-3 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-plus"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Packages Added</span>
                        <span class="info-box-number"><?= $packageStats['today']['added'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">In Progress</span>
                        <span class="info-box-number"><?= $packageStats['today']['in_progress'] ?></span>
                        <?php if ($packageStats['today']['in_progress_rate'] > 0): ?>
                            <div class="stats-rate"><?= $packageStats['today']['in_progress_rate'] ?>% of added</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-truck"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Delivered</span>
                        <span class="info-box-number"><?= $packageStats['today']['delivered'] ?></span>
                        <?php if ($packageStats['today']['delivered_rate'] > 0): ?>
                            <div class="stats-rate"><?= $packageStats['today']['delivered_rate'] ?>% of in progress</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Completed</span>
                        <span class="info-box-number"><?= $packageStats['today']['completed'] ?></span>
                        <?php if ($packageStats['today']['completed_rate'] > 0): ?>
                            <div class="stats-rate"><?= $packageStats['today']['completed_rate'] ?>% of delivered</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Task Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"><i class="fas fa-tasks"></i> Task Statistics - Today</h5>
            </div>
            <div class="col-lg-3 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-plus"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Tasks Added</span>
                        <span class="info-box-number"><?= $taskStats['today']['added'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">In Progress</span>
                        <span class="info-box-number"><?= $taskStats['today']['in_progress'] ?></span>
                        <?php if ($taskStats['today']['in_progress_rate'] > 0): ?>
                            <div class="stats-rate"><?= $taskStats['today']['in_progress_rate'] ?>% of added</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-shipping-fast"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Delivered</span>
                        <span class="info-box-number"><?= $taskStats['today']['delivered'] ?></span>
                        <?php if ($taskStats['today']['delivered_rate'] > 0): ?>
                            <div class="stats-rate"><?= $taskStats['today']['delivered_rate'] ?>% of in progress</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Completed</span>
                        <span class="info-box-number"><?= $taskStats['today']['completed'] ?></span>
                        <?php if ($taskStats['today']['completed_rate'] > 0): ?>
                            <div class="stats-rate"><?= $taskStats['today']['completed_rate'] ?>% of delivered</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics Tables -->
        <div class="row">
            <!-- Package Statistics Table -->
            <div class="col-lg-6 col-12 mb-4">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-box"></i> Package Statistics
                    </div>

                    <!-- Desktop Table View -->
                    <div class="period-stats-desktop">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Period</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">New</th>
                                    <th class="text-center">In Progress</th>
                                    <th class="text-center">Delivered</th>
                                    <th class="text-center">Completed</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><strong>Today</strong></td>
                                    <td class="text-center"><?= $packageStats['today']['total'] ?></td>
                                    <td class="text-center"><?= $packageStats['today']['new'] ?></td>
                                    <td class="text-center"><?= $packageStats['today']['in_progress'] ?></td>
                                    <td class="text-center"><?= $packageStats['today']['delivered'] ?></td>
                                    <td class="text-center"><?= $packageStats['today']['completed'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>This Week</strong></td>
                                    <td class="text-center"><?= $packageStats['week']['total'] ?></td>
                                    <td class="text-center"><?= $packageStats['week']['new'] ?></td>
                                    <td class="text-center"><?= $packageStats['week']['in_progress'] ?></td>
                                    <td class="text-center"><?= $packageStats['week']['delivered'] ?></td>
                                    <td class="text-center"><?= $packageStats['week']['completed'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>This Month</strong></td>
                                    <td class="text-center"><?= $packageStats['month']['total'] ?></td>
                                    <td class="text-center"><?= $packageStats['month']['new'] ?></td>
                                    <td class="text-center"><?= $packageStats['month']['in_progress'] ?></td>
                                    <td class="text-center"><?= $packageStats['month']['delivered'] ?></td>
                                    <td class="text-center"><?= $packageStats['month']['completed'] ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="period-stats-mobile">
                        <?php foreach ([
                                           'today' => 'Today',
                                           'week' => 'This Week',
                                           'month' => 'This Month'
                                       ] as $period => $label): ?>
                            <div class="period-stat-card">
                                <div class="period-stat-header"><?= $label ?></div>
                                <div class="period-stat-grid">
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Total</div>
                                        <div class="period-stat-value"><?= $packageStats[$period]['total'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">New</div>
                                        <div class="period-stat-value"><?= $packageStats[$period]['new'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">In Progress</div>
                                        <div class="period-stat-value"><?= $packageStats[$period]['in_progress'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Delivered</div>
                                        <div class="period-stat-value"><?= $packageStats[$period]['delivered'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Completed</div>
                                        <div class="period-stat-value"><?= $packageStats[$period]['completed'] ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Task Statistics Table -->
            <div class="col-lg-6 col-12 mb-4">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-tasks"></i> Task Statistics
                    </div>

                    <!-- Desktop Table View -->
                    <div class="period-stats-desktop">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Period</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">New</th>
                                    <th class="text-center">In Progress</th>
                                    <th class="text-center">Delivered</th>
                                    <th class="text-center">Completed</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><strong>Today</strong></td>
                                    <td class="text-center"><?= $taskStats['today']['total'] ?></td>
                                    <td class="text-center"><?= $taskStats['today']['new'] ?></td>
                                    <td class="text-center"><?= $taskStats['today']['in_progress'] ?></td>
                                    <td class="text-center"><?= $taskStats['today']['delivered'] ?></td>
                                    <td class="text-center"><?= $taskStats['today']['completed'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>This Week</strong></td>
                                    <td class="text-center"><?= $taskStats['week']['total'] ?></td>
                                    <td class="text-center"><?= $taskStats['week']['new'] ?></td>
                                    <td class="text-center"><?= $taskStats['week']['in_progress'] ?></td>
                                    <td class="text-center"><?= $taskStats['week']['delivered'] ?></td>
                                    <td class="text-center"><?= $taskStats['week']['completed'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>This Month</strong></td>
                                    <td class="text-center"><?= $taskStats['month']['total'] ?></td>
                                    <td class="text-center"><?= $taskStats['month']['new'] ?></td>
                                    <td class="text-center"><?= $taskStats['month']['in_progress'] ?></td>
                                    <td class="text-center"><?= $taskStats['month']['delivered'] ?></td>
                                    <td class="text-center"><?= $taskStats['month']['completed'] ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="period-stats-mobile">
                        <?php foreach ([
                                           'today' => 'Today',
                                           'week' => 'This Week',
                                           'month' => 'This Month'
                                       ] as $period => $label): ?>
                            <div class="period-stat-card">
                                <div class="period-stat-header"><?= $label ?></div>
                                <div class="period-stat-grid">
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Total</div>
                                        <div class="period-stat-value"><?= $taskStats[$period]['total'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">New</div>
                                        <div class="period-stat-value"><?= $taskStats[$period]['new'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">In Progress</div>
                                        <div class="period-stat-value"><?= $taskStats[$period]['in_progress'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Delivered</div>
                                        <div class="period-stat-value"><?= $taskStats[$period]['delivered'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Completed</div>
                                        <div class="period-stat-value"><?= $taskStats[$period]['completed'] ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Courier Statistics -->
        <div class="row">
            <div class="col-12">
                <div class="stats-table">
                    <div class="table-header">
                        <i class="fas fa-users"></i> Courier Statistics
                    </div>

                    <!-- Desktop Table View -->
                    <div class="period-stats-desktop">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>Period</th>
                                    <th class="text-center">New Couriers</th>
                                    <th class="text-center">Passed Test</th>
                                    <th class="text-center">Interviewed</th>
                                    <th class="text-center">Signed Contract</th>
                                    <th class="text-center">Workers</th>
                                    <th class="text-center">Removed by Reminders</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td><strong>Today</strong></td>
                                    <td class="text-center"><?= $courierStats['today']['new_couriers'] ?></td>
                                    <td class="text-center"><?= $courierStats['today']['passed_test'] ?></td>
                                    <td class="text-center"><?= $courierStats['today']['interviewed'] ?></td>
                                    <td class="text-center"><?= $courierStats['today']['signed_contract'] ?></td>
                                    <td class="text-center"><?= $courierStats['today']['workers'] ?></td>
                                    <td class="text-center"><?= $courierStats['today']['removed_by_reminders'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Yesterday</strong></td>
                                    <td class="text-center"><?= $courierStats['yesterday']['new_couriers'] ?></td>
                                    <td class="text-center"><?= $courierStats['yesterday']['passed_test'] ?></td>
                                    <td class="text-center"><?= $courierStats['yesterday']['interviewed'] ?></td>
                                    <td class="text-center"><?= $courierStats['yesterday']['signed_contract'] ?></td>
                                    <td class="text-center"><?= $courierStats['yesterday']['workers'] ?></td>
                                    <td class="text-center"><?= $courierStats['yesterday']['removed_by_reminders'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>This Week</strong></td>
                                    <td class="text-center"><?= $courierStats['week']['new_couriers'] ?></td>
                                    <td class="text-center"><?= $courierStats['week']['passed_test'] ?></td>
                                    <td class="text-center"><?= $courierStats['week']['interviewed'] ?></td>
                                    <td class="text-center"><?= $courierStats['week']['signed_contract'] ?></td>
                                    <td class="text-center"><?= $courierStats['week']['workers'] ?></td>
                                    <td class="text-center"><?= $courierStats['week']['removed_by_reminders'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>This Month</strong></td>
                                    <td class="text-center"><?= $courierStats['month']['new_couriers'] ?></td>
                                    <td class="text-center"><?= $courierStats['month']['passed_test'] ?></td>
                                    <td class="text-center"><?= $courierStats['month']['interviewed'] ?></td>
                                    <td class="text-center"><?= $courierStats['month']['signed_contract'] ?></td>
                                    <td class="text-center"><?= $courierStats['month']['workers'] ?></td>
                                    <td class="text-center"><?= $courierStats['month']['removed_by_reminders'] ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="period-stats-mobile">
                        <?php foreach ([
                                           'today' => 'Today',
                                           'yesterday' => 'Yesterday',
                                           'week' => 'This Week',
                                           'month' => 'This Month'
                                       ] as $period => $label): ?>
                            <div class="period-stat-card">
                                <div class="period-stat-header"><?= $label ?></div>
                                <div class="period-stat-grid">
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">New</div>
                                        <div class="period-stat-value"><?= $courierStats[$period]['new_couriers'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Test</div>
                                        <div class="period-stat-value"><?= $courierStats[$period]['passed_test'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Interview</div>
                                        <div class="period-stat-value"><?= $courierStats[$period]['interviewed'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Contract</div>
                                        <div class="period-stat-value"><?= $courierStats[$period]['signed_contract'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Workers</div>
                                        <div class="period-stat-value"><?= $courierStats[$period]['workers'] ?></div>
                                    </div>
                                    <div class="period-stat-item">
                                        <div class="period-stat-label">Removed</div>
                                        <div class="period-stat-value"><?= $courierStats[$period]['removed_by_reminders'] ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>