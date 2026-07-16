<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use common\models\User;

class ChangePasswordForm extends Model
{
    public $new_password;
    public $confirm_password;

    /**
     * @var User
     */
    private $_user;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['new_password', 'confirm_password'], 'required'],
            ['new_password', 'string', 'min' => 4, 'max' => 255],
            ['confirm_password', 'string', 'min' => 4, 'max' => 255],
            ['confirm_password', 'compare', 'compareAttribute' => 'new_password', 'message' => 'Passwords do not match.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'new_password' => 'New Password',
            'confirm_password' => 'Confirm Password',
        ];
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->_user = $user;
    }

    /**
     * @return bool
     */
    public function changePassword()
    {
        if (!$this->validate()) {
            return false;
        }

        if (!$this->_user) {
            $this->addError('new_password', 'User not found.');
            return false;
        }

        $this->_user->setPassword($this->new_password);

        if ($this->_user->save()) {
            $this->syncMailPassword($this->new_password);
            return true;
        }

        foreach ($this->_user->getErrors() as $attribute => $errors) {
            foreach ($errors as $error) {
                $this->addError('new_password', $error);
            }
        }

        return false;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->_user;
    }

    private function syncMailPassword($newPassword)
    {
        $corporateEmail = \common\models\UserCorporateEmail::findOne(['user_id' => $this->_user->id]);

        if (!$corporateEmail) {
            return;
        }

        $email = $corporateEmail->email;
        $salt  = bin2hex(random_bytes(8));
        $hash  = '{SHA512-CRYPT}' . crypt($newPassword, '$6$' . $salt);

        $corporateEmail->plainPassword = $newPassword;
        $corporateEmail->save();

        try {
            Yii::$app->mailDb->createCommand()->update(
                'mail_users',
                ['password' => $hash],
                ['email' => $email]
            )->execute();
        } catch (\Exception $e) {
            Yii::error("Failed to update mail_user password for {$email}: " . $e->getMessage());
        }

        $result = shell_exec(
            'sudo /usr/local/bin/mailbox-manager.sh update '
            . escapeshellarg($email) . ' '
            . escapeshellarg($hash)
        );
        Yii::info("mailbox-manager update result: {$result}", 'email');
    }
}