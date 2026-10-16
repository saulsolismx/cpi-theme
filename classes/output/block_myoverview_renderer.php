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

use block_myoverview\output\main;
use theme_cpi\local\ads;

/**
 * Bloque de cursos (block_myoverview) con los anuncios de CPI arriba de los filtros.
 *
 * Nombre theme_cpi\output\{componente}_renderer: el que busca el factory sin subtipo
 * (ver block_myprofile_renderer). El anuncio queda DENTRO de la tarjeta del bloque, en
 * .card-text.content, con el mismo ancho que la fila de filtros; la cuadrícula de cursos
 * no cambia. Solo en el dashboard (layout mydashboard): /my/courses.php usa el mismo
 * bloque y el mismo pagetype my-index, pero el layout mycourses.
 *
 * @package    theme_cpi
 */
class block_myoverview_renderer extends \block_myoverview\output\renderer {

    /**
     * Contenido del bloque, con los anuncios vigentes delante en el dashboard.
     *
     * @param main $main
     * @return string
     */
    public function render_main(main $main) {
        $html = parent::render_main($main);
        if ($this->page->pagelayout !== 'mydashboard') {
            return $html;
        }
        return ads::html($this->output) . $html;
    }
}
