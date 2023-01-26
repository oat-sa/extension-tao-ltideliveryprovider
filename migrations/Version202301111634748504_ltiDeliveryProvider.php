<?php

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\ltiDeliveryProvider\model\events\LtiAgsListener;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoResultServer\models\Events\DeliveryExecutionResultsRecalculated;

final class Version202301111634748504_ltiDeliveryProvider extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register LtiAgsListener events';
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);

        $eventManager->attach(
            DeliveryExecutionResultsRecalculated::class,
            [LtiAgsListener::class, 'onDeliveryExecutionResultsRecalculated']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);

        $eventManager->detach(
            DeliveryExecutionResultsRecalculated::class,
            [LtiAgsListener::class, 'onDeliveryExecutionResultsRecalculated']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }
}
