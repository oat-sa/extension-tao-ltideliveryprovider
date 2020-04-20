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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */
declare(strict_types=1);

namespace oat\ltiDeliveryProvider\test\unit\model\options\DataAccess\Repository;

use common_session_Session as Session;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\ltiDeliveryProvider\model\options\DataAccess\Mapper\OptionCollectionMapper;
use oat\ltiDeliveryProvider\model\options\DataAccess\Repository\OverriddenLtiToolsRepository;
use oat\oatbox\session\SessionService;
use oat\taoLti\models\classes\LtiLaunchData;
use oat\taoLti\models\classes\TaoLtiSession;
use oat\taoQtiTest\models\runner\config\Business\Domain\Option;
use oat\taoQtiTest\models\runner\config\Business\Domain\OptionCollection;
use oat\taoQtiTest\models\TestCategoryPreset;
use oat\taoQtiTest\models\TestCategoryPresetProvider;

class OverriddenLtiToolsRepositoryTest extends TestCase
{
    /** @var TestCategoryPresetProvider|MockObject */
    private $presetRepository;

    /** @var SessionService|MockObject */
    private $sessionService;

    /** @var OverriddenLtiToolsRepository */
    private $sut;

    /**
     * @before
     */
    public function init(): void
    {
        $this->presetRepository = $this->createMock(TestCategoryPresetProvider::class);
        $this->sessionService   = $this->createMock(SessionService::class);

        $this->sut = new OverriddenLtiToolsRepository(
            $this->presetRepository,
            $this->sessionService,
            new OptionCollectionMapper()
        );
    }

    /**
     * @param OptionCollection   $expected
     * @param Session            $sessionData
     * @param TestCategoryPreset ...$availableToolPresets
     *
     * @dataProvider dataProvider
     */
    public function testFindAll(
        OptionCollection $expected,
        Session $sessionData,
        TestCategoryPreset ...$availableToolPresets
    ): void {
        $this->presetRepository
            ->method('findPresetGroupOrFail')
            ->willReturn(['presets' => $availableToolPresets]);

        $this->sessionService
            ->expects(static::once())
            ->method('getCurrentSession')
            ->willReturn($sessionData);

        $this->assertEquals($expected, $this->sut->findAll());
    }

    public function dataProvider(): array
    {
        return [
            'Matching options'         => [
                new OptionCollection(
                    new Option('opt_1', true),
                    new Option('opt_2', false)
                ),
                $this->createLtiSession(
                    [
                        'opt_1' => true,
                        'opt_2' => false,
                    ]
                ),
                $this->createPreset('opt_1'),
                $this->createPreset('opt_2'),
                $this->createPreset('opt_3'),
            ],
            'Extra options'            => [
                new OptionCollection(
                    new Option('opt_1', true),
                    new Option('opt_2', false)
                ),
                $this->createLtiSession(
                    [
                        'opt_1' => true,
                        'opt_2' => false,
                        'opt_4' => false,
                    ]
                ),
                $this->createPreset('opt_1'),
                $this->createPreset('opt_2'),
                $this->createPreset('opt_3'),
            ],
            'Empty unfiltered options' => [
                new OptionCollection(),
                $this->createLtiSession([]),
                $this->createPreset('opt_1'),
                $this->createPreset('opt_2'),
                $this->createPreset('opt_3'),
            ],
            'Non-LTI session' => [
                new OptionCollection(),
                $this->createMock(Session::class),
                $this->createPreset('opt_1'),
                $this->createPreset('opt_2'),
                $this->createPreset('opt_3'),
            ],
        ];
    }

    private function createPreset(string $id): TestCategoryPreset
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new TestCategoryPreset($id, 'test', 'test', []);
    }

    private function createLtiSession(array $toolConfiguration): TaoLtiSession
    {
        $session    = $this->createMock(TaoLtiSession::class);
        $launchData = $this->createMock(LtiLaunchData::class);

        $session
            ->expects(static::once())
            ->method('getLaunchData')
            ->willReturn($launchData);

        $hasData = !empty($toolConfiguration);

        $launchData
            ->expects(static::once())
            ->method('hasVariable')
            ->with('custom_x_tao_tools')
            ->willReturn($hasData);

        $launchData
            ->expects($hasData ? static::once() : static::never())
            ->method('getVariable')
            ->with('custom_x_tao_tools')
            ->willReturn(json_encode($toolConfiguration));

        return $session;
    }
}
