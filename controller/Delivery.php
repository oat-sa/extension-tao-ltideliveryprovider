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

namespace oat\ltiDeliveryProvider\controller;

use oat\tao\model\http\Controller;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use function GuzzleHttp\Psr7\stream_for;
use oat\taoLti\models\classes\LtiService;
use oat\ltiDeliveryProvider\model\Queue\QueueService;
use oat\ltiDeliveryProvider\model\Queue\Ticket;
use common_http_Request;
use oat\oatbox\session\SessionService;
use oat\ltiDeliveryProvider\model\Queue\QueuedUser;
use oat\tao\helpers\Template;
use oat\tao\model\security\SecurityException;

/**
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package filemanager
 */
class Delivery extends Controller implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Launch a delivery execution
     */
    public function index()
    {
        $request = $this->getPsrRequest();
        // @todo validate request

        $queueService = $this->getServiceLocator()->get(QueueService::class);
        $ticket = $queueService->createTicket($request);
        if (Ticket::STATUS_QUEUED == $ticket->getStatus()) {
            $session = new \common_session_DefaultSession(new QueuedUser($ticket->getId()));
            $this->getServiceLocator()->get(SessionService::SERVICE_ID)->setSession($session);
            return $this->forward('queue', null, null, ['ticket' => $ticket->getId()]);
        }
        return $this->launchTicket($ticket);
    }

    /**
     * Load the queue view
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function queue() {
        $renderer = new \Renderer();
        $renderer->setTemplate(Template::getTemplate('Deliver/queue.tpl', 'ltiDeliveryProvider'));
        $renderer->setData('ticketId', $this->getGetParameter('ticket'));
        return $this->getPsrResponse()->withBody(stream_for($renderer->render()));
    }

    /**
     * Retrieve the ticket informaion
     * @throws SecurityException
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function ticket() {
        $queueService = $this->getServiceLocator()->get(QueueService::class);
        $ticket = $queueService->getTicket($this->getGetParameter('id'));
        if ($ticket->getId() !== $this->getTicketIdFromSession()) {
            throw new SecurityException('User tried to access invalid ticket');
        }
        return $this->getPsrResponse()->withBody(stream_for(json_encode([
            'id' => $ticket->getId(),
            'status' => $ticket->getStatus()
        ])))->withHeader('Content-Type', 'application/json');
    }

    /**
     * Launch the queued action
     * @throws \common_exception_InconsistentData
     * @throws SecurityException
     */
    public function launch() {
        $queueService = $this->getServiceLocator()->get(QueueService::class);
        $ticket = $queueService->getTicket($this->getGetParameter('ticket'));
        if ($ticket->getStatus() !== Ticket::STATUS_READY) {
            throw new \common_exception_InconsistentData('User trying to launch a call that is not ready');
        }
        if ($ticket->getId() !== $this->getTicketIdFromSession()) {
            throw new SecurityException('User tried to access invalid ticket');
        }
        $this->launchTicket($ticket);
    }

    /**
     * Wrap the request, validate the session, and redirect to the lti tool
     * @param Ticket $ticket
     */
    protected function launchTicket(Ticket $ticket)
    {
        $ltiService = $this->getServiceLocator()->get(LtiService::class);
        $request = $ticket->getRequest();
        $combined = array_merge($request->getQueryParams(), $request->getParsedBody());
        $legacyRequest = new common_http_Request(
            $request->getUri()->__toString(),
            $request->getMethod(),
            $combined,
            $request->getHeaders(),
            $request->getBody()
        );
        $session = $ltiService->createLtiSession($legacyRequest);
        $this->getServiceLocator()->get(SessionService::SERVICE_ID)->setSession($session);
        $this->redirect(_url('run', 'DeliveryTool', 'ltiDeliveryProvider', $request->getQueryParams()));
    }

    /**
     * @return string ticekt from session
     */
    protected function getTicketIdFromSession()
    {
        $session = $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentSession();
        if (!$session->getUser() instanceof QueuedUser) {
            throw new \common_exception_InconsistentData('User is not queued');
        }
        return $session->getUser()->getAsociatedTicket();
    }
}