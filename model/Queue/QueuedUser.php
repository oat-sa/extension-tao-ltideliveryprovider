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

use oat\oatbox\user\AnonymousUser;
use oat\generis\model\GenerisRdf;

class QueuedUser extends AnonymousUser
{
    const INSTANCE_ROLE = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LTIQueueRole';

    /**
     * Identifier of the ticket the user is wating for
     * @var string
     */
    private $ticketId;

    /**
     * Create a new queued User
     * @param string $ticketId
     */
    public function __construct($ticketId)
    {
        $this->ticketId = $ticketId;
    }

    /**
     * {@inheritDoc}
     * @see \oat\oatbox\user\AnonymousUser::getIdentifier()
     */
    public function getIdentifier() {
        return 'Queued User for '.$this->ticketId;
    }

    /**
     * (non-PHPdoc)
     * @see \oat\oatbox\user\User::getRoles()
     */
    public function getRoles() {
        return array(GenerisRdf::INSTANCE_ROLE_ANONYMOUS, self::INSTANCE_ROLE);
    }

    /**
     * Return the ticket identifier
     * @return string
     */
    public function getAsociatedTicket() {
        return $this->ticketId;
    }
}
