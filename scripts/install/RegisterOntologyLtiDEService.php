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
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\scripts\install;

use common_exception_Error;
use common_report_Report;
use oat\ltiDeliveryProvider\model\execution\implementation\KvLtiDeliveryExecutionService;
use oat\ltiDeliveryProvider\model\execution\implementation\LtiDeliveryExecutionService;
use oat\oatbox\extension\InstallAction;

/**
 * Class RegisterKvLtiDEService
 *
 * usage :
 * sudo -u www-data php index.php 'oat\ltiDeliveryProvider\scripts\install\RegisterOntologyLtiDEService'
 * @package oat\ltiDeliveryProvider\scripts\install
 * @author Antoine Robin, <antoine@taotesting.com>
 */
class RegisterOntologyLtiDEService extends InstallAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws \common_Exception
     * @throws common_exception_Error
     */
    public function __invoke($params)
    {
        $ltiDeliveryExecution = new LtiDeliveryExecutionService([LtiDeliveryExecutionService::OPTION_QUEUE_PERSISTENCE => 'cache']);
        $this->getServiceManager()->register(LtiDeliveryExecutionService::SERVICE_ID, $ltiDeliveryExecution);
        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Registered Lti delivery execution service in Ontology'));
    }
}
