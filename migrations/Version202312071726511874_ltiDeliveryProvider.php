<?php

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\ltiDeliveryProvider\model\events\LtiAgsListener;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDelivery\models\classes\execution\event\DeliveryExecutionState;
use oat\taoQtiTest\models\event\TestVariablesRecorded;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202312071726511874_ltiDeliveryProvider extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register ResultTestVariablesAfterTransmissionEvent event,' .
            'Unregister LtiAgsListener onDeliveryExecutionStateUpdate event';
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);

        $eventManager->attach(
            TestVariablesRecorded::class,
            [LtiAgsListener::class, 'onDeliveryExecutionFinish']
        );

        $eventManager->detach(
            DeliveryExecutionState::class,
            [LtiAgsListener::class, 'onDeliveryExecutionStateUpdate']
        );


        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);

        $eventManager->detach(
            TestVariablesRecorded::class,
            [LtiAgsListener::class, 'onDeliveryExecutionFinish']
        );

        $eventManager->attach(
            DeliveryExecutionState::class,
            [LtiAgsListener::class, 'onDeliveryExecutionStateUpdate']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }
}
