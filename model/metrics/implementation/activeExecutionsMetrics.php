<?php
/**
 *
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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\ltiDeliveryProvider\model\metrics\implementation;

use oat\ltiDeliveryProvider\model\actions\GetActiveDeliveryExecution;
use oat\tao\model\metrics\implementations\abstractMetrics;

class activeExecutionsMetrics extends abstractMetrics
{

    /**
     * Collect values, caches
     * @param bool $force
     * @return mixed
     * @throws \common_Exception
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function collect($force = false)
    {
        $active = $this->getPersistence()->get(self::class);
        if (!$active || $force) {
            $action = new GetActiveDeliveryExecution();
            $this->propagate($action);
            $active = $action->getNumberOfActiveActions();
            $this->getPersistence()->set(self::class, $active, $this->getOption(self::OPTION_TTL));
        }
        return $active;


    }
}