<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2018  (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\scripts\dbMigrations;

use oat\ltiDeliveryProvider\model\execution\implementation\KvLTIDeliveryExecutionLink;
use oat\ltiDeliveryProvider\model\execution\implementation\KvLtiDeliveryExecutionService;
use oat\ltiDeliveryProvider\model\execution\LtiDeliveryExecutionService;
use oat\oatbox\extension\InstallAction;

/**
 * Class RegisterKvLtiDEService
 *
 * usage :
 * sudo -u www-data php index.php 'oat\ltiDeliveryProvider\scripts\dbMigrations\LtiLinksStorage'
 */
class LtiLinksStorage extends InstallAction
{
    public function __invoke($params)
    {
        /** @var LtiDeliveryExecutionService  $ltiDeliveryExecution */
        $ltiDeliveryExecution = $this->getServiceLocator()->get('ltiDeliveryProvider/LtiDeliveryExecution');
        if (!$ltiDeliveryExecution instanceof KvLtiDeliveryExecutionService) {
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, ' migration only available for KvLtiDeliveryExecutionService');
        }
        $migrated = false;
        $persistenceOption = $ltiDeliveryExecution->getOption(KvLtiDeliveryExecutionService::OPTION_PERSISTENCE);
        $persistence = (is_object($persistenceOption)) ? $persistenceOption : \common_persistence_KeyValuePersistence::getPersistence($persistenceOption);
        $keys = $persistence->getDriver()->keys(KvLtiDeliveryExecutionService::LTI_DE_LINK_LINK .'*');

        foreach ($keys as $key) {
            $data = $persistence->get($key);
            if (is_null($data)) {
                continue;
            }
            $objects = $ltiDeliveryExecutionLinks = KvLTIDeliveryExecutionLink::unSerialize($data);
            /** @var KvLTIDeliveryExecutionLink $object */
            foreach ($objects as $object) {
                $linkKey = KvLtiDeliveryExecutionService::LINKS_OF_DELIVERY_EXECUTION . $object->getUserId() . $object->getDeliveryExecutionId();
                $linksOfExecutionAndUser = $persistence->get($linkKey);

                if (is_null($linksOfExecutionAndUser)) {
                    $linksOfExecutionAndUser = [];
                } else {
                    $linksOfExecutionAndUser = json_decode($linksOfExecutionAndUser, true);
                }

                $linksOfExecutionAndUser[] = $object->getLinkId();

                $persistence->set($linkKey, json_encode($linksOfExecutionAndUser));
                $migrated = true;
            }
        }

        if ($migrated) {
            return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, ' migration success');
        }

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, ' nothing migrated');
    }
}