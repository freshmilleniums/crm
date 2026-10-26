<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use common\models\Investor;

/* @var $this yii\web\View */
/* @var $investor common\models\Investor */
/* @var $employee backend\models\User */

// Result row in investor search dropdown: full name + email + assigned employees count
$investorResultTemplate = new JsExpression('function(item) {
    if (item.loading) {
        return item.text;
    }
    var $row = $("<div></div>");
    $("<div class=\"font-weight-bold\"></div>").text(item.text).appendTo($row);
    var meta = item.email + " · " + item.employees_count + (item.employees_count === 1 ? " employee" : " employees");
    $("<small class=\"text-muted investor-search-meta\"></small>").text(meta).appendTo($row);
    return $row;
}');
?>

    <div class="add-investor-form"
         data-investor-data-url="<?= Url::to(['users/get-investor-data']) ?>"
         data-employee-id="<?= $employee->id ?>">

        <div class="card card-secondary card-tabs mb-0">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active"
                           data-toggle="pill"
                           href="#add-investor-tab-create"
                           role="tab"
                           aria-controls="add-investor-tab-create"
                           aria-selected="true">Create Investor</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           data-toggle="pill"
                           href="#add-investor-tab-assign"
                           role="tab"
                           aria-controls="add-investor-tab-assign"
                           aria-selected="false">Assign Existing</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">

                    <!-- Create Investor tab -->
                    <div class="tab-pane fade show active" id="add-investor-tab-create" role="tabpanel">

                        <?php $form = ActiveForm::begin([
                            'id'     => 'add-investor-form',
                            'action' => ['users/add-investor', 'id' => $employee->id],
                            'options'=> ['data-pjax' => false],
                        ]); ?>

                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($investor, 'first_name')->textInput(['maxlength' => true]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($investor, 'last_name')->textInput(['maxlength' => true]) ?>
                            </div>
                        </div>

                        <?= $form->field($investor, 'email')->textInput(['maxlength' => true, 'type' => 'email']) ?>

                        <?= $form->field($investor, 'address')->textInput(['maxlength' => true]) ?>

                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($investor, 'investor_type')->dropDownList(
                                    Investor::getTypeList(),
                                    ['prompt' => 'Select Type...']
                                ) ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($investor, 'net_value')->textInput(['type' => 'number', 'step' => '0.01']) ?>
                            </div>
                        </div>

                        <?= $form->field($investor, 'comment')->textarea(['rows' => 4, 'maxlength' => true]) ?>

                        <div class="form-group">
                            <label class="control-label">Assigned Employee</label>
                            <input type="text" class="form-control"
                                   value="<?= Html::encode($employee->getFullName()) ?>" disabled>
                        </div>

                        <div class="form-group text-right">
                            <?= Html::button('Create Investor', [
                                'class' => 'btn btn-success add-investor-submit-btn',
                                'type'  => 'submit',
                            ]) ?>
                            <?= Html::button('Cancel', [
                                'class'        => 'btn btn-secondary ml-2',
                                'data-dismiss' => 'modal',
                            ]) ?>
                        </div>

                        <?php ActiveForm::end(); ?>

                    </div>

                    <!-- Assign Existing tab -->
                    <div class="tab-pane fade" id="add-investor-tab-assign" role="tabpanel">

                        <?= Html::beginForm(['users/assign-existing-investor', 'id' => $employee->id], 'post', [
                            'id' => 'assign-existing-investor-form',
                        ]) ?>

                        <div class="form-group">
                            <label class="control-label">Investor</label>
                            <?= Select2::widget([
                                'name'          => 'investor_id',
                                'options'       => [
                                    'id'          => 'assign-investor-select',
                                    'placeholder' => 'Search by name or email...',
                                ],
                                'pluginOptions' => [
                                    'allowClear'         => true,
                                    'minimumInputLength' => 0,
                                    'dropdownParent'     => new JsExpression('$("#assign-investor-select").closest(".modal")'),
                                    'ajax'               => [
                                        'url'      => Url::to(['users/search-investors']),
                                        'dataType' => 'json',
                                        'delay'    => 250,
                                        'data'     => new JsExpression('function(params) { return {q: params.term}; }'),
                                    ],
                                    'templateResult' => $investorResultTemplate,
                                ],
                            ]) ?>
                        </div>

                        <div id="assign-investor-preview" style="display: none;">

                            <div class="alert alert-warning assign-investor-same-warning" style="display: none;">
                                This investor is already assigned to this employee.
                            </div>

                            <div class="alert alert-warning assign-investor-shared-notice" style="display: none;">
                                This investor is already assigned to other employees. They will stay assigned,
                                and <strong><?= Html::encode($employee->getFullName()) ?></strong> will be added.
                            </div>

                            <!-- Definition list instead of table: global mobile .table styles would break it -->
                            <div class="border rounded p-2 mb-3">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Name</dt>
                                    <dd class="col-sm-8 assign-investor-name"></dd>

                                    <dt class="col-sm-4">Email</dt>
                                    <dd class="col-sm-8 assign-investor-email"></dd>

                                    <dt class="col-sm-4">Type</dt>
                                    <dd class="col-sm-8 assign-investor-type"></dd>

                                    <dt class="col-sm-4">Net Value</dt>
                                    <dd class="col-sm-8 assign-investor-net-value"></dd>

                                    <dt class="col-sm-4">Address</dt>
                                    <dd class="col-sm-8 assign-investor-address"></dd>

                                    <dt class="col-sm-4">Comment</dt>
                                    <dd class="col-sm-8 assign-investor-comment" style="white-space: pre-wrap;"></dd>

                                    <dt class="col-sm-4">Assigned Employees</dt>
                                    <dd class="col-sm-8 mb-0 assign-investor-employees"></dd>
                                </dl>
                            </div>

                        </div>

                        <div class="form-group text-right">
                            <?= Html::button('Assign Investor', [
                                'class'    => 'btn btn-success assign-investor-submit-btn',
                                'type'     => 'submit',
                                'disabled' => true,
                            ]) ?>
                            <?= Html::button('Cancel', [
                                'class'        => 'btn btn-secondary ml-2',
                                'data-dismiss' => 'modal',
                            ]) ?>
                        </div>

                        <?= Html::endForm() ?>

                    </div>

                </div>
            </div>
        </div>

    </div>

<?php
$js = <<<'EOD'
var $addInvestorWrapper   = $('.add-investor-form');
var investorDataUrl       = $addInvestorWrapper.data('investor-data-url');
var addInvestorEmployeeId = parseInt($addInvestorWrapper.data('employee-id'), 10);
var $assignInvestorForm   = $('#assign-existing-investor-form');

function resetAssignInvestorPreview() {
    $('#assign-investor-preview').hide();
    $assignInvestorForm.find('.assign-investor-submit-btn').prop('disabled', true);
}

$('#assign-investor-select').on('select2:select', function(e) {
    $.getJSON(investorDataUrl, { id: e.params.data.id }, function(response) {
        if (!response.success) {
            toastr.error(response.message);
            resetAssignInvestorPreview();
            return;
        }

        var investor = response.investor;
        var $preview = $('#assign-investor-preview');

        $preview.find('.assign-investor-name').text(investor.full_name);
        $preview.find('.assign-investor-email').text(investor.email || '—');
        $preview.find('.assign-investor-type').text(investor.type_name || '—');
        $preview.find('.assign-investor-net-value').text(investor.net_value !== null ? investor.net_value : '—');
        $preview.find('.assign-investor-address').text(investor.address || '—');
        $preview.find('.assign-investor-comment').text(investor.comment || '—');

        var isAlreadyAssigned = false;
        var $employeesCell    = $preview.find('.assign-investor-employees').empty();

        if (response.employees.length) {
            response.employees.forEach(function(investorEmployee) {
                if (parseInt(investorEmployee.id, 10) === addInvestorEmployeeId) {
                    isAlreadyAssigned = true;
                }
                $('<div></div>').text(investorEmployee.name).appendTo($employeesCell);
            });
        } else {
            $employeesCell.text('—');
        }

        var hasOtherEmployees = response.employees.length > (isAlreadyAssigned ? 1 : 0);

        $preview.find('.assign-investor-same-warning').toggle(isAlreadyAssigned);
        $preview.find('.assign-investor-shared-notice').toggle(!isAlreadyAssigned && hasOtherEmployees);

        $assignInvestorForm.find('.assign-investor-submit-btn').prop('disabled', isAlreadyAssigned);

        $preview.show();
    }).fail(function() {
        toastr.error('Failed to load investor data');
        resetAssignInvestorPreview();
    });
});

$('#assign-investor-select').on('select2:clear', function() {
    resetAssignInvestorPreview();
});
EOD;

$this->registerJs($js, \yii\web\View::POS_END);
?>