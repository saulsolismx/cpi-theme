<?php
defined('MOODLE_INTERNAL') || die();
$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => \theme_cpi\local\hooks\output\before_standard_head_html_generation::class . '::callback',
    ],
    // Alumnos en /user/view.php (perfil dentro de curso) → perfil de CPI (/user/profile.php).
    [
        'hook' => \core\hook\output\before_http_headers::class,
        'callback' => \theme_cpi\local\hooks\output\before_http_headers::class . '::callback',
    ],
    // El banner de curso ya NO se inyecta por hook (top_of_body quedaba fuera de #page
    // y desalineaba). Ahora lo renderiza \theme_cpi\output\core_renderer::full_header()
    // vía \theme_cpi\local\course_banner, dentro del contenedor del contenido.
];
