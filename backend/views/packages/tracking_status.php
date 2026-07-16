<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $trackingData array */
/* @var $statusText string */
/* @var $package \common\models\Packages */

// Helper function to get expected delivery date
function getExpectedDeliveryDate($trackingData) {
    // Priority order for expected delivery date
    $deliveryFields = [
        'aftership_estimated_delivery_date',  // AfterShip AI estimate (most accurate)
        'expected_delivery',                  // Carrier provided estimate
        'latest_estimated_delivery',          // Latest estimate
        'earliest_estimated_delivery'         // Earliest estimate
    ];

    foreach ($deliveryFields as $field) {
        if (!empty($trackingData[$field])) {
            return $trackingData[$field];
        }
    }

    return null;
}

$expectedDelivery = getExpectedDeliveryDate($trackingData);
?>

<div class="tracking-status">
    <!-- Status badge -->
    <div class="mb-3 text-center">
        <span class="badge badge-info badge-lg p-2">
            <?= Html::encode($statusText) ?>
        </span>
    </div>

    <!-- Main info table -->
    <table class="table table-sm table-borderless">
        <tr>
            <td><strong>Tracking Number:</strong></td>
            <td><?= Html::encode($trackingData['tracking_number']) ?></td>
        </tr>
        <tr>
            <td><strong>Courier:</strong></td>
            <td><?= Html::encode(strtoupper($trackingData['slug'])) ?></td>
        </tr>
        <tr>
            <td><strong>Status:</strong></td>
            <td><?= Html::encode($statusText) ?></td>
        </tr>
        <tr>
            <td><strong>Last Updated:</strong></td>
            <td><?= $trackingData['updated_at'] ?></td>
        </tr>
        <?php if ($expectedDelivery): ?>
            <tr>
                <td><strong>Expected Delivery:</strong></td>
                <td><?= $expectedDelivery ?></td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<style>
    .badge-lg {
        font-size: 1rem;
        padding: 0.5rem 1rem !important;
    }
    .tracking-status .table td {
        padding: 0.25rem 0.5rem;
        vertical-align: top;
    }
    .tracking-status .table td:first-child {
        width: 40%;
        font-weight: 500;
    }
</style>