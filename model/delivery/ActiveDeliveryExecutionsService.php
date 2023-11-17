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
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\model\delivery;

use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\DeliveryExecutionService;

class ActiveDeliveryExecutionsService
{
    private Ontology $ontology;
    private DeliveryExecutionService $deliveryExecutionService;

    public function __construct(
        Ontology $ontology,
        DeliveryExecutionService $deliveryExecutionService
    ) {
        $this->ontology = $ontology;
        $this->deliveryExecutionService = $deliveryExecutionService;
    }

    public function getDeliveryIdByExecutionId(string $executionId): ?string
    {
        $executionClass = $this->ontology->getClass(DeliveryExecutionInterface::CLASS_URI);
        $deliveryProperty = $this->ontology->getProperty(DeliveryExecutionInterface::PROPERTY_DELIVERY);

        $executionInstance = $executionClass->getResource($executionId);
        $deliveryUri = $executionInstance->getUniquePropertyValue($deliveryProperty);

        if ($deliveryUri instanceof core_kernel_classes_Resource) {
            $deliveryUri = $deliveryUri->getUri();
        }

        $logger = \common_Logger::singleton()->getLogger();
        $logger->info(
            sprintf('getDeliveryIdByExecutionId: deliveryUri=%s', var_export($deliveryUri, true))
        );

        if ($deliveryUri) {
            return (string) $deliveryUri;
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getExecutionIdsForOtherDeliveries(
        string $userUri,
        string $currentExecutionId
    ): array {
        $currentDeliveryUri = (string) $this->getDeliveryIdByExecutionId($currentExecutionId);
        $executions = $this->getActiveDeliveryExecutionsByUser($userUri);

        $logger = \common_Logger::singleton();

        $logger->logCritical(
            sprintf(
                '%s: userUri=%s currentExecutionId=%s currentDeliveryUri=%s',
                __FUNCTION__,
                $userUri,
                $currentExecutionId,
                $currentDeliveryUri
            )
        );

        $executionIdsForOtherDeliveries = [];

        foreach ($executions as $execution) {
            if (
                $execution->getIdentifier() !== $currentExecutionId
                && $execution->getDelivery()->getUri() !== $currentDeliveryUri
            ) {
                $executionIdsForOtherDeliveries[] = $execution->getUri();

                $logger->logCritical(
                    sprintf(
                        '%s: execution %s belongs to other delivery "%s" != "%s"',
                        __FUNCTION__,
                        $execution->getIdentifier(),
                        $execution->getDelivery()->getUri(),
                        $currentDeliveryUri
                    )
                );
            }
        }

        return $executionIdsForOtherDeliveries;
    }

    /**
     * @return DeliveryExecutionInterface[]
     */
    public function getActiveDeliveryExecutionsByUser(string $userUri): array
    {
        $executionClass = $this->ontology->getClass(DeliveryExecutionInterface::CLASS_URI);
        $executionInstances = $executionClass->searchInstances([
            DeliveryExecutionInterface::PROPERTY_SUBJECT  => $userUri,
            DeliveryExecutionInterface::PROPERTY_STATUS => DeliveryExecutionInterface::STATE_ACTIVE,
        ], [
            'like' => false
        ]);

        $executions = [];

        foreach ($executionInstances as $executionInstance) {
            $executions[] = $this->deliveryExecutionService->getDeliveryExecution(
                $executionInstance->getUri()
            );
        }

        return $executions;
    }
}
