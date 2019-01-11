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
 *
 */

namespace oat\ltiDeliveryProvider\model\execution\implementation;

use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService as LtiDeliveryExecutionServiceInterface;
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteRequest;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;

/**
 * Class KvLtiDeliveryExecutionService
 * Key value implementation of the LtiDeliveryExecutionServiceInterface
 * @package oat\ltiDeliveryProvider\model\execution
 */
class KvLtiDeliveryExecutionService extends AbstractLtiDeliveryExecutionService
{

    const OPTION_PERSISTENCE = 'persistence';

    const LTI_DE_LINK_LINK = 'kvlti_ll_';

    const LINKS_OF_DELIVERY_EXECUTION = 'kvlti_links_de_';

    /**
     * @var \common_persistence_KeyValuePersistence
     */
    private $persistence;

    /**
     * @return \common_persistence_KeyValuePersistence|\common_persistence_Persistence
     */
    protected function getPersistence()
    {
        if (is_null($this->persistence)) {
            $persistenceOption = $this->getOption(self::OPTION_PERSISTENCE);
            $this->persistence = (is_object($persistenceOption))
                ? $persistenceOption
                : \common_persistence_KeyValuePersistence::getPersistence($persistenceOption);
        }
        return $this->persistence;
    }

    /**
     * Returns an array of DeliveryExecution
     *
     * @param \core_kernel_classes_Resource $delivery
     * @param \core_kernel_classes_Resource $link
     * @param string $userId
     * @return DeliveryExecution[]
     */
    public function getLinkedDeliveryExecutions(\core_kernel_classes_Resource $delivery, \core_kernel_classes_Resource $link, $userId)
    {

        $data = $this->getPersistence()->get(self::LTI_DE_LINK_LINK . $link->getUri() . $userId);

        $ltiDeliveryExecutionLinks = KvLTIDeliveryExecutionLink::unSerialize($data);

        $results = [];
        foreach ($ltiDeliveryExecutionLinks as $ltiDeliveryExecutionLink){
            $deliveryExecution = ServiceProxy::singleton()->getDeliveryExecution($ltiDeliveryExecutionLink->getDeliveryExecutionId());
            if ($delivery->equals($deliveryExecution->getDelivery())) {
                $results[] = $deliveryExecution;
            }
        }
        return $results;
    }


    /**
     * @inheritdoc
     */
    public function createDeliveryExecutionLink($userUri, $link, $deliveryExecutionUri)
    {
        $ltiDeliveryExecutionLink = new KvLTIDeliveryExecutionLink($userUri, $deliveryExecutionUri, $link);
        $this->getPersistence()->set(self::LTI_DE_LINK_LINK . $link . $userUri, json_encode($ltiDeliveryExecutionLink));
        $this->saveLinkReference($link, $userUri, $deliveryExecutionUri);

        return $ltiDeliveryExecutionLink;
    }

    /**
     * @inheritdoc
     */
    public function deleteDeliveryExecutionData(DeliveryExecutionDeleteRequest $request)
    {
        $userUri = $request->getDeliveryExecution()->getUserIdentifier();
        $deUri   = $request->getDeliveryExecution()->getIdentifier();
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
     * @return bool
     * @throws \common_Exception
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