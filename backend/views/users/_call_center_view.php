<?php

/** @var yii\web\View $this */
/** @var backend\models\User $model */
/** @var common\models\UserComments $newComment */
/** @var common\models\UserComments[] $userComments */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\models\User;

$countries = \common\models\User::getCountries();
?>

<div class="call-center-employee-view">
    <div class="row">

        <!-- Left: Employee Info + Status -->
        <div class="col-md-7">

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Employee Information</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-bordered mb-0">
                        <tr>
                            <th style="width: 35%;">#</th>
                            <td><?= Html::encode($model->sequential_number) ?></td>
                        </tr>
                        <tr>
                            <th>Full Name</th>
                            <td><?= Html::encode($model->getFullName()) ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?= Html::encode($model->email) ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?= Html::encode($model->phone_number) ?></td>
                        </tr>
                        <tr>
                            <th>Home Phone</th>
                            <td><?= Html::encode($model->home_phone) ?></td>
                        </tr>
                        <tr>
                            <th>Position</th>
                            <td><?= Html::encode($model->position_title) ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?= Html::encode($model->address . ', ' . $model->city . ', ' . $model->state . ' ' . $model->zip_code) ?></td>
                        </tr>
                        <tr>
                            <th>Country</th>
                            <td><?= Html::encode($countries[$model->country] ?? $model->country) ?></td>
                        </tr>
                        <tr>
                            <th>HR Source</th>
                            <td><?= Html::encode($model->hr_source) ?></td>
                        </tr>
                        <tr>
                            <th>Administrator</th>
                            <td><?= $model->administrator ? Html::encode($model->administrator->getFullName()) : 'Not assigned' ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><?= Html::encode($model->getSubstatusLabel()) ?></td>
                        </tr>
                        <tr>
                            <th>Online</th>
                            <td><?= $model->is_online ? 'Online' : 'Offline' ?></td>
                        </tr>
                        <tr>
                            <th>Last Activity</th>
                            <td><?= $model->last_activity ? date('Y-m-d H:i:s', $model->last_activity) : 'Never' ?></td>
                        </tr>
                        <tr>
                            <th>Registered</th>
                            <td><?= date('Y-m-d H:i:s', $model->created_at) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php $allowedStatuses = $model->getAllowedStatusTransitionsForPhoneOperator(); ?>
            <?php if (!empty($allowedStatuses)): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Change Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-2">
                            <?= Html::dropDownList(
                                'new_substatus',
                                '',
                                ['' => 'Select new status...'] + $allowedStatuses,
                                [
                                    'class' => 'form-control',
                                    'id'    => 'cc-status-dropdown-' . $model->id,
                                ]
                            ) ?>
                        </div>
                        <?= Html::button('Save Status', [
                            'class'            => 'btn btn-success cc-change-status-btn',
                            'data-user-id'     => $model->id,
                            'data-dropdown-id' => 'cc-status-dropdown-' . $model->id,
                        ]) ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Right: Comments -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">My Comments</h5>
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
                                            <?= Html::button('Edit', [
                                                'class' => 'btn btn-sm btn-outline-primary cc-edit-comment-btn',
                                            ]) ?>
                                            <?= Html::button('Delete', [
                                                'class'           => 'btn btn-sm btn-outline-danger cc-delete-comment-btn',
                                                'data-comment-id' => $comment->id,
                                                'data-user-id'    => $model->id,
                                            ]) ?>
                                        </div>
                                    </div>

                                    <div class="comment-edit-form" style="display: none;">
                                        <?php $editForm = ActiveForm::begin([
                                            'id'      => 'cc-edit-comment-form-' . $comment->id,
                                            'action'  => Url::to(['users/for-call-center-view', 'id' => $model->id]),
                                            'options' => [
                                                'data-pjax'    => false,
                                                'class'        => 'cc-update-comment-form',
                                                'data-user-id' => $model->id,
                                            ],
                                        ]); ?>

                                        <?= Html::hiddenInput('action', 'update') ?>
                                        <?= Html::hiddenInput('comment_id', $comment->id) ?>

                                        <div class="form-group">
                                            <textarea name="comment"
                                                      class="form-control"
                                                      rows="4"><?= Html::encode($comment->comment) ?></textarea>
                                        </div>
                                        <div class="comment-edit-actions">
                                            <?= Html::submitButton('Save', ['class' => 'btn btn-sm btn-success cc-comment-submit']) ?>
                                            <?= Html::button('Cancel', ['class' => 'btn btn-sm btn-secondary cc-cancel-edit-btn']) ?>
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
                            'id'      => 'cc-comment-form-' . $model->id,
                            'action'  => Url::to(['users/for-call-center-view', 'id' => $model->id]),
                            'options' => [
                                'data-pjax'    => false,
                                'class'        => 'cc-add-comment-form',
                                'data-user-id' => $model->id,
                            ],
                        ]); ?>

                        <?= Html::hiddenInput('action', 'add') ?>

                        <div class="form-group">
                            <textarea name="comment"
                                      class="form-control"
                                      rows="4"
                                      placeholder="Enter your comment here..."></textarea>
                        </div>
                        <div class="form-group">
                            <?= Html::submitButton('Add Comment', ['class' => 'btn btn-primary cc-comment-submit']) ?>
                        </div>

                        <?php ActiveForm::end(); ?>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>