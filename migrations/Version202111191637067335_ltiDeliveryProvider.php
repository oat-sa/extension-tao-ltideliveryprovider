<?php

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use oat\ltiDeliveryProvider\model\execution\Lti1p3ResultServerServiceFactory;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDelivery\model\execution\DeliveryServerService;

final class Version202111191637067335_ltiDeliveryProvider extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register ResultServerServiceFactory for Delivery extension';
    }

    public function up(Schema $schema): void
    {
        $deliveryServerService = $this->getServiceManager()->get(DeliveryServerService::SERVICE_ID);

        $deliveryServerService->setOption(
            DeliveryServerService::OPTION_RESULT_SERVER_SERVICE_FACTORY,
            new Lti1p3ResultServerServiceFactory()
        );

        $this->registerService(DeliveryServerService::SERVICE_ID, $deliveryServerService);
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration(
            'Configuration file has been modified'
        );
    }
}
