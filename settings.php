<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Admin settings for the quizgenerator module
 *
 * @package    mod_quizgenerator
 * @copyright  2025 Your Name
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    
    // Quiz Generator API Endpoint URL
    $settings->add(new admin_setting_configtext(
        'mod_quizgenerator/api_endpoint',
        get_string('api_endpoint', 'mod_quizgenerator'),
        get_string('api_endpoint_desc', 'mod_quizgenerator'),
        'http://103.155.224.67:5200/quiz',
        PARAM_URL
    ));

    // API Key setting
    $settings->add(new admin_setting_configtext(
        'mod_quizgenerator/api_key',
        get_string('api_key', 'mod_quizgenerator'),
        get_string('api_key_desc', 'mod_quizgenerator'),
        '',
        PARAM_TEXT
    ));

    // API Timeout setting (in seconds)
    $settings->add(new admin_setting_configtext(
        'mod_quizgenerator/api_timeout',
        get_string('api_timeout', 'mod_quizgenerator'),
        get_string('api_timeout_desc', 'mod_quizgenerator'),
        '30',
        PARAM_INT
    ));

    // SSL Verification setting
    $settings->add(new admin_setting_configcheckbox(
        'mod_quizgenerator/ssl_verify',
        get_string('ssl_verify', 'mod_quizgenerator'),
        get_string('ssl_verify_desc', 'mod_quizgenerator'),
        0
    ));

}
