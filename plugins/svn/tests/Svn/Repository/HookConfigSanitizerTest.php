<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Svn\Repository;

class HookConfigSanitizerTest extends \TuleapTestCase
{
    public function itFilterImproperValuesForHookConfig()
    {
        $hook_config = array(
            'an_incorrect_key'              => 'value',
            HookConfig::MANDATORY_REFERENCE => true
        );

        $hook_config_sanitizer = new HookConfigSanitizer();
        $this->assertEqual(
            array(HookConfig::MANDATORY_REFERENCE => true),
            $hook_config_sanitizer->sanitizeHookConfigArray($hook_config)
        );
    }

    public function itReturnsACorrectHookConfiguration()
    {
        $hook_config = array(
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
            HookConfig::MANDATORY_REFERENCE       => false
        );

        $hook_config_sanitizer = new HookConfigSanitizer();
        $this->assertEqual(
            array(
                HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
                HookConfig::MANDATORY_REFERENCE       => false
            ),
            $hook_config_sanitizer->sanitizeHookConfigArray($hook_config)
        );
    }
}
