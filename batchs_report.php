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

if (!defined('MOODLE_INTERNAL')) die('You cannot use this script this way');

$reportrenderer = $PAGE->get_renderer('report_learningtimecheck');

echo $OUTPUT->heading(get_string('batchs', 'report_learningtimecheck'), 1);

echo '<div id="report-learningtimecheck-batchtable" class="container-fluid">';

echo '<div class="row-fluid  report-learningtimecheck-batchrow">';

echo '<div class="span6 report-learningtimecheck-batchcell">';
echo $OUTPUT->heading(get_string('pendings', 'report_learningtimecheck'), 2);
echo $reportrenderer->batch_list($id);
echo '</div>';

echo '<div class="span6 report-learningtimecheck-batchcell">';
echo $OUTPUT->heading(get_string('results', 'report_learningtimecheck'), 2);
echo $reportrenderer->batch_result_area();
echo '</div>';

echo '</div>';

// Command line.

echo '<div class="row-fluid  report-learningtimecheck-batchrow">';
echo '<div class="span12 report-learningtimecheck-batchcell">';
echo $reportrenderer->batch_commands($view, $id);
echo '</div>';
echo '</div>';

echo '</div>';