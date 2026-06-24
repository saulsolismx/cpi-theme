<?php
namespace theme_cpi\local\hooks\output;

defined('MOODLE_INTERNAL') || die();

class before_standard_head_html_generation {
    public static function callback(\core\hook\output\before_standard_head_html_generation $hook): void {
        $links = '<link rel="preconnect" href="https://fonts.googleapis.com">'
               . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
               . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap">';
        $hook->add_html($links);
    }
}
