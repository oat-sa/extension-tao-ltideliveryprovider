<?php

error_reporting(E_ALL);
if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

class ltiDeliveryProvider_models_classes_LTIDeliveryTool extends ltiProvider_models_classes_LTITool {
	
	const TOOL_INSTANCE = 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LTIToolDelivery';
	
	public function getToolResource() {
		return new core_kernel_classes_Resource(self::TOOL_INSTANCE);
	}
	
}

?>