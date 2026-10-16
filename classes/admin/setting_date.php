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

namespace theme_cpi\admin;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Ajuste de fecha AAAA-MM-DD opcional (vacío = sin límite) para los anuncios.
 *
 * Además del formato, comprueba que la fecha exista: una expresión regular dejaría pasar
 * 2026-02-31, y ese anuncio no se publicaría nunca (ver \theme_cpi\local\ads).
 *
 * @package    theme_cpi
 */
class setting_date extends \admin_setting_configtext {

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $visiblename
     * @param string $description
     */
    public function __construct($name, $visiblename, $description) {
        parent::__construct($name, $visiblename, $description, '', PARAM_RAW_TRIMMED, 12);
    }

    /**
     * Valida AAAA-MM-DD y que la fecha exista; vacío es válido.
     *
     * @param string $data
     * @return true|string
     */
    public function validate($data) {
        $data = trim((string) $data);
        if ($data === '') {
            return true;
        }
        $dt = \DateTime::createFromFormat('!Y-m-d', $data);
        if (!$dt || $dt->format('Y-m-d') !== $data) {
            return get_string('ad_date_invalid', 'theme_cpi');
        }
        return true;
    }
}
