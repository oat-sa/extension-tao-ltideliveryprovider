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
     * @see taoLti_actions_ToolModule::getToolResource()
     */
    protected function getToolResource()
    {
        return ltiDeliveryProvider_models_classes_LTIDeliveryTool::singleton()->getToolResource();
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
        if ($this->hasRequestParameter('delivery')) {
            $returnValue = new core_kernel_classes_Resource($this->getRequestParameter('delivery'));
        } else {
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
        $delivery = $this->getDelivery();
        
        if (is_null($delivery)) {
            if (tao_helpers_funcACL_funcACL::hasAccess('ltiDeliveryProvider', 'LinkConfiguration', 'configureDelivery')) {
                // user authorised to select the Delivery
                $this->redirect(tao_helpers_Uri::url('configureDelivery', 'LinkConfiguration', null));
            } else {
                // user NOT authorised to select the Delivery
                $this->returnError(__('This tool has not yet been configured, please contact your instructor'), false);
            }
        } else {
            $processDefinition = $delivery->getUniquePropertyValue(new core_kernel_classes_Property(TAO_DELIVERY_PROCESS));
            
            $userService = taoDelivery_models_classes_UserService::singleton();
            $subject = $userService->getCurrentUser();
            
            if (tao_helpers_funcACL_funcACL::hasAccess('taoDelivery', 'ProcessBrowser', 'index')) {
                $this->startResumeDelivery($delivery);
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
    protected function startResumeDelivery(core_kernel_classes_Resource $delivery)
    {
        $processDefinition = $delivery->getUniquePropertyValue(new core_kernel_classes_Property(TAO_DELIVERY_PROCESS));
        
        $userService = taoDelivery_models_classes_UserService::singleton();
        $subject = $userService->getCurrentUser();
        
        // find existing execution
        $activityExecutionClass = new core_kernel_classes_Class(CLASS_ACTIVITY_EXECUTION);
        $currentUserActivityExecutions = $activityExecutionClass->searchInstances(array(
            PROPERTY_ACTIVITY_EXECUTION_CURRENT_USER => $subject->getUri(),
            PROPERTY_ACTIVITY_EXECUTION_STATUS => INSTANCE_PROCESSSTATUS_STARTED
        ), array(
            'like' => false
        ));
        $param = null;
        foreach ($currentUserActivityExecutions as $actExec) {
            $procExec = $actExec->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_ACTIVITY_EXECUTION_PROCESSEXECUTION));
            $procDef = $procExec->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_PROCESSINSTANCES_EXECUTIONOF));
            if ($procDef->getUri() == $processDefinition->getUri()) {
                $param = array(
                    'processUri' => $procExec->getUri(),
                    'activityUri' => $actExec->getUri()
                );
                break;
            }
        }
        // non found, spawn new
        if (is_null($param)) {
            $newProcessExecution = taoDelivery_models_classes_DeliveryService::singleton()->initDeliveryExecution($processDefinition, $subject);
        	$param = array('processUri' => $newProcessExecution->getUri());
        }
        $param['allowControl'] = false;

        //The result server from LTI context depend on call parameters rather than static result server definition
        $param['resultServerCallOverrideOptions'] = 
            array(
                "type" =>"LTI_Basic_1.1.1",
                "result_identifier" => "lis_result_sourcedid",
                "consumer_key" => "oauth_consumer_key",
                "service_url" => "lis_outcome_service_url",
                "user_identifier" => "lis_person_sourcedid" //optional
                );
        // lis_outcome_service_url This value should not change from one launch to the next and in general,
        //  the TP can expect that there is a one-to-one mapping between the lis_outcome_service_url and a particular oauth_consumer_key.  This value might change if there was a significant re-configuration of the TC system or if the TC moved from one domain to another.

        $this->redirect(tao_helpers_Uri::url('index', 'ProcessBrowser', 'taoDelivery', $param));
			
	}
	
}