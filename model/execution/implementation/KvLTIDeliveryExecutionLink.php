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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

namespace oat\ltiDeliveryProvider\model\execution\implementation;



use oat\ltiDeliveryProvider\model\execution\LTIDeliveryExecutionLink;

/**
 * Class KvLTIDeliveryExecutionLink
 * Key value implementation of LTIDeliveryExecutionLink
 * @package oat\ltiDeliveryProvider\model\execution\implementation
 */
class KvLTIDeliveryExecutionLink implements LTIDeliveryExecutionLink, \JsonSerializable {


    private $userId;
    private $deliveryExecutionId;
    private $linkId;

    /**
     * KvLTIDeliveryExecutionLink constructor.
     * @param $userId
     * @param $deliveryExecutionId
     * @param $linkId
     */
    public function __construct($userId, $deliveryExecutionId, $linkId)
    {
        $this->userId = $userId;
        $this->deliveryExecutionId = $deliveryExecutionId;
        $this->linkId = $linkId;
    }


    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getDeliveryExecutionId()
    {
        return $this->deliveryExecutionId;
    }

    /**
     * @param string $deliveryExecutionId
     */
    public function setDeliveryExecutionId($deliveryExecutionId)
    {
        $this->deliveryExecutionId = $deliveryExecutionId;
    }

    /**
     * @return string
     */
    public function getLinkId()
    {
        return $this->linkId;
    }

    /**
     * @param string $linkId
     */
    public function setLinkId($linkId)
    {
        $this->linkId = $linkId;
    }


    /**
     * @param $values
     * @return KvLTIDeliveryExecutionLink[]
     */
    public static function unSerialize($values)
    {
        $links = [];
        $data = $values !== false ? json_decode($values,true) : array();

        if(isset($data['userId']) && isset($data['linkId']) && isset($data['deliveryExecutionId'])){
            $links[] = new self($data['userId'], $data['deliveryExecutionId'], $data['linkId']);
        }

        return $links;
    }


    public function jsonSerialize()
    {
        return [
            'userId' => $this->getUserId(),
            'deliveryExecutionId' => $this->getDeliveryExecutionId(),
            'linkId' => $this->getLinkId(),
        ];
    }


}
