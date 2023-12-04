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
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\test\unit\model\navigation\Command;

use oat\ltiDeliveryProvider\model\navigation\Command\GenerateReturnUrlCommand;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiLaunchData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GenerateReturnUrlCommandTest extends TestCase
{
    /**
     * @var LtiLaunchData|MockObject
     */
    private $ltiLaunchData;

    /**
     * @var DeliveryExecutionInterface|MockObject
     */
    private $deliveryExecution;

    private GenerateReturnUrlCommand $sut;

    protected function setUp(): void
    {
        $this->ltiLaunchData = $this->createMock(LtiLaunchData::class);
        $this->deliveryExecution = $this->createMock(DeliveryExecutionInterface::class);

        $this->sut = new GenerateReturnUrlCommand(
            $this->ltiLaunchData,
            $this->deliveryExecution,
            true,
            ['test' => 'test']
        );
    }

    public function testIsCustomFeedback(): void
    {
        $this->assertTrue($this->sut->isCustomFeedback());
    }

    public function testGetQueryStringData(): void
    {
        $this->assertEquals(['test' => 'test'], $this->sut->getQueryStringData());
    }

    public function testGetLaunchData(): void
    {
        $this->assertEquals($this->ltiLaunchData, $this->sut->getLaunchData());
    }

    public function testGetDeliveryExecution(): void
    {
        $this->assertEquals($this->deliveryExecution, $this->sut->getDeliveryExecution());
    }
}
