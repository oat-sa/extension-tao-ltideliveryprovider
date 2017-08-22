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

namespace oat\ltiDeliveryProvider\scripts\dbMigrations;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Schema;
/**
 * Class LtiResultAliasStorage_v1
 *
 * NOTE! Do not change this file. If you need to change schema of storage create new version of this class.
 *
 * @package oat\ltiDeliveryProvider\scripts\dbMigrations
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class LtiResultAliasStorage_v1
{
    const TABLE_NAME = 'lti_result_identifiers';
    const DELIVERY_EXECUTION_ID = 'delivery_execution_id';
    const RESULT_ID = 'result_id';

    /**
     * Create table in database
     * @param \common_persistence_SqlPersistence $persistence
     */
    public static function apply(\common_persistence_SqlPersistence $persistence)
    {
        /** @var AbstractSchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();

        /** @var Schema $schema */
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $table = $schema->createTable(self::TABLE_NAME);
            $table->addOption('engine', 'MyISAM');

            $table->addColumn(self::DELIVERY_EXECUTION_ID, "string", ["notnull" => true, 'comment' => 'Delivery Execution Identifier']);
            $table->addColumn(self::RESULT_ID,             "string", ["notnull" => true, 'comment' => 'Results Identifier']);

            $table->setPrimaryKey([self::DELIVERY_EXECUTION_ID]);
            $table->addUniqueIndex([self::RESULT_ID], 'IDX_' . self::RESULT_ID . '_UNIQUE');
        } catch (SchemaException $e) {
            \common_Logger::i('Database Schema of LtiResultIdStorage service already up to date.');
        }

        $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }
}
