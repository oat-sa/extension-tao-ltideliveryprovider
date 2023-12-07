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
 * Copyright (c) 2021-2023 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\model\events;

use DateTime;
use DateTimeInterface;
use OAT\Library\Lti1p3Ags\Model\Score\ScoreInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use oat\ltiDeliveryProvider\model\execution\LtiContextRepositoryInterface;
use oat\ltiDeliveryProvider\model\tasks\SendAgsScoreTask;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\user\Lti1p3User;
use oat\taoQtiTest\models\event\ResultTestVariablesAfterTransmissionEvent;
use oat\taoResultServer\models\Events\DeliveryExecutionResultsRecalculated;
use tao_helpers_Date as DateHelper;
use taoResultServer_models_classes_OutcomeVariable as OutcomeVariable;

class LtiAgsListener extends ConfigurableService
{
    public const OPTION_AGS_MAX_RETRY = 'ags_max_retries';

    public function onDeliveryExecutionStart(DeliveryExecutionCreated $event): void
    {
        $user = $event->getUser();
        $deliveryExecution = $event->getDeliveryExecution();

        if ($user instanceof Lti1p3User && $user->getLaunchData()->hasVariable(LtiLaunchData::AGS_CLAIMS)) {

            /** @var AgsClaim $agsClaim */
            $agsClaim = $user->getLaunchData()->getVariable(LtiLaunchData::AGS_CLAIMS);

            /** @var QueueDispatcherInterface $taskQueue */
            $taskQueue = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
            $taskQueue->createTask(new SendAgsScoreTask(), [
                'registrationId' => $user->getRegistrationId(),
                'deliveryExecutionId' => $deliveryExecution->getIdentifier(),
                'agsClaim' => $agsClaim->normalize(),
                'data' => [
                    'userId' => $user->getIdentifier(),
                    'activityProgress' => ScoreInterface::ACTIVITY_PROGRESS_STATUS_STARTED,
                    'timestamp' => (new DateTime('now'))->format(DateTimeInterface::RFC3339_EXTENDED),
                ]
            ], 'AGS score send on test launch');
        }
    }

    public function onDeliveryExecutionResultsRecalculated(DeliveryExecutionResultsRecalculated $event): void
    {
        $deliveryExecution = $event->getDeliveryExecution();
        $gradingStatus = ScoreInterface::GRADING_PROGRESS_STATUS_PENDING_MANUAL;
        if ($event->isFullyGraded()) {
            $gradingStatus = ScoreInterface::GRADING_PROGRESS_STATUS_FULLY_GRADED;
        }

        if ($launchData = $this->getLtiContextRepository()->findByDeliveryExecution($deliveryExecution)) {
            $this->queueSendAgsScoreTaskWithScores(
                'AGS scores send on result recalculation',
                $launchData,
                $deliveryExecution->getUserIdentifier(),
                $event->getTotalScore(),
                $event->getTotalMaxScore(),
                $gradingStatus,
                $event->getTimestamp()
            );
        }
    }

    public function onDeliveryExecutionFinish(ResultTestVariablesAfterTransmissionEvent $event): void
    {
        $launchData = $this->getLtiContextRepository()->findByDeliveryExecutionId($event->getDeliveryExecutionId());

        $scoreTotal = null;
        $scoreTotalMax = null;
        $scoreTotalMicrotime = null;
        foreach ($event->getVariables() as $variable) {
            $variable = array_pop($variable)->variable;

            if ($variable instanceof OutcomeVariable) {
                if ($variable->getIdentifier() === 'SCORE_TOTAL') {
                    $scoreTotal = $variable->getValue();
                    $scoreTotalMicrotime = $variable->getEpoch();
                }

                if ($variable->getIdentifier() === 'SCORE_TOTAL_MAX') {
                    $scoreTotalMax = $variable->getValue();
                }

                if ($scoreTotal !== null && $scoreTotalMax !== null) {
                    break;
                }
            }
        }

        $this->queueSendAgsScoreTaskWithScores(
            'AGS score send on test finish',
            $launchData,
            $event->getDeliveryExecutionId(),
            $scoreTotal,
            $scoreTotalMax,
            $event->getIsManualScored()
                ? ScoreInterface::GRADING_PROGRESS_STATUS_PENDING_MANUAL
                : ScoreInterface::GRADING_PROGRESS_STATUS_FULLY_GRADED,
            DateHelper::formatMicrotime($scoreTotalMicrotime)
        );
    }

    private function queueSendAgsScoreTaskWithScores(
        string $taskLabel,
        LtiLaunchData $ltiLaunchData,
        string $deliveryExecutionId,
        $scoreTotal,
        $scoreTotalMax,
        string $gradingStatus,
        ?string $timestamp = null
    ): void {

        if (!$ltiLaunchData->hasVariable(LtiLaunchData::AGS_CLAIMS)) {
            return;
        }

        $agsClaim = $ltiLaunchData->getVariable(LtiLaunchData::AGS_CLAIMS);
        $registrationId = $ltiLaunchData->getVariable(LtiLaunchData::TOOL_CONSUMER_INSTANCE_ID);
        $userId = $ltiLaunchData->getUserID();
        $taskBody = [
            'retryMax' => $this->getAgsMaxRetries(),
            'registrationId' => $registrationId,
            'deliveryExecutionId' => $deliveryExecutionId,
            'agsClaim' => $agsClaim->normalize(),
            'data' => [
                'userId' => $userId,
                'activityProgress' => ScoreInterface::ACTIVITY_PROGRESS_STATUS_COMPLETED,
                'gradingProgress' => $gradingStatus,
                'scoreGiven' => $scoreTotal,
                'scoreMaximum' => $scoreTotalMax,
                'timestamp' => $timestamp ?? (new DateTime('now'))->format(DateTimeInterface::RFC3339_EXTENDED),
            ]
        ];

        /** @var QueueDispatcherInterface $taskQueue */
        $taskQueue = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
        $taskQueue->createTask(new SendAgsScoreTask(), $taskBody, $taskLabel);
    }

    private function getAgsMaxRetries(): int
    {
        return $this->getOption(self::OPTION_AGS_MAX_RETRY, 5);
    }

    private function getLtiContextRepository(): LtiContextRepositoryInterface
    {
        return $this->getServiceManager()->getContainer()->get(LtiContextRepositoryInterface::class);
    }
}
