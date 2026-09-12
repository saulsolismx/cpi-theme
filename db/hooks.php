<?php
defined('MOODLE_INTERNAL') || die();
$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => \theme_cpi\local\hooks\output\before_standard_head_html_generation::class . '::callback',
    ],
    // El banner de curso ya NO se inyecta por hook (top_of_body quedaba fuera de #page
    // y desalineaba). Ahora lo renderiza \theme_cpi\output\core_renderer::full_header()
    // vía \theme_cpi\local\course_banner, dentro del contenedor del contenido.
];
