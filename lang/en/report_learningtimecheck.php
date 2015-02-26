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
 * Strings for component 'report_learningtimecheck'.
 *
 * @package    report
 * @subpackage learningtimecheck
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Learning Time Report';
$string['learningtimecheck:view'] = 'View learning time session report';
$string['learningtimecheck:viewother'] = 'View learning time reports from other users';
$string['learningtimecheck:export'] = 'Export data from checks';

$string['page-report-learningtimecheck-x'] = 'Learning time report';
$string['page-report-learningtimecheck-index'] = 'Learning time report index';

$string['allusers'] = 'All users';
$string['activeusers'] = 'Active users';
$string['midrangeusers'] = 'Mid range users';
$string['nullusers'] = 'Not started users';
$string['fullusers'] = 'Achieved users';
$string['addbatch'] = 'Add a batch';
$string['backtoindex'] = 'Back to selection';
$string['batchs'] = 'Batchs';
$string['batchname'] = 'Batch name';
$string['changeoptions'] = 'Change options';
$string['clearall'] = 'Clear common batchs';
$string['clearmarks'] = 'Clear marks';
$string['clearowned'] = 'Clear owned batchs';
$string['cohort'] = 'Cohort';
$string['cohortreport'] = 'Cohort : {$a->name} [{$a->idnumber}]';
$string['course'] = 'Course';
$string['detail'] = 'Detail';
$string['repeat'] = 'Repetition (mn)';
$string['torun'] = 'To be run';
$string['coursereport'] = 'Course : {$a->shortname} {$a->fullname} [{$a->idnumber}]';
$string['disabled'] = 'Disabled';
$string['doneratio'] = '% Time done';
$string['endweek'] = 'Week Range End';
$string['exportpdf'] = 'Export as PDF';
$string['exportpdfdetail'] = 'Export detailed as PDF';
$string['exportxls'] = 'Export as Excel';
$string['exportxlsdetail'] = 'Export detailed as Excel';
$string['exportcsv'] = 'Export as CSV';
$string['exportcsvdetail'] = 'Export detailed as CSV';
$string['exportxml'] = 'Export as XML';
$string['exportxmldetail'] = 'Export detailed as XML';
$string['filters'] = 'User filters';
$string['fromnow'] = '{$a} from now';
$string['globalbatchs'] = 'Shared batchs';
$string['groupseparation'] = 'Group separation mode';
$string['groupseparation_desc'] = 'Chooses if reports are splitted by groups or by groupings.';
$string['idnumber'] = 'ID';
$string['invalidgroupaccess'] = 'Sorry you cannot see data from this group';
$string['item'] = 'Item';
$string['detail'] = 'Detail batch';
$string['itemnamepdf'] = 'Item';
$string['itemtimecreditpdf'] = 'Item time credit';
$string['learningtimecheck'] = 'Learning times';
$string['leftratio'] = 'Yet to do ratio';
$string['makebatch'] = 'Make batch from marks';
$string['myreportoptions'] = 'My report options';
$string['nobatchs'] = 'No batchs recorded';
$string['notifyemails'] = 'Emails to notify';
$string['noresults'] = 'No Results';
$string['nousers'] = 'No users.';
$string['output'] = 'Output';
$string['ownedbatchs'] = 'Owned batchs';
$string['params'] = 'Batch options';
$string['pdfpage'] = 'Page: {$a}';
$string['pdfreportfooter'] = 'PDF report footer image';
$string['pdfreportfooter_desc'] = 'Provide a JPG image for the bottom footer (880px large x up to 100px height)';
$string['pdfreportheader'] = 'PDF report header image';
$string['pdfreportheader_desc'] = 'Provide a JPG image for the top header part (880px large x up to 220px height)';
$string['pdfreportinnerheader'] = 'PDF report inner header image';
$string['pdfreportinnerheader_desc'] = 'Provide a JPG image for the top header part in inner pages (880px large x up to 150px height). If none given, the first page header will be used again.';
$string['pendings'] = 'Pending';
$string['pruneprocessedbatchsafter'] = 'Prune finished processes after';
$string['pruneprocessedbatchsafter_desc'] = 'When a batch is finished, it will remain in register for some time for the administrator to notice the completion';
$string['newbatch'] = '<New batch job>';
$string['recipient'] = 'Recipient';
$string['recipient_desc'] = 'Default recipient of the PDF documents. May be locally overloaded by each operator.';
$string['repeatdelay'] = 'Repeat delay';
$string['reportdate'] = 'Report date';
$string['reportdoctitle'] = 'Learning Time Export';
$string['reportforcohort'] = 'Report for cohort';
$string['reportforcourse'] = 'Report for course';
$string['reportforuser'] = 'Report for user';
$string['results'] = 'Results';
$string['schedule'] = 'Schedule';
$string['scheduleabatch'] = 'Schedule a batch';
$string['searchbytext'] = 'Search by text';
$string['searchcourses'] = 'Search a course';
$string['searchincategories'] = 'Search by categories';
$string['senddetailtobatch'] = 'Send details to batchs';
$string['sendtobatch'] = 'Send to batchs';
$string['selfmarked'] = 'Self';
$string['sharebatch'] = 'Share this batch';
$string['startweek'] = 'Week Range Start';
$string['runtime'] = 'Time to run';
$string['top'] = 'Top level';
$string['type'] = 'Report type';
$string['updatetype'] = '(Not shown)';
$string['user'] = 'User';
$string['usercourseprogress'] = 'User progress in course';
$string['usertimeearned'] = 'User time earned ';
$string['userreport'] = 'User : {$a->lastname} {$a->firstname} [{$a->idnumber}]';
$string['vacationdays'] = 'Vacation days';
$string['vacationdays_help'] = 'Give a comma seprated list of days (yearday index) that are not workable anyway';
$string['weeksfilter'] = 'Week Range Filter';
$string['weeksfilter'] = 'Week Range Filter';
$string['workendtime'] = 'Work end time';
$string['workstarttime'] = 'Work start time';
$string['worktimefilter'] = 'Worktime Filter';
$string['validatedbypdf'] = 'Validated by';
$string['pdfoptions'] = 'PDF Options';
$string['pdfhideidnumbers'] = 'Hide id numbers';
$string['pdfhidegroups'] = 'Hide groups';

$string['idnumberpdf'] = 'ID';
$string['shortnamepdf'] = 'CID';
$string['progressbarpdf'] = '% done';
$string['itemstodopdf'] = 'Required';
$string['doneitemspdf'] = 'Done';
$string['timedonepdf'] = 'Done';
$string['ratioleftpdf'] = '% left';
$string['doneratiopdf'] = '% done';
$string['timeleftpdf'] = 'Left';
$string['itemspdf'] = 'Items';
$string['timepdf'] = 'Time';

$string['errornoexporterclass'] = 'Missing exporter class {$a} ';
