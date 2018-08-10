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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\ltiDeliveryProvider\test\unit\model\requestLog\rds;

use oat\tao\test\TaoPhpUnitTestRunner;
use oat\ltiDeliveryProvider\model\LtiResultAliasStorage;
use oat\oatbox\service\ServiceManager;
use oat\taoDelivery\model\execution\DeliveryExecution;

/**
 * Class LtiResultAliasStorageTest
 * @package oat\ltiDeliveryProvider\test\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiResultAliasStorageTest extends TaoPhpUnitTestRunner
{

    private $deId = 'http://sample/first.rdf#i1450191587554175';

    public function testStoreResultAlias()
    {
        $storage = $this->getService();
        $deId = $this->deId . '9';

        $this->assertEquals([], $storage->getResultAlias($deId));
        $this->assertTrue($storage->storeResultAlias($deId, '9'));
        $this->assertEquals(['9'], $storage->getResultAlias($deId));


        //Try to log another delivery execution with the same result id.
        //Delivery execution identifier should be overwritten
        $deId = $this->deId . '10';
        $this->assertTrue($storage->storeResultAlias($deId, '9'));
        $this->assertEquals($this->deId . '10', $storage->getDeliveryExecutionId('9'));


        //Try to log the same delivery execution with another .
        //Delivery execution identifier should be overwritten
        $deId = $this->deId . '10';
        $this->assertTrue($storage->storeResultAlias($deId, '10'));
        $this->assertEquals($this->deId . '10', $storage->getDeliveryExecutionId('10'));
    }

    public function testGetDeliveryExecution()
    {
        $storage = $this->getService();

        $result = $storage->getDeliveryExecutionId('0');
        $this->assertEquals($this->deId . '0', $result);

        $result = $storage->getDeliveryExecutionId('9');
        $this->assertEquals('9', $result);
    }

    public function testGetResultId()
    {
        $storage = $this->getService();

        $deId = $this->deId . '0';
        $this->assertEquals(['0'], $storage->getResultAlias($deId));

        $deId = $this->deId . '3';
        $this->assertEquals(['3'], $storage->getResultAlias($deId));

        $deId = $this->deId . '9';
        $this->assertEquals([], $storage->getResultAlias($deId));
    }

    /**
     * @return LtiResultIdStorage
     */
    protected function getService()
    {
        $persistenceManager = $this->getSqlMock('test_LtiResultIdStorageTest');
        (new \oat\ltiDeliveryProvider\scripts\install\RegisterLtiResultAliasStorage)->createTable($persistenceManager->getPersistenceById('test_LtiResultIdStorageTest'));
        $storage = new LtiResultAliasStorage([
            LtiResultAliasStorage::OPTION_PERSISTENCE => 'test_LtiResultIdStorageTest'
        ]);
        $config = new \common_persistence_KeyValuePersistence([], new \common_persistence_InMemoryKvDriver());
        $config->set(\common_persistence_Manager::SERVICE_ID, $persistenceManager);
        $serviceManager = new ServiceManager($config);
        $storage->setServiceManager($serviceManager);
        $this->loadFixtures($storage);
        return $storage;
    }

    protected function loadFixtures(LtiResultAliasStorage $storage)
    {
        for ($i = 0; $i < 5; $i++) {
            $deId = $this->deId .$i;
            $storage->storeResultAlias($deId, $i);
        }
    }

    protected function getDeliveryExecution($id = null)
    {
        if ($id === null) {
            // @todo fix, no such property
            $id = $this->deliveryExecutionId;
        }
        $prophet = new \Prophecy\Prophet();
        $deliveryExecutionProphecy = $prophet->prophesize(DeliveryExecution::class);
        $deliveryExecutionProphecy->getIdentifier()->willReturn($id);
        return $deliveryExecutionProphecy->reveal();
    }
}