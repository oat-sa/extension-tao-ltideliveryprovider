<?php
/**
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package ltiDeliveryProvider
 * @subpackage actions
 */
class ltiDeliveryProvider_actions_DeliveryTool extends ltiProvider_actions_ToolModule {
	
	/**
	 * (non-PHPdoc)
	 * @see ltiProvider_actions_ToolModule::getToolResource()
	 */
	protected function getToolResource() {
		return ltiDeliveryProvider_models_classes_LTIDeliveryTool::singleton()->getToolResource();
	}
	
	/**
	 * Returns the delivery associated with the current link
	 * either from url or from the remote_link if configured
	 * returns null if none found
	 * 
	 * @return core_kernel_classes_Resource
	 */
	private function getDelivery() {
		$returnValue = null;
		if ($this->hasRequestParameter('delivery')) {
			$returnValue = new core_kernel_classes_Resource($this->getRequestParameter('delivery'));
		} else {
			$returnValue = ltiDeliveryProvider_models_classes_LTIDeliveryTool::singleton()->getDeliveryFromLink();
		}
		return $returnValue;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ltiProvider_actions_ToolModule::run()
	 */
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
	
	/**
	 * Resumes the delivery if the current user has already an active execution of the delivery
	 * or alternatively starts a new ProcessExecution and redirects the user to the processBrowser
	 * 
	 * @param core_kernel_classes_Resource $delivery
	 */
	protected function startResumeDelivery(core_kernel_classes_Resource $delivery) {
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
			$param['allowControl'] = false;
			$this->redirect(tao_helpers_Uri::url('index', 'ProcessBrowser', 'taoDelivery', $param));
			
	}
	
}
?>