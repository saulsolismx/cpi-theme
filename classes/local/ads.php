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
 * Anuncios del dashboard (Administración del sitio > Apariencia > Ajustes de personalización de tema > Publicidad).
 *
 * Se pintan arriba de la lista de cursos, en la columna central del dashboard
 * (layout/dashboard.php → templates/dashboard.mustache). Solo los activos, con imagen y
 * dentro de su rango de fechas en America/Mexico_City: inicio desde las 00:00 y fin hasta
 * las 23:59:59 de esas fechas; vacías = sin límite. 1 anuncio → imagen fija; 2 o más →
 * carrusel de Bootstrap (6 s, puntos, pausa al pasar el mouse). Sin anuncios: nada.
 *
 * @package    theme_cpi
 */
class ads {

    /** @var int Número de anuncios configurables. */
    public const MAX = 6;

    /** @var string Zona de las fechas de inicio/fin (todos los alumnos están en México). */
    private const TIMEZONE = 'America/Mexico_City';

    /**
     * Anuncios vigentes en el momento dado, ya ordenados.
     *
     * @param int|null $now timestamp de referencia (por defecto, ahora)
     * @return array[] [{index, number, first, image, imagemobile, hasmobile, url, haslink, alt}]
     */
    public static function current(?int $now = null): array {
        $now = $now ?? time();
        $theme = \theme_config::load('cpi');
        $config = get_config('theme_cpi');

        $ads = [];
        for ($i = 1; $i <= self::MAX; $i++) {
            if (empty($config->{"ad{$i}active"}) || empty($config->{"ad{$i}image"})) {
                continue;
            }
            $start = self::day_bound($config->{"ad{$i}start"} ?? '', false);
            $end = self::day_bound($config->{"ad{$i}end"} ?? '', true);
            // Fecha imposible (p. ej. 2026-02-31): no se publica, para no dejar un anuncio
            // vencido "sin límite" por un error de captura.
            if ($start === false || $end === false) {
                continue;
            }
            if (($start !== null && $now < $start) || ($end !== null && $now > $end)) {
                continue;
            }
            $url = clean_param(trim((string) ($config->{"ad{$i}url"} ?? '')), PARAM_URL);
            $mobile = empty($config->{"ad{$i}imagemobile"}) ? '' : $theme->setting_file_url("ad{$i}imagemobile", "ad{$i}imagemobile");
            $ads[] = [
                'slot' => $i,
                'order' => (int) ($config->{"ad{$i}order"} ?? $i),
                'image' => $theme->setting_file_url("ad{$i}image", "ad{$i}image"),
                'imagemobile' => $mobile,
                'hasmobile' => $mobile !== '',
                'url' => $url,
                'haslink' => $url !== '',
                'alt' => trim((string) ($config->{"ad{$i}alt"} ?? '')),
            ];
        }

        usort($ads, fn($a, $b) => [$a['order'], $a['slot']] <=> [$b['order'], $b['slot']]);
        foreach ($ads as $k => &$ad) {
            $ad['index'] = $k;
            $ad['number'] = $k + 1;
            $ad['first'] = ($k === 0);
        }
        return $ads;
    }

    /**
     * HTML de los anuncios para el dashboard, o '' si no hay ninguno vigente.
     *
     * @param \renderer_base $output
     * @return string
     */
    public static function html(\renderer_base $output): string {
        global $PAGE;

        $ads = self::current();
        if (!$ads) {
            return '';
        }
        $id = \html_writer::random_id('cpiads');
        $multiple = count($ads) > 1;
        if ($multiple) {
            // El data-api de Bootstrap arranca el autoplay en el evento "load" de window, que
            // puede llegar antes que el loader AMD de Boost: se inicializa explícitamente.
            $PAGE->requires->js_amd_inline("require(['theme_boost/bootstrap/carousel'], function(Carousel) {
                var el = document.getElementById('{$id}');
                if (el) { Carousel.getOrCreateInstance(el).cycle(); }
            });");
        }
        return $output->render_from_template('theme_cpi/ads', [
            'id' => $id,
            'multiple' => $multiple,
            'ads' => $ads,
        ]);
    }

    /**
     * Timestamp del inicio (00:00:00) o fin (23:59:59) de una fecha AAAA-MM-DD en la zona de CPI.
     *
     * @param string $date AAAA-MM-DD o vacío
     * @param bool $endofday
     * @return int|false|null null si está vacía (= sin límite); false si no es una fecha válida
     */
    private static function day_bound(string $date, bool $endofday): int|false|null {
        $date = trim($date);
        if ($date === '') {
            return null;
        }
        $dt = \DateTime::createFromFormat('!Y-m-d', $date, new \DateTimeZone(self::TIMEZONE));
        if (!$dt || $dt->format('Y-m-d') !== $date) {
            return false;
        }
        if ($endofday) {
            $dt->setTime(23, 59, 59);
        }
        return $dt->getTimestamp();
    }
}
