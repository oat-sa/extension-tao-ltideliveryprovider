<?php

declare(strict_types=1);

namespace oat\ltiDeliveryProvider\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\ltiDeliveryProvider\model\events\LtiAgsListener;
use oat\generis\model\DependencyInjection\ServiceOptions;
use oat\tao\model\featureFlag\FeatureFlagCheckerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202204281419021874_ltiDeliveryProvider extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Enable this option will change the mechanic of sending AGS status on survey complete from '
            . 'completed to not ready';
    }

    public function up(Schema $schema): void
    {
        /** @var FeatureFlagCheckerInterface $featureFlagChecker */
        $featureFlagChecker = $this->getServiceLocator()->get(FeatureFlagCheckerInterface::class);

        $this->getServiceLocator()
            ->get(ServiceOptions::SERVICE_ID)
            ->save(
                LtiAgsListener::SERVICE_ID,
                LtiAgsListener::OPTION_SCORING_OWNS_GRADING_PROGRESS,
                $featureFlagChecker->isEnabled(FeatureFlagCheckerInterface::FEATURE_FLAG_SCORING_OWNS_GRADING_PROGRESS)
            );
    }

    public function down(Schema $schema): void
    {
        $this->getServiceLocator()
            ->get(ServiceOptions::SERVICE_ID)
            ->remove(LtiAgsListener::SERVICE_ID, LtiAgsListener::OPTION_SCORING_OWNS_GRADING_PROGRESS);
    }
}
