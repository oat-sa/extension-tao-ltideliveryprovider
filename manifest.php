<?php
/**
 * @author CRP Henri Tudor - TAO Team - {@link http://www.tao.lu}
 * @license GPLv2  http://www.opensource.org/licenses/gpl-2.0.php
 */
$extpath = dirname(__FILE__).DIRECTORY_SEPARATOR;
$taopath = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'tao'.DIRECTORY_SEPARATOR;

return array(
	'name' => 'ltiDeliveryProvider',
	'description' => 'LTI Delivery Tool Provider',
	'version' => '0.8',
	'author' => 'Open Assessment Technologies',
	'dependencies' => array('taoDelivery', 'ltiProvider'),
	'classLoaderPackages' => array(
		dirname(__FILE__).'/actions/'
	 ),
	'models' => array(
	 	'http://www.tao.lu/Ontologies/TAOLTI.rdf',
		'http://www.imsglobal.org/imspurl/lis/v1/vocab/membership'
	 ),
	'install' => array(
		'checks' => array(
			array('type' => 'CheckFileSystemComponent', 'value' => array('id' => 'fs_ltiDeliveryProvider_includes', 'location' => 'ltiDeliveryProvider/includes', 'rights' => 'r'))
		),
		'rdf' => array(
			dirname(__FILE__). '/models/ontology/deliverytool.rdf',
			dirname(__FILE__). '/models/ontology/ims_membership.rdf'
		)
	),
	'constants' => array(
		# actions directory
		"DIR_ACTIONS"			=> $extpath."actions".DIRECTORY_SEPARATOR,
	
		# models directory
		"DIR_MODELS"			=> $extpath."models".DIRECTORY_SEPARATOR,
	
		# views directory
		"DIR_VIEWS"				=> $extpath."views".DIRECTORY_SEPARATOR,
	
		# helpers directory
		"DIR_HELPERS"			=> $extpath."helpers".DIRECTORY_SEPARATOR,
	
		# default module name
		'DEFAULT_MODULE_NAME'	=> 'Browser',
	
		#default action name
		'DEFAULT_ACTION_NAME'	=> 'index',
	
		#BASE PATH: the root path in the file system (usually the document root)
		'BASE_PATH'				=> $extpath ,
	
		#BASE URL (usually the domain root)
		'BASE_URL'				=> ROOT_URL . 'ltiDeliveryProvider/',
	
		#BASE WWW the web resources path
		'BASE_WWW'				=> ROOT_URL . 'ltiDeliveryProvider/views/',
	 
	
		#TAO extension Paths
		'TAOBASE_WWW'			=> ROOT_URL  . 'tao/views/',
		'TAOVIEW_PATH'			=> $taopath.'views'.DIRECTORY_SEPARATOR,
		'TAO_TPL_PATH'			=> $taopath.'views'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR,

	)
);
?>