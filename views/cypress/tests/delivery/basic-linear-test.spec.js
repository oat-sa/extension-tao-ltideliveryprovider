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
 * Copyright (c) 2021 Open Assessment Technologies SA ;
 */

/**
 * Note: this test requires the following:
 * - the extension `oat-sa/extension-tao-ltideliveryprovider` is installed
 * - the test `taoQtiTest/views/cypress/fixtures/testPackages/basic-linear-test.zip` is imported
 * - the test is published
 * - a LTI provider is created
 * - the LTI credentials are supplied through the cypress env file from the properties ltiKey and ltiSecret
 * - the LTI launch key is supplied through the cypress env file from the property ltiDeliveryIds.basicLinearTest
 */

import { launchLtiDelivery1p0, checkLtiReturnPage } from '../utils/lti.js'
import { basicLinearTestSpecs } from '../../../../../taoQtiTest/views/cypress/tests/delivery/shared/basic-linear-test.js';

describe('LTI launch of the basic linear test with 4 items', () => {
    const deliveryKey = 'basicLinearTest';

    describe('LTI launch', () => {
        it('successfully launches', () => {
            launchLtiDelivery1p0(deliveryKey);
        });
    });

    describe('Basic linear test with 4 items', () => {
        basicLinearTestSpecs();
    });

    describe('LTI end', () => {
        it('redirects the page', () => {
            checkLtiReturnPage();
        });
    });
});
