<?php

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\ltiDeliveryProvider\model\events\LtiAgsListener;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionCreated;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;

final class Version202110201634748504_ltiDeliveryProvider extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register LtiAgsListener events';
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);

        $eventManager->attach(
            DeliveryExecutionCreated::class,
            [LtiAgsListener::class, 'onDeliveryExecutionStart']
        );

        $eventManager->attach(
            DeliveryExecutionState::class,
            [LtiAgsListener::class, 'onDeliveryExecutionStateUpdate']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);

        $eventManager->detach(
            DeliveryExecutionCreated::class,
            [LtiAgsListener::class, 'onDeliveryExecutionStart']
        );

        $eventManager->detach(
            DeliveryExecutionState::class,
            [LtiAgsListener::class, 'onDeliveryExecutionStateUpdate']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }
}
