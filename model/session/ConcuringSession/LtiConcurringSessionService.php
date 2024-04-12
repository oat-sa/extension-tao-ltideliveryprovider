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
 * Copyright (c) 2024 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\model\session\ConcuringSession;

use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use core_kernel_classes_Resource;

class LtiConcurringSessionService
{
    private LtiDeliveryExecutionService $ltiDeliveryExecutionService;

    public function __construct(LtiDeliveryExecutionService $ltiDeliveryExecutionService)
    {
        $this->ltiDeliveryExecutionService = $ltiDeliveryExecutionService;
    }
    /**
     * @param core_kernel_classes_Resource $delivery
     *
     * @return mixed|null|DeliveryExecution
     */
    public function getActiveDeliveryExecution(core_kernel_classes_Resource $delivery)
    {
        return  $this->ltiDeliveryExecutionService
            ->getActiveDeliveryExecution($delivery);
    }
}
