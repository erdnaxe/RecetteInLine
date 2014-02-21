<?php
/* 
 * Language gestion
 * --------------------------
 * This code is a part of the RecetteInLine project.
 * He has been created by erdnaxe.
 * This project is under GPLv2 license.
 */

// Change the language if specified
if (null !== (filter_input(INPUT_GET, 'lang'))) {
    switch (filter_input(INPUT_GET, 'lang')) {
        case "en":
            Atomik\Translations::set('en');
            break;
        case "fr":
            Atomik\Translations::set('fr');
            break;
    }
}