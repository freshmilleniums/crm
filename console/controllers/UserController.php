<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\User;

class UserController extends Controller
{
    public function actionCreateSuperAdmin($email, $password)
    {
        $user = new User();
        $user->email = $email;
        $user->first_name = 'Super';
        $user->last_name = 'Admin';
        $user->status = User::STATUS_ACTIVE;
        $user->company_id = 0;
        $user->role = 'super-administrator';

        $user->setPassword($password);
        $user->generateAuthKey();

        if ($user->save()) {
            $auth = Yii::$app->authManager;
            $role = $auth->getRole('super-administrator');

            if ($role) {
                $auth->assign($role, $user->id);
                $this->stdout("Super administrator created successfully!\n", \yii\helpers\Console::FG_GREEN);
                $this->stdout("Email: {$email}\n");
                $this->stdout("Password: {$password}\n");
                $this->stdout("User ID: {$user->id}\n");
                return Controller::EXIT_CODE_NORMAL;
            } else {
                $this->stderr("Error: Role 'super-administrator' not found!\n", \yii\helpers\Console::FG_RED);
                $user->delete();
                return Controller::EXIT_CODE_ERROR;
            }
        } else {
            $this->stderr("Error creating user:\n", \yii\helpers\Console::FG_RED);
            print_r($user->errors);
            return Controller::EXIT_CODE_ERROR;
        }
    }
}