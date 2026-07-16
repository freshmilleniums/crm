<?php

namespace common\services;

use Yii;
use common\models\UserCorporateEmail;
use common\models\CorporateEmailMessage;
use common\models\EmailAccount;
use common\models\EmailAccountUser;
use common\models\ExternalEmailMessage;
use common\models\ExternalEmailReadStatus;

class EmailUnreadService
{
    public static function getUnreadCount($userId)
    {
        $count = 0;

        // Corporate email unread
        $corporateEmail = UserCorporateEmail::findOne([
            'user_id'   => $userId,
            'is_active' => 1,
        ]);

        if ($corporateEmail) {
            $count += (int) CorporateEmailMessage::find()
                ->where([
                    'corporate_email_id' => $corporateEmail->id,
                    'is_read'            => 0,
                    'folder'             => 'INBOX',
                    'direction'          => 'incoming',
                ])
                ->count();
        }

        // External emails unread
        $externalAccounts = EmailAccount::find()
            ->innerJoin('email_account_users', 'email_account_users.email_account_id = email_accounts.id')
            ->where([
                'email_account_users.user_id' => $userId,
                'email_accounts.is_active'    => 1,
            ])
            ->all();

        foreach ($externalAccounts as $account) {
            $count += (int) ExternalEmailMessage::find()
                ->alias('m')
                ->leftJoin(
                    'external_email_read_status rs',
                    'rs.message_id = m.id AND rs.user_id = :userId',
                    [':userId' => $userId]
                )
                ->where([
                    'm.email_account_id' => $account->id,
                    'm.folder'           => 'INBOX',
                    'm.direction'        => 'incoming',
                ])
                ->andWhere(['or',
                    ['rs.is_read' => 0],
                    ['rs.is_read' => null],
                ])
                ->count();
        }

        return $count;
    }
}