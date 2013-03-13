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

error_reporting(E_ALL);
if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

class ltiDeliveryProvider_models_classes_LTIDeliveryTool extends ltiProvider_models_classes_LtiTool {
	
	const TOOL_INSTANCE = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LTIToolDelivery';
	
	public function getToolResource() {
		return new core_kernel_classes_Resource(INSTANCE_LTITOOL_DELIVERY);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ltiProvider_models_classes_LtiTool::getRemoteLinkClass()
	 */
	public function getRemoteLinkClass() {
		return new core_kernel_classes_Class(CLASS_LTI_DELIVERYTOOL_LINK);
	}
	
	public function getDeliveryFromLink() {
		$ltiSession = ltiProvider_models_classes_LtiService::singleton()->getLTISession();
		$remoteLink = $ltiSession->getLtiLinkResource();
		return $remoteLink->getOnePropertyValue(new core_kernel_classes_Property(PROPERTY_LINK_DELIVERY));
	}
}

?>