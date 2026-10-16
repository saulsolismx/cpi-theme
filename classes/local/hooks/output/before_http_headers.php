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

namespace theme_cpi\local\hooks\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Perfil dentro de curso (/user/view.php?id=X&course=Y) → perfil de CPI para alumnos.
 *
 * Dentro de un curso, core_renderer::user_picture() y muchos módulos (foros, calificador,
 * participantes, "Perfiles de curso"…) enlazan a view.php, que pinta el perfil estándar.
 * Para quien no gestiona el curso se redirige al perfil nuevo (/user/profile.php?id=X).
 *
 * Se engancha al principio de $OUTPUT->header(), antes de enviar nada: view.php ya hizo
 * require_login($course) y sus comprobaciones de acceso, así que redirect() todavía es
 * posible. profile.php aplica su propia comprobación (user_can_view_profile), de modo que
 * la redirección no abre perfiles que el alumno no pudiera ver.
 *
 * Profesores, gestores y administradores (moodle/course:enrolreview o
 * moodle/user:viewalldetails en el curso) se quedan en view.php: ahí se gestionan roles e
 * inscripciones.
 *
 * @package    theme_cpi
 */
class before_http_headers {

    /**
     * Redirige view.php al perfil de CPI cuando el visitante no gestiona el curso.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function callback(\core\hook\output\before_http_headers $hook): void {
        global $PAGE;

        if (!$PAGE->has_set_url() || $PAGE->url->get_path(false) !== '/user/view.php') {
            return;
        }
        $userid = (int) $PAGE->url->get_param('id');
        $courseid = (int) $PAGE->url->get_param('course');
        if (!$userid || !$courseid || $courseid == SITEID || !isloggedin() || isguestuser()) {
            return;
        }
        $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$coursecontext) {
            return;
        }
        if (has_capability('moodle/course:enrolreview', $coursecontext)
                || has_capability('moodle/user:viewalldetails', $coursecontext)) {
            return;
        }
        redirect(new \moodle_url('/user/profile.php', ['id' => $userid]));
    }
}
