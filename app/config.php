<?php
/*
 * Atomik Config
 * --------------------------
 * This code is a part of the RecetteInLIne project.
 * He has been created by erdnaxe.
 * This project is under GPLv2 license.
 */
Atomik::set(array(
    'plugins' => array(
        'Errors' => array(
            'catch_errors' => true
        ),
        'Translations',
        'Session',
        'Flash'
    ),
    'app.layout' => '_layout'
));
