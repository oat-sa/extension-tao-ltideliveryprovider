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

namespace oat\ltiDeliveryProvider\model\execution;

use oat\taoDelivery\model\execution\DeliveryExecution;

/**
 * Interface LtiDeliveryExecutionService
 *
 * Service is used to make lti specific delivery executions actions
 *
 * @package oat\ltiDeliveryProvider\model\execution
 */
interface LtiDeliveryExecutionService
{
    const SERVICE_ID = 'ltiDeliveryProvider/LtiDeliveryExecution';

    /**
     * Check whether delivery execution is finished or not (can be resumed).
     *
     * @param DeliveryExecution $deliveryExecution
     * @return boolean
     */
    public function isFinished(DeliveryExecution $deliveryExecution);

    /**
     * Get delivery executions linked to user and $link resource
     *
     * @param \core_kernel_classes_Resource $delivery
     * @param \core_kernel_classes_Resource $link
     * @param string $userId
     * @return DeliveryExecution[]
     */
    public function getLinkedDeliveryExecutions(\core_kernel_classes_Resource $delivery, \core_kernel_classes_Resource $link, $userId);
}