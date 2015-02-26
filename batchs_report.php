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

echo $OUTPUT->heading(get_string('batchs', 'report_learningtimecheck'));

echo '<table width="100%" cellspacing="10" id="batchtable">';
echo '<tr valign="top">';
echo '<th width="48%">';
echo get_string('pendings', 'report_learningtimecheck');
echo '</th>';
echo '<th width="4%"></th>';
echo '<th width="48%">';
echo get_string('results', 'report_learningtimecheck');
echo '</th>';
echo '</tr>';
echo '<tr valign="top">';
echo '<td width="48%">';
echo $reportrenderer->batch_list();
echo '</td>';
echo '<td></td>';
echo '<td width="48%">';
echo $reportrenderer->batch_result_area();
echo '</td>';
echo '<tr>';

// command line
$clearallstr = get_string('clearall', 'report_learningtimecheck');
$clearownedstr = get_string('clearowned', 'report_learningtimecheck');
$clearmarksstr = get_string('clearmarks', 'report_learningtimecheck');
$addbatchstr = get_string('addbatch', 'report_learningtimecheck');
$makebatchstr = get_string('makebatch', 'report_learningtimecheck');

echo '<tr>';
echo '<td colspan="2"><br/>';
echo '<form name="batch_commands" action="batch_controller.php">';
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
echo '<input type="hidden" name="view" value="'.$view.'" />';
echo '<input type="hidden" name="id" value="'.$id.'" />';
echo '<input type="submit" name="clearall" value="'.$clearallstr.'" />';
echo '<input type="submit" name="clearowned" value="'.$clearownedstr.'"/>';
echo '<input type="submit" name="clearmarks" value="'.$clearmarksstr.'" />';
echo '<input type="submit" name="addbatch" value="'.$addbatchstr.'" />';
echo '<input type="submit" name="makebatchfrommarks" value="'.$makebatchstr.'" />';
echo '</form>';
echo '</td>';
echo '</tr>';

echo '</table>';