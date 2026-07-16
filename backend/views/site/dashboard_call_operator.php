<?php

/** @var yii\web\View $this */
/** @var array $todayStats */
/** @var array $yesterdayStats */
/** @var array $operatorStats */
/** @var array $summaryStats */
/** @var \common\services\CallStatisticsService $callStatsService */

$this->title = 'Dashboard Call Operator';
?>

<style>
    /* Call Statistics Table - Desktop styles only */
    .call-stats-table {
        background: white;
        border-radius: 0.375rem;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .call-stats-table .table-header {
        background: #6c757d;
        color: white;
        padding: 1rem 1.5rem;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .call-stats-table .table {
        margin-bottom: 0;
    }

    .call-stats-table .table thead th {
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

    .call-stats-table .table tbody td {
        padding: 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
    }

    .call-stats-table .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .operator-name {
        font-weight: 600;
        color: #212529;
    }

    .call-count {
        font-weight: 500;
        font-size: 0.95rem;
    }

    .change-indicator {
        font-size: 0.8rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .change-indicator i {
        font-size: 0.7rem;
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

    .answer-rate-cell {
        font-weight: 600;
    }

    .answer-rate-good { color: #28a745; }
    .answer-rate-average { color: #ffc107; }
    .answer-rate-poor { color: #dc3545; }
</style>

<div class="site-index">
    <div class="body-content">

        <!-- Call Statistics Widgets -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"> Call Statistics for Today</h5>
            </div>

            <!-- Total Calls Widget -->
            <div class="col-lg-6 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-phone"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Calls Today</span>
                        <span class="info-box-number"><?= $todayStats['total_calls'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Pending Calls Widget -->
            <div class="col-lg-6 col-6 dashboard-info-box-col">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending Calls Today</span>
                        <span class="info-box-number"><?= $todayStats['unanswered_calls'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Call Statistics Table -->
        <div class="row">
            <div class="col-lg-6 col-md-8 col-12">
                <div class="call-stats-table">
                    <div class="table-header">
                        <i class="fas fa-chart-bar"></i> Detailed Call Statistics by Operator
                    </div>

                    <!-- Desktop Table View -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Operator</th>
                                <th class="text-center">Today</th>
                                <th class="text-center">Change<br><small>vs Yesterday</small></th>
                                <th class="text-center">Yesterday</th>
                                <th class="text-center">This Month</th>
                                <th class="text-center">Answer Rate<br><small>Today</small></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($operatorStats as $operator):
                                $todayAnswerRate = $operator['today_calls'] > 0 ?
                                    (($operator['today_calls'] - $operator['today_unanswered']) / $operator['today_calls']) * 100 : 0;

                                $totalChange = $callStatsService->calculatePercentageChange($operator['today_calls'], $operator['yesterday_calls']);
                                $changeClass = $callStatsService->getChangeClass($totalChange);
                                $changeIcon = $callStatsService->getChangeIcon($totalChange);

                                ?>
                                <tr>
                                    <td>
                                        <div class="operator-name"><?= $operator['name'] ?></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="call-count"><?= $operator['today_calls'] ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="<?= $changeClass ?> change-indicator">
                                            <i class="<?= $changeIcon ?>"></i>
                                            <?= abs($totalChange) ?>%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="call-count"><?= $operator['yesterday_calls'] ?></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="call-count"><?= $operator['month_calls'] ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="answer-rate-cell ">
                                            <?= number_format($todayAnswerRate, 1) ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Summary Row -->
                            <?php
                            $totalChangePercent = $callStatsService->calculatePercentageChange($summaryStats['total_today'], $summaryStats['total_yesterday']);
                            $totalChangeClass = $callStatsService->getChangeClass($totalChangePercent);
                            $totalChangeIcon = $callStatsService->getChangeIcon($totalChangePercent);
                            ?>
                            <tr class="summary-row">
                                <td><strong>TOTAL</strong></td>
                                <td class="text-center">
                                    <div><strong><?= $summaryStats['total_today'] ?></strong></div>
                                </td>
                                <td class="text-center">
                                    <span class="<?= $totalChangeClass ?> change-indicator">
                                        <i class="<?= $totalChangeIcon ?>"></i>
                                        <strong><?= abs($totalChangePercent) ?>%</strong>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div><strong><?= $summaryStats['total_yesterday'] ?></strong></div>
                                </td>
                                <td class="text-center">
                                    <div><strong><?= $summaryStats['total_month'] ?></strong></div>
                                </td>
                                <td class="text-center">
                                    <span class="answer-rate-cell ">
                                        <strong><?= number_format($summaryStats['today_answer_rate'], 1) ?>%</strong>
                                    </span>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="operator-stats-mobile">
                        <?php foreach ($operatorStats as $operator):
                            $todayAnswerRate = $operator['today_calls'] > 0 ?
                                (($operator['today_calls'] - $operator['today_unanswered']) / $operator['today_calls']) * 100 : 0;

                            $totalChange = $callStatsService->calculatePercentageChange($operator['today_calls'], $operator['yesterday_calls']);
                            $changeClass = $callStatsService->getChangeClass($totalChange);
                            $changeIcon = $callStatsService->getChangeIcon($totalChange);
                            ?>
                            <div class="operator-card">
                                <div class="operator-card-header">
                                    <div class="operator-card-name"><?= $operator['name'] ?></div>
                                    <div class="operator-card-answer-rate answer-rate-cell">
                                        <?= number_format($todayAnswerRate, 1) ?>%
                                    </div>
                                </div>
                                <div class="operator-stats-grid">
                                    <div class="operator-stat-item">
                                        <div class="operator-stat-label">Today</div>
                                        <div class="operator-stat-value"><?= $operator['today_calls'] ?></div>
                                    </div>
                                    <div class="operator-stat-item">
                                        <div class="operator-stat-label">Yesterday</div>
                                        <div class="operator-stat-value"><?= $operator['yesterday_calls'] ?></div>
                                    </div>
                                    <div class="operator-stat-item">
                                        <div class="operator-stat-label">Change</div>
                                        <div class="operator-stat-change <?= $changeClass ?>">
                                            <i class="<?= $changeIcon ?>"></i>
                                            <?= abs($totalChange) ?>%
                                        </div>
                                    </div>
                                    <div class="operator-stat-item">
                                        <div class="operator-stat-label">This Month</div>
                                        <div class="operator-stat-value"><?= $operator['month_calls'] ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Summary Card -->
                        <?php
                        $totalChangePercent = $callStatsService->calculatePercentageChange($summaryStats['total_today'], $summaryStats['total_yesterday']);
                        $totalChangeClass = $callStatsService->getChangeClass($totalChangePercent);
                        $totalChangeIcon = $callStatsService->getChangeIcon($totalChangePercent);
                        ?>
                        <div class="operator-card operator-summary-card">
                            <div class="operator-card-header">
                                <div class="operator-card-name">TOTAL</div>
                                <div class="operator-card-answer-rate answer-rate-cell">
                                    <?= number_format($summaryStats['today_answer_rate'], 1) ?>%
                                </div>
                            </div>
                            <div class="operator-stats-grid">
                                <div class="operator-stat-item">
                                    <div class="operator-stat-label">Today</div>
                                    <div class="operator-stat-value"><?= $summaryStats['total_today'] ?></div>
                                </div>
                                <div class="operator-stat-item">
                                    <div class="operator-stat-label">Yesterday</div>
                                    <div class="operator-stat-value"><?= $summaryStats['total_yesterday'] ?></div>
                                </div>
                                <div class="operator-stat-item">
                                    <div class="operator-stat-label">Change</div>
                                    <div class="operator-stat-change <?= $totalChangeClass ?>">
                                        <i class="<?= $totalChangeIcon ?>"></i>
                                        <?= abs($totalChangePercent) ?>%
                                    </div>
                                </div>
                                <div class="operator-stat-item">
                                    <div class="operator-stat-label">This Month</div>
                                    <div class="operator-stat-value"><?= $summaryStats['total_month'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>