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
 * Copyright (c) 2024 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\model\session\ConcuringSession;

use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\tao\model\featureFlag\FeatureFlagChecker;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\StateServiceInterface;
use oat\taoQtiTest\model\Service\ConcurringSessionService;
use Psr\Container\ContainerInterface;
use oat\oatbox\service\ServiceManager;
use core_kernel_classes_Resource;

class LtiConcurringSessionService extends ConcurringSessionService
{
    /**
     * @var string Controls whether a delivery execution state should be kept as is or reset each time it starts.
     *             `false` – the state will be reset on each restart.
     *             `true` – the state will be maintained upon a restart.
     *
     * phpcs:disable Generic.Files.LineLength
     */
    public const FEATURE_FLAG_MAINTAIN_RESTARTED_DELIVERY_EXECUTION_STATE = 'FEATURE_FLAG_MAINTAIN_RESTARTED_DELIVERY_EXECUTION_STATE';
    // phpcs:enable Generic.Files.LineLength

    public function pauseActiveDeliveryExecution($activeExecution): void
    {
        if ($activeExecution instanceof DeliveryExecution) {
            $this->getConcurringSessionService()->pauseConcurrentSessions($activeExecution);

            if ($activeExecution->getState()->getUri() === DeliveryExecution::STATE_PAUSED) {
                $this->getConcurringSessionService()->adjustTimers($activeExecution);
            }

            $this->getConcurringSessionService()->clearConcurringSession($activeExecution);
        }

        $this->resetDeliveryExecutionState($activeExecution);
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     *
     * @return mixed|null|DeliveryExecution
     */
    public function getActiveDeliveryExecution(core_kernel_classes_Resource $delivery)
    {
        /** @var LtiDeliveryExecutionService $deliveryExecutionService */
        $deliveryExecutionService = $this->getServiceManager()->get(LtiDeliveryExecutionService::SERVICE_ID);
        return  $deliveryExecutionService
            ->getActiveDeliveryExecution($delivery);
    }

    private function resetDeliveryExecutionState(DeliveryExecution $activeExecution = null): void
    {
        if (
            null === $activeExecution
            || !$this->isDeliveryExecutionStateResetEnabled()
            || $activeExecution->getState()->getUri() === DeliveryExecution::STATE_PAUSED
        ) {
            return;
        }

        $this->getStateService()->pause($activeExecution);
    }

    private function isDeliveryExecutionStateResetEnabled(): bool
    {
        return !$this->getFeatureFlagChecker()->isEnabled(
            static::FEATURE_FLAG_MAINTAIN_RESTARTED_DELIVERY_EXECUTION_STATE
        );
    }

    private function getStateService(): StateServiceInterface
    {
        return $this->getPsrContainer()->get(StateServiceInterface::SERVICE_ID);
    }

    private function getFeatureFlagChecker(): FeatureFlagChecker
    {
        return $this->getPsrContainer()->get(FeatureFlagChecker::class);
    }

    private function getConcurringSessionService(): ConcurringSessionService
    {
        return $this->getPsrContainer()->get(ConcurringSessionService::class);
    }

    public function getPsrContainer(): ContainerInterface
    {
        return $this->getServiceManager()->getContainer();
    }

    private function getServiceManager()
    {
        return ServiceManager::getServiceManager();
    }
}
