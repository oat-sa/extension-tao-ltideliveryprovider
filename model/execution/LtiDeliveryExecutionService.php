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

use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDelete;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;

/**
 * Interface LtiDeliveryExecutionService
 *
 * Service is used to make lti specific delivery executions actions
 *
 * @package oat\ltiDeliveryProvider\model\execution
 */
interface LtiDeliveryExecutionService extends DeliveryExecutionDelete
{
    const SERVICE_ID = 'ltiDeliveryProvider/LtiDeliveryExecution';

    /**
     * Get delivery executions linked to user and $link resource
     *
     * @param \core_kernel_classes_Resource $delivery
     * @param \core_kernel_classes_Resource $link
     * @param string $userId
     * @return DeliveryExecution[]
     */
    public function getLinkedDeliveryExecutions(\core_kernel_classes_Resource $delivery, \core_kernel_classes_Resource $link, $userId);

    /**
     * Get delivery active execution by delivery for current user
     * @param \core_kernel_classes_Resource $delivery
     * @return mixed
     */
    public function getActiveDeliveryExecution(\core_kernel_classes_Resource $delivery);

    /**
     * Listener of changing delivery execution state event
     * @param DeliveryExecutionState $event
     * @return mixed
     */
    public function executionStateChanged(DeliveryExecutionState $event);

	/**
     * create a LTIDeliveryExecutionLink from parameters
     * @param string $userUri
     * @param string $link
     * @param string $deliveryExecutionUri
     * @return LTIDeliveryExecutionLink
     */
    public function createDeliveryExecutionLink($userUri, $link, $deliveryExecutionUri);
}