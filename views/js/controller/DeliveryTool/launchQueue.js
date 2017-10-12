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
 */

define([
    'lodash',
    'jquery'
], function (_, $) {
    'use strict';

    var _defaultParams = {
        "relaunchInterval" : 30,
        "relaunchIntervalDeviation" : 5
    };

    /**
     * @exports
     */
    return {
        start: function() {
            var loaderScript = $('#amd-loader');
            var params = loaderScript.data('params');
            var interval;
            var deviation;

            params = _.defaults(params, _defaultParams);

            interval = params.relaunchInterval;
            deviation = parseInt(params.relaunchIntervalDeviation) - (Math.random() * parseInt(params.relaunchIntervalDeviation) * 2);
            interval = (interval + deviation) * 1000;

            if (params.runUrl) {
                setTimeout(function () {
                    window.location = params.runUrl;
                }, interval);
            }
        }
    };
});
