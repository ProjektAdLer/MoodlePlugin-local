<?php

namespace local_adler\external;


global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/lib/horde/framework/Horde/Support/Uuid.php');  # required on some installs (bitnami moodle on phils pc), unknown why

use backup;
use context_coursecat;
use core_course_category;
use Exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use local_adler\local\exceptions\not_an_adler_course_exception;
use moodle_exception;
use restore_controller;
use restore_controller_exception;
use restore_dbops;


class upload_course extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'category_id' => new external_value(PARAM_INT, 'ID of the category in which the course should be created. If null, the course will be created in the category with the lowest available ID. Please note that even if a user has restore permissions for a category other than the one with the lowest ID, the course restoration process will not be successful.', VALUE_OPTIONAL),
                'mbz' => new external_value(PARAM_FILE, 'Required (moodle tag "optional" is due to moodle limitations). MBZ as file upload. Upload the file in this field. Moodle external_api wont recognize it / this field will be empty but it can be loaded from this field via plain PHP code.', VALUE_OPTIONAL),
            )
        );
    }

    public static function execute_returns(): external_function_parameters {
        return new external_function_parameters([
            'data' => new external_single_structure(
                array(
                    'course_id' => new external_value(PARAM_INT, 'id of the newly created course', VALUE_REQUIRED),
                    'course_fullname' => new external_value(PARAM_TEXT, 'fullname of the newly created course. This value might differ from the one specified in mbz. If a name already exists moodle renames the course.', VALUE_REQUIRED),
                )
            )
        ]);
    }

    /**
     * @param int $category_id ID of the category the course should be restored in
     * @throws invalid_parameter_exception
     * @throws not_an_adler_course_exception
     * @throws restore_controller_exception
     * @throws moodle_exception
     * @throws Exception
     */
    public static function execute(int $category_id=null): array {
        global $USER;

        // get category id (if parameter is set, use this id, otherwise the first the user is allowed to restore courses in)
        if (empty($category_id)) {
            $categories = core_course_category::make_categories_list('moodle/restore:restorecourse');
            if (count($categories) == 0) {
                throw new moodle_exception('not_allowed', '', '', NULL, 'user requires moodle/restore:restorecourse capability on at least one category');
            }
            $category_id = array_key_first($categories);
        }

        // permission validation
        $context = context_coursecat::instance($category_id);
        require_capability('moodle/restore:restorecourse', $context);

        // Moodle parameter validation not needed because moodle is too stupid to support direct file upload
        // instead manual validation is needed
        if (!isset($_FILES['mbz'])) {
            throw new invalid_parameter_exception('mbz is missing');
        }
        if ($_FILES['mbz']['error'] !== UPLOAD_ERR_OK) {
            throw new invalid_parameter_exception('mbz upload failed');
        }

        // Saving file (taken from externallib.php (file) upload)
        $dir = make_temp_directory('wsupload');

        $filename = uniqid('wsupload', true) . '_' . time() . '.tmp';

        if (file_exists($dir . $filename)) {
            $savedfilepath = $dir . uniqid('m') . $filename;
        } else {
            $savedfilepath = $dir . $filename;
        }

        // move file "mbz" from $_FILES to $savedfilepath
        rename($_FILES['mbz']['tmp_name'], $savedfilepath);

        // create course
        $course_id = restore_dbops::create_new_course('', '', $category_id);

        // extract mbz
        $foldername = restore_controller::get_tempdir_name($course_id, $USER->id);
        $fp = get_file_packer('application/vnd.moodle.backup');
        $tempdir = make_backup_temp_directory($foldername);
        $fp->extract_to_pathname($savedfilepath, $tempdir);

        // validate course is adler course
        $course_xml_path = $tempdir . '/course/course.xml';
        $contents = file_get_contents($course_xml_path);
        if (!property_exists(simplexml_load_string($contents), 'plugin_local_adler_course')
            || !property_exists(simplexml_load_string($contents)->plugin_local_adler_course, 'adler_course')) {
            throw new not_an_adler_course_exception();
        }

        // do restore: create controller
        $controller = new restore_controller(
            $foldername,
            $course_id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );

        // do restore: set required enrolment setting
        $plan = $controller->get_plan();
        $plan->get_tasks()[0]->get_setting('enrolments')->set_value(backup::ENROL_ALWAYS);

        // do restore: execute
        $controller->execute_precheck();
        $controller->execute_plan();
        $controller->destroy();


        // delete file $savedfilepath
        unlink($savedfilepath);
        // delete tempdir
        fulldelete($tempdir);


        // get course object
        $course = get_course($course_id);
        $course_fullname = $course->fullname;


        return array('data' => array(
            'course_id' => $course_id,
            'course_fullname' => $course_fullname
        ));
    }
}
