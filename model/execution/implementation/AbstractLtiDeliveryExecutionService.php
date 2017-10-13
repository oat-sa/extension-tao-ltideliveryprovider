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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\model\execution\implementation;

use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService as LtiDeliveryExecutionServiceInterface;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\tao\model\actionQueue\ActionQueue;
use oat\ltiDeliveryProvider\model\actions\GetActiveDeliveryExecution;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;

/**
 * Class AbstractLtiDeliveryExecutionService
 * @author Antoine Robin, <antoine@taotesting.com>
 * @package oat\ltiDeliveryProvider\model\execution
 */
abstract class AbstractLtiDeliveryExecutionService extends ConfigurableService implements LtiDeliveryExecutionServiceInterface
{

    const OPTION_QUEUE_PERSISTENCE = 'queue_persistence';

    /**
     * @inheritdoc
     */
    public function isFinished(DeliveryExecution $deliveryExecution)
    {
        return $deliveryExecution->getState()->getUri() === DeliveryExecution::STATE_FINISHIED;
    }


    /**
     * @inheritdoc
     */
    public function getActiveDeliveryExecution(\core_kernel_classes_Resource $delivery)
    {
        /** @var ActionQueue $actionQueue */
        $actionQueue = $this->getServiceManager()->get(ActionQueue::SERVICE_ID);
        $action = new GetActiveDeliveryExecution($delivery);
        if ($actionQueue->perform($action)) {
            return $action->getResult();
        } else {
            throw new \oat\tao\model\actionQueue\ActionFullException($actionQueue->getPosition($action));
        }
    }

    /**
     * @param DeliveryExecutionState $event
     */
    public function executionStateChanged(DeliveryExecutionState $event)
    {
        $persistence = $this->getPersistence();
        if ($event->getState() === DeliveryExecution::STATE_ACTIVE) {
            $persistence->incr(self::class.'_'.'active_executions');
        } else if ($event->getPreviousState() === DeliveryExecution::STATE_ACTIVE) {
            $persistence->decr(self::class.'_'.'active_executions');
        }
    }

    /**
     * @param DeliveryExecutionCreated $event
     * @throws
     */
    public function executionCreated(DeliveryExecutionCreated $event)
    {
        $persistence = $this->getPersistence();
        if ($event->getDeliveryExecution()->getState()->getUri() === DeliveryExecution::STATE_ACTIVE) {
            $persistence->incr(self::class.'_'.'active_executions');
        }
    }

    /**
     * @return int
     */
    public function getNumberOfActiveDeliveryExecutions()
    {
        return intval($this->getPersistence()->get(self::class.'_'.'active_executions'));
    }

    /**
     * @return \common_persistence_KeyValuePersistence
     */
    protected function getPersistence()
    {
        $persistenceId = $this->getOption(self::OPTION_QUEUE_PERSISTENCE);
        return $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById($persistenceId);
    }
}