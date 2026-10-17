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
use core_user;
use core_user\output\myprofile\tree;
use moodle_url;
use theme_cpi\local\dates;

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
        // user:viewalldetails (los administradores). Para los campos personalizados esto ya
        // lo resuelve is_visible(); esta bandera aplica la misma regla a los nativos (§10).
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

        // --- Escuela: solo Nivel. (Tutor sigue existiendo como campo, pero no se pinta.)
        $escuela = array_values(array_filter([
            $this->custom_row($fields, 'level', 'fa-school', 'Nivel', 'Sin especificar'),
        ]));

        // --- Personal: Género, Celular, Cumpleaños, Ciudad. Teléfono, Estado civil,
        // Dirección y País siguen en la base de datos y en el formulario de edición; solo
        // dejan de pintarse aquí.
        $personal = array_values(array_filter([
            $this->custom_row($fields, 'gender', 'fa-venus-mars', 'Género', 'Sin especificar'),
            $this->native_row($user, 'phone2', 'fa-mobile-alt', 'Celular', $hidden, $canviewpersonal),
            $this->custom_row($fields, 'birthdate', 'fa-birthday-cake', 'Cumpleaños'),
            $this->native_row($user, 'city', 'fa-city', 'Ciudad', $hidden, $canviewpersonal),
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
            ['icon' => 'fa-calendar', 'label' => 'Activo desde',  'value' => dates::format($user->timecreated)],
            ['icon' => 'fa-calendar', 'label' => 'Primer acceso', 'value' => dates::format($user->firstaccess)],
            ['icon' => 'fa-calendar', 'label' => 'Último acceso', 'value' => dates::format($user->lastaccess)],
        ];

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

        // --- Engrane: solo en el perfil propio (el admin edita a otros desde la tarjeta "Más"
        // o desde Administración del sitio).
        $canedit = $currentuser && has_capability('moodle/user:editownprofile', \context_system::instance());
        $editurl = (new moodle_url('/user/edit.php', ['id' => $user->id]))->out(false);

        // --- Mensaje: sustituye al botón del encabezado estándar, que se oculta por CSS
        // (#page-user-profile #page-header). A diferencia del core, no se ofrece en el
        // perfil propio. can_send_message() ya comprueba moodle/site:sendmessage y las
        // preferencias de privacidad del destinatario.
        $canmessage = !$currentuser && !empty($CFG->messaging)
            && \core_message\api::can_send_message($user->id, $USER->id);
        $messageattrs = [];
        if ($canmessage) {
            // Mismos data-* que el botón del core; id propio porque el del encabezado
            // (#message-user-button) sigue en el DOM aunque esté oculto.
            $params = \core_message\helper::messageuser_link_params($user->id);
            $messageattrs = [
                ['name' => 'data-conversationid', 'value' => $params['data-conversationid']],
                ['name' => 'data-userid', 'value' => $params['data-userid']],
            ];
            $this->page->requires->js_call_amd('core_message/message_user_button', 'send',
                ['#cpi-message-user-button']);
        }

        // --- Reenviar credenciales (plugin local_cpiwelcome, si está instalado): botón en
        // "Acerca de mi" para quien tiene moodle/user:update en el sistema y solo si el usuario
        // es elegible (cuenta manual, no suspendida, con correo; nunca el propio ni un admin).
        $resendurl = class_exists('\\local_cpiwelcome\\local\\alta')
            ? \local_cpiwelcome\local\alta::resend_url($user) : null;

        // --- Cursos: el propio alumno y los administradores ven todos, con avance; un
        // compañero, solo los nombres de los que comparte con el usuario visto. Los ocultos,
        // solo los administradores. Sin cursos que mostrar a un compañero, la tarjeta no se
        // pinta.
        $seeall = $canviewpersonal;
        $groups = $this->course_groups($user, $seeall, $canviewpersonal, $canviewall);

        $data = [
            'fullname'      => fullname($user),
            'email'         => (string) $user->email,
            'showemail'     => $this->can_see_email($user, $usercontext, $currentuser, $hidden),
            'avatar'        => $this->output->user_picture($user, ['size' => 124, 'link' => false, 'class' => 'cpi-profile-avatar-img']),
            'description'   => $description,
            'hasdescription' => ($description !== ''),
            'canedit'       => $canedit,
            'editurl'       => $editurl,
            'canmessage'    => $canmessage,
            'messageurl'    => (new moodle_url('/message/index.php', ['id' => $user->id]))->out(false),
            'messageattrs'  => $messageattrs,
            'canresend'     => $resendurl !== null,
            'resendurl'     => $resendurl ? $resendurl->out(false) : '',
            'resendlabel'   => $resendurl ? get_string('resend', 'local_cpiwelcome') : '',

            'escuela'       => $escuela,
            'hasescuela'    => !empty($escuela),
            'personal'      => $personal,
            'haspersonal'   => !empty($personal),
            'redes'         => $redes,
            'hasredes'      => !empty($redes),

            'accesos'       => $accesos,
            'isownprofile'  => $currentuser,

            'coursegroups'  => $groups,
            'hascourses'    => !empty($groups),
            'showcourses'   => !empty($groups) || $seeall,
            'showprogress'  => $canviewpersonal,

            'inmo'          => $this->inmobiliaria($fields, $currentuser, $editurl),

            // Todo lo que trae el perfil estándar (detalles, informes, sesiones…) se conserva
            // en una tarjeta colapsable para no perder funcionalidad de administración.
            // Solo para administradores (moodle/user:viewalldetails), también en su propio
            // perfil. El alumno tiene Calificaciones, Preferencias y Cerrar sesión en el
            // menú de usuario y el Resumen de retención de datos en el pie de página.
            'hasmore'       => $canviewall,
            'more'          => $canviewall ? parent::render_tree($tree) : '',
        ];

        return $this->render_from_template('theme_cpi/myprofile', $data);
    }

    /**
     * Tarjeta "Mi inmobiliaria": campos de la categoría del mismo nombre (visibles para todos).
     *
     * Las URL y el WhatsApp se validan aquí: el inventario solo se enlaza con http/https, y
     * el WhatsApp se limpia a dígitos y se enlaza solo con 10 (o 12 que empiecen con 52, el
     * código de México, que se quita).
     *
     * @param array $fields mapa shortname => profile_field_base
     * @param bool $currentuser
     * @param string $editurl
     * @return array
     */
    private function inmobiliaria(array $fields, bool $currentuser, string $editurl): array {
        $value = function (string $shortname) use ($fields): string {
            if (!isset($fields[$shortname]) || !$fields[$shortname]->is_visible()) {
                return '';
            }
            $v = trim((string) $fields[$shortname]->data);
            return ($v === 'Sin especificar') ? '' : $v;
        };

        $rows = [];
        foreach ([
            ['inmobiliaria', 'fa-building', 'Inmobiliaria'],
            ['zonaoperacion', 'fa-map-marker-alt', 'Zona de operación'],
            ['especialidad', 'fa-tag', 'Especialidad'],
            ['operacion', 'fa-handshake', 'Operación'],
            ['numpropiedades', 'fa-home', 'Propiedades'],
        ] as [$shortname, $icon, $label]) {
            $v = $value($shortname);
            if ($v !== '') {
                $rows[] = ['icon' => $icon, 'label' => $label, 'value' => s($v), 'empty' => false];
            }
        }

        $comparte = ($value('compartecomision') === '1');

        $inventario = '';
        $raw = $value('inventariourl');
        if ($raw !== '') {
            $url = preg_match('#^[a-z][a-z0-9+.-]*://#i', $raw) ? $raw : 'https://' . ltrim($raw, '/');
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            $url = clean_param($url, PARAM_URL);
            if ($url !== '' && in_array($scheme, ['http', 'https'], true)) {
                $inventario = $url;
            }
        }

        $whatsapp = '';
        $digits = preg_replace('/\D+/', '', $value('whatsappnegocio'));
        if (strlen($digits) === 12 && strpos($digits, '52') === 0) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 10) {
            $whatsapp = 'https://wa.me/52' . $digits;
        }

        $has = !empty($rows) || $comparte || $inventario !== '' || $whatsapp !== '';
        return [
            'has'          => $has,
            'rows'         => $rows,
            'comparte'     => $comparte,
            'inventario'   => $inventario,
            'hasinventario' => ($inventario !== ''),
            'whatsapp'     => $whatsapp,
            'haswhatsapp'  => ($whatsapp !== ''),
            'hasbuttons'   => ($inventario !== '' || $whatsapp !== ''),
            'isown'        => $currentuser,
            'editurl'      => $editurl,
        ];
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
        $value = $empty ? $emptytext : $field->display_data();
        // En los menús "Sin especificar" es una opción elegida, no un campo vacío,
        // pero se pinta en gris igual que "Sin tutor" y "-".
        if (!$empty && $field->field->datatype === 'menu'
                && trim(strip_tags((string) $value)) === 'Sin especificar') {
            $empty = true;
        }
        return [
            'icon'  => $icon,
            'label' => $label,
            'value' => $value,
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
     * Tarjeta "Cursos": cursos agrupados por categoría (programa), con avance.
     *
     * Cursos en los que está inscrito el usuario visto. Si el visitante no es el propio
     * usuario ni un administrador ($seeall = false), solo los que comparten ambos. Grupos en
     * el orden de las categorías del sitio y cursos en el orden de su categoría. El nombre
     * enlaza al curso solo si el visitante puede entrar (can_access_course).
     *
     * Avance (solo con $showprogress: el propio alumno y moodle/user:viewalldetails) =
     * \core_completion\progress::get_course_progress_percentage, el mismo del Área personal;
     * null si el curso no tiene seguimiento de finalización. Estado:
     * - completado: curso completado o 100 %;
     * - en curso: avance > 0, o sin seguimiento pero ya entró al curso (user_lastaccess);
     * - sin empezar: lo demás.
     * Avance del programa = promedio de los cursos del grupo con seguimiento.
     *
     * @param \stdClass $user usuario visto
     * @param bool $seeall todos sus cursos (si no, solo los compartidos con el visitante)
     * @param bool $showprogress mostrar avance y estado
     * @param bool $showhidden mostrar cursos ocultos (administradores), con etiqueta "Oculto"
     * @return array grupos para la plantilla
     */
    private function course_groups(\stdClass $user, bool $seeall, bool $showprogress, bool $showhidden): array {
        global $DB, $USER;

        // enrol_get_all_users_courses() y no enrol_get_users_courses(): esta última quita los
        // cursos ocultos que el usuario VISTO no puede ver, y el administrador debe verlos.
        $courses = enrol_get_all_users_courses($user->id, true, 'id, category, fullname, visible, enablecompletion, sortorder',
            'sortorder ASC');
        if (!$seeall) {
            $mine = enrol_get_users_courses($USER->id, true, 'id');
            $courses = array_intersect_key($courses, $mine);
        }
        $courses = array_filter($courses, fn($c) => $c->visible || $showhidden);
        if (!$courses) {
            return [];
        }
        $lastaccess = $showprogress
            ? $DB->get_records_menu('user_lastaccess', ['userid' => $user->id], '', 'courseid, timeaccess') : [];
        $categories = $DB->get_records_list('course_categories', 'id', array_unique(array_column($courses, 'category')),
            'sortorder', 'id, name, sortorder');

        $groups = [];
        foreach ($categories as $category) {
            $rows = [];
            $percentages = [];
            $completed = 0;
            foreach ($courses as $course) {
                if ($course->category != $category->id) {
                    continue;
                }
                $coursecontext = \context_course::instance($course->id);
                $row = [
                    'name'    => format_string($course->fullname, true, ['context' => $coursecontext]),
                    'url'     => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                    'haslink' => can_access_course($course, $USER),
                    'hidden'  => !$course->visible,
                ];
                if ($showprogress) {
                    $pct = \core_completion\progress::get_course_progress_percentage($course, $user->id);
                    $pct = $pct === null ? null : (int) floor($pct);
                    if ($pct === 100) {
                        $status = 'done';
                        $completed++;
                    } else if ($pct > 0 || ($pct === null && !empty($lastaccess[$course->id]))) {
                        $status = 'doing';
                    } else {
                        $status = 'todo';
                    }
                    if ($pct !== null) {
                        $percentages[] = $pct;
                    }
                    $row += [
                        'hasprogress' => $pct !== null,
                        'percent'     => (int) $pct,
                        'isdone'      => $status === 'done',
                        'isdoing'     => $status === 'doing',
                        'istodo'      => $status === 'todo',
                        'statuslabel' => ['done' => 'Completado', 'doing' => 'En curso', 'todo' => 'Sin empezar'][$status],
                    ];
                }
                $rows[] = $row;
            }
            if (!$rows) {
                continue;
            }
            $groups[] = [
                'name'        => format_string($category->name, true, ['context' => \context_coursecat::instance($category->id)]),
                'courses'     => $rows,
                'total'       => count($rows),
                'completed'   => $completed,
                'hasprogress' => !empty($percentages),
                'percent'     => $percentages ? (int) round(array_sum($percentages) / count($percentages)) : 0,
                'open'        => empty($groups), // El primer programa abierto, los demás cerrados.
            ];
        }
        return $groups;
    }
}
