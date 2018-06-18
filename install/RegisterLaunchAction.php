<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\ltiDeliveryProvider\install;

use oat\ltiDeliveryProvider\model\metrics\activeLimitRestriction;
use oat\ltiDeliveryProvider\model\metrics\implementation\activeExecutionsMetrics;
use oat\oatbox\extension\AbstractAction;
use oat\tao\model\actionQueue\ActionQueue;
use oat\ltiDeliveryProvider\model\actions\GetActiveDeliveryExecution;
use oat\tao\model\metrics\MetricsService;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\oatbox\event\EventManager;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;

/**
 * Installation action that register delivery launch action in the action queue.
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RegisterLaunchAction extends AbstractAction
{

    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function __invoke($params)
    {
        $actionQueue = $this->getServiceManager()->get(ActionQueue::SERVICE_ID);
        $actions = $actionQueue->getOption(ActionQueue::OPTION_ACTIONS);
        $actions[GetActiveDeliveryExecution::class] = [
                'restrictions' => array(
                    activeLimitRestriction::class => 0
                ),
            ActionQueue::ACTION_PARAM_TTL => 3600, //one hour
        ];
        $actionQueue->setOption(ActionQueue::OPTION_ACTIONS, $actions);
        $this->getServiceManager()->register(ActionQueue::SERVICE_ID, $actionQueue);

        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->attach(DeliveryExecutionState::class, [LtiDeliveryExecutionService::SERVICE_ID, 'executionStateChanged']);
        $eventManager->attach(DeliveryExecutionCreated::class, [LtiDeliveryExecutionService::SERVICE_ID, 'executionCreated']);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        $metricsService = $this->getServiceManager()->get(MetricsService::class);
        $metrics = $metricsService->getOption($metricsService::OPTION_METRICS);

        $metrics[activeExecutionsMetrics::class]=new activeExecutionsMetrics([
            activeExecutionsMetrics::OPTION_TTL => 1,
            activeExecutionsMetrics::OPTION_PERSISTENCE => 'cache'
        ]);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('GetActiveDeliveryExecution action registered'));
    }
}
