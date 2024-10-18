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
 * Ardora function library
 * Auxiliary functions for Ardora
 *
 * @package    mod_ardora
 * @copyright  2024 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_login();
require_once("$CFG->dirroot/mod/ardora/lib.php");

$action = required_param('action', PARAM_ALPHA);

$action = str_replace('getjob', 'get_job', $action);
$action = str_replace('addjob', 'add_job', $action);
$action = str_replace('geteval', 'get_eval', $action);
$action = str_replace('getinfo', 'get_info', $action);
$action = str_replace('deljob', 'del_job', $action);

switch ($action) {
    case 'add_job':
        $datajob = required_param('datajob', PARAM_TEXT);
        $type = required_param('type', PARAM_TEXT);
        $father = required_param('father', PARAM_TEXT);
        $paqname = required_param('paq_name', PARAM_TEXT);
        $ardoraid = required_param('ardora_id', PARAM_TEXT);
        $activity = required_param('activity', PARAM_TEXT);
        $hstart = required_param('hstart', PARAM_TEXT);
        $hend = required_param('hend', PARAM_TEXT);
        $attemps = required_param('attemps', PARAM_INT);
        $points = required_param('points', PARAM_INT);
        $state = required_param('state', PARAM_TEXT);
        $typegrade = required_param('typegrade', PARAM_TEXT);

        mod_ardora_save_job(
            $datajob,
            $father,
            $type,
            $paqname,
            $activity,
            $hstart,
            $hend,
            $attemps,
            $points,
            $state,
            $ardoraid,
            $typegrade
        );
        break;

    case 'get_job':
        $type = required_param('type', PARAM_TEXT);
        $father = required_param('father', PARAM_TEXT);
        $paqname = required_param('paq_name', PARAM_TEXT);
        $ardoraid = required_param('ardora_id', PARAM_TEXT);

        $jobs = get_user_ardora_jobs($type, $father, $paqname, $ardoraid);
        $jsonresponse = json_encode($jobs);
        header('Content-Type: application/json');
        echo $jsonresponse;
        break;

    case 'get_eval':
        $type = required_param('type', PARAM_TEXT);
        $father = required_param('father', PARAM_TEXT);
        $paqname = required_param('paq_name', PARAM_TEXT);
        $ardoraid = required_param('ardora_id', PARAM_TEXT);

        $jobs = get_user_ardora_eval($type, $father, $paqname, $ardoraid);
        $jsonresponse = json_encode($jobs);
        header('Content-Type: application/json');
        echo $jsonresponse;
        break;

    case 'get_info':
        $type = required_param('type', PARAM_TEXT);
        $ardoraid = required_param('ardora_id', PARAM_TEXT);

        $jobs = get_user_ardora_info($type, $ardoraid);
        $jsonresponse = json_encode($jobs);
        header('Content-Type: application/json');
        echo $jsonresponse;
        break;

    case 'del_job':
        $userid = required_param('user_id', PARAM_INT);
        $datajob = required_param('datajob', PARAM_TEXT);
        $ardoraid = required_param('ardora_id', PARAM_TEXT);

        del_user_ardora_job($userid, $datajob, $ardoraid);
        break;

    default:
        throw new moodle_exception('invalidaction', 'mod_ardora');
}
