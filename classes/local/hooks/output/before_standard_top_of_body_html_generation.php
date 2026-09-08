<?php
namespace theme_cpi\local\hooks\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Capa 2 — Banner de cabecera de curso.
 *
 * Inyecta un banner (portada + título + resumen + botón "Continuar") en la parte
 * superior del body, SOLO en la vista de curso. Punto de inyección robusto: hook de
 * output, sin overridear ninguna plantilla de core.
 */
class before_standard_top_of_body_html_generation {

    /**
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     */
    public static function callback(
        \core\hook\output\before_standard_top_of_body_html_generation $hook
    ): void {
        global $DB, $OUTPUT, $PAGE;

        // ── GATE: solo vista de curso, con curso real (no el site, id 1). ──
        if ($PAGE->pagelayout !== 'course' || $PAGE->course->id <= 1) {
            return;
        }

        $course    = $PAGE->course;
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

        $hook->add_html($OUTPUT->render_from_template('theme_cpi/course_banner', $data));
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
