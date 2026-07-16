<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;
use backend\models\User;
use common\models\UrgentCall;

/* @var $this yii\web\View */
/* @var $model backend\models\User */
/* @var $userDetails common\models\UserDetails */
/* @var $notifications backend\models\NotificationModel[] */
/* @var $urgentCalls common\models\UrgentCall[] */

?>

<div class="container-fluid">
    <div class="card card-secondary card-tabs">
        <div class="card-header p-0 pt-1">
            <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?= (!isset($activeTab) || $activeTab == 'main') ? 'active' : '' ?>"
                       id="main-tab"
                       data-toggle="pill"
                       href="#main"
                       role="tab"
                       aria-controls="main"
                       aria-selected="<?= (!isset($activeTab) || $activeTab == 'main') ? 'true' : 'false' ?>">
                        Main
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (isset($activeTab) && $activeTab == 'test-results') ? 'active' : '' ?>"
                       id="test-results-tab"
                       data-toggle="pill"
                       href="#test-results"
                       role="tab"
                       aria-controls="test-results"
                       aria-selected="<?= (isset($activeTab) && $activeTab == 'test-results') ? 'true' : 'false' ?>">
                        Test Results
                    </a>
                </li>
                <?php if (!empty($urgentCalls)): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= (isset($activeTab) && $activeTab == 'urgent-calls') ? 'active' : '' ?>"
                           id="urgent-calls-tab"
                           data-toggle="pill"
                           href="#urgent-calls"
                           role="tab"
                           aria-controls="urgent-calls"
                           aria-selected="<?= (isset($activeTab) && $activeTab == 'urgent-calls') ? 'true' : 'false' ?>">
                            Urgent Calls (<?= count($urgentCalls) ?>)
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= (isset($activeTab) && $activeTab == 'notifications') ? 'active' : '' ?>"
                       id="notifications-tab"
                       data-toggle="pill"
                       href="#notifications"
                       role="tab"
                       aria-controls="notifications"
                       aria-selected="<?= (isset($activeTab) && $activeTab == 'notifications') ? 'true' : 'false' ?>">
                        Notifications
                    </a>
                </li>
                <?php if ($model->contract_pdf_path && file_exists(Yii::getAlias('@webroot/uploads/') . $model->contract_pdf_path)): ?>
                    <li class="nav-item">
                        <a class="nav-link"
                           id="contract-tab"
                           data-toggle="pill"
                           href="#contract"
                           role="tab"
                           aria-controls="contract"
                           aria-selected="false">
                            Contract
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="custom-tabs-one-tabContent">
                <!-- Main Tab -->
                <div class="tab-pane fade <?= (!isset($activeTab) || $activeTab == 'main') ? 'show active' : '' ?>"
                     id="main"
                     role="tabpanel"
                     aria-labelledby="main-tab">
                    <div class="row">
                        <div class="col-md-7">

                            <?php
                            $attributes = [
                                'email:email',
                                'first_name',
                                'last_name',
                                'address',
                                'phone_number',
                                'city',
                                'state',
                                'zip_code',
                                [
                                    'attribute' => 'substatus',
                                    'label' => 'Courier Status',
                                    'value' => $model->getSubstatusLabel(),
                                ],
                            ];

                            if ($model->substatus == User::SUBSTATUS_ARCHIVED) {
                                $attributes[] = [
                                    'label' => 'Rejection Reason',
                                    'value' => $model->userDetails && $model->userDetails->rejection_reason
                                        ? $model->userDetails->rejection_reason
                                        : '',
                                    'format' => 'ntext',
                                ];
                            }
                            ?>

                            <?= DetailView::widget([
                                'model' => $model,
                                'attributes' => $attributes,
                            ]) ?>

                            <?php if ($model->substatus != User::SUBSTATUS_ARCHIVED){ ?>
                                <!-- Status Management Section -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5>Status Management</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($model->substatus == User::SUBSTATUS_WAITING_FOR_CALL): ?>
                                            <!-- Button for WAITING_FOR_CALL status -->
                                            <?= Html::button('Set Status: Will pass the test', [
                                                'class' => 'btn btn-primary change-status-btn',
                                                'data-user-id' => $model->id,
                                                'data-new-status' => User::SUBSTATUS_TAKING_TEST,
                                            ]) ?>

                                        <?php elseif ($model->substatus == User::SUBSTATUS_TEST_PASSED): ?>
                                            <!-- Buttons for TEST_PASSED status -->
                                            <div class="mb-3">
                                                <?= Html::button('Set Back to: Will pass the test', [
                                                    'class' => 'btn btn-warning change-status-btn',
                                                    'data-user-id' => $model->id,
                                                    'data-new-status' => User::SUBSTATUS_TAKING_TEST,
                                                ]) ?>

                                                <?= Html::button('Approve Test: Test Verified', [
                                                    'class' => 'btn btn-success change-status-btn',
                                                    'data-user-id' => $model->id,
                                                    'data-new-status' => User::SUBSTATUS_TEST_VERIFIED,
                                                ]) ?>
                                            </div>

                                        <?php elseif ($model->substatus == User::SUBSTATUS_TEST_VERIFIED): ?>
                                            <!-- Buttons for TEST_VERIFIED status -->
                                            <div class="form-group">
                                                <label for="interview-status">Set Interview Status:</label>
                                                <?php
                                                // Different statuses based on user role
                                                if (Yii::$app->user->can('call-operator')) {
                                                    // Call center statuses for call operators
                                                    $statusOptions = [
                                                        '' => 'Select status...',
                                                        User::SUBSTATUS_INTERVIEWED => 'Interviewed',
                                                        User::SUBSTATUS_CC_IN_VOICE => 'CC: Voice',
                                                        User::SUBSTATUS_CC_VOICE_FULL => 'CC: Voice full',
                                                        User::SUBSTATUS_RESEND_CREDENTIALS => 'Resend the login and pass(online panel)',
                                                        User::SUBSTATUS_CC_UNAVAILABLE => 'CC: Unavailable',
                                                        User::SUBSTATUS_CC_WRONG_NUMBER => 'CC: Wrong number',
                                                        User::SUBSTATUS_CC_REFUSED => 'CC: Refused',
                                                        User::SUBSTATUS_CC_HUNG_UP => 'CC: Hung up',
                                                    ];
                                                } else {
                                                    // HR statuses for HR specialists
                                                    $statusOptions = [
                                                        '' => 'Select status...',
                                                        User::SUBSTATUS_INTERVIEWED => 'Interviewed',
                                                        User::SUBSTATUS_IN_VOICE => 'Voice',
                                                        User::SUBSTATUS_VOICE_FULL => 'Voice full',
                                                        User::SUBSTATUS_RESEND_CREDENTIALS => 'Resend the login and pass(online panel)',
                                                        User::SUBSTATUS_UNAVAILABLE => 'Unavailable',
                                                        User::SUBSTATUS_WRONG_NUMBER => 'Wrong number',
                                                        User::SUBSTATUS_REFUSED => 'Refused',
                                                        User::SUBSTATUS_HUNG_UP => 'Hung up',
                                                    ];
                                                }
                                                ?>
                                                <?= Html::dropDownList('interview_status', '', $statusOptions, [
                                                    'class' => 'form-control status-dropdown',
                                                    'data-user-id' => $model->id,
                                                    'id' => 'interview-status'
                                                ]) ?>
                                            </div>

                                        <?php elseif (in_array($model->substatus, [
                                            User::SUBSTATUS_IN_VOICE,
                                            User::SUBSTATUS_VOICE_FULL,
                                            User::SUBSTATUS_RESEND_CREDENTIALS,
                                            User::SUBSTATUS_UNAVAILABLE,
                                            User::SUBSTATUS_WRONG_NUMBER,
                                            User::SUBSTATUS_REFUSED,
                                            User::SUBSTATUS_HUNG_UP,
                                            User::SUBSTATUS_INTERVIEWED,
                                            User::SUBSTATUS_SIGNED_CONTRACT,
                                            // Call center statuses
                                            User::SUBSTATUS_CC_IN_VOICE,
                                            User::SUBSTATUS_CC_VOICE_FULL,
                                            User::SUBSTATUS_CC_UNAVAILABLE,
                                            User::SUBSTATUS_CC_WRONG_NUMBER,
                                            User::SUBSTATUS_CC_REFUSED,
                                            User::SUBSTATUS_CC_HUNG_UP,
                                        ])): ?>

                                            <!-- Button for Worker status -->
                                            <?= Html::button('Set Status: Worker', [
                                                'class' => 'btn btn-success change-status-btn',
                                                'data-user-id' => $model->id,
                                                'data-new-status' => User::SUBSTATUS_WORKER,
                                            ]) ?>

                                            <div class="form-group">
                                                <label for="interview-status">Set Interview Status:</label>
                                                <?php
                                                // Different statuses based on user role
                                                if (Yii::$app->user->can('call-operator')) {
                                                    // Call center statuses for call operators
                                                    $statusOptions = [
                                                        '' => 'Select status...',
                                                        User::SUBSTATUS_INTERVIEWED => 'Interviewed',
                                                        User::SUBSTATUS_CC_IN_VOICE => 'CC: Voice',
                                                        User::SUBSTATUS_CC_VOICE_FULL => 'CC: Voice full',
                                                        User::SUBSTATUS_RESEND_CREDENTIALS => 'Resend the login and pass(online panel)',
                                                        User::SUBSTATUS_CC_UNAVAILABLE => 'CC: Unavailable',
                                                        User::SUBSTATUS_CC_WRONG_NUMBER => 'CC: Wrong number',
                                                        User::SUBSTATUS_CC_REFUSED => 'CC: Refused',
                                                        User::SUBSTATUS_CC_HUNG_UP => 'CC: Hung up',
                                                    ];
                                                } else {
                                                    // HR statuses for HR specialists
                                                    $statusOptions = [
                                                        '' => 'Select status...',
                                                        User::SUBSTATUS_INTERVIEWED => 'Interviewed',
                                                        User::SUBSTATUS_IN_VOICE => 'Voice',
                                                        User::SUBSTATUS_VOICE_FULL => 'Voice full',
                                                        User::SUBSTATUS_RESEND_CREDENTIALS => 'Resend the login and pass(online panel)',
                                                        User::SUBSTATUS_UNAVAILABLE => 'Unavailable',
                                                        User::SUBSTATUS_WRONG_NUMBER => 'Wrong number',
                                                        User::SUBSTATUS_REFUSED => 'Refused',
                                                        User::SUBSTATUS_HUNG_UP => 'Hung up',
                                                    ];
                                                }
                                                ?>
                                                <?= Html::dropDownList('interview_status', '', $statusOptions, [
                                                    'class' => 'form-control status-dropdown',
                                                    'data-user-id' => $model->id,
                                                    'id' => 'interview-status'
                                                ]) ?>
                                            </div>

                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php } ?>
                            <!-- Archive button for HR specialists - always visible -->
                            <?php if (Yii::$app->user->can('hr-specialist') && $model->substatus != User::SUBSTATUS_ARCHIVED): ?>
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5>Archive or Reject Courier with Rejection Reason</h5>
                                    </div>
                                    <div class="card-body">

                                        <div class="">
                                            <div class="form-group">
                                                <label for="rejection-reason">Rejection Reason (required, min 20 characters):</label>
                                                <textarea id="rejection-reason"
                                                          class="form-control"
                                                          rows="3"
                                                          placeholder="Please provide a detailed reason for archiving this Courier..."
                                                          minlength="20"
                                                          required></textarea>
                                            </div>
                                            <button type="button"
                                                    class="btn btn-warning archive-with-reason-btn"
                                                    data-user-id="<?= $model->id ?>"
                                                    data-new-status="<?= User::SUBSTATUS_ARCHIVED ?>">
                                                Set Status: Archived
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-5">
                            <div class="card">
                                <div class="card-header">
                                    <h5>My Comments</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($userComments)): ?>
                                        <div class="comments-list">
                                            <?php foreach ($userComments as $comment): ?>
                                                <div class="comment-item mb-3 p-3 border rounded" data-comment-id="<?= $comment->id ?>">
                                                    <div class="comment-display">
                                                        <div class="comment-text mb-2">
                                                            <?= nl2br(Html::encode($comment->comment)) ?>
                                                        </div>

                                                        <div class="comment-meta mb-2">
                                                            <small class="text-muted">
                                                                <strong>Created:</strong> <?= date('Y-m-d H:i:s', $comment->created_at) ?>
                                                                <?php if ($comment->created_at != $comment->updated_at): ?>
                                                                    | <strong>Updated:</strong> <?= date('Y-m-d H:i:s', $comment->updated_at) ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>

                                                        <div class="comment-actions">
                                                            <button type="button" class="btn btn-sm btn-outline-primary edit-comment-btn">
                                                                Edit
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger delete-comment-btn"
                                                                    data-comment-id="<?= $comment->id ?>">
                                                                Delete
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="comment-edit-form" style="display: none;">
                                                        <?php $editForm = ActiveForm::begin([
                                                            'id' => 'edit-comment-form-' . $comment->id,
                                                            'action' => ['employees/for-hr-view', 'id' => $model->id],
                                                            'options' => [
                                                                'data-pjax' => false,
                                                                'class' => 'edit-comment-form'
                                                            ],
                                                        ]); ?>

                                                        <?= Html::hiddenInput('comment_id', $comment->id) ?>

                                                        <div class="form-group">
                                                            <?= $editForm->field($comment, "[{$comment->id}]comment")->textarea([
                                                                'rows' => 4,
                                                                'class' => 'form-control',
                                                                'value' => $comment->comment
                                                            ])->label(false) ?>
                                                        </div>

                                                        <div class="comment-edit-actions">
                                                            <?= Html::submitButton('Save', [
                                                                'class' => 'btn btn-sm btn-success',
                                                                'name' => 'action',
                                                                'value' => 'update'
                                                            ]) ?>
                                                            <button type="button" class="btn btn-sm btn-secondary cancel-edit-btn">
                                                                Cancel
                                                            </button>
                                                        </div>

                                                        <?php ActiveForm::end(); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <hr>
                                    <?php else: ?>
                                        <p class="text-muted">No comments yet.</p>
                                        <hr>
                                    <?php endif; ?>

                                    <div class="add-comment-section">
                                        <h6>Add New Comment:</h6>
                                        <?php $form = ActiveForm::begin([
                                            'id' => 'comment-form-' . $model->id,
                                            'action' => ['employees/for-hr-view', 'id' => $model->id],
                                            'options' => [
                                                'data-pjax' => false,
                                                'class' => 'comment-form'
                                            ],
                                        ]); ?>

                                        <div class="form-group">
                                            <?= $form->field($newComment, 'comment')->textarea([
                                                'rows' => 4,
                                                'placeholder' => 'Enter your comment here...',
                                                'class' => 'form-control'
                                            ])->label(false) ?>
                                        </div>

                                        <div class="form-group">
                                            <?= Html::submitButton('Add Comment', [
                                                'class' => 'btn btn-primary',
                                                'name' => 'action',
                                                'value' => 'add'
                                            ]) ?>
                                        </div>

                                        <?php ActiveForm::end(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--.col-md-5-->
                    </div>
                    <!--.row-->
                </div>

                <!-- Test Results Tab -->
                <div class="tab-pane fade <?= (isset($activeTab) && $activeTab == 'test-results') ? 'show active' : '' ?>"
                     id="test-results"
                     role="tabpanel"
                     aria-labelledby="test-results-tab">

                    <div class="test-results test-results-page">
                        <div class="row">
                            <div class="col-lg-10 col-md-12 mx-auto">
                                <div class="card test-results-card">
                                    <div class="card-body test-results-body">
                                        <?php if (empty($userAnswers)): ?>
                                            <div class="alert alert-secondary test-results-alert">
                                                <h4>No Test Results Available</h4>
                                                <p>This user hasn't started the test yet.</p>
                                            </div>
                                        <?php elseif ($model->substatus == User::SUBSTATUS_WAITING_FOR_CALL ||
                                            $model->substatus == User::SUBSTATUS_TAKING_TEST): ?>
                                            <div class="alert alert-secondary test-results-alert">
                                                <h4>Test in Progress</h4>
                                                <p>This user is currently taking the test. Results will be available after completion.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="answers-list">
                                                <?php foreach ($userAnswers as $index => $userAnswer): ?>
                                                    <div class="answer-block mb-4">
                                                        <div class="answer-header mb-3">
                                                            <h5 class="question-title">
                                                                <span class="question-number"><?= $index + 1 ?>.</span>
                                                                <?= Html::encode($userAnswer->question_name) ?>
                                                            </h5>
                                                        </div>

                                                        <div class="answer-content">
                                                            <div class="answer-text">
                                                                <?php
                                                                $answerText = $userAnswer->getAnswerText();
                                                                if (!empty($answerText)):
                                                                    ?>
                                                                    <div class="user-answer">
                                                                        <?= Html::encode($answerText) ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="no-answer text-muted">
                                                                        <em>No answer provided</em>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <?php if ($index < count($userAnswers) - 1): ?>
                                                        <hr class="answer-separator">
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Urgent Calls Tab -->
                <?php if (!empty($urgentCalls)): ?>
                    <div class="tab-pane fade <?= (isset($activeTab) && $activeTab == 'urgent-calls') ? 'show active' : '' ?>"
                         id="urgent-calls"
                         role="tabpanel"
                         aria-labelledby="urgent-calls-tab">

                        <div class="urgent-calls-history">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">
                                                <i class="fas fa-phone-alt text-secondary mr-2"></i>
                                                Urgent Calls History
                                            </h5>
                                            <small class="text-muted">
                                                Total: <?= count($urgentCalls) ?> calls
                                            </small>
                                        </div>
                                        <div class="card-body">
                                            <div class="urgent-calls-list">
                                                <?php foreach ($urgentCalls as $index => $call): ?>
                                                    <div class="urgent-call-item mb-3 p-3 border rounded">
                                                        <!-- Header with status and date -->
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div>
                                                                <strong>Call #<?= $call->id ?></strong>
                                                                <span class="badge ml-2 badge-secondary">
                                                                <?= $call->getStatusLabel() ?>
                                                            </span>
                                                            </div>
                                                            <small class="text-muted">
                                                                <?= date('Y-m-d H:i:s', $call->created_at) ?>
                                                            </small>
                                                        </div>

                                                        <!-- HR Instructions Section -->
                                                        <div class="mb-3">
                                                            <h6 class="text-secondary mb-2">
                                                                <i class="fas fa-user-tie mr-1"></i>
                                                                HR Instructions
                                                            </h6>
                                                            <div class="pl-3">
                                                                <div class="mb-1">
                                                                    <strong>Created by:</strong>
                                                                    <?= Html::encode($call->hrEmployee ? $call->hrEmployee->first_name . ' ' . $call->hrEmployee->last_name : '-') ?>
                                                                </div>
                                                                <div class="urgent-call-text">
                                                                    <?= nl2br(Html::encode($call->hr_comment)) ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Call Center Result Section -->
                                                        <?php if ($call->wasCalled() || $call->isConfirmed()): ?>
                                                            <div class="mb-3">
                                                                <h6 class="text-secondary mb-2">
                                                                    <i class="fas fa-headset mr-1"></i>
                                                                    Call Center Result
                                                                </h6>
                                                                <div class="pl-3">
                                                                    <div class="mb-1">
                                                                        <strong>Handled by:</strong>
                                                                        <?= Html::encode($call->callCenterEmployee ? $call->callCenterEmployee->first_name . ' ' . $call->callCenterEmployee->last_name : '-') ?>
                                                                    </div>
                                                                    <div class="urgent-call-text">
                                                                        <?= nl2br(Html::encode($call->call_center_comment)) ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                        <!-- Confirmation Section -->
                                                        <?php if ($call->isConfirmed()): ?>
                                                            <div class="mb-0">
                                                                <h6 class="text-secondary mb-2">
                                                                    <i class="fas fa-check-circle mr-1"></i>
                                                                    Confirmation
                                                                </h6>
                                                                <div class="pl-3">
                                                                    <div class="mb-1">
                                                                        <strong>Confirmed by:</strong>
                                                                        <?= Html::encode($call->confirmedBy ? $call->confirmedBy->first_name . ' ' . $call->confirmedBy->last_name : '-') ?>
                                                                    </div>
                                                                    <div>
                                                                        <strong>Confirmed at:</strong>
                                                                        <?= $call->confirmed_at ? date('Y-m-d H:i:s', $call->confirmed_at) : '-' ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($index < count($urgentCalls) - 1): ?>
                                                        <hr class="urgent-call-separator">
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endif; ?>

                <!-- Notifications Tab -->
                <div class="tab-pane fade <?= (isset($activeTab) && $activeTab == 'notifications') ? 'show active' : '' ?>"
                     id="notifications"
                     role="tabpanel"
                     aria-labelledby="notifications-tab">

                    <div class="notifications-section">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Notifications</h5>
                                        <?php if (!empty($notifications)): ?>
                                            <small class="text-muted">
                                                Total: <?= count($notifications) ?> notifications
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($notifications)): ?>
                                            <div class="alert alert-secondary ">
                                                <h4>No Notifications</h4>
                                                <p class="mb-0">This user has no notifications yet.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="notifications-list">
                                                <?php foreach ($notifications as $index => $notification): ?>
                                                    <div class="notification-item mb-3 p-3 border rounded">

                                                        <!-- Header with date and resend button -->
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <small class="text-muted">
                                                                <strong>Sent:</strong> <?= Yii::$app->formatter->asDatetime($notification->created_at) ?>
                                                                <?php if ($notification->resent_at): ?>
                                                                    | <strong>Resent:</strong> <?= Yii::$app->formatter->asDatetime($notification->resent_at) ?>
                                                                    <?php if ($notification->resentBy): ?>
                                                                        by <?= Html::encode($notification->resentBy->first_name . ' ' . $notification->resentBy->last_name) ?>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </small>
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-success resend-notification-btn"
                                                                    data-notification-id="<?= $notification->id ?>"
                                                                    data-user-id="<?= $model->id ?>">
                                                                Resend notification
                                                            </button>
                                                        </div>

                                                        <!-- Notification Text -->
                                                        <div class="notification-text">
                                                            <?= nl2br(Html::encode($notification->text)) ?>
                                                        </div>
                                                    </div>

                                                    <?php if ($index < count($notifications) - 1): ?>
                                                        <hr class="notification-separator">
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>


                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Contract Tab -->
                <?php if ($model->contract_pdf_path && file_exists(Yii::getAlias('@webroot/uploads/') . $model->contract_pdf_path)): ?>
                    <div
                            class="tab-pane fade"
                            id="contract"
                            role="tabpanel"
                            aria-labelledby="contract-tab">

                        <div class="contract-section">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card">

                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <p class="text">
                                                        <strong>Contract signed on:</strong> <?= Yii::$app->formatter->asDate($model->sign_signature_date) ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-12">
                                                    <!-- PDF Preview with better styling -->
                                                    <div class="pdf-container" style="border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                                                        <object
                                                                class="pdf-preview"
                                                                title="Contract PDF"
                                                                data="<?= \yii\helpers\Url::to(['view-contract-pdf', 'id' => $model->id]) ?>"
                                                                type="application/pdf"
                                                                style="width: 100%; height: 800px;">
                                                            <div class="alert alert-warning text-center p-4">
                                                                <i class="fas fa-exclamation-triangle"></i>
                                                                <p class="mb-2">Your browser does not support PDF preview.</p>
                                                            </div>
                                                        </object>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!--.card-->
</div>

<style>
    .notification-item {
        transition: all 0.3s ease;
    }

    .notification-item:hover {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .notification-separator {
        margin: 1.5rem 0;
        border-color: #e9ecef;
    }

    .notifications-list {
        max-height: 600px;
        overflow-y: auto;
    }

    .notification-actions .btn {
        margin-right: 0.5rem;
    }

    .bulk-actions {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.25rem;
    }

    /* Updated Urgent Calls History Styles - Simple Design */
    .urgent-calls-history .card {
        border: 1px solid #dee2e6;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .urgent-calls-history .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .urgent-call-item {
        transition: all 0.3s ease;
    }

    .urgent-call-item:hover {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .urgent-call-separator {
        margin: 1.5rem 0;
        border-color: #e9ecef;
    }

    .urgent-calls-list {
        max-height: 600px;
        overflow-y: auto;
        padding-right: 10px;
    }

    .urgent-call-text {
        word-break: break-word;
        white-space: normal;
        overflow-wrap: break-word;
    }

    .urgent-calls-list::-webkit-scrollbar {
        width: 6px;
    }

    .urgent-calls-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .urgent-calls-list::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .urgent-calls-list::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>