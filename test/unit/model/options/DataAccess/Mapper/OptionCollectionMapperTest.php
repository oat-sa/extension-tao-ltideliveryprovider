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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\test\unit\model\options\DataAccess\Mapper;

use oat\generis\test\TestCase;
use oat\ltiDeliveryProvider\model\options\DataAccess\Mapper\OptionCollectionMapper;
use oat\taoQtiTest\models\runner\config\Business\Domain\Option;
use oat\taoQtiTest\models\runner\config\Business\Domain\OptionCollection;

class OptionCollectionMapperTest extends TestCase
{
    /** @var OptionCollectionMapper */
    private $sut;

    /**
     * @before
     */
    public function init(): void
    {
        $this->sut = new OptionCollectionMapper();
    }

    /**
     * @param array $rawData
     * @param OptionCollection $expected
     *
     * @dataProvider dataProvider
     */
    public function testToDomain(array $rawData, OptionCollection $expected): void
    {
        $this->assertEquals($expected, $this->sut->toDomain($rawData));
    }

    public function dataProvider(): array
    {
        return [
            'Valid statuses' => [
                [
                    'opt_1' => true,
                    'opt_2' => false,
                ],
                new OptionCollection(
                    new Option('opt_1', true),
                    new Option('opt_2', false)
                ),
            ],
            'Invalid statuses' => [
                [
                    'opt_1' => false,
                    'opt_2' => true,
                    'opt_3' => 'false',
                    'opt_4' => 'true',
                ],
                new OptionCollection(
                    new Option('opt_1', false),
                    new Option('opt_2', true)
                ),
            ],
        ];
    }
}
