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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\model;

use core_kernel_classes_Container;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;

class LtiDeliveryFactory extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'ltiDeliveryProvider/LtiDeliveryFactory';

    /**
     * @param string $uri
     *
     * @return core_kernel_classes_Container|core_kernel_classes_Resource
     */
    public function create($uri)
    {
        return $uri !== null
            ? $this->getResource($uri)
            : $this->getLTIDeliveryTool()->getDeliveryFromLink();
    }

    /**
     * @return LTIDeliveryTool
     */
    private function getLTIDeliveryTool()
    {
        return $this->getServiceLocator()->get(LTIDeliveryToolFactory::SERVICE_ID)->create();
    }
}
