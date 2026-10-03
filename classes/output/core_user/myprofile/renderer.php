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

namespace theme_cpi\output\core_user\myprofile;

use context_user;
use core_tag_tag;
use core_user;
use core_user\output\myprofile\tree;
use moodle_url;

/**
 * Página de perfil de CPI: réplica del perfil de welearning.
 *
 * El factory de renderers resuelve este override porque el tema declara
 * $THEME->rendererfactory = 'theme_overridden_renderer_factory' (config.php) y la clase
 * sigue el patrón prefijo + subtipo que arma renderer_factory_base::standard_renderer_classnames():
 * theme_cpi\output\core_user\myprofile\renderer para get_renderer('core_user', 'myprofile').
 *
 * Referencia: perfil-welearning.md (§3 iconos, §4 colores/medidas, §5 fechas, §10 decisiones).
 * El layout de escritorio es el de §10: dos columnas, sin banda de portada.
 *
 * Nada de lo que se pinta aquí salta la seguridad del core: cada campo personalizado se
 * consulta con profile_field_base::is_visible(), que ya resuelve PROFILE_VISIBLE_* contra
 * el visitante, y los campos nativos se filtran con $CFG->hiddenuserfields y capacidades.
 */
class renderer extends \core_user\output\myprofile\renderer {

    /** @var string[] Abreviaturas de mes en español (§5). */
    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    /**
     * Pinta el árbol del perfil con la plantilla de CPI.
     *
     * Si algo no encaja (contexto que no es de usuario, usuario inexistente) se cae con
     * elegancia al render estándar de Moodle en vez de romper la página.
     *
     * @param tree $tree
     * @return string
     */
    public function render_tree(tree $tree) {
        global $CFG, $USER;

        $context = $this->page->context;
        if (empty($context) || $context->contextlevel != CONTEXT_USER) {
            return parent::render_tree($tree);
        }

        $user = core_user::get_user($context->instanceid);
        if (!$user || !empty($user->deleted)) {
            return parent::render_tree($tree);
        }

        require_once($CFG->dirroot . '/user/profile/lib.php');

        $usercontext  = context_user::instance($user->id);
        $currentuser  = ($user->id == $USER->id);
        $canviewall   = has_capability('moodle/user:viewalldetails', $usercontext);
        // "Personal" y "Escuela" son privados: solo el propio usuario y quien tenga
        // user:viewalldetails. Para los campos personalizados esto ya lo resuelve
        // is_visible(); esta bandera aplica la misma regla a los nativos (§10).
        $canviewpersonal = $currentuser || $canviewall;

        // Campos personalizados, indexados por shortname.
        $fields = [];
        foreach (profile_get_user_fields_with_data_by_category($user->id) as $catfields) {
            foreach ($catfields as $field) {
                $fields[$field->field->shortname] = $field;
            }
        }

        // Campos nativos que el sitio puede estar ocultando.
        $hidden = [];
        if (!has_capability('moodle/user:viewhiddendetails', $usercontext)) {
            $hidden = array_flip(explode(',', (string) $CFG->hiddenuserfields));
        }

        // --- Escuela (§1): Nivel, Tutor.
        $escuela = array_values(array_filter([
            $this->custom_row($fields, 'level', 'fa-school', 'Nivel'),
            $this->custom_row($fields, 'guardian', 'fa-user-graduate', 'Tutor', 'Sin tutor'),
        ]));

        // --- Personal (§1), en el orden de welearning.
        $personal = array_values(array_filter([
            $this->custom_row($fields, 'gender', 'fa-venus-mars', 'Género', 'Sin especificar'),
            $this->native_row($user, 'phone1', 'fa-phone-alt', 'Teléfono', $hidden, $canviewpersonal),
            $this->native_row($user, 'phone2', 'fa-mobile', 'Celular', $hidden, $canviewpersonal),
            $this->custom_row($fields, 'birthdate', 'fa-birthday-cake', 'Cumpleaños'),
            $this->custom_row($fields, 'maritalstatus', 'fa-heart', 'Estado civil', 'Sin especificar'),
            $this->native_row($user, 'address', 'fa-map-marker-alt', 'Dirección', $hidden, $canviewpersonal),
            $this->native_row($user, 'city', 'fa-city', 'Ciudad', $hidden, $canviewpersonal),
            $this->native_row($user, 'country', 'fa-globe-americas', 'País', $hidden, $canviewpersonal),
        ]));

        // --- Redes sociales (§3, §6, §10): los 6 iconos siempre, con enlace solo si hay valor.
        $redes = [];
        $networks = [
            'facebook'  => ['fa-facebook-square', 'Facebook'],
            'twitter'   => ['fa-square-x-twitter', 'X'],
            'linkedin'  => ['fa-linkedin', 'LinkedIn'],
            'instagram' => ['fa-instagram', 'Instagram'],
            'youtube'   => ['fa-youtube-square', 'YouTube'],
            'skype'     => ['fa-skype', 'Skype'],
        ];
        foreach ($networks as $shortname => [$icon, $title]) {
            if (!isset($fields[$shortname]) || !$fields[$shortname]->is_visible()) {
                continue;
            }
            $field = $fields[$shortname];
            $value = trim((string) $field->data);
            $redes[] = [
                'icon'    => $icon,
                'title'   => $title,
                'haslink' => ($value !== ''),
                'url'     => ($value === '') ? '' : $this->social_url($value),
            ];
        }

        // --- Accesos (§5). Formato D-Mmm-AAAA hh:mm am/pm.
        $accesos = [
            ['icon' => 'fa-calendar', 'label' => 'Activo desde',  'value' => $this->cpi_date($user->timecreated)],
            ['icon' => 'fa-calendar', 'label' => 'Primer acceso', 'value' => $this->cpi_date($user->firstaccess)],
            ['icon' => 'fa-calendar', 'label' => 'Último acceso', 'value' => $this->cpi_date($user->lastaccess)],
        ];

        // --- Habilidades e intereses (tags del core).
        $intereses = [];
        if (!empty($CFG->usetags)) {
            foreach (core_tag_tag::get_item_tags('core', 'user', $user->id) as $tag) {
                $intereses[] = [
                    'name' => $tag->get_display_name(false),
                    'url'  => $tag->get_view_url()->out(false),
                ];
            }
        }

        // --- Descripción: va dentro de la tarjeta (§10). profile.php la imprime antes del
        // árbol; esa copia se oculta por CSS (.userprofile > .description) para no duplicarla.
        $description = '';
        if (!empty($user->description) && !isset($hidden['description'])) {
            $description = format_text(
                file_rewrite_pluginfile_urls(
                    $user->description, 'pluginfile.php', $usercontext->id, 'user', 'profile', null
                ),
                $user->descriptionformat
            );
        }

        // --- Engrane: editar perfil.
        $canedit = ($currentuser && has_capability('moodle/user:editownprofile', \context_system::instance()))
            || has_capability('moodle/user:update', $usercontext);

        $data = [
            'fullname'      => fullname($user),
            'email'         => (string) $user->email,
            'showemail'     => $this->can_see_email($user, $usercontext, $currentuser, $hidden),
            'avatar'        => $this->output->user_picture($user, ['size' => 124, 'link' => false, 'class' => 'cpi-profile-avatar-img']),
            'description'   => $description,
            'hasdescription' => ($description !== ''),
            'canedit'       => $canedit,
            'editurl'       => (new moodle_url('/user/edit.php', ['id' => $user->id]))->out(false),

            'escuela'       => $escuela,
            'hasescuela'    => !empty($escuela),
            'personal'      => $personal,
            'haspersonal'   => !empty($personal),
            'redes'         => $redes,
            'hasredes'      => !empty($redes),

            'accesos'       => $accesos,

            // El texto de "sin intereses" y el botón de añadir solo tienen sentido en el
            // perfil propio: a un visitante no se le invita a editar lo que no es suyo.
            'isownprofile'  => $currentuser,
            'intereses'     => $intereses,
            'hasintereses'  => !empty($intereses),
            'interesesempty' => $currentuser
                ? 'Da clic en el botón de ⊕ para agregar habilidades e intereses a tu perfil.'
                : 'Sin habilidades o intereses',

            'courses'       => $this->courses_for($user, $usercontext, $currentuser),

            // Todo lo que trae el perfil estándar (cursos, informes, sesiones…) se conserva
            // en una tarjeta colapsable para no perder funcionalidad de administración.
            // Solo para el propio usuario y para quien administra: a un compañero de curso
            // no se le amplía lo que ya ve en las tarjetas de arriba.
            'hasmore'       => $canviewpersonal,
            'more'          => $canviewpersonal ? parent::render_tree($tree) : '',
        ];
        $data['hascourses'] = !empty($data['courses']);

        return $this->render_from_template('theme_cpi/myprofile', $data);
    }

    /**
     * Fila de un campo de perfil personalizado, respetando su visibilidad.
     *
     * @param array  $fields    mapa shortname => profile_field_base
     * @param string $shortname
     * @param string $icon      clase FA sin el prefijo "fas"
     * @param string $label
     * @param string $emptytext texto cuando el campo está vacío
     * @return array|null null si el campo no existe o el visitante no puede verlo
     */
    private function custom_row(array $fields, string $shortname, string $icon, string $label,
            string $emptytext = '-'): ?array {
        if (!isset($fields[$shortname])) {
            return null;
        }
        $field = $fields[$shortname];
        // is_visible() ya resuelve PROFILE_VISIBLE_* contra el usuario que mira.
        if (!$field->is_visible()) {
            return null;
        }
        $empty = $field->is_empty();
        return [
            'icon'  => $icon,
            'label' => $label,
            'value' => $empty ? $emptytext : $field->display_data(),
            'empty' => $empty,
        ];
    }

    /**
     * Fila de un campo nativo del usuario (teléfono, ciudad, país…).
     *
     * @param \stdClass $user
     * @param string $name nombre de la propiedad en el registro de usuario
     * @param string $icon
     * @param string $label
     * @param array  $hidden campos ocultos por $CFG->hiddenuserfields
     * @param bool   $canview si el visitante puede ver los datos personales
     * @return array|null
     */
    private function native_row(\stdClass $user, string $name, string $icon, string $label,
            array $hidden, bool $canview): ?array {
        if (!$canview || isset($hidden[$name])) {
            return null;
        }
        $value = trim((string) ($user->$name ?? ''));
        if ($name === 'country' && $value !== '') {
            $countries = get_string_manager()->get_list_of_countries(true);
            $value = $countries[$value] ?? $value;
        }
        $empty = ($value === '');
        return [
            'icon'  => $icon,
            'label' => $label,
            'value' => $empty ? '-' : s($value),
            'empty' => $empty,
        ];
    }

    /**
     * ¿Se le puede enseñar el email al visitante?
     *
     * @param \stdClass $user
     * @param \context $usercontext
     * @param bool $currentuser
     * @param array $hidden
     * @return bool
     */
    private function can_see_email(\stdClass $user, \context $usercontext, bool $currentuser, array $hidden): bool {
        if (isset($hidden['email'])) {
            return false;
        }
        return $currentuser
            || $user->maildisplay == core_user::MAILDISPLAY_EVERYONE
            || has_capability('moodle/course:useremail', $usercontext)
            || has_capability('moodle/user:viewalldetails', $usercontext);
    }

    /**
     * URL de una red social. El campo guarda texto libre; si no parece una URL se le
     * antepone https:// para poder enlazarlo (§10: el icono con valor va envuelto en <a>).
     *
     * @param string $value
     * @return string
     */
    private function social_url(string $value): string {
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }
        return 'https://' . ltrim($value, '/');
    }

    /**
     * Fecha con el formato de welearning (§5): D-Mmm-AAAA hh:mm am/pm.
     * Día sin cero inicial, mes abreviado en español, hora de 12 h con dos dígitos.
     *
     * @param int|null $timestamp
     * @return string
     */
    private function cpi_date(?int $timestamp): string {
        if (empty($timestamp)) {
            return '-';
        }
        $d = usergetdate($timestamp);
        $hour = $d['hours'] % 12;
        if ($hour === 0) {
            $hour = 12;
        }
        return sprintf(
            '%d-%s-%d %02d:%02d %s',
            $d['mday'],
            self::MESES[$d['mon'] - 1],
            $d['year'],
            $hour,
            $d['minutes'],
            $d['hours'] < 12 ? 'am' : 'pm'
        );
    }

    /**
     * Tarjeta "Cursos" (§10): Nombre del curso | Categoría | Profesor.
     *
     * Mismo criterio de acceso que el perfil estándar: el propio usuario o quien tenga
     * moodle/user:viewdetails. Se omiten los cursos ocultos que el visitante no puede ver.
     *
     * @param \stdClass $user
     * @param \context $usercontext
     * @param bool $currentuser
     * @return array
     */
    private function courses_for(\stdClass $user, \context $usercontext, bool $currentuser): array {
        if (!$currentuser && !has_capability('moodle/user:viewdetails', $usercontext)) {
            return [];
        }

        $rows = [];
        foreach (enrol_get_users_courses($user->id, true, ['category']) as $course) {
            $coursecontext = \context_course::instance($course->id);
            if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                continue;
            }

            $teachers = [];
            foreach (get_role_users(0, $coursecontext, false, 'u.id, u.firstname, u.lastname, r.archetype',
                    null, true, '', '', '', 'r.archetype IN (:a1, :a2)',
                    ['a1' => 'editingteacher', 'a2' => 'teacher']) as $t) {
                $teachers[$t->id] = fullname($t);
            }

            $category = \core_course_category::get($course->category, IGNORE_MISSING, true);
            $rows[] = [
                'name'        => format_string($course->fullname, true, ['context' => $coursecontext]),
                'url'         => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                'category'    => $category ? format_string($category->name) : '-',
                'teachers'    => implode(', ', $teachers),
                'hasteachers' => !empty($teachers),
            ];
        }
        return $rows;
    }
}
