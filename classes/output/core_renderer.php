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

namespace theme_cpi\output;

/**
 * Renderer de CPI: reactiva la navegación de actividad (Anterior/Siguiente) al pie de
 * cada recurso, que el core suprime cuando el tema usa course index.
 *
 * Extiende el renderer de Boost para conservar sus personalizaciones (navbar, login,
 * context_header, etc.). Cambios respecto al core:
 *  - se elimina el early-return del gate `usescourseindex` en activity_navigation();
 *  - los enlaces prev/next muestran texto compacto "Anterior"/"Siguiente" en vez del
 *    nombre largo de la actividad vecina (solo cambia el texto visible, no el href).
 */
class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Banner de cabecera de curso (Capa 2) DENTRO del contenedor del contenido.
     *
     * Se antepone al header nativo (para mantener el banner arriba, como el diseño
     * previo) y, al formar parte de la salida de full_header(), queda dentro de
     * #page-header (→ #topofscroll): el mismo contenedor que el contenido del curso.
     * Así banner y contenido alinean con el índice de curso abierto o cerrado, sin
     * acoplarse a la geometría de los drawers. El gate (solo vista de curso, id > 1)
     * vive en \theme_cpi\local\course_banner::html().
     *
     * @return string
     */
    public function full_header() {
        return \theme_cpi\local\course_banner::html($this, $this->page) . parent::full_header();
    }

    /**
     * Copia de \core\output\core_renderer::activity_navigation() SIN el early-return del
     * gate de course index, y con el texto de los enlaces prev/next fijado a un literal
     * compacto (Anterior/Siguiente) en lugar del nombre de la actividad.
     *
     * @return string
     */
    public function activity_navigation() {
        // First we should check if we want to add navigation.
        $context = $this->page->context;
        if (
            ($this->page->pagelayout !== 'incourse' && $this->page->pagelayout !== 'frametop')
            || $context->contextlevel != CONTEXT_MODULE
        ) {
            return '';
        }

        // If the activity is in stealth mode, show no links.
        if ($this->page->cm->is_stealth()) {
            return '';
        }

        $course = $this->page->cm->get_course();

        // CPI: se ELIMINA a propósito el early-return del gate usescourseindex del core,
        // para mostrar la navegación aunque el tema use course index.

        // Get a list of all the activities in the course.
        $modules = get_fast_modinfo($course->id)->get_cms();

        // Put the modules into an array in order by the position they are shown in the course.
        $mods = [];
        $activitylist = [];
        foreach ($modules as $module) {
            // Only add activities the user can access, aren't in stealth mode, are of a type that is visible on the course,
            // and have a url (eg. mod_label does not).
            if (!$module->uservisible || $module->is_stealth() || empty($module->url) || !$module->is_of_type_that_can_display()) {
                continue;
            }
            $mods[$module->id] = $module;

            // No need to add the current module to the list for the activity dropdown menu.
            if ($module->id == $this->page->cm->id) {
                continue;
            }
            // Module name.
            $modname = $module->get_formatted_name();
            // Display the hidden text if necessary.
            if (!$module->visible) {
                $modname .= ' ' . get_string('hiddenwithbrackets');
            }
            // Module URL.
            $linkurl = new \moodle_url($module->url, ['forceview' => 1]);
            // Add module URL (as key) and name (as value) to the activity list array.
            $activitylist[$linkurl->out(false)] = $modname;
        }

        $nummods = count($mods);

        // If there are only one or fewer mods then do nothing.
        if ($nummods <= 1) {
            return '';
        }

        // Get an array of just the course module ids used to get the cmid value based on their position in the course.
        $modids = array_keys($mods);

        // Get the position in the array of the course module we are viewing.
        $position = array_search($this->page->cm->id, $modids);

        $prevmod = null;
        $nextmod = null;

        // Check if we have a previous mod to show.
        if ($position > 0) {
            $prevmod = $mods[$modids[$position - 1]];
        }

        // Check if we have a next mod to show.
        if ($position < ($nummods - 1)) {
            $nextmod = $mods[$modids[$position + 1]];
        }

        $activitynav = new \core_course\output\activity_navigation($prevmod, $nextmod, $activitylist);

        // CPI: texto compacto en vez del nombre de la actividad (conserva el href).
        if ($activitynav->prevlink) {
            $activitynav->prevlink->text = $this->larrow() . ' ' . get_string('previous');
        }
        if ($activitynav->nextlink) {
            $activitynav->nextlink->text = get_string('next') . ' ' . $this->rarrow();
        }

        $renderer = $this->page->get_renderer('core', 'course');
        return $renderer->render($activitynav);
    }
}
