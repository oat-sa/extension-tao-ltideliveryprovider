<?php

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\ltiDeliveryProvider\controller\DeliveryTool;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\tao\model\user\TaoRoles;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoLti\controller\Security;

final class Version2021090801_ltiDeliveryProvider extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow run DeliveryTool::launch1p3 by anonymous';
    }

    public function up(Schema $schema): void
    {
        AclProxy::applyRule($this->getRule());
    }

    public function down(Schema $schema): void
    {
        AclProxy::revokeRule($this->getRule());
    }

    private function getRule(): AccessRule
    {
        return new AccessRule(AccessRule::GRANT, TaoRoles::ANONYMOUS, DeliveryTool::class . '@launch1p3');
    }
}
