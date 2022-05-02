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

use OAT\Library\Lti1p3Ags\Model\Score\ScoreInterface;
use oat\oatbox\user\User;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionStateContext;
use \PHPUnit\Framework\TestCase;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\ltiDeliveryProvider\model\events\LtiAgsListener;
use oat\generis\test\ServiceManagerMockTrait;

class LtiAgsListenerTest extends TestCase
{
    use ServiceManagerMockTrait;

    /** @var DeliveryExecutionState */
    private $event;

    /** @var LtiAgsListener */
    private $subject;

    /** @var string */
    private $gradingProgress;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $deliveryExecutionMock = $this->createMock(DeliveryExecutionInterface::class);

        $deliveryExecutionContext = new DeliveryExecutionStateContext([
            DeliveryExecutionStateContext::PARAM_USER => $this->createMock(User::class),
        ]);

        $this->event = new DeliveryExecutionState(
            $deliveryExecutionMock,
            DeliveryExecutionInterface::STATE_FINISHED,
            DeliveryExecutionInterface::STATE_ACTIVE,
            $deliveryExecutionContext
        );

        $self = &$this;
        $queue = $this->createMock(
            QueueDispatcherInterface::class
        )
            ->method('createTask')
            ->with(
                $this->callback(
                    function ($class, $params) use (&$self) {
                        $self->gradingProgress = $params['data']['gradingProgress'];
                    }
                )
            );

        $this->subject = $this->createMock(LtiAgsListener::class);
        $this->subject->expects($this->once())->method('getQueueDispatcher')->willReturn($queue);
    }


    public function testSendNotReadyStatusIfScoringOwnsGradingProgressEnabled()
    {
        $this->subject->expects($this->once())->method('isScoringOwnsGradingProgressEnabled')->willReturn(true);
        $this->subject->onDeliveryExecutionStateUpdate($this->event);
        $this->assertEquals(ScoreInterface::GRADING_PROGRESS_STATUS_NOT_READY, $this->gradingProgress);
    }

    public function testSendNotReadyStatusIfScoringOwnsGradingProgressDisable()
    {
        $this->subject->method('isScoringOwnsGradingProgressEnabled')->willReturn(false);
        $this->subject->onDeliveryExecutionStateUpdate($this->event);
        $this->assertEquals(ScoreInterface::GRADING_PROGRESS_STATUS_FULLY_GRADED, $this->gradingProgress);
    }

    public function testSendNotReadyStatusFlagEnabled()
    {
        $_ENV[FeatureFlagCheckerInterface::FEATURE_FLAG_SCORING_OWNS_GRADING_PROGRESS] = true;
        $this->assertTrue($this->subject->isScoringOwnsGradingProgressEnabled());
        $this->subject->onDeliveryExecutionStateUpdate($this->event);
        $this->assertEquals(ScoreInterface::GRADING_PROGRESS_STATUS_NOT_READY, $this->gradingProgress);
    }
}