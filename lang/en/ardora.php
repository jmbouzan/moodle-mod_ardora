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
 * Strings for component 'ardora', language 'en'.
 *
 * @package    mod_ardora
 * @copyright  2024 José Manuel Bouzán Matanza
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['clicktodownload'] = 'Click {$a} link to download the file.';
$string['clicktoopen2'] = 'Click {$a} link to view the file.';
$string['configdisplayoptions'] = 'Select all options that should be available, existing settings are not modified. Hold CTRL key to select multiple fields.';
$string['configframesize'] = 'When a web page or an uploaded file is displayed within a frame, this value is the height (in pixels) of the top frame (which contains the navigation).';
$string['configparametersettings'] = 'This sets the default value for the Parameter settings pane in the form when adding some new ardoras. After the first time, this becomes an individual user preference.';
$string['configpopup'] = 'When adding a new ardora which is able to be shown in a popup window, should this option be enabled by default?';
$string['configpopupdirectories'] = 'Should popup windows show directory links by default?';
$string['configpopupheight'] = 'What height should be the default height for new popup windows?';
$string['configpopuplocation'] = 'Should popup windows show the location bar by default?';
$string['configpopupmenubar'] = 'Should popup windows show the menu bar by default?';
$string['configpopupresizable'] = 'Should popup windows be resizable by default?';
$string['configpopupscrollbars'] = 'Should popup windows be scrollable by default?';
$string['configpopupstatus'] = 'Should popup windows show the status bar by default?';
$string['configpopuptoolbar'] = 'Should popup windows show the tool bar by default?';
$string['configpopupwidth'] = 'What width should be the default width for new popup windows?';
$string['contentheader'] = 'Content';
$string['displayoptions'] = 'Available display options';
$string['displayselect'] = 'Display';
$string['displayselect_help'] = 'This setting, together with the file type and whether the browser allows embedding, determines how the file is displayed. Options may include:

* Automatic - The best display option for the file type is selected automatically
* Embed - The file is displayed within the page below the navigation bar together with the file description and any blocks
* Force download - The user is prompted to download the file
* Open - Only the file is displayed in the browser window
* In pop-up - The file is displayed in a new browser window without menus or an address bar
* In frame - The file is displayed within a frame below the navigation bar and file description
* New window - The file is displayed in a new browser window with menus and an address bar';
$string['displayselect_link'] = 'mod/file/mod';
$string['displayselectexplain'] = 'Choose display type, unfortunately not all types are suitable for all files.';
$string['dnduploadardora'] = 'Create file ardora';
$string['encryptedcode'] = 'Encrypted code';
$string['filenotfound'] = 'File not found, sorry.';
$string['filterfiles'] = 'Use filters on file content';
$string['filterfilesexplain'] = 'Select type of file content filtering, please note this may cause problems for some Flash and Java applets. Please make sure that all text files are in UTF-8 encoding.';
$string['filtername'] = 'ardora names auto-linking';
$string['forcedownload'] = 'Force download';
$string['framesize'] = 'Frame height';
$string['indicator:cognitivedepth'] = 'File cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in a File ardora.';
$string['indicator:cognitivedepthdef'] = 'File cognitive';
$string['indicator:cognitivedepthdef_help'] = 'The participant has reached this percentage of the cognitive engagement offered by the File ardoras during this analysis interval (Levels = No view, View)';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'File social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in a File ardora.';
$string['indicator:socialbreadthdef'] = 'File social';
$string['indicator:socialbreadthdef_help'] = 'The participant has reached this percentage of the social engagement offered by the File ardoras during this analysis interval (Levels = No participation, Participant alone)';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';
$string['legacyfiles'] = 'Migration of old course file';
$string['legacyfilesactive'] = 'Active';
$string['legacyfilesdone'] = 'Finished';
$string['modifieddate'] = 'Modified {$a}';
$string['modulename'] = 'Ardora';
$string['modulename_help'] = 'Axuda explicativa aquí';
$string['modulename_link'] = 'mod/ardora/view';
$string['modulenameplural'] = 'Files';
$string['notmigrated'] = 'This legacy ardora type ({$a}) was not yet migrated, sorry.';
$string['optionsheader'] = 'Display options';
$string['page-mod-ardora-x'] = 'Any file module page';
$string['pluginadministration'] = 'File module administration';
$string['pluginname'] = 'Ardora';
$string['popupheight'] = 'Pop-up height (in pixels)';
$string['popupheightexplain'] = 'Specifies default height of popup windows.';
$string['popupwidth_desc'] = 'Default width of the popup window (in pixels).';
$string['popupheight_desc'] = 'Default height of the popup window (in pixels).';
$string['popupardora'] = 'This ardora should appear in a popup window.';
$string['popupardoralink'] = 'If it didn\'t, click here: {$a}';
$string['popupwidth'] = 'Pop-up width (in pixels)';
$string['popupwidthexplain'] = 'Specifies default width of popup windows.';
$string['printintro'] = 'Display ardora description';
$string['printintroexplain'] = 'Display ardora description below content? Some display types may not display description even if enabled.';
$string['privacy:metadata'] = 'The File ardora plugin does not store any personal data.';
$string['ardora:addinstance'] = 'Add a new ardora';
$string['ardoracontent'] = 'Files and subfolders';
$string['ardoradetails_sizetype'] = '{$a->size} {$a->type}';
$string['ardoradetails_sizedate'] = '{$a->size} {$a->date}';
$string['ardoradetails_typedate'] = '{$a->type} {$a->date}';
$string['ardoradetails_sizetypedate'] = '{$a->size} {$a->type} {$a->date}';
$string['ardora:exportardora'] = 'Export ardora';
$string['ardora:view'] = 'View ardora';
$string['ardora:grade'] = 'Grade Ardora submissions';
$string['passinggrade'] = 'Passing grade';
$string['passinggrade_help'] = 'Minimum grade a user must achieve to consider the activity as passed.';
$string['invalidpassinggrade'] = 'The passing grade must be a number between 0 and 100.';
$string['completionpassgrade'] = 'The student must achieve the passing grade to complete this activity.';
$string['completionpassgrade_help'] = 'If this option is enabled, the activity will be marked as complete only when the student achieves the specified passing grade.';
$string['maximumgrade'] = 'Maximum grade';
$string['maximumgrade_help'] = 'Specify the maximum grade that can be achieved for this activity.';

$string['gradingoptions'] = 'Grading options';
$string['search:activity'] = 'Ardora';
$string['selectmainfile'] = 'Please select the main file by clicking the icon next to file name.';
$string['showdate'] = 'Show upload/modified date';
$string['showdate_desc'] = 'Display upload/modified date on course page?';
$string['showdate_help'] = 'Displays the upload/modified date beside links to the file.

If there are multiple files in this ardora, the start file upload/modified date is displayed.';
$string['showsize'] = 'Show size';
$string['showsize_help'] = 'Displays the file size, such as \'3.1 MB\', beside links to the file.

If there are multiple files in this ardora, the total size of all files is displayed.';
$string['showsize_desc'] = 'Display file size on course page?';
$string['showtype'] = 'Show type';
$string['showtype_desc'] = 'Display file type (e.g. \'Word document\') on course page?';
$string['showtype_help'] = 'Displays the type of the file, such as \'Word document\', beside links to the file.

If there are multiple files in this ardora, the start file type is displayed.

If the file type is not known to the system, it will not display.';
$string['uploadeddate'] = 'Uploaded {$a}';

$string['embedheightexplain'] = 'The height of the stage frame.';
$string['embedwidthexplain'] = 'The width of the stage frame.';
$string['embedwidth'] = 'Width';
$string['embedheight'] = 'Height';
$string['privacy:metadata'] = 'The File ardora plugin does not store any personal data.';
