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

namespace oat\ltiDeliveryProvider\test\model\requestLog\rds;

use oat\tao\test\TaoPhpUnitTestRunner;
use oat\ltiDeliveryProvider\model\LtiResultAliasStorage;
use oat\oatbox\service\ServiceManager;

/**
 * Class LtiResultAliasStorageTest
 * @package oat\ltiDeliveryProvider\test\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiResultAliasStorageTest extends TaoPhpUnitTestRunner
{

    private $deId = 'http://sample/first.rdf#i1450191587554175';

    public function testLog()
    {
        $storage = $this->getService();
        $de = $this->getDeliveryExecution($this->deId . '9');

        $this->assertEquals(null, $storage->getResultId($de));
        $this->assertTrue($storage->log($de, '9'));
        $this->assertEquals('9', $storage->getResultId($de));
    }

    public function testGetDeliveryExecutions()
    {
        $storage = $this->getService();

        $result = $storage->getDeliveryExecutions('0');
        $this->assertEquals(1, count($result));
        $this->assertEquals($this->deId . '0', $result[0]->getIdentifier());

        $result = $storage->getDeliveryExecutions('9');
        $this->assertEquals([], $result);
    }

    public function testGetResultId()
    {
        $storage = $this->getService();

        $de = $this->getDeliveryExecution($this->deId . '0');
        $this->assertEquals('0', $storage->getResultId($de));

        $de = $this->getDeliveryExecution($this->deId . '3');
        $this->assertEquals('3', $storage->getResultId($de));

        $de = $this->getDeliveryExecution($this->deId . '9');
        $this->assertEquals(null, $storage->getResultId($de));
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
            $de = $this->getDeliveryExecution($this->deId .$i);
            $storage->log($de, $i);
        }
    }

    protected function getDeliveryExecution($id = null)
    {
        if ($id === null) {
            $id = $this->deliveryExecutionId;
        }
        $prophet = new \Prophecy\Prophet();
        $deliveryExecutionProphecy = $prophet->prophesize('oat\taoDelivery\models\classes\execution\DeliveryExecution');
        $deliveryExecutionProphecy->getIdentifier()->willReturn($id);
        return $deliveryExecutionProphecy->reveal();
    }
}