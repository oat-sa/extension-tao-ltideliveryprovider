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
 * Copyright (c) 2018  (original work) Open Assessment Technologies SA;
 *
 * @author Ilya Yarkavets <ilya.yarkavets@1pt.com>
 */

namespace oat\ltiDeliveryProvider\scripts\cli;

use oat\oatbox\extension\AbstractAction;
use oat\taoDelivery\model\execution\Counter\DeliveryExecutionCounterInterface;
use oat\oatbox\service\ServiceNotFoundException;
use oat\taoDelivery\model\execution\Counter\DeliveryExecutionCounterService;
use \common_report_Report as Report;
use oat\oatbox\event\EventManager;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\tao\model\actionQueue\ActionQueue;
use oat\tao\model\actionQueue\implementation\InstantActionQueue;
use oat\ltiDeliveryProvider\model\actions\GetActiveDeliveryExecution;

class EnableDeliveryQueue extends AbstractAction
{
    public function __invoke($params)
    {
        $validationOutcome = $this->validateParameters($params);
        if ($validationOutcome instanceof Report) {
            return $validationOutcome;
        }
        list($persistenceId, $executions) = $params;
        $this->validateCounterservice($persistenceId);
        $this->setActiveExecutions($executions);
        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Queue setup with %s concurrent executions', $executions));
    }
    
    protected function validateParameters($params)
    {
        if (count($params) < 2) {
            return new Report(Report::TYPE_ERROR, __('Usage: %s [PERSISTENCE_ID] [MAX_EXECUTIONS]', __CLASS__));
        }
        list($persistenceId, $executions) = $params;
        $pm = $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID);
        if (!$pm->hasPersistence($persistenceId)) {
            return new Report(Report::TYPE_ERROR, __('Persistence "%s" not found', $persistenceId));
        }
        $persistence = $pm->getPersistenceById($persistenceId);
        if (!$persistence instanceof \common_persistence_AdvKeyValuePersistence) {
            return new Report(Report::TYPE_ERROR, __('Persistence "%s" needs to be an advanced key-value persistence', $persistenceId));
        }
        if (!is_numeric($executions) || $executions <= 0) {
            return new Report(Report::TYPE_ERROR, __('Please provide the number of concurrent executions as a whole number greater than 0'));
        }
    }
    
    protected function validateCounterservice($persistenceId)
    {
        // validate service present
        try {
            $counterService = $this->getServiceLocator()->get(DeliveryExecutionCounterInterface::SERVICE_ID);
        } catch (ServiceNotFoundException $e) {
            $counterService = new DeliveryExecutionCounterService([
                DeliveryExecutionCounterService::OPTION_PERSISTENCE => $persistenceId
            ]);
            $this->getServiceManager()->register(DeliveryExecutionCounterInterface::SERVICE_ID,$counterService);
        }
        // validate events registered
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->attach(DeliveryExecutionState::class, [DeliveryExecutionCounterInterface::SERVICE_ID, 'executionStateChanged']);
        $eventManager->attach(DeliveryExecutionCreated::class, [DeliveryExecutionCounterInterface::SERVICE_ID, 'executionCreated']);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }
    
    protected function setActiveExecutions($count)
    {
        $aq = $this->getServiceLocator()->get(ActionQueue::SERVICE_ID);
        if (!$aq instanceof InstantActionQueue) {
            throw new \common_exception_NoImplementation('Only InstantActionQueue supported as no interface for setting limits exists');
        }
        $actions = $aq->getOption(InstantActionQueue::OPTION_ACTIONS);
        $actions[GetActiveDeliveryExecution::class]['limit'] = $count;
        $aq->setOption(InstantActionQueue::OPTION_ACTIONS, $actions);
        $this->getServiceManager()->register(ActionQueue::SERVICE_ID, $aq);
    }
}