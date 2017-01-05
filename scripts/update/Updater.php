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
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\ltiDeliveryProvider\scripts\update;
use oat\ltiDeliveryProvider\model\LtiAssignment;
use oat\oatbox\service\ServiceNotFoundException;

class Updater extends \common_ext_ExtensionUpdater
{

    /**
     * @param string $initialVersion
     * @return null
     */
    public function update($initialVersion)
    {
        $this->skip('0', '1.5.1');

        if ($this->isVersion('1.5.1')) {
            try {
                $this->getServiceManager()->get(LtiAssignment::LTI_SERVICE_ID);
            } catch (ServiceNotFoundException $e) {
                $service = new LtiAssignment();
                $service->setServiceManager($this->getServiceManager());
                $this->getServiceManager()->register(LtiAssignment::LTI_SERVICE_ID, $service);
            }
            $this->setVersion('1.6.0');
        }
    }
}
