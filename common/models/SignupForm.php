<?php

namespace common\models;

use Yii;
use yii\base\Model;
use common\models\User;

/**
 * Signup form
 */
class SignupForm extends Model
{
    public $first_name;
    public $last_name;
    public $email;
    public $phone_number;
    public $home_phone;
    public $position_title;
    public $address;
    public $city;
    public $state;
    public $country;
    public $zip_code;
    public $hr_source;
    public $password;
    public $password_repeat;
    public $documents;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name', 'last_name', 'email', 'phone_number', 'home_phone', 'position_title', 'address', 'city', 'state', 'country', 'zip_code', 'password'], 'required'],
            [['first_name', 'last_name', 'city', 'state', 'zip_code', 'position_title', 'hr_source'], 'string', 'max' => 255],
            [['phone_number', 'home_phone'], 'string', 'max' => 20],
            [['address'], 'string', 'max' => 500],
            [['country'], 'string', 'max' => 100],
            ['email', 'trim'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This email address has already been taken.'],
            ['password', 'required'],
            ['password', 'string', 'min' => Yii::$app->params['user.passwordMinLength'] ?? 6],
            ['password_repeat', 'required'],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
            [['documents'], 'each', 'rule' => [
                'file',
                'skipOnEmpty' => true,
                'extensions'  => 'png, jpg, jpeg, pdf, doc, docx, txt, rtf',
                'maxSize'     => 10 * 1024 * 1024,
            ]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'home_phone' => 'Home Phone',
            'position_title' => 'Position Title',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State/Region',
            'country' => 'Country',
            'zip_code' => 'ZIP Code',
            'hr_source' => 'HR Source',
            'password' => 'Password',
            'password_repeat' => 'Repeat Password',
        ];
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup()
    {
        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->first_name = $this->first_name;
        $user->last_name = $this->last_name;
        $user->email = $this->email;
        $user->phone_number = $this->phone_number;
        $user->home_phone = $this->home_phone;
        $user->position_title = $this->position_title;
        $user->address = $this->address;
        $user->city = $this->city;
        $user->state = $this->state;
        $user->country = $this->country;
        $user->zip_code = $this->zip_code;
        $user->hr_source = $this->hr_source;
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();

        if ($user->save()) {
            // Save uploaded documents
            $uploadedFiles = \yii\web\UploadedFile::getInstancesByName('documents');
            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile->size === 0) {
                    continue;
                }
                $document       = new \common\models\UserDocument();
                $document->user_id     = $user->id;
                $document->uploaded_by = null;
                $document->file        = $uploadedFile;
                if ($document->upload()) {
                    $document->save(false);
                }
            }
            return $user;
        }

        return null;
    }

    /**
     * Sends confirmation email to user
     * @param User $user user model to with email should be send
     * @return bool whether the email was sent
     */
    protected function sendEmail($user)
    {
        return Yii::$app
            ->mailer
            ->compose(
                ['html' => 'emailVerify-html', 'text' => 'emailVerify-text'],
                ['user' => $user]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($this->email)
            ->setSubject('Account registration at ' . Yii::$app->name)
            ->send();
    }
}