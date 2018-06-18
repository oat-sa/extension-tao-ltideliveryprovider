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
 *
 */

namespace oat\ltiDeliveryProvider\model\metrics;

use oat\ltiDeliveryProvider\model\metrics\implementation\activeExecutionsMetrics;
use oat\tao\model\actionQueue\restriction\basicRestriction;
use oat\tao\model\metrics\MetricsService;

class activeLimitRestriction extends basicRestriction
{

    const METRIC = activeExecutionsMetrics::class;

    /**
     * @param $value
     * @return boolean
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     * @throws \oat\tao\model\metadata\exception\InconsistencyConfigException
     */
    public function doesComplies($value)
    {
        if ($value === 0) {
            return true;
        }
        $metric = $this->getServiceManager()->get(MetricsService::class)->getOneMetric(self::METRIC);
        return $value > $metric->collect();
    }


}