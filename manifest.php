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
 * Copyright (c) 2013-2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

use oat\ltiDeliveryProvider\controller\DeliveryRunner;
use oat\ltiDeliveryProvider\controller\DeliveryTool;
use oat\ltiDeliveryProvider\controller\LinkConfiguration;
use oat\ltiDeliveryProvider\install\InstallAssignmentService;
use oat\ltiDeliveryProvider\install\InstallDeliveryContainerService;
use oat\ltiDeliveryProvider\install\RegisterLaunchAction;
use oat\ltiDeliveryProvider\scripts\install\RegisterLtiAttemptService;
use oat\ltiDeliveryProvider\scripts\install\RegisterLtiResultAliasStorage;
use oat\ltiDeliveryProvider\scripts\install\RegisterMetrics;
use oat\ltiDeliveryProvider\scripts\install\RegisterOverriddenLtiToolRepository;
use oat\ltiDeliveryProvider\scripts\install\RegisterServices;
use oat\ltiDeliveryProvider\scripts\install\RegisterSessionCookieAttributesFactory;
use oat\ltiDeliveryProvider\scripts\update\Updater;
use oat\tao\model\user\TaoRoles;
use oat\taoLti\models\classes\LtiRoles;

return [
    'name' => 'ltiDeliveryProvider',
    'label' => 'LTI Delivery Tool Provider',
    'description' => 'The LTI Delivery Tool Provider allows third party applications to embed deliveries created in Tao',
    'license' => 'GPL-2.0',
    'version' => '11.4.2.1',
    'author' => 'Open Assessment Technologies',
    'requires' => [
        'generis' => '>=12.15.0',
        'tao' => '>=45.7.0',
        'taoDeliveryRdf' => '>=6.0.0',
        'taoLti' => '>=11.12.0',
        'taoResultServer' => '>=7.0.0',
        'taoDelivery' => '>=11.0.0',
        'taoOutcomeUi' => '>=7.0.0',
        'taoQtiTest' => '>=37.1.0',
    ],
    'models' => [
         'http://www.tao.lu/Ontologies/TAOLTI.rdf',
        'http://www.imsglobal.org/imspurl/lis/v1/vocab/membership'
     ],
    'install' => [
        'php' => [
            InstallAssignmentService::class,
            RegisterLtiResultAliasStorage::class,
            RegisterServices::class,
            RegisterLaunchAction::class,
            InstallDeliveryContainerService::class,
            RegisterLtiAttemptService::class,
            RegisterMetrics::class,
            RegisterOverriddenLtiToolRepository::class,
            RegisterSessionCookieAttributesFactory::class
        ],
        'rdf' => [
            __DIR__ . '/install/ontology/deliverytool.rdf'
        ]
    ],
    'routes' => [
        '/ltiDeliveryProvider' => 'oat\\ltiDeliveryProvider\\controller'
    ],
    'update' => Updater::class,
    'managementRole' => 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LtiDeliveryProviderManagerRole',
    'acl' => [
        ['grant', 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LtiDeliveryProviderManagerRole', ['ext' => 'ltiDeliveryProvider']],
        ['grant', TaoRoles::ANONYMOUS, ['ext' => 'ltiDeliveryProvider', 'mod' => 'DeliveryTool', 'act' => 'launch']],
        ['grant', 'http://www.tao.lu/Ontologies/TAOLTI.rdf#LtiBaseRole', ['ext' => 'ltiDeliveryProvider', 'mod' => 'DeliveryTool', 'act' => 'run']],
        ['grant', LtiRoles::CONTEXT_LEARNER, DeliveryRunner::class],
        ['grant', LtiRoles::CONTEXT_LEARNER, DeliveryTool::class, 'launchQueue'],
        ['grant', LtiRoles::CONTEXT_INSTRUCTOR, LinkConfiguration::class]
    ],
    'constants' => [

        # views directory
        'DIR_VIEWS'           => __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,

        # default module name
        'DEFAULT_MODULE_NAME' => 'Browser',

        #default action name
        'DEFAULT_ACTION_NAME' => 'index',

        #BASE PATH: the root path in the file system (usually the document root)
        'BASE_PATH'           => __DIR__ . DIRECTORY_SEPARATOR ,

        #BASE URL (usually the domain root)
        'BASE_URL'                => ROOT_URL . 'ltiDeliveryProvider/',
    ],
    'extra' => [
        'structures' => __DIR__ . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . 'structures.xml',
    ]
];
