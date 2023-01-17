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

namespace oat\taoQtiItem\test\unit\model\import\Repository;

use oat\ltiDeliveryProvider\model\execution\implementation\Lti1p3ContextCacheRepository;
use oat\oatbox\cache\CacheItem;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiLaunchData;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class Lti1p3ContextCacheRepositoryTest extends TestCase
{
    private Lti1p3ContextCacheRepository $subject;

    public function setUp(): void
    {
        $this->storage = $this->createMock(CacheItemPoolInterface::class);
        $this->subject = new Lti1p3ContextCacheRepository($this->storage);
        $this->launchDataObject = new LtiLaunchData(['key' => 'value'], ['custom_key', 'value']);
        $this->deliveryExecution = new FakeDeliveryExecution();
    }

    public function testFindByDeliveryExecution(): void
    {
        $cacheItem = $this
            ->createConfiguredMock(
                CacheItemInterface::class,
                ['get' => json_encode($this->launchDataObject)]
            );
        $this->storage
            ->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $result = $this->subject->findByDeliveryExecution($this->deliveryExecution);

        $this->assertEquals($this->launchDataObject, $result);
    }

    public function testSave(): void
    {
        $cacheItem = new CacheItem('anykey');

        $this->storage
            ->expects($this->once())
            ->method('getItem')
            ->willReturn($cacheItem);

        $this->storage
            ->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(function (CacheItemInterface $subjToSave) {
                    return $subjToSave->get() === json_encode($this->launchDataObject);
                })
            );

        $this->subject->save($this->launchDataObject, $this->deliveryExecution);
    }
}

// phpcs:ignore
final class FakeDeliveryExecution implements DeliveryExecutionInterface
{
    public function getIdentifier()
    {
        return 'test/test#deliveryID';
    }

    public function getLabel()
    {
    }

    public function getStartTime()
    {
    }

    public function getFinishTime()
    {
    }

    public function getState()
    {
    }

    public function setState($state)
    {
    }

    public function getDelivery()
    {
    }

    public function getUserIdentifier()
    {
    }
}
