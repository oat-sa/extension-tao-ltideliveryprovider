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
    'module',
    'core/polling',
    'core/request',
], function (module, polling, request) {
    'use strict';

    const _defaultConfig = {
        relaunchInterval: 30,
    };

    /**
     * @exports
     */
    return {
        start() {
            const { relaunchConfig = {} } = module.config();
            const {
                capacityCheckUrl = _defaultConfig.capacityCheckUrl,
                relaunchInterval = _defaultConfig.relaunchInterval,
                runUrl = _defaultConfig.runUrl
            } = relaunchConfig;

            polling({
                action: function () {
                    const async = this.async();

                    request({
                        url: capacityCheckUrl,
                        method: 'GET',
                        dataType: 'json',
                        noToken: true,
                    })
                        .then(({ status }) => {
                            if (status == 1) {
                                async.reject();
                                window.location = runUrl;
                            } else {
                                async.resolve();
                            }
                        })
                        .catch(() => {
                            async.resolve();
                        });
                },
                interval: relaunchInterval * 1000,
                autoStart: true,
            });
        }
    };
});
