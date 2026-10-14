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
 * Formato de fechas de CPI (perfil-welearning.md §5), compartido por el perfil
 * (tarjeta Accesos) y el bloque "Usuario identificado" del dashboard.
 *
 * D-Mmm-AAAA [hh:mm am/pm]: día sin cero inicial, mes abreviado en español y hora de
 * 12 h con dos dígitos. Usa usergetdate(), así que respeta la zona horaria del usuario
 * (forzada a America/Mexico_City en el sitio).
 *
 * @package    theme_cpi
 */
class dates {

    /** @var string[] Abreviaturas de mes en español (§5). */
    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    /**
     * Fecha con el formato de CPI, o "-" si no hay fecha.
     *
     * @param int|null $timestamp
     * @param bool $withtime true: D-Mmm-AAAA hh:mm am/pm; false: solo D-Mmm-AAAA
     * @return string
     */
    public static function format(?int $timestamp, bool $withtime = true): string {
        if (empty($timestamp)) {
            return '-';
        }
        $d = usergetdate($timestamp);
        $date = sprintf('%d-%s-%d', $d['mday'], self::MESES[$d['mon'] - 1], $d['year']);
        if (!$withtime) {
            return $date;
        }
        $hour = $d['hours'] % 12;
        if ($hour === 0) {
            $hour = 12;
        }
        return sprintf('%s %02d:%02d %s', $date, $hour, $d['minutes'], $d['hours'] < 12 ? 'am' : 'pm');
    }
}
