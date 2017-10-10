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
use oat\ltiDeliveryProvider\controller\DeliveryTool;
use \oat\ltiDeliveryProvider\model\LTIDeliveryTool;
/**
 * Class LtiDeliveryExecutionService
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package oat\ltiDeliveryProvider\model\execution
 */
class LtiDeliveryExecutionService extends ConfigurableService implements LtiDeliveryExecutionServiceInterface
{
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
        $class = new \core_kernel_classes_Class(CLASS_LTI_DELIVERYEXECUTION_LINK);
        $links = $class->searchInstances([
            PROPERTY_LTI_DEL_EXEC_LINK_USER => $userId,
            PROPERTY_LTI_DEL_EXEC_LINK_LINK => $link,
        ], [
            'like' => false
        ]);
        $result = [];
        foreach ($links as $link) {
            $execId = $link->getUniquePropertyValue(new \core_kernel_classes_Property(PROPERTY_LTI_DEL_EXEC_LINK_EXEC_ID));
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($execId);
            if ($delivery->equals($deliveryExecution->getDelivery())) {
                $result[] = $deliveryExecution;
            }
        }
        return $result;
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @return DeliveryExecution
     * @throws
     */
    public function getActiveDeliveryExecution(\core_kernel_classes_Resource $delivery)
    {
        $remoteLink = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLtiLinkResource();
        $user = \common_session_SessionManager::getSession()->getUser();

        $launchData = \taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        if ($launchData->hasVariable(DeliveryTool::PARAM_FORCE_RESTART) && $launchData->getVariable(DeliveryTool::PARAM_FORCE_RESTART) == 'true') {
            // ignore existing executions to force restart
            $executions = array();
        } else {
            $executions = $this->getLinkedDeliveryExecutions($delivery, $remoteLink, $user->getIdentifier());
        }

        $active = null;

        if (empty($executions)) {
            $active = $this->getTool()->startDelivery($delivery, $remoteLink, $user);
        } else {
            foreach ($executions as $deliveryExecution) {
                if (!$this->isFinished($deliveryExecution)) {
                    $active = $deliveryExecution;
                    break;
                }
            }
        }
        return $active;
    }

    /**
     * @return LTIDeliveryTool
     */
    protected function getTool()
    {
        return LTIDeliveryTool::singleton();
    }
}