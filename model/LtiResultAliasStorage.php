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

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDelete;
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteRequest;
use oat\taoResultServer\models\classes\ResultAliasServiceInterface;

/**
 * Class LtiResultAliasStorage
 * @package oat\ltiDeliveryProvider\model
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiResultAliasStorage extends ConfigurableService implements DeliveryExecutionDelete
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
     * @param string $deliveryExecutionId
     * @param string $resultId
     * @return boolean
     */
    public function storeResultAlias($deliveryExecutionId, $resultId)
    {
        $data = [
            self::DELIVERY_EXECUTION_ID => $deliveryExecutionId,
            self::RESULT_ID => $resultId,
        ];

        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->delete($this->getTableName());
        $queryBuilder->where(self::RESULT_ID . '=? OR ' . self::DELIVERY_EXECUTION_ID . '= ?');
        $queryBuilder->setParameters([$resultId, $deliveryExecutionId]);
        $res = $this->persistence->query($queryBuilder->getSQL(), $queryBuilder->getParameters())->execute();

        $result = $this->getPersistence()->insert(self::TABLE_NAME, $data) === 1;
        return $result;
    }

    /**
     * @see ResultAliasServiceInterface::getResultAlias
     */
    public function getResultAlias($deliveryExecutionId)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select(self::RESULT_ID);
        $queryBuilder->where('t.'.self::DELIVERY_EXECUTION_ID . '=?');
        $queryBuilder->setParameters([$deliveryExecutionId]);
        $stmt = $this->persistence->query($queryBuilder->getSQL(), $queryBuilder->getParameters());
        $result = $stmt->fetch(\PDO::FETCH_COLUMN);
        return $result === false ? [] : [$result];
    }

    /**
     * @see ResultAliasServiceInterface::getDeliveryExecutionId
     *
     * Should return null if not found, but as there is no aggregation of alias
     * services yet, we need mimic the oat\taoResultServer\models\classes::ResultAliasService
     * behaviour here
     */
    public function getDeliveryExecutionId($aliasId)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select(self::DELIVERY_EXECUTION_ID);
        $queryBuilder->where('t.'.self::RESULT_ID . '=?');
        $queryBuilder->setParameters([$aliasId]);
        $stmt = $this->persistence->query($queryBuilder->getSQL(), $queryBuilder->getParameters());
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return isset($data[self::DELIVERY_EXECUTION_ID])
            ? $data[self::DELIVERY_EXECUTION_ID]
            : $aliasId;
    }

    /**
     * Create table in database
     * @param $persistence
     */
    public static function install($persistence)
    {
        $migration = new \oat\ltiDeliveryProvider\scripts\dbMigrations\LtiResultAliasStorage_v1();
        $migration->apply($persistence);
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
     * @inheritdoc
     */
    public function deleteDeliveryExecutionData(DeliveryExecutionDeleteRequest $request)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->delete(static::TABLE_NAME)
            ->where(static::DELIVERY_EXECUTION_ID .'=:deliveryExecutionId')
            ->setParameter('deliveryExecutionId', $request->getDeliveryExecution()->getIdentifier());

        return $this->persistence->exec($queryBuilder->getSQL(), $queryBuilder->getParameters());
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getQueryBuilder()
    {
        return $this->getPersistence()->getPlatForm()->getQueryBuilder()->from($this->getTableName(), 't');
    }
}
