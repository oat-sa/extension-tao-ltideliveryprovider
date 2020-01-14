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
 * Copyright (c) 2016-2017  (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\scripts\install;

use oat\ltiDeliveryProvider\model\LtiOutcomeService;
use oat\ltiDeliveryProvider\model\ResultAliasService;
use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoDelivery\model\execution\Counter\DeliveryExecutionCounterInterface;
use oat\taoDelivery\model\execution\Counter\DeliveryExecutionCounterService;

/**
 * Class RegisterServices
 * @package oat\ltiDeliveryProvider\scripts\install
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RegisterServices extends InstallAction
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_exception_Error
     */
    public function __invoke($params)
    {
        $this->getServiceManager()->register(ResultAliasService::SERVICE_ID, new ResultAliasService());
        $this->getServiceManager()->register(LtiOutcomeService::SERVICE_ID, new LtiOutcomeService());
        $this->getServiceManager()->register(
            DeliveryExecutionCounterInterface::SERVICE_ID,
            new DeliveryExecutionCounterService([
                DeliveryExecutionCounterService::OPTION_PERSISTENCE => 'cache'
            ])
        );

        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $eventManager->attach(DeliveryExecutionState::class, [LtiOutcomeService::SERVICE_ID, 'deferTransmit']);

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Registered and created ResultAliasService / LtiOutcomeService services'));
    }
}
