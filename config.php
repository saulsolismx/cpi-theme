<?php
defined('MOODLE_INTERNAL') || die();
$THEME->name = 'cpi';
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->parents = ['boost'];
$THEME->enable_dock = false;
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->scss = 'theme_cpi_get_main_scss_content';
$THEME->prescsscallback = 'theme_cpi_get_pre_scss';
$THEME->extrascsscallback = 'theme_cpi_get_extra_scss';
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->haspagelayouts = false;
$THEME->usescourseindex = true;
// Sin las hojas CSS de YUI 3 (cssreset, cssfonts, cssgrids, cssbase), igual que Boost.
// Esta propiedad NO se hereda del tema padre: sin declararla, theme_config carga las 4
// por defecto. cssreset ponía html { background: #FFF } (el body gris solo cubría la
// primera pantalla: franja blanca al hacer scroll) y cssbase márgenes de listas y bordes
// de celdas que Boost no espera.
$THEME->yuicssmodules = [];

// Dashboard del alumno: layout propio de 3 columnas (25/50/25, sin drawer de bloques).
// Override SOLO de 'mydashboard'; los demás layouts se heredan de boost (cascada).
$THEME->layouts['mydashboard'] = [
    'file' => 'dashboard.php',
    'regions' => ['content-left', 'content-right'],
    'defaultregion' => 'content-left',
    'options' => ['nonavbar' => false, 'langmenu' => true],
];
