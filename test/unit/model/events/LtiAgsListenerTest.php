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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\test\unit\model\events;

use oat\generis\test\MockObject;
use OAT\Library\Lti1p3Ags\Model\Score\ScoreInterface;
use oat\oatbox\user\User;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionStateContext;
use \PHPUnit\Framework\TestCase;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\ltiDeliveryProvider\model\events\LtiAgsListener;

class LtiAgsListenerTest extends TestCase
{
    /** @var DeliveryExecutionState  */
    private $event;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $deliveryExecutionMock = $this->createMock(DeliveryExecutionInterface::class);

        $deliveryExecutionContext = new DeliveryExecutionStateContext([
            DeliveryExecutionStateContext::PARAM_USER => $this->createMock(User::class)
        ]);

        $this->event = new DeliveryExecutionState(
            $deliveryExecutionMock,
            DeliveryExecutionInterface::STATE_FINISHED,
            DeliveryExecutionInterface::STATE_ACTIVE,
            $deliveryExecutionContext
        );
    }


    public function testSendNotReadyStatusIfScoringOwnsGradingProgressEnabled()
    {
        $subject = $this->createMock(LtiAgsListener::class);
        $subject->method('isScoringOwnsGradingProgressEnabled')
            ->willReturn(true);

        $gradingProgress = ScoreInterface::GRADING_PROGRESS_STATUS_FULLY_GRADED;
        $this->createMock(
            QueueDispatcherInterface::class
        )
            ->method('createTask')
            ->with(
                $this->callback(
                    function ($class, $params) use(&$gradingProgress) {
                        $gradingProgress = $params['data']['gradingProgress'];
                    }
                )
            );

        $subject->onDeliveryExecutionStateUpdate($this->event);

        $this->assertEquals($gradingProgress, ScoreInterface::GRADING_PROGRESS_STATUS_NOT_READY);
    }

    public function testSendNotReadyStatusIfScoringOwnsGradingProgressDisable()
    {
        $subject = $this->createMock(LtiAgsListener::class);
        $subject->method('isScoringOwnsGradingProgressEnabled')
            ->willReturn(false);

        $gradingProgress = ScoreInterface::GRADING_PROGRESS_STATUS_FULLY_GRADED;
        $this->createMock(
            QueueDispatcherInterface::class
        )
            ->method('createTask')
            ->with(
                $this->callback(
                    function ($class, $params) use(&$gradingProgress) {
                        $gradingProgress = $params['data']['gradingProgress'];
                    }
                )
            );

        $subject->onDeliveryExecutionStateUpdate($this->event);

        $this->assertEquals($gradingProgress, ScoreInterface::GRADING_PROGRESS_STATUS_NOT_READY);
    }
}