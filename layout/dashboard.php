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
 * Layout del dashboard del alumno para CPI: 3 columnas (25/50/25), sin drawer de bloques.
 *
 * Basado en theme_boost/layout/drawers.php. La columna central es el output de
 * my/index.php (región 'content'); izquierda y derecha son las regiones nuevas
 * 'content-left' y 'content-right' declaradas en el layout 'mydashboard' de cpi.
 *
 * @package   theme_cpi
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Regiones nuevas (columnas izquierda/derecha) + sus botones "Agregar bloque".
$contentleft = $OUTPUT->blocks('content-left');
$contentright = $OUTPUT->blocks('content-right');
$addblockleft = $OUTPUT->addblockbutton('content-left');
$addblockright = $OUTPUT->addblockbutton('content-right');

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
} else {
    $courseindexopen = false;
}

$extraclasses = ['uses-drawers', 'cpi-dashboard'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

// En el dashboard no hay curso -> course index vacío; se mantiene por compatibilidad.
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $selectmenu = new \core\output\select_menu(
            'tertiarynavigation',
            $overflowdata->urls,
            $overflowdata->selected,
        );
        $selectmenu->set_label($overflowdata->label, $overflowdata->labelattributes);
        $overflow = $selectmenu->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    // Columnas del dashboard CPI.
    'contentleft' => $contentleft,
    'contentright' => $contentright,
    'addblockleft' => $addblockleft,
    'addblockright' => $addblockright,
];

echo $OUTPUT->render_from_template('theme_cpi/dashboard', $templatecontext);
