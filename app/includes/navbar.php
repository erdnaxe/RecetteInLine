<?php
/*
 * Nav Bar
 * --------------------------
 * This code is a part of the RecetteInLIne project.
 * He has been created by erdnaxe.
 * This project is under GPLv2 license.
 */
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
                <!-- Home -->
                <li class="active"><a href="<?php echo Atomik::url('index'); ?>">Accueil</a></li>
                
                <!-- Recipes explorer -->
                <li>
                    <a href="<?php echo Atomik::url('explorer'); ?>" style="padding-right: 5px">
                        Naviguer dans les recettes
                    </a>
                </li>
                <li class="dropdown">
                    <a href="#" style="padding-left: 5px" class="dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li class="dropdown-header"><span class="badge pull-right">42</span> Catégorie 1</li>
                        <li><a href="<?php echo Atomik::url('viewer', array('id' => 'onefile')); ?>">Exemple...</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header"><span class="badge pull-right">5</span> Catégorie 2</li>
                        <li><a href="<?php echo Atomik::url('viewer'); ?>">Exemple...</a></li>
                    </ul>
                </li>

                <!-- New recipes -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        Nouvelle recette <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li class="dropdown-header">Nouvelle recette</li>
                        <li><a href="<?php echo Atomik::url('newRecipes'); ?>">&Agrave; partir d'un modèle vierge</a></li>
                        <li class="divider"></li>
                        <li class="dropdown-header">Importation</li>
                        <li><a href="<?php echo Atomik::url('importWord'); ?>">Importer à partir de word</a></li>
                    </ul>
                </li>
            </ul>
            
            <!-- Search form -->
            <form class="navbar-form navbar-right" role="search" method="GET" action="<?php echo Atomik::url('search'); ?>">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Rechercher">
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-default">
                            Rechercher
                        </button>
                    </span>
                </div>
            </form>
        </div>
    </div>
</nav>
