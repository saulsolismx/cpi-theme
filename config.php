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
