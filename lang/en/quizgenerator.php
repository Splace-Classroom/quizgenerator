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
 * Plugin strings are defined here.
 *
 * @package     mod_quizgenerator
 * @category    string
 * @copyright   2025 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Quiz Generator';
$string['modulename'] = 'Quiz Generator';
$string['modulenameplural'] = 'Quiz Generators';
$string['pluginadministration'] = 'Quiz Generator administration';
$string['questions'] = 'Questions';
$string['results'] = 'Results';
$string['eventcoursemoduleviewed'] = 'Quiz Generator activity viewed';
$string['quizgeneratorname'] = 'Quiz Generator Name';
$string['noquizgeneratorinstances'] = 'No quiz generator instances';

// Capabilities
$string['quizgenerator:addinstance'] = 'Add a new Quiz Generator activity';
$string['quizgenerator:view'] = 'View Quiz Generator activity';
$string['quizgenerator:generate'] = 'Generate questions in Quiz Generator';
$string['quizgenerator:manage'] = 'Manage Quiz Generator settings';

// Additional strings
$string['generatequestions'] = 'Generate Questions';
$string['generatedquestions'] = 'Generated Questions';
$string['selectmodule'] = 'Select Module';
$string['choosemodule'] = 'Choose a module...';
$string['nomodulesfound'] = 'No modules found in this course';
$string['moduleselectionhelp'] = 'Select a module from this course to generate questions based on its content';

// Settings strings
$string['api_endpoint'] = 'API Endpoint URL';
$string['api_endpoint_desc'] = 'The URL of the Quiz Generator API endpoint. Default: http://103.155.224.67:5200/quiz';
$string['api_key'] = 'API Key';
$string['api_key_desc'] = 'The X-API-Key to be sent in the header when making API requests. Leave blank if not required.';
$string['api_timeout'] = 'API Timeout';
$string['api_timeout_desc'] = 'The timeout in seconds for API requests. Default: 30 seconds';
$string['ssl_verify'] = 'Verify SSL Certificate';
$string['ssl_verify_desc'] = 'Enable SSL certificate verification for API requests. Leave unchecked for development environments';
