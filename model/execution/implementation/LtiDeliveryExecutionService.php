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
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\tao\model\actionQueue\ActionQueue;
use oat\ltiDeliveryProvider\model\actions\GetActiveDeliveryExecution;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;

/**
 * Class LtiDeliveryExecutionService
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package oat\ltiDeliveryProvider\model\execution
 */
class LtiDeliveryExecutionService extends ConfigurableService implements LtiDeliveryExecutionServiceInterface
{

    const OPTION_PERSISTENCE = 'persistence';

    /**
     * @inheritdoc
     */
    public function isFinished(DeliveryExecution $deliveryExecution)
    {
        return $deliveryExecution->getState()->getUri() === DeliveryExecution::STATE_FINISHIED;
    }

    /**
     * Returns an array of DeliveryExecution
     *
     * @param \core_kernel_classes_Resource $delivery
     * @param \core_kernel_classes_Resource $link
     * @param string $userId
     * @throws
     * @return DeliveryExecution[]
     */
    public function getLinkedDeliveryExecutions(\core_kernel_classes_Resource $delivery, \core_kernel_classes_Resource $link, $userId)
    {
        $class = new \core_kernel_classes_Class(OntologyLTIDeliveryExecutionLink::CLASS_LTI_DELIVERYEXECUTION_LINK);
        $links = $class->searchInstances([
            OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_USER => $userId,
            OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_LINK => $link,
        ], [
            'like' => false
        ]);
        $result = [];
        foreach ($links as $link) {
            $execId = $link->getUniquePropertyValue(new \core_kernel_classes_Property(OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_EXEC_ID));
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($execId);
            if ($delivery->equals($deliveryExecution->getDelivery())) {
                $result[] = $deliveryExecution;
            }
        }
        return $result;
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
        $persistenceId = $this->getOption(self::OPTION_PERSISTENCE);
        return $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById($persistenceId);
    }


	 /**
     * @inheritdoc
     */
    public function createDeliveryExecutionLink($userUri, $link, $deliveryExecutionUri)
    {
        $class = new \core_kernel_classes_Class(OntologyLTIDeliveryExecutionLink::CLASS_LTI_DELIVERYEXECUTION_LINK);
        $link = $class->createInstanceWithProperties(array(
            OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_USER => $userUri,
            OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_LINK => $link,
            OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_EXEC_ID => $deliveryExecutionUri
        ));

        return $link;
    }
}