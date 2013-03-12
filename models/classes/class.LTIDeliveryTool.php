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