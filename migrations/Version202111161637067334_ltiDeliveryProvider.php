<?php

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\ltiDeliveryProvider\model\execution\DryRunChecker;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDelivery\model\execution\DeliveryServerService;

final class Version202111161637067334_ltiDeliveryProvider extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register DryRun checker';
    }

    public function up(Schema $schema): void
    {
        $deliveryServerService = $this->getServiceManager()->get(DeliveryServerService::SERVICE_ID);

        $deliveryServerService->addProvider(new DryRunChecker());

        $this->registerService(DeliveryServerService::SERVICE_ID, $deliveryServerService);
    }

    public function down(Schema $schema): void
    {
        $deliveryServerService = $this->getServiceManager()->get(DeliveryServerService::SERVICE_ID);

        $deliveryServerService->removeProvider(DryRunChecker::class);

        $this->registerService(DeliveryServerService::SERVICE_ID, $deliveryServerService);
    }
}
