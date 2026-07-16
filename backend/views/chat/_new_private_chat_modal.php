<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use backend\models\Chat;
use kartik\select2\Select2;
use yii\helpers\Url;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $availableChatTypes array */

?>

<?php if (in_array(Chat::TYPE_USER_PRIVATE, $availableChatTypes)): ?>
    <div class="modal fade" id="newPrivateChatModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user"></i>
                        New Private Chat
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'privateChatForm',
                        'options' => ['data-pjax' => false],
                    ]); ?>

                    <div class="form-group">
                        <label for="privateParticipantSelect">Select Employee <span class="text-danger">*</span></label>
                        <?= Select2::widget([
                            'name' => 'private_participant_id',
                            'options' => [
                                'placeholder' => 'Search and select employee...',
                                'id' => 'privateParticipantSelect',
                                'required' => true,
                                'multiple' => true,
                            ],
                            'pluginOptions' => [
                                'allowClear' => true,
                                'maximumSelectionLength' => 1,
                                // 'dropdownParent' => new JsExpression('$("#newPrivateChatModal")'),
                                'ajax' => [
                                    'url' => Url::to(['employees/get-employees']),
                                    'dataType' => 'json',
                                    'data' => new JsExpression('function(params) { return {q: params.term}; }'),
                                    'processResults' => new JsExpression('function(data) {
                                        if (!data.success) { return { results: [] }; }
                                        return {
                                            results: $.map(data.users, function(item) {
                                                return {
                                                    id: item.id,
                                                    text: item.first_name + " " + item.last_name
                                                };
                                            })
                                        };
                                    }'),
                                ],
                                'minimumInputLength' => 1,
                                'templateSelection' => new JsExpression('function(data) {
                                    return data.text || "Search and select employee...";
                                }'),
                            ],
                        ]); ?>
                        <small class="form-text text-muted">
                            If a private chat with this employee already exists, it will be opened instead of creating a new one.
                        </small>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="createPrivateChatBtn" disabled>
                        <i class="fas fa-plus"></i> Create Chat
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>