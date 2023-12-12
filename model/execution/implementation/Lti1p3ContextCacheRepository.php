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
 * Copyright (c) 2023  (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\model\execution\implementation;

use oat\ltiDeliveryProvider\model\execution\LtiContextRepositoryInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiLaunchData;
use Psr\Cache\CacheItemPoolInterface;

class Lti1p3ContextCacheRepository implements LtiContextRepositoryInterface
{
    private CacheItemPoolInterface $cache;

    private const KEY_PREFIX = 'de_lti1p3context_';

    public function __construct(?CacheItemPoolInterface $cache = null)
    {
        $this->cache = $cache;
    }

    public function findByDeliveryExecutionId($deliveryExecutionId): ?LtiLaunchData
    {

        $item = $this->cache->getItem($this->buildKey($deliveryExecutionId));

        if ($data = $item->get()) {
            return LtiLaunchData::fromJsonArray(json_decode($data, true));
        }

        return null;
    }

    public function findByDeliveryExecution(DeliveryExecutionInterface $deliveryExecution): ?LtiLaunchData
    {

        $item = $this->cache->getItem($this->buildKey($deliveryExecution->getIdentifier()));

        if ($data = $item->get()) {
            return LtiLaunchData::fromJsonArray(json_decode($data, true));
        }

        return null;
    }

    public function save(LtiLaunchData $ltiLaunchData, DeliveryExecutionInterface $deliveryExecution): void
    {
        $key = $this->buildKey($deliveryExecution->getIdentifier());
        $item = $this->cache->getItem($key);

        $this->cache->save(
            $item->set(json_encode($ltiLaunchData))
        );
    }

    private function buildKey(string $deliveryExecutionId): string
    {
        return self::KEY_PREFIX . $deliveryExecutionId;
    }
}
