<?php

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\ltiDeliveryProvider\model\execution\Lti1p3DryRunChecker;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDelivery\model\execution\DeliveryServerService;

final class Version202111161637067334_ltiDeliveryProvider extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register LTI 1.3 DryRun checker';
    }

    public function up(Schema $schema): void
    {
        $deliveryServerService = $this->getServiceManager()->get(DeliveryServerService::SERVICE_ID);

        $deliveryServerService->registerMiddleware(new Lti1p3DryRunChecker());

        $this->registerService(DeliveryServerService::SERVICE_ID, $deliveryServerService);
    }

    public function down(Schema $schema): void
    {
        $deliveryServerService = $this->getServiceManager()->get(DeliveryServerService::SERVICE_ID);

        $deliveryServerService->unregisterMiddleware(Lti1p3DryRunChecker::class);

        $this->registerService(DeliveryServerService::SERVICE_ID, $deliveryServerService);
    }
}
