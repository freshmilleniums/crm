<?php

namespace common\services;

use Yii;
use common\models\Template;
use common\models\UserSentTemplate;
use backend\models\User;
use backend\models\Chat;
use backend\models\ChatMessage;
use backend\models\ChatParticipant;

class TemplateSendService
{
    /**
     * Send template to employee, save history, send email, send chat message
     *
     * @param Template $template
     * @param int $employeeId
     * @param int $sentBy
     * @return array ['success' => bool, 'message' => string]
     */
    public function send(Template $template, int $employeeId, int $sentBy): array
    {
        $employee = User::findOne($employeeId);

        if (!$employee) {
            return ['success' => false, 'message' => 'Employee not found'];
        }

        $replaced = $template->replaceMacros($employeeId);
        $subject  = $replaced['subject'];
        $body     = $replaced['body'];

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $record          = UserSentTemplate::createFromTemplate($template, $employeeId, $sentBy);
            $record->subject = $subject;
            $record->body    = $body;

            if (!$record->save()) {
                throw new \Exception('Failed to save sent template record');
            }

            $emailSent = $this->sendEmail($employee, $subject, $body);
            $chatSent  = $this->sendChatMessage($employee, $body, $sentBy);

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Template sent successfully',
                'email'   => $emailSent,
                'chat'    => $chatSent,
            ];

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error('TemplateSendService error: ' . $e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send email to employee
     */
    private function sendEmail(User $employee, string $subject, string $body): bool
    {
        try {
            return Yii::$app->mailer
                ->compose()
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo($employee->email)
                ->setSubject($subject)
                ->setHtmlBody($body)
                ->send();
        } catch (\Exception $e) {
            Yii::error('Email send failed for user ' . $employee->id . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send message to employee chat
     */
    private function sendChatMessage(User $employee, string $body, int $sentBy): bool
    {
        try {
            $chat = Chat::findOne([
                'type'        => Chat::TYPE_EMPLOYEE,
                'employee_id' => $employee->id,
            ]);

            if (!$chat) {
                $chat              = new Chat();
                $chat->type        = Chat::TYPE_EMPLOYEE;
                $chat->employee_id = $employee->id;

                if (!$chat->save()) {
                    throw new \Exception('Failed to create employee chat');
                }

                $participant          = new ChatParticipant();
                $participant->chat_id = $chat->id;
                $participant->user_id = $employee->id;
                $participant->save();
            }

            $senderParticipant = ChatParticipant::findOne([
                'chat_id' => $chat->id,
                'user_id' => $sentBy,
                'left_at' => null,
            ]);

            if (!$senderParticipant) {
                $p          = new ChatParticipant();
                $p->chat_id = $chat->id;
                $p->user_id = $sentBy;
                $p->save();
            }

            $message               = new ChatMessage();
            $message->chat_id      = $chat->id;
            $message->sender_id    = $sentBy;
            $message->message_text = strip_tags($body);
            $message->message_type = ChatMessage::MESSAGE_TYPE_TEXT;

            return $message->save();

        } catch (\Exception $e) {
            Yii::error('Chat send failed for user ' . $employee->id . ': ' . $e->getMessage());
            return false;
        }
    }
}