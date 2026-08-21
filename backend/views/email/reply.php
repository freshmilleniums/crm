<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $account common\models\UserCorporateEmail|common\models\EmailAccount */
/* @var $accountType string */
/* @var $replyTo common\models\CorporateEmailMessage|common\models\ExternalEmailMessage */

$this->title = 'Reply: ' . $replyTo->subject;
$this->params['breadcrumbs'][] = ['label' => 'Email', 'url' => ['index', 'account_id' => $account->id, 'account_type' => $accountType]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-reply"></i> Reply</h3>
        </div>
        <div class="card-body">
            <?= $this->render('_compose_form', [
                'account' => $account,
                'accountType' => $accountType,
                'replyTo' => $replyTo,
            ]) ?>
        </div>
    </div>
</div>