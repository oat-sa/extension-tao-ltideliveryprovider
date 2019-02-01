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

namespace oat\ltiDeliveryProvider\model\navigation;

use oat\oatbox\service\ConfigurableService;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\ltiDeliveryProvider\controller\DeliveryTool;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;

class LtiNavigationService extends ConfigurableService
{
    const SERVICE_ID = 'ltiDeliveryProvider/LtiNavigation';

    const OPTION_MESSAGE_FACTORY = 'message';

    /**
     * Whenever or not the thank you screen should be shown by default
     */
    const OPTION_THANK_YOU_SCREEN = 'thankyouScreen';

    public function getReturnUrl(LtiLaunchData $launchData, DeliveryExecutionInterface $deliveryExecution)
    {
        return $this->showThankyou($launchData)
            ? $this->getThankYouUrl()
            : $this->getConsumerReturnUrl($launchData, $deliveryExecution);
    }

    protected function getConsumerReturnUrl(LtiLaunchData $launchData, DeliveryExecutionInterface $deliveryExecution)
    {
        $urlParts = parse_url($launchData->getReturnUrl());
        if (!isset($urlParts['query'])) {
            $urlParts['query'] = '';
        }
        parse_str($urlParts['query'], $params);
        $urlParts['query'] = http_build_query(array_merge($params, $this->getConsumerReturnParams($launchData, $deliveryExecution)));

        $port = '';
        if (array_key_exists('port', $urlParts)) {
            $port = ':' . $urlParts['port'];
        }

        return $urlParts['scheme'] . '://' . $urlParts['host'] . $port . $urlParts['path'] . '?' . $urlParts['query'];
    }

    protected function getConsumerReturnParams(LtiLaunchData $launchData, DeliveryExecutionInterface $deliveryExecution)
    {
        $ltiMessage = $this->getSubService(self::OPTION_MESSAGE_FACTORY)->getLtiMessage($deliveryExecution);
        $params = $ltiMessage
            ? $ltiMessage->getUrlParams()
            : []
        ;
        $params['deliveryExecution'] = $deliveryExecution->getIdentifier();
        return $params;
    }

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @return LtiMessage
     * @throws \common_exception_NotFound
     */
    protected function getLtiMessage(DeliveryExecutionInterface $deliveryExecution)
    {
        $state = $deliveryExecution->getState()->getLabel();
        return new LtiMessage($state, null);
    }

    /**
     * Whenever or not to show the thank you screen
     * @param LtiLaunchData $launchData
     * @return boolean
     */
    protected function showThankyou(LtiLaunchData $launchData)
    {
        if (!$launchData->hasReturnUrl()) {
            return true;
        }

        if ($launchData->hasVariable(DeliveryTool::PARAM_SKIP_THANKYOU)) {
            switch ($launchData->getVariable(DeliveryTool::PARAM_SKIP_THANKYOU)) {
                case 'true':
                    return false;
                case 'false':
                    return true;
                default:
                    $this->logWarning('Unexpected value for \''.DeliveryTool::PARAM_SKIP_THANKYOU.'\': '
                        .$launchData->getVariable(DeliveryTool::PARAM_SKIP_THANKYOU));
            }
        }
        return $this->getOption(self::OPTION_THANK_YOU_SCREEN);
    }

    /**
     * @return string
     */
    protected function getThankYouUrl()
    {
        return _url('thankYou', 'DeliveryRunner', 'ltiDeliveryProvider');
    }
}
