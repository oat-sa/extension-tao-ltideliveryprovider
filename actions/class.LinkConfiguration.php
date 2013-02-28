<?php
/**
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package filemanager
 * @subpackage action
 */
class ltiDeliveryProvider_actions_LinkConfiguration extends tao_actions_CommonModule {
	
	private function getDelivery() {
		$returnValue = null;
		if ($this->hasRequestParameter('delivery')) {
			$returnValue = new core_kernel_classes_Resource($this->getRequestParameter('delivery'));
		}
		return $returnValue;
	}
	
	public function selectDelivery() {
		$this->setView('selectDelivery.tpl');
	}
	
}
?>