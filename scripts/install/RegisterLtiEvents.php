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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\scripts\install;

use oat\ltiDeliveryProvider\model\events\LtiAgsListener;
use oat\oatbox\extension\InstallAction;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoQtiTest\models\event\DeliveryExecutionFinish;
use oat\taoResultServer\models\Events\DeliveryExecutionResultsRecalculated;

class RegisterLtiEvents extends InstallAction
{
    public function __invoke($params)
    {
        $this->registerEvent(
            DeliveryExecutionCreated::class,
            [LtiAgsListener::class, 'onDeliveryExecutionStart']
        );

        $this->registerEvent(
            DeliveryExecutionResultsRecalculated::class,
            [LtiAgsListener::class, 'onDeliveryExecutionResultsRecalculated']
        );
        $this->registerEvent(
            DeliveryExecutionFinish::class,
            [LtiAgsListener::class, 'onDeliveryExecutionFinish']
        );
    }
}
