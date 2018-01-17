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

use common_report_Report;
use oat\ltiDeliveryProvider\model\results\LtiResultWatcher;
use oat\oatbox\extension\InstallAction;
use oat\taoResultServer\models\classes\search\ResultsWatcher;


class RegisterLtiResultWatcherService extends InstallAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws \common_Exception
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        $resultWatcher = $this->getServiceLocator()->get(ResultsWatcher::SERVICE_ID);
        $ltiResultWatcher = new LtiResultWatcher($resultWatcher->getOptions());
        $this->getServiceManager()->register(ResultsWatcher::SERVICE_ID, $ltiResultWatcher);
        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Registered Lti result watcher service'));
    }

}
