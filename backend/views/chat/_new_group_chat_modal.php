<?php
/*
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use backend\models\Chat;
use kartik\select2\Select2;
use yii\helpers\Url;
use yii\web\JsExpression;



?>

<?php if (in_array(Chat::TYPE_EMPLOYEE_GROUP, $availableChatTypes)): ?>
    <div class="modal fade" id="newGroupChatModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-users"></i>
                        New Group Chat
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'groupChatForm',
                        'options' => ['data-pjax' => false],
                    ]); ?>

                    <div class="form-group">
                        <label for="groupChatTitle">Chat Title <span class="text-danger">*</span></label>
                        <?= Html::textInput('group_chat_title', '', [
                            'class' => 'form-control',
                            'id' => 'groupChatTitle',
                            'placeholder' => 'Enter group chat title',
                            'required' => true,
                        ]) ?>
                    </div>

                    <div class="form-group">
                        <label>Select Participants <span class="text-danger">*</span></label>
                        <?= Select2::widget([
                            'name' => 'group_participant_ids',
                            'options' => [
                                'placeholder' => 'Search and select employees...',
                                'multiple' => true,
                                'id' => 'groupParticipantSelect',
                            ],
                            'pluginOptions' => [
                                'allowClear' => true,
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
                                    return data.text || "Search and select employees...";
                                }'),
                            ],
                        ]); ?>
                        <small class="form-text text-muted">
                            Start typing to search for employees. You can select multiple participants for the group chat.
                        </small>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="createGroupChatBtn" disabled>
                        <i class="fas fa-plus"></i> Create Chat
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php endif; */ ?>