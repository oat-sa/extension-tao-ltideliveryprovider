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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 * @author Ilya Yarkavets <ilya.yarkavets@1pt.com>
 */

namespace oat\ltiDeliveryProvider\model\event;

use oat\oatbox\event\Event;

/**
 * Class LtiLaunchEvent
 *
 * This event is provided to execute additional actions on LTI launch
 *
 * @author Ilya Yarkavets <ilya.yarkavets@1pt.com>
 * @package oat\ltiDeliveryProvider\model\event
 */
class LtiLaunchEvent implements Event
{
    private $request;

    /**
     * @param string $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Return a unique name for this event
     * @see \oat\oatbox\event\Event::getName()
     */
    public function getName()
    {
        return get_class($this);
    }

    public function getRequest()
    {
        return $this->request;
    }

}