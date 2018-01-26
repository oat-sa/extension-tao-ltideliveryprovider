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

use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteRequest;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;

/**
 * Class LtiDeliveryExecutionService
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package oat\ltiDeliveryProvider\model\execution
 */
class LtiDeliveryExecutionService extends AbstractLtiDeliveryExecutionService
{

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

    /**
     * @inheritdoc
     */
    public function deleteDeliveryExecutionData(DeliveryExecutionDeleteRequest $request)
    {
        $removed = [];
        $deliveryExecutionUri = $request->getDeliveryExecution()->getIdentifier();
        $userUri = $request->getDeliveryExecution()->getUserIdentifier();
        $class = new \core_kernel_classes_Class(OntologyLTIDeliveryExecutionLink::CLASS_LTI_DELIVERYEXECUTION_LINK);

        $resources = $class->searchInstances([
            OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_USER => $userUri,
            OntologyLTIDeliveryExecutionLink::PROPERTY_LTI_DEL_EXEC_LINK_EXEC_ID => $deliveryExecutionUri
        ]);

        /** @var \core_kernel_classes_Resource $resource */
        foreach ($resources as $resource){
            $removed[] = $resource->delete();
        }

        return !in_array(false, $removed);
    }
}