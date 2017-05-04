<?php
/**
 *   
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
 */
use oat\taoLti\models\classes\LtiRoles;
use oat\tao\model\user\TaoRoles;
use oat\ltiDeliveryProvider\controller\DeliveryRunner;
use oat\ltiDeliveryProvider\controller\LinkConfiguration;
use oat\taoDeliveryRdf\controller\DeliveryMgmt;

return array(
    'name' => 'ltiDeliveryProvider',
    'label' => 'LTI Delivery Tool Provider',
    'description' => 'The LTI Delivery Tool Provider allows third party applications to embed deliveries created in Tao',
    'license' => 'GPL-2.0',
    'version' => '3.0.1',
    'author' => 'Open Assessment Technologies',
    'requires' => array(
        'tao' => '>=9.0.0',
        'taoDeliveryRdf' => '>=1.0',
        'taoLti' => '>=1.13.0',
        'taoLtiBasicOutcome' => '>=2.6',
        'taoDelivery' => '>=5.0'
    ),
    'models' => array(
         'http://www.tao.lu/Ontologies/TAOLTI.rdf',
        'http://www.imsglobal.org/imspurl/lis/v1/vocab/membership'
     ),
    'install' => array(
        'php' => array(
            \oat\ltiDeliveryProvider\install\InstallAssignmentService::class,
        ),
        'rdf' => array(
            dirname(__FILE__). '/install/ontology/deliverytool.rdf'
        )
    ),
    'routes' => array(
        '/ltiDeliveryProvider' => 'oat\\ltiDeliveryProvider\\controller'
    ),
    'update' => 'oat\\ltiDeliveryProvider\\scripts\\update\\Updater',
    'managementRole' => 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LtiDeliveryProviderManagerRole',
    'acl' => array(
        array('grant', 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LtiDeliveryProviderManagerRole', array('ext'=>'ltiDeliveryProvider')),
        array('grant', TaoRoles::ANONYMOUS, array('ext'=>'ltiDeliveryProvider', 'mod' => 'DeliveryTool', 'act' => 'launch')),
        array('grant', 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LtiBaseRole', array('ext'=>'ltiDeliveryProvider', 'mod' => 'DeliveryTool', 'act' => 'run')),
        array('grant', LtiRoles::CONTEXT_LEARNER, DeliveryRunner::class),
        array('grant', LtiRoles::CONTEXT_INSTRUCTOR, LinkConfiguration::class)
    ),
    'constants' => array(
    
        # views directory
        "DIR_VIEWS"                => __DIR__.DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR,
    
        # default module name
        'DEFAULT_MODULE_NAME'    => 'Browser',
    
        #default action name
        'DEFAULT_ACTION_NAME'    => 'index',
    
        #BASE PATH: the root path in the file system (usually the document root)
        'BASE_PATH'                => __DIR__.DIRECTORY_SEPARATOR ,
    
        #BASE URL (usually the domain root)
        'BASE_URL'                => ROOT_URL . 'ltiDeliveryProvider/',
    ),
    'extra' => array(
        'structures' => __DIR__.DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'structures.xml',
    )
);
