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
 * Copyright (c) 2021-2022 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\model\tasks;

use InvalidArgumentException;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use oat\oatbox\extension\AbstractAction;
use oat\oatbox\reporting\Report;
use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\taoLti\models\classes\LtiAgs\LtiAgsException;
use oat\taoLti\models\classes\LtiAgs\LtiAgsScoreService;
use oat\taoLti\models\classes\LtiAgs\LtiAgsScoreServiceInterface;
use oat\taoLti\models\classes\Platform\Repository\Lti1p3RegistrationRepository;

class SendAgsScoreTask extends AbstractAction
{
    public const FEATURE_FLAG_AGS_SCORE_SENDING_RETRY = 'FEATURE_FLAG_AGS_SCORE_SENDING_RETRY';

    public const RETRY_COUNT = 'retryCount';
    public const RETRY_MAX = 'retryMax';

    /** @var array */
    private $params = [self::RETRY_COUNT => 0];

    public function __invoke($params): Report
    {
        $this->params = array_merge($this->params, $params);

        $this->getLogger()->info('Start AGS score sending task', $params);

        try {
            $this->validateParams($params);
        } catch (InvalidArgumentException $e) {
            return $this->reportError($e->getMessage());
        }

        $registrationId = $params['registrationId'];
        $agsClaim = AgsClaim::denormalize($params['agsClaim']);
        $data = $params['data'];

        /** @var Lti1p3RegistrationRepository $repository */
        $repository = $this->getServiceLocator()->get(Lti1p3RegistrationRepository::SERVICE_ID);
        $registration = $repository->find($registrationId);

        if (null === $registration) {
            return $this->reportError(sprintf('Registration with identifier "%s" not found', $registrationId));
        }

        /** @var LtiAgsScoreService $agsScoreService */
        $agsScoreService = $this->getServiceLocator()->getContainer()->get(LtiAgsScoreServiceInterface::class);

        try {
            $agsScoreService->send($registration, $agsClaim, $data);
        } catch (LtiAgsException $e) {
            $this->retryTask($e);

            return $this->reportError($e->getMessage());
        }

        $this->logInfo('Finish AGS score sending task successfully');

        return Report::createSuccess('AGS score has been sent successfully');
    }

    private function validateParams(array $params): void
    {
        if (!is_string($params['registrationId'] ?? null)) {
            throw new InvalidArgumentException('Parameter "registrationId" must be a string');
        }

        if (!is_array($params['agsClaim'] ?? null) || !is_array($params['agsClaim']['scope'] ?? null)) {
            throw new InvalidArgumentException('Parameter "agsClaim" must be an array and include "scope" as an array');
        }

        if (!is_array($params['data'] ?? null)) {
            throw new InvalidArgumentException('Parameter "data" must be an array');
        }
    }

    private function retryTask(LtiAgsException $exception): void
    {
        if (!$this->isRetryEnabled()) {
            $this->logNotice('Retry is disabled');

            return;
        }

        if ($this->isMaxRetryCountReached()) {
            $this->logCritical(
                'Failed to send AGS Score message: the max number of retries has been reached',
                [
                    'agsClaim' => $exception->getAgsClaim()->normalize(),
                    'score' => json_encode($exception->getScore()),
                    'registration' => $exception->getRegistration()->getIdentifier(),
                ]
            );

            return;
        }

        $this->increaseRetryCount();
        $this->getQueueDispatcher()->createTask(new self, $this->params);
        $this->logInfo('AGS Score message has been rescheduled for another try');
    }

    private function isRetryEnabled(): bool
    {
        return !empty($this->params[self::RETRY_MAX])
            && $this->getFeatureFlagChecker()->isEnabled(self::FEATURE_FLAG_AGS_SCORE_SENDING_RETRY);
    }

    private function increaseRetryCount(): void
    {
        $this->params[self::RETRY_COUNT]++;
    }

    private function isMaxRetryCountReached(): bool
    {
        return $this->params[self::RETRY_COUNT] >= $this->params[self::RETRY_MAX];
    }

    private function reportError(string $message): Report
    {
        $this->logError($message);

        return Report::createError($message);
    }

    private function getQueueDispatcher(): QueueDispatcherInterface
    {
        return $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
    }

    private function getFeatureFlagChecker(): FeatureFlagCheckerInterface
    {
        return $this->getServiceLocator()->get(FeatureFlagChecker::class);
    }
}
