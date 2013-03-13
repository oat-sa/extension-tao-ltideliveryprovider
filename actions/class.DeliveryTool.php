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
 * Copyright (c) 2013 (original work) Open Assessment Techonologies SA (under the project TAO-PRODUCT);
 *               
 * 
 */
?>
<?php
/**
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package filemanager
 * @subpackage action
 */
class ltiDeliveryProvider_actions_DeliveryTool extends ltiProvider_actions_ToolModule {
	
	protected function getToolResource() {
		return ltiDeliveryProvider_models_classes_LTIDeliveryTool::singleton()->getToolResource();
	}
	
	private function getDelivery() {
		$returnValue = null;
		if ($this->hasRequestParameter('delivery')) {
			$returnValue = new core_kernel_classes_Resource($this->getRequestParameter('delivery'));
		} else {
			$returnValue = ltiDeliveryProvider_models_classes_LTIDeliveryTool::singleton()->getDeliveryFromLink();
		}
		return $returnValue;
	}
	
	protected function run() {
		
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
			
			$newProcessExecution = taoDelivery_models_classes_DeliveryService::singleton()->initDeliveryExecution($processDefinition, $subject);
	
			if (tao_helpers_funcACL_funcACL::hasAccess('taoDelivery', 'ProcessBrowser', 'index')) {
				$this->startResumeDelivery($delivery);
			} elseif (tao_helpers_funcACL_funcACL::hasAccess('ltiDeliveryProvider', 'LinkConfiguration', 'configureDelivery')) {
				$this->redirect(tao_helpers_Uri::url('configureDelivery', 'LinkConfiguration', null));
			} else {
				$this->returnError(__('Access to this functionality is restricted to students'), false);
			}
		}
	}
	
	protected function startResumeDelivery($delivery) {
			$processDefinition = $delivery->getUniquePropertyValue(new core_kernel_classes_Property(TAO_DELIVERY_PROCESS));

			$userService = taoDelivery_models_classes_UserService::singleton();
			$subject = $userService->getCurrentUser();
			
			// find existing execution
			$activityExecutionClass = new core_kernel_classes_Class(CLASS_ACTIVITY_EXECUTION);
			$currentUserActivityExecutions = $activityExecutionClass->searchInstances(array(
				PROPERTY_ACTIVITY_EXECUTION_CURRENT_USER => $subject->getUri(),
			), array('like'=>false));
			$param = null;
			foreach ($currentUserActivityExecutions as $actExec) {
				$procExec = $actExec->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_ACTIVITY_EXECUTION_PROCESSEXECUTION));
				$procDef = $procExec->getUniquePropertyValue(new core_kernel_classes_Property(PROPERTY_PROCESSINSTANCES_EXECUTIONOF));
				if ($procDef->getUri() == $processDefinition->getUri()) {
					$param = array('processUri' => $procExec->getUri(), 'activityUri' => $actExec->getUri());
					break;
				} 
			}
			// non found, spawn new
			if (is_null($param)) {
				$newProcessExecution = taoDelivery_models_classes_DeliveryService::singleton()->initDeliveryExecution($processDefinition, $subject);
				$param = array('processUri' => $newProcessExecution->getUri());
			}
			$this->redirect(tao_helpers_Uri::url('index', 'ProcessBrowser', 'taoDelivery', $param));
			
	}
	
}
?>