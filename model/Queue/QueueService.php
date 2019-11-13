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
use oat\tao\model\actionQueue\event\InstantActionOnQueueEvent;
use oat\oatbox\event\EventManager;
use oat\oatbox\user\AnonymousUser;

class QueueService extends ConfigurableService
{
    const SERVICE_ID = 'ltiDeliveryProvider/QueueService';

    /**
     * Create a new ticket, with the correct status based on capacity 
     * @param RequestInterface $request
     * @return \oat\ltiDeliveryProvider\model\Queue\Ticket
     */
    public function createTicket(RequestInterface $request) {
        $capacityService = $this->getServiceLocator()->get(CapacityInterface::SERVICE_ID);
        $repo = $this->getServiceLocator()->get(TicketRepository::SERVICE_ID);
        $ticket = $repo->createTicket($request);
        if ($capacityService->consume()) {
            $ticket->setStatus(Ticket::STATUS_READY);
        } else {
            $this->queueTicket($ticket);
        }
        return $ticket;
    }

    public function getTicket($ticketId) {
        $ticket = $this->getServiceLocator()->get(TicketRepository::SERVICE_ID)->loadTicket($ticketId);
        $capacityService = $this->getServiceLocator()->get(CapacityInterface::SERVICE_ID);
        if ($capacityService->consume()) {
            $this->unQueueTicket($ticket);
        }
        return $ticket;
    }

    /**
     * Called if new slots are available
     * @param int $count
     * @return int unqueued tickets
     */
    public function unqueue($count) {
        return 0;
    }

    protected function queueTicket(Ticket $ticket) {
        $ticket->setStatus(Ticket::STATUS_QUEUED);
        $event = new InstantActionOnQueueEvent($ticket->getId(), new AnonymousUser(), 0, 'queue');
        $this->getServiceLocator()->get(EventManager::SERVICE_ID)->trigger($event);
        $this->saveTicket($ticket);
    }

    protected function unQueueTicket(Ticket $ticket) {
        $ticket->setStatus(Ticket::STATUS_READY);
        $event = new InstantActionOnQueueEvent($ticket->getId(), new AnonymousUser, 0, 'dequeue');
        $this->getServiceLocator()->get(EventManager::SERVICE_ID)->trigger($event);
        $this->saveTicket($ticket);
    }

    protected function saveTicket(Ticket $ticket) {
        $this->getServiceLocator()->get(TicketRepository::SERVICE_ID)->saveTicket($ticket);
    }
}
