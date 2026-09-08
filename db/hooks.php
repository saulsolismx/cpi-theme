<?php
defined('MOODLE_INTERNAL') || die();
$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => \theme_cpi\local\hooks\output\before_standard_head_html_generation::class . '::callback',
    ],
    [
        'hook' => \core\hook\output\before_standard_top_of_body_html_generation::class,
        'callback' => \theme_cpi\local\hooks\output\before_standard_top_of_body_html_generation::class . '::callback',
    ],
];
