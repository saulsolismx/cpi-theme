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

/**
 * Sirve los archivos de los ajustes del tema (imágenes de los anuncios del dashboard).
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function theme_cpi_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel == CONTEXT_SYSTEM && preg_match('/^ad[1-9]image(mobile)?$/', $filearea)) {
        $theme = theme_config::load('cpi');
        // Los anuncios son públicos para cualquiera que vea el dashboard.
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
    }
    send_file_not_found();
}
