<?php
use oat\ltiDeliveryProvider\model\Queue\QueueService;

return new QueueService([
    QueueService::OPTION_PERSISTENCE => 'default_kv',
    QueueService::OPTION_TTL => 3600 * 24,
]);