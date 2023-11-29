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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA.
 *
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\model\navigation\Command;

use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;

class GenerateReturnUrlCommand
{
    private LtiLaunchData $launchData;
    private DeliveryExecutionInterface $deliveryExecution;
    private bool $isCustomFeedback;
    private array $queryStringData;

    public function __construct(
        LtiLaunchData $launchData,
        DeliveryExecutionInterface $deliveryExecution,
        bool $isCustomFeedback = false,
        array $queryStringData = []
    ) {
        $this->launchData = $launchData;
        $this->deliveryExecution = $deliveryExecution;
        $this->isCustomFeedback = $isCustomFeedback;
        $this->queryStringData = $queryStringData;
    }

    public function isCustomFeedback(): bool
    {
        return $this->isCustomFeedback;
    }

    public function getQueryStringData(): array
    {
        return $this->queryStringData;
    }

    public function getLaunchData(): LtiLaunchData
    {
        return $this->launchData;
    }

    public function getDeliveryExecution(): DeliveryExecutionInterface
    {
        return $this->deliveryExecution;
    }
}
