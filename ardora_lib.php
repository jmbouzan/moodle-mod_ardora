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
 * Ardora AJAX handler.
 * Processes all AJAX calls for the Ardora plugin.
 *
 * @package    mod_ardora
 * @copyright  2026 José Manuel Bouzán Matanza (https://www.webardora.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_login();

if (!optional_param('sesskey', null, PARAM_RAW)) {
    $_REQUEST['sesskey'] = $USER->sesskey;
}
require_once("$CFG->dirroot/mod/ardora/lib.php");
require_once("$CFG->dirroot/mod/ardora/server_pages_lib.php");

// Try to get courseid from parameters, then cache, then by querying ardora_id.
$contextid = optional_param('context_id', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$ardoraid = optional_param('ardora_id', '', PARAM_TEXT);

$ardorarecord = null;

if ($contextid) {
    try {
        $context = context::instance_by_id($contextid, MUST_EXIST);
        if ($context->contextlevel == CONTEXT_MODULE) {
            $cm = get_coursemodule_from_id('ardora', $context->instanceid, 0, false, MUST_EXIST);
            $courseid = $cm->course;
            $ardorarecord = $DB->get_record('ardora', ['id' => $cm->instance], '*', MUST_EXIST);
            $ardoraid = $ardorarecord->ardora_id;
        }
    } catch (Exception $e) {
        $ardorarecord = null;
    }
}

if (!$ardorarecord) {
    if (!$courseid) {
        $cache = \cache::make('mod_ardora', 'courseid_cache');
        $courseid = $cache->get('current_courseid');
    }

    if ($courseid && $ardoraid) {
        $ardorarecord = $DB->get_record('ardora', ['ardora_id' => $ardoraid, 'course' => $courseid], '*');
        if (!$ardorarecord && is_numeric($ardoraid)) {
            $ardorarecord = $DB->get_record('ardora', ['id' => $ardoraid, 'course' => $courseid], '*');
        }
    }
    if (!$ardorarecord && $ardoraid) {
        $ardorarecord = $DB->get_record('ardora', ['ardora_id' => $ardoraid], '*', IGNORE_MULTIPLE);
        if (!$ardorarecord && is_numeric($ardoraid)) {
            $ardorarecord = $DB->get_record('ardora', ['id' => $ardoraid], '*', IGNORE_MULTIPLE);
        }

        if ($ardorarecord) {
            $courseid = $ardorarecord->course;
        }
    }
}

if (!$courseid || (!$ardorarecord && !$contextid)) {
    throw new moodle_exception('courseidnotfound', 'mod_ardora');
}

// Use courseid to get context globally for all actions.
$cm = get_coursemodule_from_instance('ardora', $ardorarecord->id, $courseid, false, MUST_EXIST);
$context = context_module::instance($cm->id);

$action = required_param('action', PARAM_ALPHAEXT);
$action = str_replace('getjob', 'get_job', $action);
$action = str_replace('addjob', 'add_job', $action);
$action = str_replace('geteval', 'get_eval', $action);
$action = str_replace('getinfo', 'get_info', $action);
$action = str_replace('deljob', 'del_job', $action);
$tipocontido = optional_param('tipocontido', '', PARAM_ALPHANUMEXT);

// If it's a server page action derived from messenger, route it regardless of tipocontido
// so that Sticky, Polaroid, etc. can use isOpen and saveFile.
switch ($action) {
    case 'isOpen':
        $cfg = $DB->get_record(
            'ardora_server_pages',
            ['courseid' => $courseid, 'ardora_id' => $ardoraid, 'type' => 'messenger_cfg']
        );
        if ($cfg) {
            $p = json_decode($cfg->field01, true);
            echo json_encode([
                "userType" => (is_siteadmin() || has_capability('mod/ardora:managejobs', $context)) ? 'profe' : 'alu',
                "iduser" => $USER->username,
                "is_o" => $p['isOpen'] ?? 'Y',
                "txt1" => $p['txt1'] ?? 'Messenger',
                "txt2" => $p['txt2'] ?? '',
                "txt3" => $p['txt3'] ?? '',
                "cmid" => $cm->id,
                "course" => $courseid,
                "fullname" => fullname($USER),
            ]);
        } else {
            echo json_encode([
                "userType" => (is_siteadmin() || has_capability('mod/ardora:managejobs', $context)) ? 'profe' : 'alu',
                "iduser" => $USER->username,
                "is_o" => 'Y',
                "txt1" => 'Messenger',
                "txt2" => '',
                "txt3" => '',
                "cmid" => $cm->id,
                "course" => $courseid,
                "fullname" => fullname($USER),
            ]);
        }
        exit;

    case 'getuserlist':
        $users = get_enrolled_users($context);
        $listuserid = [];
        $listusername = [];
        $listavatar = [];
        $fs = get_file_storage();
        foreach ($users as $u) {
            if ($u->id == $USER->id) {
                continue;
            }
            $listuserid[] = $u->username;
            $listusername[] = fullname($u);

            $ucontext = context_user::instance($u->id);
            $afiles = $fs->get_area_files($ucontext->id, 'user', 'icon', 0, 'id DESC', false);
            if ($afiles) {
                $afile = reset($afiles);
                $listavatar[] = moodle_url::make_pluginfile_url(
                    $ucontext->id,
                    'user',
                    'icon',
                    0,
                    '/',
                    $afile->get_filename()
                )->out(false);
            } else {
                $listavatar[] = '';
            }
        }
        echo json_encode([
            "ok" => "ok",
            "listusername" => $listusername,
            "listuserid" => $listuserid,
            "listcur" => array_fill(0, count($listuserid), ''),
            "listgru" => array_fill(0, count($listuserid), ''),
            "listavatar" => $listavatar,
            "groups" => [[$course->shortname, '']],
            "lasmessageto" => [],
            "lasmessageid" => [],
        ]);
        exit;

    case 'getPosts':
        $from = required_param('from', PARAM_RAW);
        $to = required_param('to', PARAM_RAW);
        $typechat = required_param('type', PARAM_RAW);

        $sql = "SELECT * FROM {ardora_server_pages} WHERE courseid = ? AND ardora_id = ? AND type = 'messenger' ";
        if ($typechat === 'G') {
            $sql .= " AND field01 = ?";
            $params = [$courseid, $ardoraid, $to];
        } else {
            $sql .= " AND ((userid = (SELECT id FROM {user} WHERE username = ?) AND field01 = ?) ";
            $sql .= "OR (userid = (SELECT id FROM {user} WHERE username = ?) AND field01 = ?))";
            $params = [$courseid, $ardoraid, $from, $to, $to, $from];
        }
        $sql .= " ORDER BY dt ASC";
        $records = $DB->get_records_sql($sql, $params);
        $aid = [];
        $afrom = [];
        $ato = [];
        $astart = [];
        $ahour = [];
        $atext = [];
        $lastid = 0;
        foreach ($records as $r) {
            $u = $DB->get_record('user', ['id' => $r->userid], 'username');
            $aid[] = $r->id;
            $afrom[] = $u ? $u->username : '';
            $ato[] = $r->field01;
            $astart[] = $r->field04;
            $ahour[] = $r->field05;
            $atext[] = $r->field03;
            $lastid = $r->id;
        }
        echo json_encode([
            "lastid" => $lastid,
            "listid" => $aid,
            "listfrom" => $afrom,
            "listto" => $ato,
            "liststart" => $astart,
            "listhour" => $ahour,
            "listtext" => $atext,
            "lasmessageto" => [],
            "lasmessageid" => [],
        ]);
        exit;

    case 'savePost':
        $text = required_param('text', PARAM_RAW);
        $start = required_param('start', PARAM_RAW);
        $hour = required_param('hour', PARAM_RAW);
        $from = required_param('from', PARAM_RAW);
        $to = required_param('to', PARAM_RAW);
        $typechat = required_param('type', PARAM_RAW);

        $fields = [
            'field01' => $to,
            'field02' => $typechat,
            'field03' => $text,
            'field04' => $start,
            'field05' => $hour,
        ];
        $newid = mod_ardora_save_server_page_data($courseid, $ardoraid, $USER->id, 'messenger', 0, $fields);

        // Re-check isOpen for the response.
        $cfg = $DB->get_record(
            'ardora_server_pages',
            ['courseid' => $courseid, 'ardora_id' => $ardoraid, 'type' => 'messenger_cfg']
        );
        $isopen = 'Y';
        if ($cfg) {
            $p = json_decode($cfg->field01, true);
            $isopen = $p['isOpen'] ?? 'Y';
        }
        echo json_encode(["id" => $isopen]);
        exit;

    case 'delPosts':
        $id = required_param('id', PARAM_INT);
        $record = $DB->get_record('ardora_server_pages', ['id' => $id], '*', MUST_EXIST);
        if ($record->userid == $USER->id || is_siteadmin() || has_capability('mod/ardora:managejobs', $context)) {
            $DB->delete_records('ardora_server_pages', ['id' => $id]);
        }
        echo json_encode(["id" => "ok"]);
        exit;

    case 'saveCFG':
        if (!is_siteadmin() && !has_capability('mod/ardora:managejobs', $context)) {
            throw new moodle_exception('nopermission', 'mod_ardora');
        }
        $isopen = required_param('is_o', PARAM_RAW);
        $txt1 = required_param('txt1', PARAM_RAW);
        $txt2 = required_param('txt2', PARAM_RAW);
        $txt3 = required_param('txt3', PARAM_RAW);

        $fields = [
            'field01' => json_encode([
                'isOpen' => $isopen,
                'txt1' => $txt1,
                'txt2' => $txt2,
                'txt3' => $txt3,
            ]),
        ];

        $existing = $DB->get_record('ardora_server_pages', [
            'courseid' => $courseid,
            'ardora_id' => $ardoraid,
            'type' => 'messenger_cfg',
        ]);
        $id = $existing ? $existing->id : null;

        mod_ardora_save_server_page_data($courseid, $ardoraid, $USER->id, 'messenger_cfg', 0, $fields, $id);
        echo json_encode(["profundidade" => 1]);
        exit;

    case 'chat_maintenance':
        // In Moodle we don't need archiving for now, just return skipped.
        echo json_encode(["status" => "skip", "message" => "Maintenance not required in Moodle environment"]);
        exit;

    case 'logout':
        echo json_encode(["status" => "logout"]);
        exit;

    case 'saveFile':
        if (!has_capability('mod/ardora:view', $context)) {
            throw new moodle_exception('nopermission', 'mod_ardora');
        }
        $file = $_FILES['file'] ?? $_FILES['image'] ?? $_FILES['media'] ?? null;
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            $content = file_get_contents($file['tmp_name']);
            $mimetype = $file['type'];
            $base64 = 'data:' . $mimetype . ';base64,' . base64_encode($content);
            echo json_encode(["ok" => true, "namefile" => $base64]);
        } else {
            echo json_encode(["ok" => false, "error" => "Upload failed"]);
        }
        exit;
    case 'isLegal':
        echo json_encode(["isL" => true]);
        exit;
}


switch ($action) {
    case 'add_job':
        // Parámetros necesarios.
        $datajob = required_param('datajob', PARAM_TEXT);
        $type = required_param('type', PARAM_TEXT);
        $father = required_param('father', PARAM_TEXT);
        $paqname = required_param('paq_name', PARAM_TEXT);
        $points = required_param('points', PARAM_INT);
        $state = required_param('state', PARAM_TEXT);
        $typegrade = required_param('typegrade', PARAM_TEXT);
        $activity = optional_param('activity', '', PARAM_TEXT);
        $hstart = optional_param('hstart', '', PARAM_TEXT);
        $hend = optional_param('hend', '', PARAM_TEXT);
        $attemps = optional_param('attemps', '', PARAM_TEXT);

        // Validar capacidad: Solo roles con mod/ardora:grade pueden realizar esta acción.
        if (!has_capability('mod/ardora:grade', $context)) {
            throw new moodle_exception('nopermission', 'mod_ardora');
        }
        // Guardar el trabajo.
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
        // Parámetros necesarios.
        $type = required_param('type', PARAM_TEXT);
        $father = required_param('father', PARAM_TEXT);
        $paqname = required_param('paq_name', PARAM_TEXT);
        // Validar capacidad: Solo roles con mod/ardora:view pueden realizar esta acción.
        if (!has_capability('mod/ardora:view', $context)) {
            throw new moodle_exception('nopermission', 'mod_ardora');
        }

        // Obtener los trabajos del usuario.
        $jobs = get_user_ardora_jobs($type, $father, $paqname, $ardoraid);
        $jsonresponse = json_encode($jobs, true);
        header('Content-Type: application/json');
        echo $jsonresponse;
        break;

    case 'get_eval':
        // Parámetros necesarios.
        $type = required_param('type', PARAM_TEXT);
        $father = required_param('father', PARAM_TEXT);
        $paqname = required_param('paq_name', PARAM_TEXT);
        // Validar capacidad: Solo roles con mod/ardora:view pueden realizar esta acción.
        if (!has_capability('mod/ardora:view', $context)) {
            throw new moodle_exception('nopermission', 'mod_ardora');
        }

        // Obtener la evaluación del usuario.
        $jobs = get_user_ardora_eval($type, $father, $paqname, $ardoraid);
        $jsonresponse = json_encode($jobs, true);
        header('Content-Type: application/json');
        echo $jsonresponse;
        break;

    case 'get_info':
        // Parámetros necesarios.
        $type = required_param('type', PARAM_TEXT);
        // Validar capacidad: Solo roles con mod/ardora:view pueden realizar esta acción.
        if (!has_capability('mod/ardora:view', $context)) {
            throw new moodle_exception('nopermission', 'mod_ardora');
        }

        // Obtener información del usuario.
        $jobs = get_user_ardora_info($type, $ardoraid);
        $jsonresponse = json_encode($jobs, true);
        header('Content-Type: application/json');
        echo $jsonresponse;
        break;

    case 'del_job':

        // Parámetros necesarios.
        $userid = required_param('user_id', PARAM_INT);
        $datajob = required_param('datajob', PARAM_TEXT);
        // Validar capacidad: Solo roles con mod/ardora:grade pueden realizar esta acción.
        if (!has_capability('mod/ardora:grade', $context)) {
            throw new moodle_exception('nopermission', 'mod_ardora');
        }

        // Eliminar el trabajo.
        del_user_ardora_job($userid, $datajob, $ardoraid);
        break;

    case 'server_page_action': // UPDATE V.2 PLUGIN Páginas en servidor.
        $spaction = required_param('sp_action', PARAM_ALPHAEXT);
        $type = optional_param('type', '', PARAM_ALPHANUMEXT);
        // DEPURACIÓN STICKY.
        $folder = optional_param('folder', '', PARAM_TEXT);

        if ($spaction === 'save') {
            if (!has_capability('mod/ardora:view', $context)) {
                throw new moodle_exception('nopermission', 'mod_ardora');
            }
            $targetuser = optional_param('targetUser', null, PARAM_RAW);
            $userid = $USER->id;
            if ($targetuser && $targetuser !== $USER->username) {
                if (has_capability('mod/ardora:grade', $context)) {
                    $tuser = $DB->get_record('user', ['username' => $targetuser], 'id', MUST_EXIST);
                    $userid = $tuser->id;
                } else {
                    throw new moodle_exception('nopermission', 'mod_ardora');
                }
            }

            $father = optional_param('father', 0, PARAM_INT);
            $id = optional_param('id', null, PARAM_INT);
            // Si se actualiza un registro existente sin targetUser explícito, preservar el autor original.
            // Esto evita que un profe/admin que edita el sticky de un alumno se convierta en el autor.
            if ($id && !$targetuser) {
                $existingrecord = $DB->get_record('ardora_server_pages', ['id' => $id], 'userid');
                if ($existingrecord) {
                    $userid = $existingrecord->userid;
                }
            }
            $fields = [];

            if ($type === 'formularios') {
                $formdata = optional_param_array('formData', [], PARAM_RAW);
                $fields['field01'] = json_encode($formdata);
                $existing = $DB->get_record(
                    'ardora_server_pages',
                    ['courseid' => $courseid, 'ardora_id' => $ardoraid, 'type' => $type, 'folder' => $folder, 'userid' => $userid]
                );
                if ($existing) {
                    $id = $existing->id;
                }
            } else {
                for ($i = 1; $i <= 30; $i++) {
                    $fname = sprintf('field%02d', $i);
                    if (isset($_POST[$fname])) {
                        $fields[$fname] = optional_param($fname, '', PARAM_RAW);
                    }
                }
            }
            $newid = mod_ardora_save_server_page_data($courseid, $ardoraid, $userid, $type, $father, $fields, $id, $folder);
            echo json_encode(['ok' => true, 'id' => $newid]);
            exit;
        } else if ($spaction === 'all') {
            if (!has_capability('mod/ardora:view', $context)) {
                throw new moodle_exception('nopermission', 'mod_ardora');
            }
            if ($type === 'formularios') {
                $orderby = optional_param('orderBy', 'user', PARAM_ALPHA);
                $fieldid = optional_param('fieldId', '', PARAM_ALPHANUMEXT);

                $data = mod_ardora_get_server_page_data($courseid, $ardoraid, $type, $folder);
                $response = [];
                foreach ($data as $record) {
                    $formdata = json_decode($record->field01, true) ?: [];
                    $fieldval = isset($formdata[$fieldid]) ? $formdata[$fieldid] : '';

                    $userrecord = $DB->get_record('user', ['id' => $record->userid], 'id, username, firstname, lastname');
                    if (!$userrecord) {
                        continue;
                    }

                    $response[] = [
                        'username' => $userrecord->username,
                        'fullusername' => fullname($userrecord),
                        'date' => mod_ardora_wave_time($record->dt),
                        'fieldVal' => $fieldval,
                        'hasFile' => true,
                    ];
                }

                usort($response, function ($a, $b) use ($orderby) {
                    if ($orderby == 'Date') {
                        return strcmp($b['date'], $a['date']);
                    } else if ($orderby == 'user') {
                        return strcasecmp($a['fullusername'], $b['fullusername']);
                    } else if ($orderby == 'field') {
                        return strcasecmp($a['fieldVal'], $b['fieldVal']);
                    }
                    return 0;
                });

                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }

            $data = mod_ardora_get_server_page_data($courseid, $ardoraid, $type, $folder);
            // Format data to match expected JSON in JS.
            $response = [];
            foreach ($data as $record) {
                if ($type === 'timeline') {
                    $response[] = [
                        'id' => $record->id,
                        'autor' => $record->field01,
                        'titulo' => $record->field02,
                        'url' => $record->field03,
                        'anoI' => $record->field04,
                        'mesI' => $record->field05,
                        'diaI' => $record->field06,
                        'horaI' => $record->field07,
                        'minutoI' => $record->field08,
                        'segundoI' => $record->field09,
                        'anoF' => $record->field10,
                        'mesF' => $record->field11,
                        'diaF' => $record->field12,
                        'horaF' => $record->field13,
                        'minutoF' => $record->field14,
                        'segundoF' => $record->field15,
                        'color' => $record->field16,
                        'ancho' => $record->field17,
                        'media' => $record->field18,
                        'fecha' => $record->field19,
                        'comen' => $record->field20,
                        'dt' => mod_ardora_wave_time($record->dt),
                    ];
                } else if ($type === 'sticky') {
                    $response[] = [
                        'id' => $record->id,
                        'usu' => $DB->get_field('user', 'username', ['id' => $record->userid]),
                        'tit' => $record->field01,
                        'des' => $record->field02,
                        'cBa' => $record->field03,
                        'cBo' => $record->field04,
                        'dat' => mod_ardora_wave_time($record->dt),
                    ];
                } else if ($type === 'polaroid') {
                    $response[] = [
                        'id' => $record->id,
                        'usu' => $DB->get_field('user', 'username', ['id' => $record->userid]),
                        'tit' => $record->field01,
                        'des' => $record->field02,
                        'ima' => $record->field03,
                        'htm' => $record->field04,
                        'dat' => mod_ardora_wave_time($record->dt),
                    ];
                } else if ($type === 'gravadora') {
                    $response[] = [
                        'id' => $record->id,
                        'u' => $DB->get_field('user', 'username', ['id' => $record->userid]),
                        'word' => $record->field01,
                        'coment' => $record->field02,
                        'name' => $record->field03,
                        'typefile' => $record->field04,
                        'duration' => $record->field05,
                        'tea_name' => $record->field06 ?? '',
                        'tea_coment' => $record->field07 ?? '',
                        'tea_type' => $record->field08 ?? '',
                        'datetime' => mod_ardora_wave_time($record->dt),
                    ];
                } else {
                    $response[] = [
                        'id' => $record->id,
                        'father' => $record->father,
                        'usr' => $DB->get_field('user', 'username', ['id' => $record->userid]),
                        'comment' => $record->field01,
                        'dt' => mod_ardora_wave_time($record->dt),
                    ];
                }
            }
            echo json_encode($response);
            exit;
        } else if ($spaction === 'get_data') {
            $usertoload = optional_param('userToLoad', null, PARAM_RAW);
            if (!$usertoload) {
                $targetuserid = $USER->id;
            } else {
                $targetuser = $DB->get_record('user', ['username' => $usertoload], 'id', MUST_EXIST);
                $targetuserid = $targetuser->id;
            }

            $record = $DB->get_record(
                'ardora_server_pages',
                ['courseid' => $courseid, 'ardora_id' => $ardoraid, 'type' => $type, 'folder' => $folder, 'userid' => $targetuserid]
            );

            // Determine target user type.
            $targetusertype = 'alu';
            if (is_siteadmin($targetuserid) || has_capability('moodle/course:manageactivities', $context, $targetuserid)) {
                $targetusertype = 'admin';
            } else if (has_capability('mod/ardora:grade', $context, $targetuserid)) {
                $targetusertype = 'profe';
            }

            if ($record) {
                $formdata = json_decode($record->field01, true) ?: [];
                echo json_encode(['ok' => true, 'formData' => $formdata, 'targetUserType' => $targetusertype]);
                exit;
            } else {
                echo json_encode(['ok' => false, 'targetUserType' => $targetusertype]);
                exit;
            }
        } else if ($spaction === 'delete_form') {
            $usertodelete = required_param('userToDelete', PARAM_RAW);
            $targetuser = $DB->get_record('user', ['username' => $usertodelete], 'id', MUST_EXIST);

            // Check permissions.
            $isadmin = has_capability('moodle/course:manageactivities', $context);
            if ($targetuser->id != $USER->id && !$isadmin) {
                if (has_capability('mod/ardora:grade', $context)) {
                    if (has_capability('mod/ardora:grade', $context, $targetuser->id)) {
                        throw new moodle_exception('nopermission', 'mod_ardora');
                    }
                } else {
                    throw new moodle_exception('nopermission', 'mod_ardora');
                }
            }

            $DB->delete_records('ardora_server_pages', [
                'courseid' => $courseid,
                'ardora_id' => $ardoraid,
                'type' => $type,
                'folder' => $folder,
                'userid' => $targetuser->id,
            ]);
            echo json_encode(['ok' => true]);
            exit;
        } else if ($spaction === 'delete') {
            $id = required_param('id', PARAM_INT);
            $isadmin = has_capability('moodle/course:manageactivities', $context);
            mod_ardora_delete_server_page_data($id, $USER->id, $isadmin);
            echo json_encode(['ok' => true]);
            exit;
        } else if ($spaction === 'upload') {
            if (!has_capability('mod/ardora:view', $context)) {
                throw new moodle_exception('nopermission', 'mod_ardora');
            }
            $file = $_FILES['file'] ?? $_FILES['image'] ?? $_FILES['media'] ?? null;
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $content = file_get_contents($file['tmp_name']);
                $mimetype = $file['type'];
                // Some browsers might not provide the exact mimetype, but file_get_contents + base64_encode is reliable.
                $base64 = 'data:' . $mimetype . ';base64,' . base64_encode($content);

                $id = optional_param('id', null, PARAM_INT);
                $fieldname = optional_param('fieldname', '', PARAM_ALPHANUMEXT);
                if ($id && $fieldname) {
                    $record = $DB->get_record('ardora_server_pages', ['id' => $id], '*', MUST_EXIST);
                    $record->$fieldname = $base64;
                    // Also check for other fields that might be sent in the same request to avoid a second call.
                    for ($i = 1; $i <= 30; $i++) {
                        $f = sprintf('field%02d', $i);
                        if ($f !== $fieldname && isset($_POST[$f])) {
                            $record->$f = optional_param($f, '', PARAM_RAW);
                        }
                    }
                    $DB->update_record('ardora_server_pages', $record);
                    echo json_encode(['status' => 'success', 'filename' => $base64, 'id' => $id]);
                } else {
                    echo json_encode(['status' => 'success', 'filename' => $base64]);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error uploading file']);
            }
        } else if ($spaction === 'get_avatar') {
            $username = required_param('user', PARAM_RAW);
            $targetuser = $DB->get_record('user', ['username' => $username], 'id', MUST_EXIST);
            $usercontext = context_user::instance($targetuser->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($usercontext->id, 'user', 'icon', 0, 'id DESC', false);
            if ($files) {
                $file = reset($files);
                $image = base64_encode($file->get_content());
                echo json_encode(['ok' => true, 'image' => 'data:' . $file->get_mimetype() . ';base64,' . $image]);
            } else {
                echo json_encode(['ok' => false]);
            }
        } else if ($type === 'poster') {
            if ($spaction === 'get_students') {
                if (!has_capability('mod/ardora:grade', $context)) {
                    throw new moodle_exception('nopermission', 'mod_ardora');
                }
                if (empty($courseid) && !empty($ardorarecord)) {
                    $courseid = $ardorarecord->course;
                }
                require_once($CFG->dirroot . '/group/lib.php');
                $course = $DB->get_record('course', ['id' => $courseid]);
                $coursename = $course ? $course->shortname : '';
                $coursecontext = context_course::instance($courseid);
                $users = get_enrolled_users($coursecontext, '', 0, 'u.*', 'u.lastname, u.firstname ASC');
                $students = [];

                // Get assigned students to groups.
                $assigned = [];
                $groupsrecords = $DB->get_records(
                    'ardora_poster_groups',
                    ['courseid' => $courseid, 'ardora_id' => $ardoraid]
                );
                if ($groupsrecords) {
                    foreach ($groupsrecords as $g) {
                        $stulist = json_decode($g->students, true) ?: [];
                        foreach ($stulist as $s) {
                            $assigned[$s] = $g->id;
                        }
                    }
                }

                foreach ($users as $u) {
                    // Skip site admins and the current user.
                    if (is_siteadmin($u->id) || $u->id == $USER->id) {
                        continue;
                    }

                    // Get Moodle groups the user belongs to in this course.
                    $usergroups = groups_get_all_groups($courseid, $u->id);
                    $groupnames = [];
                    if ($usergroups) {
                        foreach ($usergroups as $ug) {
                            $groupnames[] = $ug->name;
                        }
                    }
                    $gnames = implode(', ', $groupnames);

                    $students[] = [
                        'id' => $u->id,
                        'username_encrypted' => $u->username,
                        'username' => $u->username,
                        'fullusername' => fullname($u),
                        'cur' => $coursename,
                        'gru' => $gnames,
                        'in_group' => $assigned[$u->username] ?? false,
                    ];
                }

                echo json_encode($students);
                exit;
            } else if ($spaction === 'save_group') {
                if (!has_capability('mod/ardora:grade', $context)) {
                    throw new moodle_exception('nopermission', 'mod_ardora');
                }
                $id = optional_param('id', null, PARAM_INT);
                $title = required_param('title', PARAM_TEXT);
                $comment = optional_param('comment', '', PARAM_TEXT);
                $students = optional_param_array('students', [], PARAM_TEXT);
                $existingimage = optional_param('existing_image', '', PARAM_RAW);

                $record = new stdClass();
                if ($id) {
                    $record->id = $id;
                }
                $record->courseid = $courseid;
                $record->ardora_id = $ardoraid;
                $record->title = $title;
                $record->comment = $comment;
                $record->students = json_encode($students);
                $record->dt = time();

                // Handle image upload if present.
                $file = $_FILES['image_file'] ?? null;
                if ($file && $file['error'] === UPLOAD_ERR_OK) {
                    $content = file_get_contents($file['tmp_name']);
                    $record->image = 'data:' . $file['type'] . ';base64,' . base64_encode($content);
                } else if ($existingimage) {
                    $record->image = $existingimage;
                }

                if ($id) {
                    $DB->update_record('ardora_poster_groups', $record);
                } else {
                    $DB->insert_record('ardora_poster_groups', $record);
                }
                echo json_encode(['status' => 'success']);
                exit;
            } else if ($spaction === 'get_groups' || $spaction === 'getPosters') {
                $groups = $DB->get_records(
                    'ardora_poster_groups',
                    ['courseid' => $courseid, 'ardora_id' => $ardoraid],
                    'title ASC'
                );
                $response = [];
                foreach ($groups as $g) {
                    $response[] = [
                        'id' => $g->id,
                        'title' => $g->title,
                        'comment' => $g->comment,
                        'image' => $g->image,
                        'students' => json_decode($g->students, true) ?: [],
                    ];
                }
                echo json_encode($response);
                exit;
            } else if ($spaction === 'delete_group') {
                if (!has_capability('mod/ardora:grade', $context)) {
                    throw new moodle_exception('nopermission', 'mod_ardora');
                }
                $id = required_param('id', PARAM_INT);
                $DB->delete_records('ardora_poster_groups', ['id' => $id]);
                // Delete elements associated with this group.
                $DB->delete_records(
                    'ardora_server_pages',
                    ['courseid' => $courseid, 'ardora_id' => $ardoraid, 'type' => 'poster_element', 'father' => $id]
                );
                echo json_encode(['status' => 'success']);
                exit;
            } else if ($spaction === 'get_user_group_info') {
                $groups = $DB->get_records('ardora_poster_groups', ['courseid' => $courseid, 'ardora_id' => $ardoraid]);
                $foundid = null;
                foreach ($groups as $g) {
                    $students = json_decode($g->students, true) ?: [];
                    if (in_array($USER->username, $students)) {
                        $foundid = $g->id;
                        break;
                    }
                }
                echo json_encode(['groupId' => $foundid]);
                exit;
            } else if ($spaction === 'get_poster_data') {
                $groupid = required_param('groupId', PARAM_INT);
                $group = $DB->get_record('ardora_poster_groups', ['id' => $groupid], '*', MUST_EXIST);

                $result = [
                    "board" => [
                        "title" => $group->title,
                        "comment" => $group->comment,
                        "idImage" => $group->image,
                        "bgColor" => $group->bg_color ?: "transparent",
                        "bgImage" => $group->bg_image ?: "",
                        "bgPos" => $group->bg_pos ?: "center",
                        "bgRepeat" => $group->bg_repeat ?: "no-repeat",
                    ],
                    "elements" => [],
                ];

                $elements = $DB->get_records(
                    'ardora_server_pages',
                    ['courseid' => $courseid, 'ardora_id' => $ardoraid, 'type' => 'poster_element', 'father' => $groupid]
                );
                foreach ($elements as $el) {
                    $result["elements"][] = [
                        "id" => $el->field01,
                        "type" => $el->field02,
                        "content" => $el->field03,
                        "x" => $el->field04,
                        "y" => $el->field05,
                        "w" => $el->field06,
                        "h" => $el->field07,
                        "styles" => [
                            "fontSize" => $el->field08,
                            "color" => $el->field09,
                            "backgroundColor" => $el->field10,
                            "borderWidth" => $el->field11,
                            "borderColor" => $el->field12,
                            "rotation" => $el->field13,
                            "zIndex" => $el->field14,
                            "fontWeight" => $el->field15,
                        ],
                        "locked_by" => $el->field16,
                        "lock_time" => $el->field17,
                    ];
                }
                echo json_encode($result);
                exit;
            } else if ($spaction === 'save_poster_element') {
                $groupid = required_param('groupId', PARAM_INT);
                $elid = required_param('id', PARAM_RAW);
                // Type_el is the specific element type (text, image, etc.).
                // Type is the general plugin type (poster) used for routing.
                $typeel = optional_param('type_el', '', PARAM_RAW);
                if (empty($typeel)) {
                    $typeel = required_param('type', PARAM_RAW);
                }
                $content = optional_param('content', '', PARAM_RAW);

                $fields = [
                    'field01' => $elid,
                    'field02' => $typeel,
                    'field03' => $content,
                    'field04' => optional_param('x', '50', PARAM_RAW),
                    'field05' => optional_param('y', '50', PARAM_RAW),
                    'field06' => optional_param('w', '200', PARAM_RAW),
                    'field07' => optional_param('h', '100', PARAM_RAW),
                    'field08' => optional_param('fontSize', '16px', PARAM_RAW),
                    'field09' => optional_param('color', '#000000', PARAM_RAW),
                    'field10' => optional_param('backgroundColor', 'transparent', PARAM_RAW),
                    'field11' => optional_param('borderWidth', '1px', PARAM_RAW),
                    'field12' => optional_param('borderColor', '#000000', PARAM_RAW),
                    'field13' => optional_param('rotation', '0', PARAM_RAW),
                    'field14' => optional_param('zIndex', '1', PARAM_RAW),
                    'field15' => optional_param('fontWeight', 'normal', PARAM_RAW),
                    'field16' => $USER->username,
                    'field17' => time(),
                ];

                $existing = $DB->get_record(
                    'ardora_server_pages',
                    [
                        'courseid' => $courseid,
                        'ardora_id' => $ardoraid,
                        'type' => 'poster_element',
                        'father' => $groupid,
                        'field01' => $elid,
                    ]
                );
                $idrec = $existing ? $existing->id : null;

                mod_ardora_save_server_page_data($courseid, $ardoraid, $USER->id, 'poster_element', $groupid, $fields, $idrec);
                echo json_encode(['status' => 'success', 'id' => $elid]);
                exit;
            } else if ($spaction === 'delete_poster_element') {
                $groupid = required_param('groupId', PARAM_INT);
                $elid = required_param('id', PARAM_RAW);
                $DB->delete_records(
                    'ardora_server_pages',
                    [
                        'courseid' => $courseid,
                        'ardora_id' => $ardoraid,
                        'type' => 'poster_element',
                        'father' => $groupid,
                        'field01' => $elid,
                    ]
                );
                echo json_encode(['status' => 'success']);
                exit;
            } else if ($spaction === 'lock_poster_element') {
                $groupid = required_param('groupId', PARAM_INT);
                $elid = required_param('id', PARAM_RAW);
                $existing = $DB->get_record(
                    'ardora_server_pages',
                    [
                        'courseid' => $courseid,
                        'ardora_id' => $ardoraid,
                        'type' => 'poster_element',
                        'father' => $groupid,
                        'field01' => $elid,
                    ]
                );
                if ($existing) {
                    if (
                        $existing->field16 && $existing->field16 != $USER->username &&
                        (time() - $existing->field17 < 30) && !has_capability('mod/ardora:grade', $context)
                    ) {
                        echo json_encode(["status" => "locked", "user" => $existing->field16]);
                    } else {
                        $existing->field16 = $USER->username;
                        $existing->field17 = time();
                        $DB->update_record('ardora_server_pages', $existing);
                        echo json_encode(["status" => "success"]);
                    }
                }
                exit;
            } else if ($spaction === 'unlock_poster_element') {
                $groupid = required_param('groupId', PARAM_INT);
                $elid = required_param('id', PARAM_RAW);
                $existing = $DB->get_record(
                    'ardora_server_pages',
                    [
                        'courseid' => $courseid,
                        'ardora_id' => $ardoraid,
                        'type' => 'poster_element',
                        'father' => $groupid,
                        'field01' => $elid,
                    ]
                );
                if ($existing && $existing->field16 == $USER->username) {
                    $existing->field16 = "";
                    $existing->field17 = 0;
                    $DB->update_record('ardora_server_pages', $existing);
                    echo json_encode(["status" => "success"]);
                }
                exit;
            } else if ($spaction === 'save_poster_settings') {
                if (!has_capability('mod/ardora:grade', $context)) {
                    throw new moodle_exception('nopermission', 'mod_ardora');
                }
                $groupid = required_param('groupId', PARAM_INT);
                $record = $DB->get_record('ardora_poster_groups', ['id' => $groupid], '*', MUST_EXIST);
                $record->bg_color = optional_param('bgColor', 'transparent', PARAM_RAW);
                $record->bg_image = optional_param('bgImage', '', PARAM_RAW);
                $record->bg_pos = optional_param('bgPos', 'center', PARAM_RAW);
                $record->bg_repeat = optional_param('bgRepeat', 'no-repeat', PARAM_RAW);
                $DB->update_record('ardora_poster_groups', $record);
                echo json_encode(['status' => 'success']);
                exit;
            } else if ($spaction === 'settings_upload') {
                if (!has_capability('mod/ardora:grade', $context)) {
                    throw new moodle_exception('nopermission', 'mod_ardora');
                }
                $groupid = required_param('groupId', PARAM_INT);
                $file = $_FILES['file'] ?? $_FILES['image'] ?? $_FILES['media'] ?? null;
                if ($file && $file['error'] === UPLOAD_ERR_OK) {
                    $content = file_get_contents($file['tmp_name']);
                    $base64 = 'data:' . $file['type'] . ';base64,' . base64_encode($content);
                    echo json_encode(['status' => 'success', 'url' => $base64, 'filename' => $base64]);
                } else {
                    echo json_encode(['status' => 'error']);
                }
                exit;
            } else if ($spaction === 'upload_poster_image' || $spaction === 'upload_poster_media') {
                $groupid = required_param('groupId', PARAM_INT);
                $file = $_FILES['file'] ?? $_FILES['image'] ?? $_FILES['media'] ?? null;
                if ($file && $file['error'] === UPLOAD_ERR_OK) {
                    $content = file_get_contents($file['tmp_name']);
                    $base64 = 'data:' . $file['type'] . ';base64,' . base64_encode($content);

                    $elid = "new_" . round(microtime(true) * 1000);
                    $fields = [
                        'field01' => $elid,
                        'field02' => ($spaction === 'upload_poster_image' ? 'image' : 'media'),
                        'field03' => $base64,
                        'field04' => '100', // X.
                        'field05' => '100', // Y.
                        'field06' => ($spaction === 'upload_poster_image' ? '200' : '300'), // W.
                        'field07' => ($spaction === 'upload_poster_image' ? '200' : '100'), // H.
                        'field16' => $USER->username,
                        'field17' => time(),
                    ];
                    mod_ardora_save_server_page_data($courseid, $ardoraid, $USER->id, 'poster_element', $groupid, $fields);
                    echo json_encode(['status' => 'success', 'id' => $elid]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Upload failed']);
                }
                exit;
            } else if ($spaction === 'get_user_group_info') {
                $groups = $DB->get_records('ardora_poster_groups', ['courseid' => $courseid, 'ardora_id' => $ardoraid]);
                $foundgroupid = null;
                foreach ($groups as $g) {
                    $students = json_decode($g->students, true) ?: [];
                    if (in_array((string)$USER->id, $students)) {
                        $foundgroupid = $g->id;
                        break;
                    }
                }
                echo json_encode(["groupId" => $foundgroupid]);
                exit;
            } else if ($spaction === 'unlock_all_user_elements') {
                $DB->set_field(
                    'ardora_server_pages',
                    'field16',
                    '',
                    ['courseid' => $courseid, 'ardora_id' => $ardoraid, 'type' => 'poster_element', 'field16' => $USER->username]
                );
                $DB->set_field(
                    'ardora_server_pages',
                    'field17',
                    0,
                    ['courseid' => $courseid, 'ardora_id' => $ardoraid, 'type' => 'poster_element', 'field16' => $USER->username]
                );
                echo json_encode(['status' => 'success']);
                exit;
            } else if ($spaction === 'cleanup_stale_locks') {
                $expiry = time() - 300; // 5 minutes.
                $sql = "UPDATE {ardora_server_pages} SET field16 = '', field17 = 0
                        WHERE courseid = ? AND ardora_id = ? AND type = ? AND field17 > 0 AND field17 < ?";
                $DB->execute($sql, [$courseid, $ardoraid, 'poster_element', $expiry]);
                echo json_encode(['status' => 'success']);
                exit;
            }
        }
        break;

    default:
        throw new moodle_exception('invalidaction', 'mod_ardora');
}

/**
 * Formats time for Server Pages UI.
 * Matches chuviaideas2/php/ardoraXML.php waveTime logic.
 *
 * @param int $t Timestamp.
 * @return string Formatted time or date.
 */
function mod_ardora_wave_time($t)
{
    // UPDATE V.2 PLUGIN Páginas en servidor.
    if (date('Y-m-d', $t) === date('Y-m-d')) {
        return date('H:i', $t);
    }
    return date('d-m-Y H:i', $t);
}
