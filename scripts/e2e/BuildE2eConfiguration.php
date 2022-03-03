<?php

/*
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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA
 */
declare(strict_types=1);

namespace oat\ltiDeliveryProvider\scripts\e2e;

use common_ext_ExtensionsManager;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\reporting\Report;
use oat\oatbox\reporting\ReportInterface;
use oat\tao\scripts\tools\e2e\models\E2eConfigDriver;
use oat\tao\scripts\tools\e2e\PrepareEnvironment;
use oat\taoOauth\model\Oauth2Service;
use stdClass;

class BuildE2eConfiguration extends AbstractAction
{

    public function __invoke($params)
    {
        if (!$this->getExtensionManager()->isEnabled('ltiDeliveryProvider')) {
            return Report::createError('ltiDeliveryProvider extension must be installed first');
        }

        $report = Report::createInfo('LtiDeliveries Configuration Processes');
        $report->add($this->addLtiCredentials());
        $report->add($this->addLtiDeliveries());

        return $report;
    }

    private function addLtiCredentials(): ReportInterface
    {
        $report = Report::createInfo('Generating Lti Credentials');

        $config = new stdClass();
        $config->ltiLocale = DEFAULT_LANG;
        $config->ltiKey = $this->getOauthService()->generateClientKey();
        $config->ltiSecret = $this->getOauthService()->generateClientSecret($config->ltiKey);
        $tokenUrl = $this->getOauthService()->getDefaultTokenUrl();
        $this->getOauthService()->spawnConsumer($config->ltiKey, $config->ltiSecret, $tokenUrl);

        $this->persistConfig($config);

        return $report;
    }

    private function addLtiDeliveries(): ReportInterface
    {
        $report = Report::createInfo('Building Lti Deliveries Mapping');

        $config = new stdClass();
        $config->ltiDeliveryIds = $this->buildDeliveriesMap();
        $this->persistConfig($config);

        return $report;
    }

    private function buildDeliveriesMap(): stdClass
    {
        $map = new stdClass();
        $config = $this->getE2eConfigDriver()->setConfigPath((new PrepareEnvironment())->getConfigPath())->read();
        foreach ($config->deliveryIds as $type => $uri) {
            $map->{$type} = base64_encode(json_encode((object)["delivery" => $uri], JSON_UNESCAPED_SLASHES));
        }
        return $map;
    }

    private function getExtensionManager(): common_ext_ExtensionsManager
    {
        return $this->getServiceLocator()->getContainer()->get(common_ext_ExtensionsManager::SERVICE_ID);
    }

    private function getOauthService(): Oauth2Service
    {
        return $this->getServiceLocator()->getContainer()->get(Oauth2Service::SERVICE_ID);
    }

    private function getE2eConfigDriver(): E2eConfigDriver
    {
        return new E2eConfigDriver();
    }

    private function persistConfig(object $config): void
    {
        $this->getE2eConfigDriver()->setConfigPath((new PrepareEnvironment())->getConfigPath())->append($config);
    }

}
