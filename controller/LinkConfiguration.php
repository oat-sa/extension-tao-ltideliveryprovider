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

namespace oat\ltiDeliveryProvider\controller;

use oat\ltiDeliveryProvider\model\LTIDeliveryTool;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\LtiService;
use \tao_actions_CommonModule;
use \core_kernel_classes_Resource;
use \core_kernel_classes_Property;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

/**
 * Allows instructors to configure the LTI remote_link
 * 
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 * @package ltiDeliveryProvider
 
 */
class LinkConfiguration extends tao_actions_CommonModule {

    /**
     * Displays the form to select a delivery
     *
     * @throws \common_Exception
     * @throws \common_exception_Error
     * @throws \oat\taoLti\models\classes\LtiException
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
	protected function selectDelivery() {
		
		$ltiSession = LtiService::singleton()->getLtiSession();
		if ($ltiSession->getLaunchData()->hasVariable(LtiLaunchData::RESOURCE_LINK_TITLE)) {
		    $this->setData('linkTitle', $ltiSession->getLaunchData()->getVariable(LtiLaunchData::RESOURCE_LINK_TITLE));
		}
		$this->setData('link', $ltiSession->getLtiLinkResource()->getUri());
		$this->setData('submitUrl', _url('setDelivery'));
		
		$deliveries = DeliveryAssemblyService::singleton()->getAllAssemblies();
		if (count($deliveries) > 0) {
            $this->setData('deliveries', $deliveries);
    		$this->setView('instructor/selectDelivery.tpl');
		} else {
		    $this->returnError(__('No deliveries available'));
		}
	}

	/**
	 * called onSelect of a delivery
	 */
	public function setDelivery() {
		$link = new core_kernel_classes_Resource($this->getRequestParameter('link'));
		$compiled = new core_kernel_classes_Resource($this->getRequestParameter('uri'));
		$link->editPropertyValues(new core_kernel_classes_Property(LTIDeliveryTool::PROPERTY_LINK_DELIVERY), $compiled);
		$this->redirect(_url('showDelivery', null, null, array('uri' => $compiled->getUri())));
	}
	
	/**
	 * Either displays the currently associated delivery
	 * or calls selectDelivery in order to allow the user to select a delivery
	 * 
	 * Only accessible to LTI instructors
	 */
	public function configureDelivery() {
		$compiledDelivery = LTIDeliveryTool::singleton()->getDeliveryFromLink();
		if (is_null($compiledDelivery)) {
			$this->selectDelivery();
		} else {
		    $this->redirect(_url('showDelivery', null, null, array('uri' => $compiledDelivery->getUri())));
		}
	}

    /**
     * Displays the currently associated delivery
     *
     * Only accessible to LTI instructors
     * @throws \common_exception_Error
     * @throws \common_exception_NoImplementation
     * @throws \oat\taoLti\models\classes\LtiException
     * @throws \oat\taoLti\models\classes\LtiVariableMissingException
     */
	public function showDelivery() {
	    $delivery = new core_kernel_classes_Resource($this->getRequestParameter('uri'));
	    $this->setData('delivery', $delivery);
	    
	    $ltiSession = LtiService::singleton()->getLtiSession();
		if ($ltiSession->getLaunchData()->hasVariable(LtiLaunchData::RESOURCE_LINK_TITLE)) {
		    $this->setData('linkTitle', $ltiSession->getLaunchData()->getVariable(LtiLaunchData::RESOURCE_LINK_TITLE));
		}
		
		if (ServiceProxy::singleton()->implementsMonitoring()) {
		    $executions = ServiceProxy::singleton()->getExecutionsByDelivery($delivery);
		    $this->setData('executionCount', count($executions));
		}
		
	    $this->setView('instructor/viewDelivery.tpl');
	}
}