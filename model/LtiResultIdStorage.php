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
 * Copyright (c) 2017  (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\ltiDeliveryProvider\model;

use oat\taoDelivery\model\execution\DeliveryExecution;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\service\ConfigurableService;

/**
 * Class LtiResultIdStorage
 * @package oat\ltiDeliveryProvider\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiResultIdStorage extends ConfigurableService
{
    const OPTION_PERSISTENCE = 'persistence';

    const SERVICE_ID = 'ltiDeliveryProvider/LtiResultIdStorage';

    const TABLE_NAME = 'lti_result_identifiers';
    const DELIVERY_EXECUTION_ID = 'delivery_execution_id';
    const RESULT_ID = 'result_id';

    /** @var \common_persistence_SqlPersistence */
    protected $persistence;

    /**
     * @return string
     */
    public function getTableName()
    {
        return self::TABLE_NAME;
    }

    /**
     * Add record to the storage
     * @param DeliveryExecution $deliveryExecution
     * @param string $resultId
     * @return boolean
     */
    public function log(DeliveryExecution $deliveryExecution, $resultId)
    {
        $data = [
            self::DELIVERY_EXECUTION_ID => $deliveryExecution->getIdentifier(),
            self::RESULT_ID => $resultId,
        ];
        return $this->getPersistence()->insert(self::TABLE_NAME, $data) === 1;
    }

    /**
     * Get result identifier linked to given delivery execution
     * Null if no result ids found
     * @param DeliveryExecution $deliveryExecution
     * @return string|null
     */
    public function getResultId(DeliveryExecution $deliveryExecution)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select(self::RESULT_ID);
        $queryBuilder->where('t.'.self::DELIVERY_EXECUTION_ID . '=?');
        $queryBuilder->setParameters([$deliveryExecution->getIdentifier()]);
        $stmt = $this->persistence->query($queryBuilder->getSQL(), $queryBuilder->getParameters());
        $result = $stmt->fetch(\PDO::FETCH_COLUMN);
        if ($result === false) {
            $result = null;
        }
        return $result;
    }

    /**
     * Get delivery execution instance by result id
     * @param $resultId
     * @return DeliveryExecution[]
     */
    public function getDeliveryExecutionIds($resultId)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select(self::DELIVERY_EXECUTION_ID);
        $queryBuilder->where('t.'.self::RESULT_ID . '=?');
        $queryBuilder->setParameters([$resultId]);
        $stmt = $this->persistence->query($queryBuilder->getSQL(), $queryBuilder->getParameters());
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $result = [];
        if ($data) {
            foreach ($data as $row) {
                $result[] = \taoDelivery_models_classes_execution_ServiceProxy::singleton()->getDeliveryExecution($row);
            }
        }

        return $result;
    }

    /**
     * @return array
     */
//    public static function tableColumns()
//    {
//        return [
//            self::DELIVERY_EXECUTION_ID,
//            self::RESULT_ID,
//        ];
//    }

    /**
     * Create table in database
     * @param $persistence
     */
    public static function install($persistence)
    {
        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();

        /** @var Schema $schema */
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $table = $schema->createTable(self::TABLE_NAME);
            $table->addOption('engine', 'MyISAM');

            $table->addColumn(self::DELIVERY_EXECUTION_ID, "text",  ["notnull" => true, 'comment' => 'Delivery Execution Identifier']);
            $table->addColumn(self::RESULT_ID,             "text",  ["notnull" => true, 'comment' => 'Results Identifier']);

            $table->setPrimaryKey([self::DELIVERY_EXECUTION_ID]);
        } catch (SchemaException $e) {
            \common_Logger::i('Database Schema of LtiResultIdStorage service already up to date.');
        }

        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    public function getPersistence()
    {
        $persistenceId = $this->getOption(self::OPTION_PERSISTENCE);
        if (is_null($this->persistence)) {
            $this->persistence = $this->getServiceManager()
                ->get(\common_persistence_Manager::SERVICE_ID)
                ->getPersistenceById($persistenceId);
        }

        return $this->persistence;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getQueryBuilder()
    {
        return $this->getPersistence()->getPlatForm()->getQueryBuilder()->from($this->getTableName(), 't');
    }
}
