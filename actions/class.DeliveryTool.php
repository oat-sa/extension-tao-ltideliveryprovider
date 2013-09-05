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
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 *
 */

/**
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package ltiDeliveryProvider
 * @subpackage actions
 */
class ltiDeliveryProvider_actions_DeliveryTool extends taoLti_actions_ToolModule
{

    /**
     * (non-PHPdoc)
     * @see taoLti_actions_ToolModule::getTool()
     */
    protected function getTool()
    {
        return ltiDeliveryProvider_models_classes_LTIDeliveryTool::singleton();
    }

    /**
     * Returns the delivery associated with the current link
     * either from url or from the remote_link if configured
     * returns null if none found
     *
     * @return core_kernel_classes_Resource
     */
    private function getDelivery()
    {
        $returnValue = null;
        //passed as aprameter
        if ($this->hasRequestParameter('delivery')) {
            $returnValue = new core_kernel_classes_Resource($this->getRequestParameter('delivery'));
        } else {
            // encoded in url
            $rootUrlPath = parse_url(ROOT_URL, PHP_URL_PATH);
            $absPath = parse_url('/' . ltrim($_SERVER['REQUEST_URI'], '/'), PHP_URL_PATH);
            if (substr($absPath, 0, strlen($rootUrlPath)) != $rootUrlPath) {
                throw new ResolverException('Request Uri ' . $request . ' outside of TAO path ' . ROOT_URL);
            }
            $relPath = substr($absPath, strlen($rootUrlPath));
            $parts = explode('/', $relPath, 4);
            ;
            if (count($parts) == 4) {
                list ($extension, $module, $action, $codedUri) = $parts;
                $params = unserialize(base64_decode($codedUri));
                $returnValue = new core_kernel_classes_Resource($params['delivery']);
            } else {
                // stored in link
                $returnValue = ltiDeliveryProvider_models_classes_LTIDeliveryTool::singleton()->getDeliveryFromLink();
            }
        }
        return $returnValue;
    }

    /**
     * (non-PHPdoc)
     * @see taoLti_actions_ToolModule::run()
     */
    protected function run()
    {
        $compiledDelivery = $this->getDelivery();
        
        if (is_null($compiledDelivery)) {
            if (tao_helpers_funcACL_funcACL::hasAccess('ltiDeliveryProvider', 'LinkConfiguration', 'configureDelivery')) {
                // user authorised to select the Delivery
                $this->redirect(tao_helpers_Uri::url('configureDelivery', 'LinkConfiguration', null));
            } else {
                // user NOT authorised to select the Delivery
                $this->returnError(__('This tool has not yet been configured, please contact your instructor'), false);
            }
        } else {
            
            if (tao_helpers_funcACL_funcACL::hasAccess('taoDelivery', 'DeliveryServer', 'resumeDeliveryExecution')) {
                $this->startResumeDelivery($compiledDelivery);
            } elseif (tao_helpers_funcACL_funcACL::hasAccess('ltiDeliveryProvider', 'LinkConfiguration', 'configureDelivery')) {
                $this->redirect(tao_helpers_Uri::url('configureDelivery', 'LinkConfiguration', null));
            } else {
                $this->returnError(__('Access to this functionality is restricted to students'), false);
            }
        }
    }

    /**
     * Resumes the delivery if the current user has already an active execution of the delivery
     * or alternatively starts a new ProcessExecution and redirects the user to the processBrowser
     *
     * @param core_kernel_classes_Resource $delivery            
     */
    protected function startResumeDelivery(core_kernel_classes_Resource $compiledDelivery)
    {
        $remoteLink = taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLtiLinkResource();
        $userId = common_session_SessionManager::getSession()->getUserUri();
        $deliveryExecution = $this->getTool()->getDeliveryExecution($remoteLink, $userId);
        if (is_null($deliveryExecution)) {
            $deliveryExecution = taoDelivery_models_classes_DeliveryExecutionService::singleton()->initDeliveryExecution(
                $compiledDelivery,
                $userId
            );
            $this->getTool()->linkDeliveryExecution($remoteLink, $userId, $deliveryExecution);
        }
        //The result server from LTI context depend on call parameters rather than static result server definition
        $this->initLtiResultServer($compiledDelivery, $deliveryExecution);
        // lis_outcome_service_url This value should not change from one launch to the next and in general,
        //  the TP can expect that there is a one-to-one mapping between the lis_outcome_service_url and a particular oauth_consumer_key.  This value might change if there was a significant re-configuration of the TC system or if the TC moved from one domain to another.
        
        $params = array(
            'uri' => $deliveryExecution->getUri()
        );
        $this->redirect(_url('resumeDeliveryExecution', 'DeliveryServer', 'taoDelivery', $params));
	}

    private function  initLtiResultServer($compiledDelivery, $deliveryExecution) {
        $launchData = taoLti_models_classes_LtiService::singleton()->getLtiSession()->getLaunchData();
        $resultServerCallOptions = array(
            "type" =>"LTI_Basic_1.1.1",
            "result_identifier" => $launchData->getVariable("lis_result_sourcedid"),
            "consumer_key" => $launchData->getOauthKey(),
            "service_url" => $launchData->getVariable("lis_outcome_service_url"),
            "user_identifier" => common_session_SessionManager::getSession()->getUserUri()
        );
        //starts or resume a taoResultServerStateFull session for results submission

        //retrieve the resultServer definition that is related to this delivery to be used
        $delivery = taoDelivery_models_classes_DeliveryServerService::singleton()->getDeliveryFromCompiledDelivery($compiledDelivery);
        //retrieve the result server definition
        $resultServer = $delivery->getUniquePropertyValue(new core_kernel_classes_Property(TAO_DELIVERY_RESULTSERVER_PROP));
        //callOptions are required in the case of a LTI basic storage

        taoResultServer_models_classes_ResultServerStateFull::singleton()->initResultServer($resultServer->getUri(), $resultServerCallOptions);

        //a unique identifier for data collected through this delivery execution
        //in the case of LTI, we should use the sourceId
        $resultIdentifier = (isset($resultServerCallOptions["result_identifier"])) ? $resultServerCallOptions["result_identifier"] :$deliveryExecution->getUri();
        //the dependency to taoResultServer should be re-thinked with respect to a delivery level proxy
        taoResultServer_models_classes_ResultServerStateFull::singleton()->spawnResult($deliveryExecution->getUri(), $resultIdentifier);
        common_Logger::i("Spawning".$resultIdentifier ."related to process execution ".$deliveryExecution->getUri());
        $userIdentifier = (isset($resultServerCallOptions["user_identifier"])) ? $resultServerCallOptions["user_identifier"] :wfEngine_models_classes_UserService::singleton()->getCurrentUser()->getUri();
        //set up the related test taker
        //a unique identifier for the test taker
        taoResultServer_models_classes_ResultServerStateFull::singleton()->storeRelatedTestTaker( $userIdentifier);

         //a unique identifier for the delivery
        taoResultServer_models_classes_ResultServerStateFull::singleton()->storeRelatedDelivery($delivery->getUri());
    }
	
}