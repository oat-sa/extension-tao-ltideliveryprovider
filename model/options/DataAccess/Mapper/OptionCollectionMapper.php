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

namespace oat\ltiDeliveryProvider\model\options\DataAccess\Mapper;

use oat\oatbox\service\ConfigurableService;
use oat\taoQtiTest\models\runner\config\Business\Domain\Option;
use oat\taoQtiTest\models\runner\config\Business\Domain\OptionCollection;

class OptionCollectionMapper extends ConfigurableService
{
    public const SERVICE_ID = 'ltiDeliveryProvider/OptionCollectionMapper';

    public function toDomain(array $rawData): OptionCollection
    {
        $resultingOptions = [];

        foreach ($rawData as $tool => $status) {
            if (is_bool($status)) {
                $resultingOptions[] = new Option((string)$tool, $status);
            }
        }

        return new OptionCollection(...$resultingOptions);
    }
}
