<?php
/**
 * Default config header
 *
 * To replace this add a file D:\domains\package-tao\ltiDeliveryProvider\config/header/LtiDeliveryExecution.conf.php
 */
use oat\ltiDeliveryProvider\model\execution\implementation\LtiDeliveryExecutionService;

return new LtiDeliveryExecutionService([
    LtiDeliveryExecutionService::OPTION_QUEUE_PERSISTENCE => 'cache'
]);
