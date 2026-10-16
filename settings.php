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

/**
 * Ajustes del tema CPI: página "Publicidad" (carrusel del dashboard).
 *
 * Solo la ven quienes tienen moodle/site:config (los administradores del sitio); no se
 * crean roles ni capacidades nuevas. Los anuncios se pintan con \theme_cpi\local\ads.
 *
 * @package    theme_cpi
 */

defined('MOODLE_INTERNAL') || die();

// Sin la página genérica del tema: una categoría propia con la página "Publicidad".
$settings = null;

if ($hassiteconfig) {
    $ADMIN->add('themes', new admin_category('theme_cpi', get_string('configtitle', 'theme_cpi')));

    $page = new admin_settingpage('theme_cpi_ads', get_string('ads', 'theme_cpi'));
    if ($ADMIN->fulltree) {
        $page->add(new admin_setting_heading('theme_cpi/adsintro', '', get_string('ads_intro', 'theme_cpi')));

        $orders = array_combine(range(1, \theme_cpi\local\ads::MAX), range(1, \theme_cpi\local\ads::MAX));

        for ($i = 1; $i <= \theme_cpi\local\ads::MAX; $i++) {
            $a = (object) ['n' => $i];
            $page->add(new admin_setting_heading("theme_cpi/ad{$i}heading", get_string('ad_heading', 'theme_cpi', $a), ''));

            $page->add(new admin_setting_configcheckbox("theme_cpi/ad{$i}active",
                get_string('ad_active', 'theme_cpi'), get_string('ad_active_desc', 'theme_cpi'), 0));

            $setting = new admin_setting_configstoredfile("theme_cpi/ad{$i}image",
                get_string('ad_image', 'theme_cpi'), get_string('ad_image_desc', 'theme_cpi'),
                "ad{$i}image", 0, ['maxfiles' => 1, 'accepted_types' => ['web_image']]);
            // Nueva revisión del tema para que el navegador no muestre la imagen anterior.
            $setting->set_updatedcallback('theme_reset_all_caches');
            $page->add($setting);

            $setting = new admin_setting_configstoredfile("theme_cpi/ad{$i}imagemobile",
                get_string('ad_imagemobile', 'theme_cpi'), get_string('ad_imagemobile_desc', 'theme_cpi'),
                "ad{$i}imagemobile", 0, ['maxfiles' => 1, 'accepted_types' => ['web_image']]);
            $setting->set_updatedcallback('theme_reset_all_caches');
            $page->add($setting);

            $page->add(new admin_setting_configtext("theme_cpi/ad{$i}url",
                get_string('ad_url', 'theme_cpi'), get_string('ad_url_desc', 'theme_cpi'), '', PARAM_URL));

            $page->add(new admin_setting_configtext("theme_cpi/ad{$i}alt",
                get_string('ad_alt', 'theme_cpi'), get_string('ad_alt_desc', 'theme_cpi'), '', PARAM_TEXT));

            // AAAA-MM-DD o vacío (vacío = sin límite); se valida que la fecha exista.
            $page->add(new \theme_cpi\admin\setting_date("theme_cpi/ad{$i}start",
                get_string('ad_start', 'theme_cpi'), get_string('ad_start_desc', 'theme_cpi')));

            $page->add(new \theme_cpi\admin\setting_date("theme_cpi/ad{$i}end",
                get_string('ad_end', 'theme_cpi'), get_string('ad_end_desc', 'theme_cpi')));

            $page->add(new admin_setting_configselect("theme_cpi/ad{$i}order",
                get_string('ad_order', 'theme_cpi'), get_string('ad_order_desc', 'theme_cpi'), $i, $orders));
        }
    }
    $ADMIN->add('theme_cpi', $page);
}
