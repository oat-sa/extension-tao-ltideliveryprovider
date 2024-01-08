<?php

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\ltiDeliveryProvider\model\events\LtiAgsListener;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoQtiTest\models\event\DeliveryExecutionFinish;

/**
 * Auto-generated Migration: Please modify to your needs!
 *
 * phpcs:disable Squiz.Classes.ValidClassName
 */
final class Version202312291225421874_ltiDeliveryProvider extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove TestVariablesRecorded and add DeliveryExecutionFinish event.';
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);

        $eventManager->attach(
            DeliveryExecutionFinish::class,
            [LtiAgsListener::class, 'onDeliveryExecutionFinish']
        );

        $eventManager->detach(
            'TestVariablesRecorded',
            [LtiAgsListener::class, 'onDeliveryExecutionFinish']
        );


        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);

        $eventManager->detach(
            DeliveryExecutionFinish::class,
            [LtiAgsListener::class, 'onDeliveryExecutionFinish']
        );

        $eventManager->attach(
            'TestVariablesRecorded',
            [LtiAgsListener::class, 'onDeliveryExecutionFinish']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }
}
