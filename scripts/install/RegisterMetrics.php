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
 */

namespace oat\ltiDeliveryProvider\scripts\install;

use common_exception_Error;
use common_report_Report as Report;
use oat\ltiDeliveryProvider\model\metrics\implementation\activeExecutionsMetrics;
use oat\oatbox\extension\InstallAction;
use oat\tao\model\metrics\MetricsService;

/**
 * Class RegisterKvLtiDEService
 *
 * usage :
 * sudo -u www-data php index.php 'oat\ltiDeliveryProvider\scripts\install\RegisterMetrics'
 * @package oat\ltiDeliveryProvider\scripts\install
 */
class RegisterMetrics extends InstallAction
{
    /**
     * @param $params
     * @return Report
     * @throws \common_Exception
     * @throws common_exception_Error
     */
    public function __invoke($params)
    {

        /** @var \common_persistence_Manager $pm */
        $pm = $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID);
        $pm->registerPersistence('metricsCache', ['driver' => 'phpfile',
            'ttlMode' => true,]);

        $metricService = $this->getServiceManager()->get(MetricsService::class);
        $limitMetric = new activeExecutionsMetrics();
        $limitMetric->setOptions([
            activeExecutionsMetrics::OPTION_TTL => 1,
            activeExecutionsMetrics::OPTION_PERSISTENCE => 'metricsCache',
        ]);
        $metricService->setOption(MetricsService::OPTION_METRICS, [activeExecutionsMetrics::class => $limitMetric]);


        $this->getServiceManager()->register(MetricsService::SERVICE_ID, $metricService);


        return new Report(Report::TYPE_SUCCESS, __('Registered activeExecutions metric'));
    }
}
