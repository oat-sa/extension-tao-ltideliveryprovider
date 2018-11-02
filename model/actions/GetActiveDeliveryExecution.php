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

namespace oat\ltiDeliveryProvider\model\actions;

use oat\tao\model\actionQueue\AbstractQueuedAction;
use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\ltiDeliveryProvider\controller\DeliveryTool;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryServerService;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiService;
use oat\taoDelivery\model\execution\Counter\DeliveryExecutionCounterInterface;

/**
 * Class GetActiveDeliveryExecution
 * @package oat\ltiProctoring\model\actions
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class GetActiveDeliveryExecution extends AbstractQueuedAction
{
    protected $delivery;

    public function __construct(\core_kernel_classes_Resource $delivery = null)
    {
        $this->delivery = $delivery;
    }

    /**
     * @param $params
     * @return DeliveryExecution
     * @throws LtiException
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     * @throws \common_exception_Unauthorized
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
    public function __invoke($params)
    {
        $active = null;

        if ($this->delivery !== null) {
            $remoteLink = LtiService::singleton()->getLtiSession()->getLtiLinkResource();
            $user = \common_session_SessionManager::getSession()->getUser();

            $launchData = LtiService::singleton()->getLtiSession()->getLaunchData();
            /** @var LtiDeliveryExecutionService $deliveryExecutionService */
            $deliveryExecutionService = $this->getServiceManager()->get(LtiDeliveryExecutionService::SERVICE_ID);
            if ($launchData->hasVariable(DeliveryTool::PARAM_FORCE_RESTART) && $launchData->getVariable(DeliveryTool::PARAM_FORCE_RESTART) == 'true') {
                // ignore existing executions to force restart
                $executions = array();
            } else {
                $executions = $deliveryExecutionService->getLinkedDeliveryExecutions($this->delivery, $remoteLink, $user->getIdentifier());
            }

            if (empty($executions)) {
                $active = $this->getTool()->startDelivery($this->delivery, $remoteLink, $user);
            } else {
                $resumable = $this->getServiceLocator()->get(DeliveryServerService::SERVICE_ID)->getResumableStates();
                foreach ($executions as $deliveryExecution) {
                    if (in_array($deliveryExecution->getState()->getUri(), $resumable)) {
                        $active = $deliveryExecution;
                        break;
                    }
                }
            }
        } else {
            $this->logNotice('Attempt to invoke action `' . $this->getId() .'` without delivery');
        }

        return $active;
    }

    /**
     * Needed for statistics
     */
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * @return int
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function getNumberOfActiveActions()
    {
        /** @var LtiDeliveryExecutionService $deliveryExecutionService */
        $deliveryExecutionService = $this->getServiceManager()->get(DeliveryExecutionCounterInterface::SERVICE_ID);
        return $deliveryExecutionService->count(DeliveryExecution::STATE_ACTIVE);
    }

    /**
     * @return LTIDeliveryTool
     */
    protected function getTool()
    {
        return LTIDeliveryTool::singleton();
    }
}