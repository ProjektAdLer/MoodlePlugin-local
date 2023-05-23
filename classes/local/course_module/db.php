<?php
namespace local_adler\local\course_module;

use dml_exception;
use stdClass;

class db {
    /**
     * Get adler-section with given section_id
     * @param string $uuid moodle section id
     * @return stdClass|false adler-section for given moodle section, false if not found
     * @throws dml_exception
     */
    public static function get_adler_course_module_by_uuid(string $uuid) {
        global $DB;
        return $DB->get_record('local_adler_course_modules', ['uuid' => $uuid]);
    }
}