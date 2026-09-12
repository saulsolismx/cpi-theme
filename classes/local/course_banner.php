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

namespace theme_cpi\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Banner de cabecera de curso (Capa 2): portada + resumen + botón "Continuar".
 *
 * Antes se inyectaba vía hook before_standard_top_of_body_html_generation, que emite
 * HTML al inicio del <body> (fuera de #page), por lo que el banner quedaba en otro
 * contenedor y no alineaba con el contenido. Ahora se renderiza desde
 * core_renderer::full_header(), de modo que queda DENTRO de #page-header (→ #topofscroll),
 * el mismo contenedor que el contenido del curso, y ambos alinean con el índice abierto
 * o cerrado. Esta clase concentra el armado de datos para no duplicarlo.
 *
 * @package theme_cpi
 */
class course_banner {

    /**
     * HTML del banner para la página actual, o '' si no aplica.
     *
     * Gate: solo vista de curso (pagelayout 'course') y curso real (id > 1).
     *
     * @param \renderer_base $output renderer capaz de render_from_template()
     * @param \moodle_page $page
     * @return string
     */
    public static function html(\renderer_base $output, \moodle_page $page): string {
        global $DB;

        // ── GATE: solo vista de curso, con curso real (no el site, id 1). ──
        if ($page->pagelayout !== 'course' || $page->course->id <= 1) {
            return '';
        }

        $course    = $page->course;
        $courseid  = (int) $course->id;
        $coursectx = \context_course::instance($courseid);

        // Título: saneado con format_string.
        $title = format_string($course->fullname, true, ['context' => $coursectx]);

        // Resumen: saneado con format_text (HTML confiable para triple-stache).
        $summary = '';
        if (!empty($course->summary)) {
            $summary = format_text($course->summary, $course->summaryformat,
                    ['context' => $coursectx]);
        }

        // Imagen de portada (overviewfiles vía exporter cacheado). false => sin imagen.
        $imageurl = \core_course\external\course_summary_exporter::get_course_image($course);

        // Badges opcionales.
        $category     = \core_course_category::get($course->category, IGNORE_MISSING);
        $categoryname = $category ? $category->get_formatted_name() : null;
        $courselang   = !empty($course->lang) ? $course->lang : null;

        // URL "Continuar" con cascada de fallbacks robustos.
        $continueurl = self::resolve_continue_url($courseid, $DB);

        $data = [
            'title'        => $title,
            'summary'      => $summary,
            'imageurl'     => $imageurl ?: null,
            'categoryname' => $categoryname,
            'courselang'   => $courselang,
            'continueurl'  => $continueurl ? $continueurl->out(false) : null,
        ];

        return $output->render_from_template('theme_cpi/course_banner', $data);
    }

    /**
     * Resuelve la URL del recurso a "continuar".
     *
     * Prioridad: último visitado (si existe y sigue visible) → primer recurso visible
     * del curso → null (sin botón).
     *
     * @param int $courseid
     * @param \moodle_database $DB
     * @return \moodle_url|null
     */
    private static function resolve_continue_url(int $courseid, \moodle_database $DB): ?\moodle_url {
        global $USER;

        $modinfo = get_fast_modinfo($courseid);

        // (a) Último visitado — solo para usuarios reales (no invitado/no logueado).
        if (isloggedin() && !isguestuser()) {
            $sql = "SELECT cmid
                      FROM {block_recentlyaccesseditems}
                     WHERE userid = :userid AND courseid = :courseid
                  ORDER BY timeaccess DESC";
            $rec = $DB->get_record_sql($sql,
                    ['userid' => $USER->id, 'courseid' => $courseid],
                    IGNORE_MULTIPLE);

            if ($rec) {
                try {
                    $cm = $modinfo->get_cm($rec->cmid);
                    if ($cm->uservisible && $cm->url) {
                        return $cm->url;
                    }
                } catch (\moodle_exception $e) {
                    // cmid apunta a actividad borrada → cae al fallback.
                    $cm = null;
                }
            }
        }

        // (b) Fallback: primer recurso visible con URL, en orden de curso.
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->uservisible && $cm->url) {
                return $cm->url;
            }
        }

        // (c) Sin recursos navegables → sin botón.
        return null;
    }
}
