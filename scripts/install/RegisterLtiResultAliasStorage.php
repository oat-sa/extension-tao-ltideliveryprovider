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
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\scripts\install;

use common_exception_Error;
use common_persistence_SqlPersistence;
use common_report_Report;
use oat\ltiDeliveryProvider\model\LtiResultAliasStorage;

/**
 * Class RegisterLtiResultAliasStorage
 * @package oat\ltiDeliveryProvider\scripts\install
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class RegisterLtiResultAliasStorage extends \common_ext_action_InstallAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws common_exception_Error
     */
    public function __invoke($params)
    {
        $persistenceId = count($params) > 0 ? reset($params) : 'default';
        $storageService = new LtiResultAliasStorage([LtiResultAliasStorage::OPTION_PERSISTENCE => $persistenceId]);
        $this->getServiceManager()->register(LtiResultAliasStorage::SERVICE_ID, $storageService);
        $this->createTable($storageService->getPersistence());
        return new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Registered and created LtiResultIdStorage Rds Storage'));
    }

    /**
     * @param common_persistence_SqlPersistence $persistence
     */
    public function createTable(\common_persistence_SqlPersistence $persistence)
    {
        LtiResultAliasStorage::install($persistence);
    }
}
