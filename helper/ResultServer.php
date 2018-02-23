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
 * Copyright (c) 2013 Open Assessment Technologies S.A.
 * 
 */

namespace oat\ltiDeliveryProvider\helper;

use \core_kernel_classes_Resource;
use \common_session_SessionManager;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\oatbox\service\ServiceManager;

class ResultServer
{

    public static function initLtiResultServer(core_kernel_classes_Resource $delivery, $executionIdentifier, $launchData)
    {
        $options = [];
        if ($launchData->hasVariable("lis_result_sourcedid") && $launchData->hasVariable("lis_outcome_service_url")) {
            $options = [
                [
                    "implementation" =>"taoLtiBasicOutcome_models_classes_LtiBasicOutcome",
                    "parameters" => [
                        "result_identifier" => $launchData->getVariable("lis_result_sourcedid"),
                        "consumer_key" => $launchData->getOauthKey(),
                        "service_url" => $launchData->getVariable("lis_outcome_service_url"),
                        "user_identifier" => common_session_SessionManager::getSession()->getUserUri(),
                        "user_fullName" => ($launchData->hasVariable(LtiLaunchData::LIS_PERSON_NAME_FULL)
                            ? $launchData->getVariable(LtiLaunchData::LIS_PERSON_NAME_FULL)
                            : '')
                    ]
                ]
            ];
        }
        ServiceManager::getServiceManager()->get(\oat\taoResultServer\models\classes\ResultServerService::SERVICE_ID)
             ->initResultServer($delivery, $executionIdentifier, $options);
    }
}

