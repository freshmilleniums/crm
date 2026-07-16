<?php

namespace common\components;

use Yii;
use yii\base\Component;
use yii\base\BootstrapInterface;

class CompanyBootstrapComponent extends Component implements BootstrapInterface
{
    /**
     * Bootstrap method that runs on application initialization
     * Updates company_id parameter from session if available
     *
     * @param \yii\base\Application $app
     */
    public function bootstrap($app)
    {
        // Skip for console applications
        if ($app instanceof \yii\console\Application) {
            return;
        }

        // Update company_id from session if available
        $sessionCompanyId = $app->session->get('call_center_company_id');
        if ($sessionCompanyId) {
            $app->params['company_id'] = $sessionCompanyId;
        }
    }
}