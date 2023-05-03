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
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\model\navigation;

use common_exception_NotFound;
use Exception;
use helpers_Random;
use oat\ltiDeliveryProvider\controller\DeliveryTool;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\exception\InvalidService;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\tao\helpers\UrlHelper;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiException;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiMessages\LtiMessage;

class LtiNavigationService extends ConfigurableService
{
    public const SERVICE_ID = 'ltiDeliveryProvider/LtiNavigation';

    public const OPTION_DELIVERY_RETURN_STATUS = 'delivery_return_status';
    public const OPTION_MESSAGE_FACTORY = 'message';
    public const OPTION_RETURN_URL_IDENTIFIER = 'returnUrlId';

    /**
     * Whenever or not the thank you screen should be shown by default
     */
    public const OPTION_THANK_YOU_SCREEN = 'thankyouScreen';

    /**
     * @param LtiLaunchData $launchData
     * @param DeliveryExecutionInterface $deliveryExecution
     *
     * @throws common_exception_NotFound
     * @throws InvalidService
     * @throws InvalidServiceManagerException
     * @throws LtiException
     *
     * @return string
     */
    public function getReturnUrl(LtiLaunchData $launchData, DeliveryExecutionInterface $deliveryExecution): string
    {
        return $this->shouldShowThankYou($launchData)
            ? $this->buildThankYouUrl()
            : $this->buildConsumerReturnUrl($launchData, $deliveryExecution);
    }

    /**
     * @param LtiLaunchData $launchData
     * @param DeliveryExecutionInterface $deliveryExecution
     *
     * @throws common_exception_NotFound
     * @throws InvalidService
     * @throws InvalidServiceManagerException
     * @throws LtiException
     *
     * @return string
     */
    protected function buildConsumerReturnUrl(
        LtiLaunchData $launchData,
        DeliveryExecutionInterface $deliveryExecution
    ): string {
        $urlParts = parse_url($launchData->getReturnUrl());
        $port = empty($urlParts['port']) ? '' : (':' . $urlParts['port']);
        $path = $urlParts['path'] ?? '';
        $queryString = $this->buildConsumerReturnUrlQuery($deliveryExecution, $urlParts);

        return $urlParts['scheme'] . '://' . $urlParts['host'] . $port . $path . '?' . $queryString;
    }

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     *
     * @throws common_exception_NotFound
     * @throws InvalidService
     * @throws InvalidServiceManagerException
     *
     * @return array
     */
    protected function getConsumerReturnParams(DeliveryExecutionInterface $deliveryExecution): array
    {
        $ltiReturnQueryParams = $this->getLtiReturnUrlQueryParams($deliveryExecution);
        $deliveryReturnQueryParams = $this->getDeliveryReturnQueryParams($deliveryExecution);
        $returnUrlIdParams = [];

        if ($this->getOption(self::OPTION_RETURN_URL_IDENTIFIER)) {
            $returnUrlIdParams = $this->getReturnUrlIdParams();
        }

        return array_merge($ltiReturnQueryParams, $deliveryReturnQueryParams, $returnUrlIdParams);
    }

    /**
     * Whenever or not to show the thank you screen
     *
     * @param LtiLaunchData $launchData
     *
     * @return bool
     */
    protected function shouldShowThankYou(LtiLaunchData $launchData): bool
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
                    $this->logWarning('Unexpected value for \'' . DeliveryTool::PARAM_SKIP_THANKYOU . '\': '
                        . $launchData->getVariable(DeliveryTool::PARAM_SKIP_THANKYOU));
            }
        }

        return $this->getOption(self::OPTION_THANK_YOU_SCREEN);
    }

    /**
     * @return string
     */
    protected function buildThankYouUrl(): string
    {
        return $this->getServiceLocator()->get(UrlHelper::class)->buildUrl('thankYou', 'DeliveryRunner', 'ltiDeliveryProvider');
    }

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param array $urlParts
     *
     * @throws common_exception_NotFound
     *
     * @return array
     */
    private function buildConsumerReturnUrlQuery(DeliveryExecutionInterface $deliveryExecution, array $urlParts): string
    {
        $urlParts['query'] = $urlParts['query'] ?? '';
        parse_str($urlParts['query'], $params);

        return http_build_query(array_merge($params, $this->getConsumerReturnParams($deliveryExecution)));
    }

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     *
     * @throws InvalidService
     * @throws InvalidServiceManagerException
     *
     * @return array
     */
    private function getLtiReturnUrlQueryParams(DeliveryExecutionInterface $deliveryExecution): array
    {
        $ltiMessage = $this->getSubService(self::OPTION_MESSAGE_FACTORY)->getLtiMessage($deliveryExecution);

        return ($ltiMessage instanceof LtiMessage) ? $ltiMessage->getUrlParams() : [];
    }

    /**
     * @param DeliveryExecutionInterface $deliveryExecution
     * @param $params
     *
     * @throws common_exception_NotFound
     *
     * @return mixed
     */
    private function getDeliveryReturnQueryParams(DeliveryExecutionInterface $deliveryExecution): array
    {
        $params = [
            'deliveryExecution' => $deliveryExecution->getIdentifier(),
        ];

        if ($this->getOption(self::OPTION_DELIVERY_RETURN_STATUS)) {
            $params['deliveryExecutionStatus'] = $deliveryExecution->getState()->getLabel();
        }

        return $params;
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    private function getReturnUrlIdParams(): array
    {
        return ['returnUrlId' => helpers_Random::generateString(10)];
    }
}
