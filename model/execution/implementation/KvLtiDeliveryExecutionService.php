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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 */

namespace oat\ltiDeliveryProvider\model\execution\implementation;

use common_Exception;
use common_exception_NotFound;
use common_persistence_KeyValuePersistence;
use common_persistence_Persistence;
use core_kernel_classes_Resource;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService as LtiDeliveryExecutionServiceInterface;
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteRequest;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;

/**
 * Class KvLtiDeliveryExecutionService
 * Key value implementation of the LtiDeliveryExecutionServiceInterface
 *
 * @package oat\ltiDeliveryProvider\model\execution
 */
class KvLtiDeliveryExecutionService extends AbstractLtiDeliveryExecutionService
{
    public const OPTION_PERSISTENCE = 'persistence';

    public const LTI_DE_LINK_LINK = 'kvlti_ll_';

    public const LINKS_OF_DELIVERY_EXECUTION = 'kvlti_links_de_';

    /**
     * @var common_persistence_KeyValuePersistence
     */
    private $persistence;

    /**
     * @return common_persistence_KeyValuePersistence|common_persistence_Persistence
     */
    protected function getPersistence()
    {
        if (is_null($this->persistence)) {
            $persistenceOption = $this->getOption(self::OPTION_PERSISTENCE);
            $this->persistence = (is_object($persistenceOption))
                ? $persistenceOption
                : common_persistence_KeyValuePersistence::getPersistence($persistenceOption);
        }

        return $this->persistence;
    }

    /**
     * Returns an array of DeliveryExecution
     *
     * @param core_kernel_classes_Resource $delivery
     * @param core_kernel_classes_Resource $link
     * @param string $userId
     *
     * @throws common_exception_NotFound
     *
     * @return DeliveryExecution[]
     */
    public function getLinkedDeliveryExecutions(
        core_kernel_classes_Resource $delivery,
        core_kernel_classes_Resource $link,
        $userId
    ) {
        $data = $this->getPersistence()->get(self::LTI_DE_LINK_LINK . $link->getUri() . $userId);
        $ltiDeliveryExecutionLinks = KvLTIDeliveryExecutionLink::unSerialize($data);

        $results = [];

        foreach ($ltiDeliveryExecutionLinks as $ltiDeliveryExecutionLink) {
            /** @var DeliveryExecution $deliveryExecution */
            $deliveryExecution = $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID)->getDeliveryExecution(
                $ltiDeliveryExecutionLink->getDeliveryExecutionId()
            );

            if ($delivery->equals($deliveryExecution->getDelivery())) {
                $results[] = $deliveryExecution;
            }
        }

        return $results;
    }

    /**
     * @param string $userUri
     * @param string $link
     * @param string $deliveryExecutionUri
     *
     * @throws common_Exception
     *
     * @return KvLTIDeliveryExecutionLink
     */
    public function createDeliveryExecutionLink($userUri, $link, $deliveryExecutionUri)
    {
        $persistence = $this->getPersistence();
        $ltiDeliveryExecutionLink = new KvLTIDeliveryExecutionLink($userUri, $deliveryExecutionUri, $link);
        $key = self::LTI_DE_LINK_LINK . $link . $userUri;

        $ltiDeliveryExecutions = json_decode($persistence->get($key), true);

        if (!is_array($ltiDeliveryExecutions)) {
            $ltiDeliveryExecutions = [];
        }

        $ltiDeliveryExecutions[] = $ltiDeliveryExecutionLink->jsonSerialize();
        $persistence->set($key, json_encode($ltiDeliveryExecutions));
        $this->saveLinkReference($link, $userUri, $deliveryExecutionUri);

        return $ltiDeliveryExecutionLink;
    }

    /**
     * @inheritdoc
     */
    public function deleteDeliveryExecutionData(DeliveryExecutionDeleteRequest $request)
    {
        $userUri = $request->getDeliveryExecution()->getUserIdentifier();
        $deUri = $request->getDeliveryExecution()->getIdentifier();
        $deleted = [];

        $linksOfDelivery = $this->getDeliveryExecutionLinks($userUri, $deUri);

        foreach ($linksOfDelivery as $link) {
            $deleted[] = $this->getPersistence()->del(self::LTI_DE_LINK_LINK . $link . $userUri);
        }

        $this->getPersistence()->del(self::LINKS_OF_DELIVERY_EXECUTION . $userUri . $deUri);

        return !in_array(false, $deleted);
    }

    /**
     * @param $link
     * @param $userUri
     * @param $deliveryExecutionUri
     *
     * @throws common_Exception
     *
     * @return bool
     */
    protected function saveLinkReference($link, $userUri, $deliveryExecutionUri)
    {
        $linksOfExecutionAndUser = $this->getPersistence()->get(static::LINKS_OF_DELIVERY_EXECUTION . $userUri . $deliveryExecutionUri);

        if (is_null($linksOfExecutionAndUser)) {
            $linksOfExecutionAndUser = [];
        } else {
            $linksOfExecutionAndUser = json_decode($linksOfExecutionAndUser, true);
        }

        $linksOfExecutionAndUser[] = $link;

        return $this->getPersistence()->set(static::LINKS_OF_DELIVERY_EXECUTION . $userUri . $deliveryExecutionUri, json_encode($linksOfExecutionAndUser));
    }

    /**
     * @param $userUri
     * @param $deliveryExecutionUri
     *
     * @return array
     */
    protected function getDeliveryExecutionLinks($userUri, $deliveryExecutionUri)
    {
        $linksOfExecutionAndUser = $this->getPersistence()->get(self::LINKS_OF_DELIVERY_EXECUTION . $userUri . $deliveryExecutionUri);

        if (empty($linksOfExecutionAndUser)) {
            $linksOfExecutionAndUser = [];
        } else {
            $linksOfExecutionAndUser = json_decode($linksOfExecutionAndUser, true);
        }

        return $linksOfExecutionAndUser;
    }
}
