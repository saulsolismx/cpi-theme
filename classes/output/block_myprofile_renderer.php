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

use block_myprofile\output\myprofile;
use theme_cpi\local\dates;

/**
 * Bloque "Usuario identificado" (block_myprofile) con el diseño de CPI.
 *
 * Lo resuelve el factory de renderers del tema (theme_overridden_renderer_factory) para
 * get_renderer('block_myprofile'). Sin subtipo, el nombre que busca es
 * theme_cpi\output\{componente}_renderer (renderer_factory_base::standard_renderer_classnames);
 * la forma theme_cpi\output\{componente}\{subtipo}\renderer es solo para subtipos, como
 * el perfil en core_user\myprofile.
 *
 * El bloque de 5.2.1 solo exporta un campo si está encendido Y tiene valor, así que no
 * distingue "apagado" de "vacío". Aquí se lee la configuración de la instancia para pintar
 * "-" en gris cuando un campo encendido está vacío (como en el perfil), y se agrega la
 * fecha de inscripción (user->timecreated), que el bloque no trae.
 *
 * @package    theme_cpi
 */
class block_myprofile_renderer extends \block_myprofile\output\renderer {

    /** @var int Alto del avatar en px. */
    private const AVATAR_SIZE = 96;

    /**
     * Contenido del bloque.
     *
     * @param myprofile $myprofile
     * @return string
     */
    public function render_myprofile(myprofile $myprofile) {
        global $USER;

        // La configuración es protected en el renderable: se lee sin modificar el core.
        $config = (function () {
            return $this->config;
        })->call($myprofile) ?: new \stdClass();
        $on = function (string $key, bool $default) use ($config): bool {
            return isset($config->{'display_' . $key}) ? (bool) $config->{'display_' . $key} : $default;
        };

        $rows = [];
        // Mismos valores por defecto que block_myprofile (country, city, email encendidos).
        if ($on('phone2', false)) {
            $rows[] = $this->row('fa-mobile-alt', 'Celular', s(trim((string) $USER->phone2)));
        }
        if ($on('phone1', false)) {
            $rows[] = $this->row('fa-phone-alt', 'Teléfono', s(trim((string) $USER->phone1)));
        }
        if ($on('email', true)) {
            $rows[] = $this->row('fa-envelope', 'Correo',
                trim((string) $USER->email) === '' ? '' : obfuscate_mailto($USER->email, ''), true);
        }
        if ($on('city', true)) {
            $rows[] = $this->row('fa-city', 'Ciudad', s(trim((string) $USER->city)));
        }
        if ($on('country', true)) {
            $countries = get_string_manager()->get_list_of_countries(true);
            $rows[] = $this->row('fa-globe-americas', 'País', s($countries[$USER->country] ?? ''));
        }
        $rows[] = $this->row('fa-calendar', 'Fecha de inscripción', dates::format((int) $USER->timecreated, false));

        $data = [
            'showpicture' => $on('picture', true),
            'userpicture' => $this->output->user_picture($USER, [
                'size' => self::AVATAR_SIZE, 'class' => 'cpi-myprofile-avatar-img', 'link' => true,
            ]),
            'userfullname' => fullname($USER),
            'rows' => $rows,
        ];
        return $this->render_from_template('block_myprofile/myprofile', $data);
    }

    /**
     * Fila de datos: icono navy, etiqueta y valor; "-" en gris si está vacío.
     *
     * @param string $icon clase FA (sin el prefijo "fas")
     * @param string $label
     * @param string $value valor ya escapado
     * @param bool $wrap el valor puede partirse (correo largo)
     * @return array
     */
    private function row(string $icon, string $label, string $value, bool $wrap = false): array {
        $empty = ($value === '' || $value === '-');
        return [
            'icon' => $icon,
            'label' => $label,
            'value' => $empty ? '-' : $value,
            'empty' => $empty,
            'wrap' => $wrap,
        ];
    }
}
