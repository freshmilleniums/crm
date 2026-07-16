<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $allAccounts array */
/* @var $activeAccount array */
/* @var $searchModel backend\models\CorporateEmailMessageSearch|backend\models\ExternalEmailMessageSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $filter string */
/* @var $filterCounts array */

$this->title = 'Email';
$this->params['breadcrumbs'][] = $this->title;

$this->registerCss('
.email-message-row {
    cursor: pointer;
}

.email-message-row:hover {
    background-color: #f8f9fa;
}

.email-message-row.unread {
    font-weight: bold;
}

.email-details-row .card {
    border-left: 3px solid #6c757d;
}

.filter-sidebar {
    border-right: 1px solid #dee2e6;
    padding-right: 15px;
}

.filter-sidebar .nav-link {
    color: #495057;
    padding: 8px 15px;
    border-radius: 4px;
    margin-bottom: 5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.filter-sidebar .nav-link:hover {
    background-color: #f8f9fa;
}

.filter-sidebar .nav-link.active {
    background-color: #dee2e6;
    color: #495057; 
}

.filter-count {
    font-size: 0.85em;
    opacity: 0.8;
}

.compose-modal .modal-dialog {
    max-width: 800px;
}

.action-details {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    margin: 10px 0;
    border-left: 3px solid #6c757d;
}
');

$viewUrl = Url::to(['view']);
$composeUrl = Url::to(['compose']);
$syncUrl = Url::to(['sync']);
$markReadUrl = Url::to(['mark-read']);
$markUnreadUrl = Url::to(['mark-unread']);
$markFlaggedUrl = Url::to(['mark-flagged']);
$markUnflaggedUrl = Url::to(['mark-unflagged']);
$archiveUrl = Url::to(['archive']);
$deleteUrl = Url::to(['delete']);

$accountId = $activeAccount['id'];
$accountType = $activeAccount['type'];

$script = "
// View message (expandable row)
$(document).on('click', '.view-message-row', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var messageId = $(this).data('id');
    var messageType = $(this).data('type');
    var \$clickedRow = $(this).closest('tr');
    var existingDetailsRow = \$clickedRow.next('.email-details-row');
    
    if (existingDetailsRow.length) {
        existingDetailsRow.find('.email-details').slideUp(500, function() {
            existingDetailsRow.remove();
        });
        return;
    }
    
    $('.email-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.email-details').slideUp(700, function() {
            \$this.remove();
        });
    });
    
    var detailsRow = $('<tr class=\"email-details-row\"><td colspan=\"' + \$clickedRow.find('td').length + '\"><div class=\"email-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    
    \$clickedRow.after(detailsRow);
    
    $.ajax({
        url: '$viewUrl',
        type: 'GET',
        data: { id: messageId, type: messageType },
        success: function(response) {
            if (response.success) {
                detailsRow.find('.content').html(response.html);
                detailsRow.find('.email-details').hide().slideDown(700);
                
                // Mark row as read
                \$clickedRow.removeClass('unread');
            } else {
                detailsRow.find('.content').html('Failed to load message');
                detailsRow.find('.email-details').hide().slideDown(700);
            }
        },
        error: function() {
            detailsRow.find('.content').html('Failed to load message');
            detailsRow.find('.email-details').hide().slideDown(700);
        }
    });
});

// Compose email
$(document).on('click', '.compose-email-btn', function(e) {
    e.preventDefault();
    
    var modal = $('#composeEmailModal');
    var modalBody = modal.find('.modal-body');
    var modalTitle = modal.find('.modal-title');
    
    modalTitle.text('Compose Email');
    modalBody.html('<div class=\"text-center\"><div class=\"spinner-border\" role=\"status\"><span class=\"sr-only\">Loading...</span></div></div>');
    modal.modal('show');
    
    $.ajax({
        url: '$composeUrl',
        type: 'GET',
        data: {
            account_id: $accountId,
            account_type: '$accountType'
        },
        success: function(html) {
            modalBody.html(html);
        },
        error: function() {
            modalBody.html('<div class=\"alert alert-danger\">Failed to load compose form</div>');
        }
    });
});

// Sync account
$(document).on('click', '.sync-account-btn', function(e) {
    e.preventDefault();
    
    var button = $(this);
    var originalHtml = button.html();
    
    button.html('<i class=\"fas fa-spinner fa-spin\"></i> Syncing...').prop('disabled', true);
    
    $.ajax({
        url: '$syncUrl',
        type: 'POST',
        data: {
            account_id: $accountId,
            account_type: '$accountType'
        },
        success: function() {
            toastr.success('Email synchronized successfully');
            location.reload();
        },
        error: function() {
            toastr.error('Failed to sync email');
        },
        complete: function() {
            button.html(originalHtml).prop('disabled', false);
        }
    });
});

// Mark as read
$(document).on('click', '.mark-read-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var messageId = $(this).data('id');
    var messageType = $(this).data('type');
    
    $.ajax({
        url: '$markReadUrl',
        type: 'POST',
        data: { 
            id: messageId, 
            type: messageType 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success('Marked as read');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to mark as read');
            }
        },
        error: function() {
            toastr.error('Failed to mark as read');
        }
    });
});

// Mark as unread
$(document).on('click', '.mark-unread-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var messageId = $(this).data('id');
    var messageType = $(this).data('type');
    
    $.ajax({
        url: '$markUnreadUrl',
        type: 'POST',
        data: { 
            id: messageId, 
            type: messageType 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success('Marked as unread');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to mark as unread');
            }
        },
        error: function() {
            toastr.error('Failed to mark as unread');
        }
    });
});

// Archive
$(document).on('click', '.archive-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var messageId = $(this).data('id');
    var messageType = $(this).data('type');
    
    $.ajax({
        url: '$archiveUrl',
        type: 'POST',
        data: { 
            id: messageId, 
            type: messageType 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to archive');
            }
        },
        error: function() {
            toastr.error('Failed to archive message');
        }
    });
});

// Flag/Unflag
$(document).on('click', '.mark-flagged-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var messageId = $(this).data('id');
    var messageType = $(this).data('type');
    
    $.ajax({
        url: '$markFlaggedUrl',
        type: 'POST',
        data: { 
            id: messageId, 
            type: messageType 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success('Message flagged');
                location.reload();
            }
        },
        error: function() {
            toastr.error('Failed to flag message');
        }
    });
});

$(document).on('click', '.mark-unflagged-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var messageId = $(this).data('id');
    var messageType = $(this).data('type');
    
    $.ajax({
        url: '$markUnflaggedUrl',
        type: 'POST',
        data: { 
            id: messageId, 
            type: messageType 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success('Flag removed');
                location.reload();
            }
        },
        error: function() {
            toastr.error('Failed to remove flag');
        }
    });
});

// Delete
$(document).on('click', '.delete-email-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    if (!confirm('Are you sure you want to permanently delete this message?')) {
        return;
    }
    
    var messageId = $(this).data('id');
    var messageType = $(this).data('type');
    
    $.ajax({
        url: '$deleteUrl',
        type: 'POST',
        data: { 
            id: messageId, 
            type: messageType 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to delete');
            }
        },
        error: function() {
            toastr.error('Failed to delete message');
        }
    });
});

// Close details
$(document).on('click', '.cancel-action', function(e) {
    e.preventDefault();
    $(this).closest('.email-details-row').find('.email-details').slideUp(700, function() {
        $(this).closest('.email-details-row').remove();
    });
});
";

$this->registerJs($script, \yii\web\View::POS_END);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <!-- Email Account Tabs (как в Users/Templates) -->
                    <div class="card card-secondary card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="email-accounts-tabs" role="tablist">
                                <?php foreach ($allAccounts as $account): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $account['id'] == $activeAccount['id'] && $account['type'] == $activeAccount['type'] ? 'active' : '' ?>"
                                           href="<?= Url::to(['index', 'account_id' => $account['id'], 'account_type' => $account['type']]) ?>">
                                            <?= Html::encode($account['email']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Filters Sidebar -->
                                <div class="col-md-2 filter-sidebar">
                                    <div class="mb-3">
                                        <?= Html::a(
                                            '<i class="fas fa-pen"></i> Compose',
                                            '#',
                                            ['class' => 'btn btn-success btn-block compose-email-btn']
                                        ) ?>
                                        <?= Html::a(
                                            '<i class="fas fa-sync"></i> Sync',
                                            '#',
                                            ['class' => 'btn btn-secondary btn-block sync-account-btn']
                                        ) ?>
                                    </div>

                                    <ul class="nav flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link <?= $filter == 'inbox' ? 'active' : '' ?>"
                                               href="<?= Url::to(['index', 'account_id' => $accountId, 'account_type' => $accountType, 'filter' => 'inbox']) ?>">
                                                <span><i class="fas fa-inbox"></i> Inbox</span>
                                                <span class="filter-count"><?= $filterCounts['inbox'] ?? 0 ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?= $filter == 'sent' ? 'active' : '' ?>"
                                               href="<?= Url::to(['index', 'account_id' => $accountId, 'account_type' => $accountType, 'filter' => 'sent']) ?>">
                                                <span><i class="fas fa-paper-plane"></i> Sent</span>
                                                <span class="filter-count"><?= $filterCounts['sent'] ?? 0 ?></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link <?= $filter == 'unread' ? 'active' : '' ?>"
                                               href="<?= Url::to(['index', 'account_id' => $accountId, 'account_type' => $accountType, 'filter' => 'unread']) ?>">
                                                <span><i class="fas fa-envelope"></i> Unread</span>
                                                <span class="filter-count"><?= $filterCounts['unread'] ?? 0 ?></span>
                                            </a>
                                        </li>
                                        <?php if ($accountType == 'external'): ?>
                                            <li class="nav-item">
                                                <a class="nav-link <?= $filter == 'flagged' ? 'active' : '' ?>"
                                                   href="<?= Url::to(['index', 'account_id' => $accountId, 'account_type' => $accountType, 'filter' => 'flagged']) ?>">
                                                    <span><i class="fas fa-star"></i> Flagged</span>
                                                    <span class="filter-count"><?= $filterCounts['flagged'] ?? 0 ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?= $filter == 'archive' ? 'active' : '' ?>"
                                               href="<?= Url::to(['index', 'account_id' => $accountId, 'account_type' => $accountType, 'filter' => 'archive']) ?>">
                                                <span><i class="fas fa-archive"></i> Archive</span>
                                                <span class="filter-count"><?= $filterCounts['archive'] ?? 0 ?></span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Messages List -->
                                <div class="col-md-10">
                                    <?= GridView::widget([
                                        'dataProvider' => $dataProvider,
                                        'filterModel' => $searchModel,
                                        'tableOptions' => ['class' => 'table table-striped table-bordered'],
                                        'rowOptions' => function ($model) use ($accountType) {
                                            $isRead = $accountType == 'corporate'
                                                ? $model->is_read
                                                : $model->isReadByUser(Yii::$app->user->id);

                                            return [
                                                'class' => 'email-message-row view-message-row' . ($isRead ? '' : ' unread'),
                                                'data-id' => $model->id,
                                                'data-type' => $accountType,
                                            ];
                                        },
                                        'columns' => [
                                            [
                                                'attribute' => 'from_email',
                                                'format' => 'raw',
                                                'value' => function ($model) {
                                                    return Html::encode($model->from_name ?: $model->from_email);
                                                },
                                                'label' => 'From',
                                                'headerOptions' => ['style' => 'width: 25%;'],
                                                'contentOptions' => ['style' => 'max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'],
                                            ],
                                            [
                                                'attribute' => 'subject',
                                                'format' => 'raw',
                                                'value' => function ($model) {
                                                    return Html::encode($model->subject ?: '(No Subject)');
                                                },
                                                'headerOptions' => ['style' => 'width: 50%;'],
                                            ],
                                            [
                                                'attribute' => 'received_at',
                                                'format'    => ['date', 'php:m/d/Y H:i'],
                                                'label' => 'Date',
                                                'headerOptions' => ['style' => 'width: 25%;'],
                                            ],
                                        ],
                                        'summaryOptions' => ['class' => 'summary mb-2'],
                                        'pager' => [
                                            'class' => 'yii\bootstrap4\LinkPager',
                                        ]
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Compose Email Modal -->
<div class="modal fade compose-modal" id="composeEmailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>