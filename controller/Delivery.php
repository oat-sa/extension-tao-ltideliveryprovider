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
        // validate request
        /*
        $ltiLaunchData = LtiLaunchData::fromRequest($request);
        $this->logLti($ltiLaunchData->getVariables());
        $validator = $this->getServiceLocator()->get(LtiValidatorService::SERVICE_ID);
        $validator->validateLaunchData($ltiLaunchData);
        */

        // check availability
        // @todo
        $queueService = $this->getServiceLocator()->get(QueueService::class);
        $ticket = $queueService->createTicket($request);
        if (Ticket::STATUS_QUEUED == $ticket->getStatus()) {
            // return launch queue client
            return $this->getPsrResponse()->withStatus(500)->withBody('Overloaded');
        }

        return $this->launchTicket($ticket);
    }

    public function ticket() {
        return $this->getPsrResponse()->withBody(json_encode([
            'id' => $this->getGetParameter('id'),
            'status' => Ticket::STATUS_READY
        ]))->withHeader('Content-Type', 'application/json');
    }

    public function launch() {
        $queueService = $this->getServiceLocator()->get(QueueService::class);
        $ticket = $queueService->getTicket($this->getGetParameter('ticket'));
        $this->launch($ticket);
    }

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
        $this->forward('run', 'DeliveryTool', 'ltiDeliveryProvider', $request->getQueryParams());
    }
}