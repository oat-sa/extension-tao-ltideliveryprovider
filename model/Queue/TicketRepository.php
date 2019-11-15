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

use oat\oatbox\service\ConfigurableService;
use oat\generis\persistence\PersistenceManager;
use oat\generis\model\kernel\uri\UriProvider;
use oat\taoLti\models\classes\LtiLaunchData;

class TicketRepository extends ConfigurableService
{
    const SERVICE_ID = 'ltiDeliveryProvider/TicketRepository';
    /**
     * KeyValue Persistence to store the queued tickets
     * @var string
     */
    const OPTION_PERSISTENCE = 'default_kv';

    /**
     * Time To Live for the tickets before they expire
     * @var string
     */
    const OPTION_TTL = 'ttl';

    /**
     * Prefix to use in the keyvalue store
     * @var string
     */
    const PREFIX_PERSISTENCE = 'ticket:';

    /**
     * Create a new ticket, with the correct status based on capacity 
     * @param LtiLaunchData $request
     * @return \oat\ltiDeliveryProvider\model\Queue\Ticket
     */
    public function createTicket(LtiLaunchData $request) {
        return new Ticket(
            $this->getServiceLocator()->get(UriProvider::SERVICE_ID)->provide(),
            $request,
            time(),
            Ticket::STATUS_INITIAL
        );
    }

    public function loadTicket($ticketId) {
        $json = json_decode($this->getPersistence()->get(self::PREFIX_PERSISTENCE.$ticketId), true);
        if (!is_array($json)) {
            throw new \common_exception_NotFound('Unable to load ticket '.$ticketId);
        }
        return Ticket::fromJson($json);
    }

    public function saveTicket(Ticket $ticket) {
        $this->getPersistence()->set(self::PREFIX_PERSISTENCE.$ticket->getId(), json_encode($ticket), $this->getOption(self::OPTION_TTL));
    }
    
    /**
     * @return \common_persistence_AdvKeyValuePersistence
     */
    protected function getPersistence() {
        $pm = $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID);
        return $pm->getPersistenceById($this->getOption(self::OPTION_PERSISTENCE));
    }
}
