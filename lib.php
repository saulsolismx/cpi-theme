<?php
defined('MOODLE_INTERNAL') || die();
function theme_cpi_get_main_scss_content($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
}
function theme_cpi_get_pre_scss($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/cpi/scss/pre.scss');
}
function theme_cpi_get_extra_scss($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/cpi/scss/post.scss');
}
