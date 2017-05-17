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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 *
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */

namespace oat\ltiDeliveryProvider\model\event;

use oat\oatbox\event\Event;
use oat\oatbox\user\User;

class DeliveryRunEvent implements Event
{
    /**
     * @var \core_kernel_classes_Resource
     */
    protected $delivery;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var bool
     */
    protected $isLearner;

    /**
     * DeliveryRunEvent constructor.
     * @param \core_kernel_classes_Resource $delivery
     * @param User $user
     * @param bool $isLearner
     */
    public function __construct(\core_kernel_classes_Resource $delivery, User $user, $isLearner = false)
    {
        $this->delivery = $delivery;
        $this->user = $user;
        $this->isLearner = $isLearner;
    }

    /**
     * Return a unique name for this event
     * @see \oat\oatbox\event\Event::getName()
     */
    public function getName()
    {
        return get_class($this);
    }

    /**
     * @return \core_kernel_classes_Resource
     */
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return bool
     */
    public function getIsLearner()
    {
        return $this->isLearner;
    }
}
