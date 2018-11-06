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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\model\navigation;

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;

class DefaultMessageFactory extends ConfigurableService
{
    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return LtiMessage
     * @throws \common_exception_NotFound
     */
    public function getLtiMessage(DeliveryExecutionInterface $deliveryExecution)
    {
        $state = $deliveryExecution->getState();
        $code = null;
        switch ($state->getUri()) {
            case DeliveryExecutionInterface::STATE_ACTIVE:
                $code = 100;
                break;
            case DeliveryExecutionInterface::STATE_PAUSED:
                $code = 101;
                break;
            case DeliveryExecutionInterface::STATE_FINISHED:
                $code = 200;
                break;
            case DeliveryExecutionInterface::STATE_TERMINATED:
                $code = 201;
                break;
        }
        return new LtiMessage('', $code);
    }
}