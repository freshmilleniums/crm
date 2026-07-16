<?php
/*
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;
use yii\web\JsExpression;



?>

<!-- Add Participant Modal -->
<div class="modal fade" id="addParticipantModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus"></i>
                    Add Participants to Chat
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php $form = ActiveForm::begin([
                    'id' => 'addParticipantForm',
                    'options' => ['data-pjax' => false],
                ]); ?>

                <div class="form-group">
                    <label>Select Employees to Add</label>
                    <?= Select2::widget([
                        'name' => 'add_participant_ids',
                        'options' => [
                            'placeholder' => 'Search and select employees...',
                            'multiple' => true,
                            'id' => 'addParticipantSelect',
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
                        Select one or more employees to add to this chat. Only employees not already in the chat will be shown.
                    </small>
                </div>

                <?= Html::hiddenInput('add_to_chat_id', '', ['id' => 'addParticipantChatId']) ?>

                <?php ActiveForm::end(); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addParticipantBtn" disabled>
                    <i class="fas fa-plus"></i> Add Participants
                </button>
            </div>
        </div>
    </div>
</div>
<?php */ ?>