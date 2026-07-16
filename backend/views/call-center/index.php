<?php

/** @var yii\web\View $this */
/** @var array $tabsData */
/** @var \common\models\CallCenterScript[] $scripts */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\widgets\Pjax;
use backend\models\User;

$this->title = 'Call Center';

$this->registerCss('
.action-details {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    margin: 10px 0;
    border-left: 3px solid #6c757d;
}
.expand-row:hover {
    background-color: #f5f5f5;
}

/* Scripts accordion */
.cc-scripts-accordion .cc-script-item {
    border-bottom: 1px solid #dee2e6;
}
.cc-scripts-accordion .cc-script-item:last-child {
    border-bottom: none;
}
.cc-scripts-accordion .cc-script-title {
    padding: 12px 15px;
    cursor: pointer;
    font-weight: 600;
    user-select: none;
    transition: background 0.15s;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.cc-scripts-accordion .cc-script-title:hover {
    background-color: #f5f5f5;
}
.cc-scripts-accordion .cc-script-title i {
    transition: transform 0.2s;
    flex-shrink: 0;
    margin-left: 8px;
}
.cc-scripts-accordion .cc-script-title.active i {
    transform: rotate(180deg);
}
.cc-scripts-accordion .cc-script-content {
    display: none;
    padding: 10px 15px 15px;
    font-size: 14px;
    line-height: 1.5;
    white-space: normal;
    word-wrap: break-word;
    border-top: 1px solid #eee;
    background-color: #fafafa;
}
.cc-scripts-accordion .cc-script-content.show {
    display: block;
}
');

$scheduleCallUrl    = Url::to(['/call-center/schedule-call']);
$markCallDoneUrl    = Url::to(['/call-center/mark-call-done']);
$deleteScheduledUrl = Url::to(['/call-center/delete-scheduled-call']);

$script = "

function showActionDetails(event, action, id) {
    event.preventDefault();
    event.stopPropagation();

    var \$clickedRow = $(event.target).closest('tr');
    var existingActionRow = \$clickedRow.next('.action-details-row');

    if (existingActionRow.length) {
        var currentAction = existingActionRow.data('current-action');
        if (currentAction === action) {
            existingActionRow.find('.action-details').slideUp(500, function() {
                existingActionRow.remove();
            });
            return;
        }

        var contentDiv = existingActionRow.find('.content');
        existingActionRow.data('current-action', action);

        contentDiv.fadeOut(200, function() {
            $(this).html('Loading...');
            $(this).fadeIn(200);
            $.ajax({
                url: '" . Url::to(['users/for-call-center-view']) . "',
                type: 'GET',
                data: { id: id },
                success: function(response) {
                    response = JSON.parse(response);
                    if (typeof response.tpl != 'undefined') {
                        contentDiv.fadeOut(200, function() {
                            $(this).html(response.tpl);
                            $(this).fadeIn(300);
                        });
                    }
                },
                error: function() {
                    contentDiv.html('Failed to load data');
                }
            });
        });
        return;
    }

    $('.action-details-row').each(function() {
        var \$this = $(this);
        \$this.find('.action-details').slideUp(700, function() {
            \$this.remove();
        });
    });

    var colspan = \$clickedRow.find('td').length;
    var actionRow = $('<tr class=\"action-details-row\"><td colspan=\"' + colspan + '\"><div class=\"action-details\"><div class=\"content\">Loading...</div></div></td></tr>');
    actionRow.data('current-action', action);
    \$clickedRow.after(actionRow);

    $.ajax({
        url: '" . Url::to(['users/for-call-center-view']) . "',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            response = JSON.parse(response);
            if (typeof response.tpl != 'undefined') {
                actionRow.find('.content').html(response.tpl);
                actionRow.find('.action-details').hide().slideDown(700);
            }
        },
        error: function() {
            actionRow.find('.content').html('Failed to load data');
            actionRow.find('.action-details').hide().slideDown(700);
        }
    });
}

// Submit add/update comment forms
$(document).on('submit', '.cc-add-comment-form, .cc-update-comment-form', function(e) {
    e.preventDefault();

    let form = $(this);
    let userId = form.data('user-id');
    let data = form.serialize();
    let submitBtn = form.find('.cc-comment-submit');
    let originalText = submitBtn.text();

    submitBtn.prop('disabled', true).text('Saving...');

    $.ajax({
        type: 'POST',
        url: '" . Url::to(['users/for-call-center-view']) . "' + '?id=' + userId,
        data: data,
        success: function(response) {
            try {
                response = JSON.parse(response);
                if (response.success === true) {
                    toastr.success(response.message);
                    if (response.tpl) {
                        form.closest('.action-details-row').find('.content').html(response.tpl);
                    }
                } else {
                    toastr.error(response.message || 'Operation failed');
                }
            } catch(e) {
                toastr.error('An error occurred');
            }
        },
        error: function() {
            toastr.error('An error occurred while saving comment');
        },
        complete: function() {
            submitBtn.prop('disabled', false).text(originalText);
        }
    });
});

// Delete comment
$(document).on('click', '.cc-delete-comment-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();

    if (!confirm('Are you sure you want to delete this comment?')) {
        return;
    }

    let button = $(this);
    let commentId = button.data('comment-id');
    let userId = button.data('user-id');
    let originalText = button.text();

    button.prop('disabled', true).text('Deleting...');

    $.ajax({
        type: 'POST',
        url: '" . Url::to(['users/for-call-center-view']) . "' + '?id=' + userId,
        data: {
            action: 'delete',
            comment_id: commentId
        },
        success: function(response) {
            try {
                response = JSON.parse(response);
                if (response.success === true) {
                    toastr.success(response.message);
                    if (response.tpl) {
                        button.closest('.action-details-row').find('.content').html(response.tpl);
                    }
                } else {
                    toastr.error(response.message || 'Failed to delete comment');
                }
            } catch(e) {
                toastr.error('An error occurred while deleting comment');
            }
        },
        error: function() {
            toastr.error('An error occurred while deleting comment');
        },
        complete: function() {
            button.prop('disabled', false).text(originalText);
        }
    });
});

// Show edit comment form
$(document).on('click', '.cc-edit-comment-btn', function(e) {
    e.stopPropagation();
    let commentItem = $(this).closest('.comment-item');
    commentItem.find('.comment-display').hide();
    commentItem.find('.comment-edit-form').show();
    commentItem.find('textarea').focus();
});

// Cancel edit comment
$(document).on('click', '.cc-cancel-edit-btn', function(e) {
    e.stopPropagation();
    let commentItem = $(this).closest('.comment-item');
    commentItem.find('.comment-display').show();
    commentItem.find('.comment-edit-form').hide();
});

// Change status from employee card
$(document).on('click', '.cc-change-status-btn', function(e) {
    e.preventDefault();

    let button = $(this);
    let userId = button.data('user-id');
    let dropdownId = button.data('dropdown-id');
    let newStatus = $('#' + dropdownId).val();

    if (!newStatus) {
        toastr.error('Please select a status');
        return;
    }

    button.prop('disabled', true).text('Processing...');

    $.ajax({
        type: 'POST',
        url: '" . Url::to(['users/change-status']) . "',
        data: { id: userId, substatus: newStatus },
        success: function(response) {
            try {
                response = JSON.parse(response);
                if (response.success === true) {
                    toastr.success(response.message || 'Status updated successfully');

                    $('.action-details-row').find('.action-details').slideUp(700, function() {
                        $('.action-details-row').remove();
                    });

                    var activeTabId = $('.tab-pane.active').attr('id');
                    if (activeTabId) {
                        $.pjax.reload({
                            container: '#pjax-' + activeTabId,
                            timeout: 10000
                        }).done(function() {
                            $.pjax.reload({
                                container: '#pjax-tab-headers',
                                timeout: 10000
                            }).done(function() {
                                $('#' + activeTabId + '-tab').tab('show');
                            });
                        });
                    }
                } else {
                    toastr.error(response.message || 'Failed to update status');
                }
            } catch(e) {
                toastr.error('An error occurred while updating status');
            }
        },
        error: function() {
            toastr.error('An error occurred while updating status');
        },
        complete: function() {
            button.prop('disabled', false).text('Save Status');
        }
    });
});

// Open schedule call modal
$(document).on('click', '.btn-schedule-call', function(e) {
    e.preventDefault();
    e.stopPropagation();

    var id = $(this).data('id');
    $('#scheduleCallModalBody').html('<div class=\"text-center py-3\"><i class=\"fas fa-spinner fa-spin\"></i></div>');
    $('#scheduleCallModal').modal('show');

    $.ajax({
        url: '$scheduleCallUrl?candidateId=' + id,
        type: 'GET',
        success: function(response) {
            response = JSON.parse(response);
            if (response.tpl) {
                $('#scheduleCallModalBody').html(response.tpl);
            }
        },
        error: function() { toastr.error('Failed to load form'); }
    });
});

// Submit schedule call form
$(document).on('submit', '#schedule-call-ajax-form', function(e) {
    e.preventDefault();

    var form = $(this);
    var btn  = form.find('.schedule-call-submit');
    btn.prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin mr-1\"></i> Saving...');

    $.ajax({
        url: form.prop('action'),
        type: 'POST',
        data: form.serialize(),
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                toastr.success(response.message);
                $('#scheduleCallModal').modal('hide');

               $.pjax.reload({
                    container: '#pjax-tab-cc-scheduled',
                    timeout: 10000
                }).done(function() {
                    $.pjax.reload({
                        container: '#pjax-tab-headers',
                        timeout: 10000
                    }).done(function() {
                        $('#tab-cc-scheduled-tab').tab('show');
                    });
                });
            } else if (response.tpl) {
                $('#scheduleCallModalBody').html(response.tpl);
            } else {
                toastr.error('Save error');
            }
        },
        error: function() { toastr.error('An error occurred'); },
        complete: function() {
            btn.prop('disabled', false).html('Schedule');
        }
    });
});

// Mark scheduled call as done
$(document).on('click', '.btn-mark-done', function(e) {
    e.preventDefault();
    e.stopPropagation();

    var id  = $(this).data('id');
    var btn = $(this);
    btn.prop('disabled', true);

    $.ajax({
        url: '$markCallDoneUrl',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                toastr.success(response.message);
                $.pjax.reload({
                    container: '#pjax-tab-cc-scheduled',
                    timeout: 10000
                }).done(function() {
                    $.pjax.reload({
                        container: '#pjax-tab-headers',
                        timeout: 10000
                    }).done(function() {
                        $('#tab-cc-scheduled-tab').tab('show');
                    });
                });
            } else {
                toastr.error(response.message);
            }
        },
        error: function() { toastr.error('An error occurred'); },
        complete: function() { btn.prop('disabled', false); }
    });
});

// Delete scheduled call
$(document).on('click', '.btn-delete-scheduled', function(e) {
    e.preventDefault();
    e.stopPropagation();

    if (!confirm('Delete this scheduled call?')) return;

    var id  = $(this).data('id');
    var btn = $(this);
    btn.prop('disabled', true);

    $.ajax({
        url: '$deleteScheduledUrl',
        type: 'POST',
        data: { id: id },
        success: function(response) {
            response = JSON.parse(response);
            if (response.success) {
                toastr.success(response.message);
                $.pjax.reload({
                    container: '#pjax-tab-cc-scheduled',
                    timeout: 10000
                }).done(function() {
                    $.pjax.reload({
                        container: '#pjax-tab-headers',
                        timeout: 10000
                    }).done(function() {
                        $('#tab-cc-scheduled-tab').tab('show');
                    });
                });
            } else {
                toastr.error(response.message);
            }
        },
        error: function() { toastr.error('An error occurred'); },
        complete: function() { btn.prop('disabled', false); }
    });
});

$(document).on('pjax:start', function() {
    $('.pjax-loading').show();
});

$(document).on('pjax:end', function() {
    $('.pjax-loading').hide();
    $('.action-details-row').remove();
});

$(document).on('shown.bs.tab', 'a[data-toggle=\"pill\"]', function(e) {
    var target = $(e.target).attr('href');
    var tabId = target.replace('#', '');
    if ($('#pjax-' + tabId).length) {
        $.pjax.reload({ container: '#pjax-' + tabId, timeout: 10000 });
    }
});

// Scripts accordion
$(document).on('click', '.cc-script-title', function() {
    var \$title = $(this);
    var \$content = \$title.next('.cc-script-content');
    var isOpen = \$content.hasClass('show');

    $('.cc-script-content.show').not(\$content).slideUp(200, function() {
        $(this).removeClass('show');
    });
    $('.cc-script-title.active').not(\$title).removeClass('active');

    if (isOpen) {
        \$content.slideUp(200, function() {
            $(this).removeClass('show');
        });
        \$title.removeClass('active');
    } else {
        \$content.addClass('show').slideDown(200);
        \$title.addClass('active');
    }
});

";

$this->registerJs($script, \yii\web\View::POS_END);
?>

<div class="call-center-index">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <div class="card card-secondary card-tabs">
                            <div class="card-header p-0 pt-1">
                                <?php Pjax::begin([
                                    'id'                 => 'pjax-tab-headers',
                                    'timeout'            => 10000,
                                    'enablePushState'    => false,
                                    'enableReplaceState' => false,
                                ]); ?>
                                <ul class="nav nav-tabs" id="call-center-tabs" role="tablist">
                                    <?php foreach ($tabsData as $tab): ?>
                                        <li class="nav-item">
                                            <a class="nav-link <?= $tab['active'] ? 'active' : '' ?> <?= $tab['labelClass'] ?? '' ?>"
                                               id="<?= $tab['id'] ?>-tab"
                                               data-toggle="pill"
                                               href="#<?= $tab['id'] ?>"
                                               role="tab"
                                               aria-controls="<?= $tab['id'] ?>"
                                               aria-selected="<?= $tab['active'] ? 'true' : 'false' ?>">
                                                <?= $tab['label'] ?>
                                                <span class="pjax-loading" style="display: none;">
                                                    <i class="fa fa-spinner fa-spin"></i>
                                                </span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php Pjax::end(); ?>
                            </div>

                            <div class="card-body">
                                <div class="tab-content" id="call-center-tabContent">
                                    <?php foreach ($tabsData as $tab): ?>
                                        <div class="tab-pane fade <?= $tab['active'] ? 'show active' : '' ?>"
                                             id="<?= $tab['id'] ?>"
                                             role="tabpanel"
                                             aria-labelledby="<?= $tab['id'] ?>-tab">

                                            <?php Pjax::begin([
                                                'id'                 => 'pjax-' . $tab['id'],
                                                'timeout'            => 10000,
                                                'enablePushState'    => false,
                                                'enableReplaceState' => false,
                                                'clientOptions'      => ['skipOuterContainers' => true],
                                            ]); ?>

                                            <?php if (!empty($tab['is_scheduled'])): ?>

                                                <?= GridView::widget([
                                                    'dataProvider' => $tab['dataProvider'],
                                                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                                                    'columns'      => [
                                                        ['class' => 'yii\grid\SerialColumn'],
                                                        [
                                                            'label'  => 'Candidate',
                                                            'format' => 'raw',
                                                            'value'  => function($model) {
                                                                return $model->candidate
                                                                    ? Html::encode($model->candidate->getFullName())
                                                                    : '—';
                                                            },
                                                        ],
                                                        [
                                                            'label' => 'Phone',
                                                            'value' => function($model) {
                                                                return $model->candidate
                                                                    ? $model->candidate->phone_number
                                                                    : '—';
                                                            },
                                                        ],
                                                        [
                                                            'label'  => 'Scheduled At',
                                                            'format' => 'raw',
                                                            'value'  => function($model) {
                                                                $time = date('Y-m-d H:i', $model->scheduled_at);
                                                                if ($model->scheduled_at <= time()) {
                                                                    return $time . ' <span class="badge badge-danger">Overdue</span>';
                                                                }
                                                                return $time;
                                                            },
                                                        ],
                                                        [
                                                            'attribute' => 'comment',
                                                            'value'     => function($model) {
                                                                return $model->comment ?: '—';
                                                            },
                                                        ],
                                                        [
                                                            'class'    => 'yii\grid\ActionColumn',
                                                            'template' => '{view} {done} {delete}',
                                                            'buttons'  => [
                                                                'view' => function($url, $model, $key) {
                                                                    return Html::a(
                                                                        '<i class="fas fa-eye"></i>',
                                                                        '#',
                                                                        [
                                                                            'title'     => 'View Candidate',
                                                                            'data-pjax' => '0',
                                                                            'onclick'   => 'showActionDetails(event, "view", ' . $model->candidate_id . '); return false;',
                                                                        ]
                                                                    );
                                                                },
                                                                'done' => function($url, $model, $key) {
                                                                    return Html::a(
                                                                        '<i class="fas fa-check"></i>',
                                                                        '#',
                                                                        [
                                                                            'title'     => 'Mark as done',
                                                                            'data-pjax' => '0',
                                                                            'class'     => 'btn-mark-done',
                                                                            'data-id'   => $model->id,
                                                                        ]
                                                                    );
                                                                },
                                                                'delete' => function($url, $model, $key) {
                                                                    return Html::a(
                                                                        '<svg aria-hidden="true" style="display:inline-block;font-size:inherit;height:1em;overflow:visible;vertical-align:-.125em;width:.875em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0048 48h288a48 48 0 0048-48V128H32zm272-256a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zm-96 0a16 16 0 0132 0v224a16 16 0 01-32 0zM432 32H312l-9-19a24 24 0 00-22-13H167a24 24 0 00-22 13l-9 19H16A16 16 0 000 48v32a16 16 0 0016 16h416a16 16 0 0016-16V48a16 16 0 00-16-16z"></path></svg>',
                                                                        '#',
                                                                        [
                                                                            'title'     => 'Delete',
                                                                            'data-pjax' => '0',
                                                                            'class'     => 'btn-delete-scheduled',
                                                                            'data-id'   => $model->id,
                                                                        ]
                                                                    );
                                                                },
                                                            ],
                                                        ],
                                                    ],
                                                ]) ?>

                                            <?php else: ?>

                                                <?= GridView::widget([
                                                    'dataProvider' => $tab['dataProvider'],
                                                    'filterModel'  => $tab['filterModel'] ?? null,
                                                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                                                    'columns'      => [
                                                        ['class' => 'yii\grid\SerialColumn'],
                                                        'first_name',
                                                        'last_name',
                                                        'phone_number',
                                                        'email:email',
                                                        [
                                                            'attribute' => 'substatus',
                                                            'label'     => 'Status',
                                                            'value'     => function($model) {
                                                                return $model->getSubstatusLabel();
                                                            },
                                                            'filter' => User::getFilteredSubstatusLabels($tab['group'] ?? null),
                                                        ],
                                                        [
                                                            'label'  => 'Comments',
                                                            'format' => 'raw',
                                                            'value'  => function($model) use ($tab) {
                                                                $userComments = $tab['comments'][$model->id] ?? [];

                                                                $result = '<div class="comments-display">';
                                                                if (!empty($userComments)) {
                                                                    foreach ($userComments as $index => $comment) {
                                                                        if ($index > 0) {
                                                                            $result .= '<hr style="margin: 4px 0; border-color: #ddd;">';
                                                                        }
                                                                        $result .= '<div style="font-size: 12px; margin-bottom: 4px;">';
                                                                        $result .= '<div>' . nl2br(Html::encode($comment->comment)) . '</div>';
                                                                        $result .= '<small class="text-muted" style="font-size: 10px;">' . date('Y-m-d H:i', $comment->created_at) . '</small>';
                                                                        $result .= '</div>';
                                                                    }
                                                                } else {
                                                                    $result .= '<em class="text-muted" style="font-size: 12px;">No comments</em>';
                                                                }
                                                                $result .= '</div>';

                                                                return $result;
                                                            },
                                                            'contentOptions' => ['style' => 'min-width: 200px; max-width: 280px; word-wrap: break-word; vertical-align: top;'],
                                                        ],
                                                        [
                                                            'attribute' => 'created_at',
                                                            'format'    => ['date', 'php:Y-m-d'],
                                                            'label'     => 'Registered',
                                                        ],
                                                        [
                                                            'class'    => 'yii\grid\ActionColumn',
                                                            'template' => '{view} {schedule}',
                                                            'buttons'  => [
                                                                'view' => function($url, $model, $key) {
                                                                    return Html::a(
                                                                        '<i class="fas fa-eye"></i>',
                                                                        $url,
                                                                        [
                                                                            'title'     => 'View',
                                                                            'data-pjax' => '0',
                                                                            'onclick'   => 'showActionDetails(event, "view", ' . $model->id . '); return false;',
                                                                        ]
                                                                    );
                                                                },
                                                                'schedule' => function($url, $model, $key) {
                                                                    return Html::a(
                                                                        '<i class="fas fa-phone-alt"></i>',
                                                                        '#',
                                                                        [
                                                                            'title'     => 'Schedule Call',
                                                                            'data-pjax' => '0',
                                                                            'class'     => 'btn-schedule-call',
                                                                            'data-id'   => $model->id,
                                                                        ]
                                                                    );
                                                                },
                                                            ],
                                                        ],
                                                    ],
                                                ]) ?>

                                            <?php endif; ?>

                                            <?php Pjax::end(); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Scripts</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="cc-scripts-accordion">
                            <?php if (!empty($scripts)): ?>
                                <?php foreach ($scripts as $script): ?>
                                    <div class="cc-script-item">
                                        <div class="cc-script-title">
                                            <span><?= Html::encode($script->title) ?></span>
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                        <div class="cc-script-content">
                                            <div class="cc-script-text">
                                                <?= html_entity_decode($script->content) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-3 text-muted">No scripts available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule call modal -->
<div class="modal fade" id="scheduleCallModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Call</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="scheduleCallModalBody">
                <div class="text-center py-3">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </div>
        </div>
    </div>
</div>