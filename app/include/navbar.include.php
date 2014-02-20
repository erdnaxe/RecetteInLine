<?php
/*
 * Nav Bar
 * --------------------------
 * This code is a part of the RecetteInLIne project.
 * He has been created by erdnaxe.
 * This project is under GPLv2 license.
 */

if (!isset($main_loaded)) {
    header('Location: /');
    exit;
}

/* Old navigation
  echo '<li><a class="menuAccueil" href="index.php"><span>Accueil</span></a></li>';
  echo '<li><a class="menuTB" href="' . Atomik::url('TB') . '"><span>Table des mati&agrave;res</span></a></li>';
  echo '<li><a class="menuEditeur" href="' . Atomik::url('editeur') . '"><span>Ajouter une recette</span></a></li>';
  echo '<li><a class="menuImport" href="' . Atomik::url('import') . '"><span>Importer de word</span></a></li>';
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
            <a class="navbar-brand" href="#"><b>Recette</b>InLine <span class="label label-warning">BETA</span></a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li class="active"><a href="#">Accueil</a></li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Naviguer dans les recettes <span class="caret"></span></a>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li class="dropdown-header"><span class="badge pull-right">42</span> Catégorie 1</li>
                        <li>
                            <a href="#">
                                Exemple...
                            </a>
                        </li>
                        <li class="divider"></li>
                        <li class="dropdown-header"><span class="badge pull-right">5</span> Catégorie 2</li>
                        <li><a href="#">Exemple...</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">Nouvelle recette <span class="caret"></span></a>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li><a href="#">Importer à partir de word</a></li>
                        <li class="divider"></li>
                        <li><a href="#">Autre...</a></li>
                    </ul>
                </li>
            </ul>
            <form class="navbar-form navbar-right" role="search">
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
