<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details.
 *
 * @category    report
 * @package     report_learningtimecheck
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright   2009 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

<<<<<<< HEAD
$plugin->version = 2021102100; // The current plugin version (Date: YYYYMMDDXX).
<<<<<<< HEAD
$plugin->requires = 2020061500; // Requires this Moodle version.
$plugin->component = 'report_learningtimecheck'; // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_RC;
$plugin->release = '3.9.0 (build 2021102100)';
=======
$plugin->requires = 2022112801; // Requires this Moodle version.
$plugin->component = 'report_learningtimecheck'; // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_RC;
$plugin->release = '4.1.0 (build 2021102100)';
>>>>>>> MOODLE_401_STABLE
=======
$plugin->version = 2023101200; // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires = 2022112801; // Requires this Moodle version.
$plugin->component = 'report_learningtimecheck'; // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_RC;
$plugin->release = '4.1.0 (build 2023101200)';
>>>>>>> MOODLE_401_STABLE
$plugin->dependencies = array('local_vflibs' => '2015122000');
$plugin->supported = [401, 402];

// Non moodle attributes.
<<<<<<< HEAD
<<<<<<< HEAD
$plugin->codeincrement = '3.9.0007';
=======
$plugin->codeincrement = '4.1.0007';
>>>>>>> MOODLE_401_STABLE
=======
$plugin->codeincrement = '4.1.0008';
>>>>>>> MOODLE_401_STABLE
$plugin->privacy = 'dualrelease';