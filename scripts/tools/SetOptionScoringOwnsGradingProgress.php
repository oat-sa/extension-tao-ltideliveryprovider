<?php

use oat\oatbox\extension\script\ScriptAction;
use oat\ltiDeliveryProvider\model\events\LtiAgsListener;
use oat\generis\model\DependencyInjection\ServiceOptions;

class SetOptionScoringOwnsGradingProgress extends ScriptAction
{
    protected function provideDescription()
    {
        return 'SCORE outcome of the test, summing both automatic scores and manual scores (when relevant)';
    }

    protected function provideOptions()
    {
        return [
            'enableOwnGrading' => [
                'longPrefix' => 'enableOwnGrading',
                'required' => false,
                'description' => 'Enable this option will change the mechanic of sending AGS status on survey complete from to not ready',
                'defaultValue' => false
            ],
        ];
    }

    protected function run()
    {
        $this->getServiceManager()
            ->get(ServiceOptions::SERVICE_ID)
            ->save(
                LtiAgsListener::SERVICE_ID
                , LtiAgsListener::OPTION_SCORING_OWNS_GRADING_PROGRESS
                , $this->getOption('enableOwnGrading')
            );
    }
}