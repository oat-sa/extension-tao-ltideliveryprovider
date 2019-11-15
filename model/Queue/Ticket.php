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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\ltiDeliveryProvider\model\Queue;

use JsonSerializable;
use oat\taoLti\models\classes\LtiLaunchData;

class Ticket implements JsonSerializable
{
    const STATUS_INITIAL = 0;

    const STATUS_READY = 1;

    const STATUS_QUEUED = 2;

    private $id;

    private $request;

    private $creationTime;

    private $status;

    public function __construct($id, LtiLaunchData $request, $creationTime, $status)
    {
        $this->id = $id;
        $this->request = $request;
        $this->creationTime = $creationTime;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return LtiLaunchData
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @return int timestamp
     */
    public function getCreationTime() {
        return $this->creationTime;
    }

    /**
     * @return int status
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param int $status
     * @return Ticket
     */
    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    /**
     * {@inheritDoc}
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'request' => $this->request,
            'creation' => $this->creationTime,
            'status' => $this->getStatus()
        ];
    }

    /**
     * Restore a ticket from json array
     * @param array $json
     * @return Ticket
     */
    public static function fromJson($json)
    {
        $request = LtiLaunchData::fromJsonArray($json['request']);
        return new self($json['id'], $request, $json['creation'], $json['status']);
    }
}
