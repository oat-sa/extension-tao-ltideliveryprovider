<?php

/*  
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
use \core_kernel_classes_Property;
use \common_session_SessionManager;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoResultServer\models\classes\ResultServerService;
use \taoLti_models_classes_LtiLaunchData;
use \common_Logger;
use \taoResultServer_models_classes_ResultServerStateFull;

class ResultServer
{

    public static function initLtiResultServer(core_kernel_classes_Resource $delivery, $executionIdentifier, $launchData) {
        
        $resultServer = $delivery->getOnePropertyValue(new core_kernel_classes_Property(DeliveryContainerService::RESULT_SERVER_PROP));
        if (empty($resultServer)) {
            //No static result server was associated with the delivery
            $resultServer = new core_kernel_classes_Resource(ResultServerService::RESULT_SERVER);
        }
        
        $resultIdentifier = $launchData->hasVariable("lis_result_sourcedid")
            ? $launchData->getVariable("lis_result_sourcedid")
            : $executionIdentifier;

        if ($launchData->hasVariable("lis_result_sourcedid") && $launchData->hasVariable("lis_outcome_service_url")) {
                
            $options = array(
                array(
    	        "implementation" =>"taoLtiBasicOutcome_models_classes_LtiBasicOutcome",
    	        "parameters" => array(
                    "result_identifier" => $launchData->getVariable("lis_result_sourcedid"),
                    "consumer_key" => $launchData->getOauthKey(),
                    "service_url" => $launchData->getVariable("lis_outcome_service_url"),
                    "user_identifier" => common_session_SessionManager::getSession()->getUserUri(),
    	            "user_fullName" => ($launchData->hasVariable(taoLti_models_classes_LtiLaunchData::LIS_PERSON_NAME_FULL)
    	                ? $launchData->getVariable(taoLti_models_classes_LtiLaunchData::LIS_PERSON_NAME_FULL)
    	                : '')
                    )
                )
    	    );
        } else {
            $options = array();
        }

        common_Logger::i("Spawning '".$resultIdentifier ."' related to delivery execution ".$executionIdentifier);
        $resultServerState = taoResultServer_models_classes_ResultServerStateFull::singleton(); 
        $resultServerState->initResultServer($resultServer->getUri(), $options);
        $resultServerState->spawnResult($executionIdentifier, $executionIdentifier);
         
        $resultServerState->storeRelatedTestTaker(common_session_SessionManager::getSession()->getUserUri());
        $resultServerState->storeRelatedDelivery($delivery->getUri());
	}


   
}

?>
