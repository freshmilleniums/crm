<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\services\RemindersService;

/**
 * Console controller for processing reminders
 */
class RemindersController extends Controller
{
    /**
     * Process all reminders
     * Usage: php yii reminders/process-all
     */
    public function actionProcessAll()
    {
        echo "Starting reminders processing...\n";

        $remindersService = Yii::createObject(RemindersService::class);

        for ($i = 1; $i <= 10; $i++) {
            $reminderCode = 'REM' . $i;
            $methodName = 'process' . $reminderCode;

            if (method_exists($remindersService, $methodName)) {
                $remindersService->$methodName();
            } else {
                Yii::stderr("Method {$methodName} not found.\n");
            }
        }

        echo "Reminders processing completed.\n";
        return Controller::EXIT_CODE_NORMAL;
    }

    /**
     * Process specific reminder by code
     * Usage: php yii reminders/process-reminder REM1
     *
     * @param string $code Reminder code (REM1-REM10)
     */
    public function actionProcessReminder($code)
    {
        echo "Starting processing for reminder: {$code}\n";

        if (!preg_match('/^REM([1-9]|10)$/', $code)) {
            Yii::stderr("Invalid reminder code format: {$code}. Use REM1 to REM10.\n");
            return Controller::EXIT_CODE_ERROR;
        }

        $remindersService = Yii::createObject(RemindersService::class);
        $methodName = 'process' . $code;

        if (method_exists($remindersService, $methodName)) {
            $remindersService->$methodName();
            echo "Processing for {$code} completed.\n";
        } else {
            Yii::stderr("Method {$methodName} not found in RemindersService.\n");
            return Controller::EXIT_CODE_ERROR;
        }

        return Controller::EXIT_CODE_NORMAL;
    }
}