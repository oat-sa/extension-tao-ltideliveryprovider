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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\model\tasks;

use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\reporting\Report;
use oat\taoLti\models\classes\LtiAgsException;
use oat\taoLti\models\classes\LtiAgsScoreService;
use oat\taoLti\models\classes\Platform\Repository\Lti1p3RegistrationRepository;

class SendAgsScoreTask extends AbstractAction
{
    public function __invoke($params): Report
    {
        $registrationId = $params['registrationId'];
        $agsClaim = AgsClaim::denormalize($params['agsClaim']);
        $data = $params['data'];

        /** @var Lti1p3RegistrationRepository $repository */
        $repository = $this->getServiceLocator()->get(Lti1p3RegistrationRepository::SERVICE_ID);
        $registration = $repository->find($registrationId);

        if (null === $registration) {
            return Report::createError(sprintf('Registration with identifier "%s" not found', $registrationId));
        }

        if (!is_array($data)) {
            return Report::createError('Data parameter must be an array');
        }

        /** @var LtiAgsScoreService $agsScoreService */
        $agsScoreService = $this->getServiceLocator()->get(LtiAgsScoreService::SERVICE_ID);
        try {
            $result = $agsScoreService->send($registration, $agsClaim, $data);
        } catch (LtiAgsException $e) {
            return Report::createError($e->getMessage());
        }

        if ($result) {
            return Report::createSuccess('AGS score has been sent successfully');
        }

        return Report::createError('AGS score has not been sent, unsuccessful response code is received');
    }
}