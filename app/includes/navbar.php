<?php
/*
 * Nav Bar
 * --------------------------
 * This code is a part of the RecetteInLIne project.
 * He has been created by erdnaxe.
 * This project is under GPLv2 license.
 */

// Get the page
if (null !== (filter_input(INPUT_GET, 'action'))) {
    $action = filter_input(INPUT_GET, 'action');
} else {
    $action = null;
}

/*
 * Function to add a simple button
 * @arg string lang
 */

function showButtonNavBar($name, $url, $actualAction, $specialArg = "") {
    echo "<li";
    if ($actualAction == $url) {
        echo ' class="active"';
    }
    echo ">";
    echo '<a href="' . Atomik::url($url) . '" ' . $specialArg . '>' . $name . '</a>';
    echo '</li>';
}

/*
 * Function to add a language button
 * @arg string lang
 */

function showLanguageNavBar($lang) {
    $currentLanguage = Atomik::get('app.language');
    echo "<li";
    if ($currentLanguage == $lang) {
        echo ' class="active"';
    }
    echo ">";
    echo '<a href="' . Atomik::url('', array("lang" => $lang)) . '"'
    . ' style="padding-right: 5px; padding-left: 5px">'
    . strtoupper($lang) . '</a>';
    echo '</li>';
}
?>
<nav class="navbar navbar-default" role="navigation">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?php echo Atomik::url('index'); ?>">
                <span class="glyphicon glyphicon-cutlery"></span>
                <b>Recette</b>InLine <span class="label label-warning">BETA</span>
            </a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <?php
                // Home
                showButtonNavBar(Atomik::translate("index"), 'index', $action);

                // Recipes explorer
                showButtonNavBar(Atomik::translate("explore"), 'explore', $action, 'style="padding-right: 5px"');
                ?>
                <li class="dropdown
                <?php
                if ($action == "explore" || $action == "viewer") {
                    echo " active";
                }
                ?>
                    ">
                    <a href="#" style="padding-left: 5px" class="dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li class="dropdown-header"><span class="badge pull-right">42</span> Catégorie 1</li>
                        <li>
                            <a href="<?php echo Atomik::url('viewer', array('id' => 'onefile')); ?>">
                                Exemple...
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li class="dropdown-header"><span class="badge pull-right">5</span> Catégorie 2</li>
                        <li>
                            <a href="<?php echo Atomik::url('viewer'); ?>">
                                Exemple...
                            </a>
                        </li>
                    </ul>
                </li>

                <?php
                // New recipes
                ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <?php echo Atomik::translate("newRecipes"); ?> <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li>
                            <a href="<?php echo Atomik::url('newRecipes'); ?>">
                                <?php echo Atomik::translate("newRecipes"); ?>
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li class="dropdown-header">
                            <?php echo Atomik::translate('Import'); ?>
                        </li>
                        <li>
                            <a href="<?php echo Atomik::url('importWord'); ?>">
                                <?php echo Atomik::translate('importWord'); ?>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>

            <!-- Language selector -->
            <ul class="nav navbar-nav navbar-right" style="padding-left: 10px;">
                <?php
                showLanguageNavBar("fr");
                showLanguageNavBar("en");
                ?>
            </ul>

            <!-- Search form -->
            <form class="navbar-form navbar-right" role="search" method="GET" action="<?php echo Atomik::url('search'); ?>">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="<?php echo Atomik::translate("Search"); ?>">
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-default">
                            <?php echo Atomik::translate("Search"); ?>
                        </button>
                    </span>
                </div>
            </form>
        </div>
    </div>
</nav>