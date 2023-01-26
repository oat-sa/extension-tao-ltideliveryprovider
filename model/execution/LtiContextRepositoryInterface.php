<?php

namespace oat\ltiDeliveryProvider\model\execution;

use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoLti\models\classes\LtiLaunchData;

interface LtiContextRepositoryInterface
{
    public function findByDeliveryExecution(DeliveryExecutionInterface $deliveryExecution): ?LtiLaunchData;

    public function save(LtiLaunchData $ltiLaunchData, DeliveryExecutionInterface $deliveryExecution): void;
}
