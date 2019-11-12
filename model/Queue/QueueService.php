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
use Psr\Http\Message\RequestInterface;
use oat\taoDelivery\model\Capacity\CapacityInterface;
use oat\generis\persistence\PersistenceManager;

class QueueService extends ConfigurableService
{
    const SERVICE_ID = 'ltiDeliveryProvider/QueueService';
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
    const PREFIX_PERSISTENCE = 'queue:';

    /**
     * Create a new ticket, with the correct status based on capacity 
     * @param RequestInterface $request
     * @return \oat\ltiDeliveryProvider\model\Queue\Ticket
     */
    public function createTicket(RequestInterface $request) {
        $capacityService = $this->getServiceLocator()->get(CapacityInterface::SERVICE_ID);
        $success = $capacityService->consume();
        $ticket = new Ticket(bin2hex(openssl_random_pseudo_bytes(20)),
            $request,
            time(),
            $success ? Ticket::STATUS_READY : Ticket::STATUS_QUEUED
        );
        if (!$success) {
            $this->getPersistence()->set(self::PREFIX_PERSISTENCE.$ticket->getId(), json_encode($ticket), $this->getOption(self::OPTION_TTL));
        }
        return $ticket;
    }

    public function getTicket($ticketId) {
        $json = json_decode($this->getPersistence()->get(self::PREFIX_PERSISTENCE.$ticketId), true);
        if (!is_array($json)) {
            throw new \common_exception_NotFound('Unable to load ticket '.$ticketId);
        }
        return Ticket::fromJson($json);
    }

    /**
     * Called if new slots are available
     * @param int $count
     * @return int unqueued tickets
     */
    public function unqueue($count) {
        return 0;
    }

    /**
     * @return \common_persistence_AdvKeyValuePersistence
     */
    protected function getPersistence() {
        $pm = $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID);
        return $pm->getPersistenceById($this->getOption(self::OPTION_PERSISTENCE));
    }
}
