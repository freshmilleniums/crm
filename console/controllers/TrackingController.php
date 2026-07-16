<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\Tasks;
use common\models\Packages;

/**
 * Console controller for updating tracking statuses
 *
 */
class TrackingController extends Controller
{
    /**
     * @var int Size of batch for processing records (to avoid memory issues)
     */
    public $batchSize = 50;

    /**
     * @var int Delay between API requests in milliseconds (to avoid rate limiting)
     */
    public $requestDelay = 200;

    /**
     * Update tracking statuses for both tasks and packages
     *
     * @return int Exit code
     */
    public function actionUpdateAll()
    {
        $this->stdout("Starting tracking status update for all active items...\n");

        $tasksUpdated = 0;
        $packagesUpdated = 0;
        $tasksErrors = 0;
        $packagesErrors = 0;

        // Process tasks
        $this->stdout("Processing tasks...\n");
        $tasksQuery = Tasks::find()
            ->where([
                'status' => [Tasks::STATUS_NEW, Tasks::STATUS_IN_PROGRESS]
            ])
            ->andWhere(['!=', 'track', ''])
            ->andWhere(['IS NOT', 'track', null]);

        $totalTasks = $tasksQuery->count();
        $this->stdout("Found {$totalTasks} tasks to update.\n");

        foreach ($tasksQuery->batch($this->batchSize) as $tasks) {
            foreach ($tasks as $task) {
                try {
                    $this->updateTrackingStatus($task);
                    $tasksUpdated++;

                    if ($this->requestDelay > 0) {
                        usleep($this->requestDelay * 1000);
                    }

                } catch (\Exception $e) {
                    $tasksErrors++;
                    $this->stderr("Error updating task {$task->id}: " . $e->getMessage() . "\n");
                }
            }
        }

        // Process packages
        $this->stdout("Processing packages...\n");
        $packagesQuery = Packages::find()
            ->where([
                'status' => [Packages::STATUS_NEW, Packages::STATUS_IN_PROGRESS]
            ])
            ->andWhere(['!=', 'track', ''])
            ->andWhere(['IS NOT', 'track', null]);

        $totalPackages = $packagesQuery->count();
        $this->stdout("Found {$totalPackages} packages to update.\n");

        foreach ($packagesQuery->batch($this->batchSize) as $packages) {
            foreach ($packages as $package) {
                try {
                    $this->updateTrackingStatus($package);
                    $packagesUpdated++;

                    if ($this->requestDelay > 0) {
                        usleep($this->requestDelay * 1000);
                    }

                } catch (\Exception $e) {
                    $packagesErrors++;
                    $this->stderr("Error updating package {$package->id}: " . $e->getMessage() . "\n");
                }
            }
        }

        $this->stdout("Update completed:\n");
        $this->stdout("Tasks - Updated: {$tasksUpdated}, Errors: {$tasksErrors}\n");
        $this->stdout("Packages - Updated: {$packagesUpdated}, Errors: {$packagesErrors}\n");

        return ExitCode::OK;
    }

    /**
     * Update tracking status for a single model (Task or Package)
     *
     * @param \yii\db\ActiveRecord $model
     * @throws \Exception
     */
    private function updateTrackingStatus($model)
    {
        if (empty($model->track)) {
            throw new \Exception('No tracking number found');
        }

        $afterShip = Yii::$app->aftership;
        $trackingData = $afterShip->getTrackingStatus($model->track);

        if ($trackingData) {
            $statusText = $trackingData['subtag_message'] ?? $trackingData['tag'];

            $model->track_status = $statusText;
            $model->track_status_update = time();

            if (!$model->save(false)) {
                throw new \Exception('Failed to save model');
            }
        } else {
            throw new \Exception('No tracking data found');
        }
    }
}