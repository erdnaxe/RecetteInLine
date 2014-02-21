<?php
	//On ouvre le fchier
	if(!$tableau=parse_ini_file("recettes/".$_GET["recette"]));
	echo "<h1>".utf8_encode($tableau["tit"])."</h1>
		<h2>Preparation :</h2>".utf8_encode($tableau["pre"])."
		<h2>Cuisson :</h2>".utf8_encode($tableau["cui"])."
		<h2>Ingrédients :</h2>".utf8_encode(nl2br($tableau["ing"]))."
		<h2>Recette :</h2>".utf8_encode(nl2br($tableau["rec"]));
	if($tableau["rem"]!="") echo "<h2>Remarques :</h2>".utf8_encode(nl2br($tableau["rem"]));
?>