<?php

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\ltiDeliveryProvider\model\session\DataAccess\Factory\SessionCookieAttributesFactory;
use oat\ltiDeliveryProvider\scripts\install\RegisterSessionCookieAttributesFactory;
use oat\tao\scripts\install\RegisterSessionCookieService;
use oat\tao\scripts\tools\migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202008201411081874_ltiDeliveryProvider extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register ' . SessionCookieAttributesFactory::class;
    }

    public function up(Schema $schema): void
    {
        $this->addReport(
            $this->propagate(
                new RegisterSessionCookieAttributesFactory()
            )()
        );
    }

    public function down(Schema $schema): void
    {
        $this->addReport(
            $this->propagate(
                new RegisterSessionCookieService()
            )()
        );

        $this->getServiceManager()->unregister(SessionCookieAttributesFactory::SERVICE_ID);
    }
}
