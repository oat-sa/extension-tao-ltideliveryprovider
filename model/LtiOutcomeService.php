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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\model;

use common_session_SessionManager;
use oat\ltiDeliveryProvider\model\tasks\SendLtiOutcomeTask;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoLti\models\classes\LtiService;
use oat\taoLti\models\classes\TaoLtiSession;

class LtiOutcomeService extends ConfigurableService
{
    const SERVICE_ID = 'ltiDeliveryProvider/LtiOutcome';

    /**
     * @param DeliveryExecutionState $event
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     * @throws \oat\taoLti\models\classes\LtiException
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    public function deferTransmit(DeliveryExecutionState $event)
    {
        if (DeliveryExecutionInterface::STATE_FINISHIED === $event->getState() && DeliveryExecutionInterface::STATE_FINISHIED !== $event->getPreviousState()
            && common_session_SessionManager::getSession() instanceof TaoLtiSession) {

            /** @var QueueDispatcherInterface $taskQueue */
            $taskQueue = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
            $launchData = LtiService::singleton()->getLtiSession()->getLaunchData();
            if ($launchData->hasVariable('lis_outcome_service_url')) {
                $params['deliveryResultIdentifier'] = $event->getDeliveryExecution()->getIdentifier();
                $params['consumerKey'] = $launchData->getOauthKey();
                $params['serviceUrl'] = $launchData->getVariable('lis_outcome_service_url');
                $taskQueue->createTask(new SendLtiOutcomeTask(), $params, 'Submit LTI results');
            }
        }

    }
}