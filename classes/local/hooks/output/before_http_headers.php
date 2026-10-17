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
 * También: alta de usuario estándar (/user/editadvanced.php?id=-1) → "Alta de alumno (CPI)"
 * (/local/cpiwelcome/alta.php), que manda la bienvenida en HTML. Ver redirect_new_user().
 *
 * @package    theme_cpi
 */
class before_http_headers {

    /**
     * Redirecciones de páginas del core a las de CPI.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function callback(\core\hook\output\before_http_headers $hook): void {
        global $PAGE;

        if (!$PAGE->has_set_url()) {
            return;
        }
        switch ($PAGE->url->get_path(false)) {
            case '/user/view.php':
                self::redirect_course_profile();
                break;
            case '/user/editadvanced.php':
                self::redirect_new_user();
                break;
        }
    }

    /**
     * Alta de usuario nuevo (/user/editadvanced.php?id=-1) → /local/cpiwelcome/alta.php.
     *
     * El formulario estándar crea la cuenta y el core manda en ese momento el correo en
     * texto plano ("Cuenta de nuevo usuario"); alta.php manda la bienvenida en HTML. Así el
     * botón "Agregar usuario" de la lista de usuarios y el menú Cuentas › Agregar usuario
     * llevan a la página de CPI.
     *
     * No se redirige:
     * - con ?core=1 (enlace "Usar el formulario estándar de Moodle" de alta.php);
     * - en POST: el formulario estándar se envía a editadvanced.php?id=-1 sin core=1 y, si
     *   la validación falla, se vuelve a pintar; redirigir perdería lo capturado;
     * - al editar usuarios existentes (id > 0);
     * - si local_cpiwelcome no está instalado o falta moodle/user:create (la que exige
     *   alta.php), para no mandar a una página que no abriría.
     */
    private static function redirect_new_user(): void {
        global $PAGE;

        if ((int) $PAGE->url->get_param('id') !== -1
                || optional_param('core', 0, PARAM_BOOL)
                || ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            return;
        }
        if (!class_exists(\local_cpiwelcome\local\alta::class)
                || !has_capability('moodle/user:create', \context_system::instance())) {
            return;
        }
        redirect(new \moodle_url('/local/cpiwelcome/alta.php'));
    }

    /**
     * Redirige view.php al perfil de CPI cuando el visitante no gestiona el curso.
     */
    private static function redirect_course_profile(): void {
        global $PAGE;

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
