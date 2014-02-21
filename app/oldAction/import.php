<?php
function listeCat(){
	$dir = opendir("recettes/.");
	$tableau = array();
	while($file = readdir($dir)) {
		if($file!="." && $file!=".." && is_dir('recettes/'.$file)){
			$tableau[] .= "<option>".$file."</option>";
			$dir2 = opendir("recettes/".$file."/.");
			while($file2 = readdir($dir2)) { 
				if($file2!="." && $file2!=".." && is_dir("recettes/".$file."/".$file2)) 
					$tableau[] .= "<option>".$file."/".$file2."</option>"; 
			}
			closedir($dir2);
		}
	}
	closedir($dir);
	sort($tableau);
	$data = "<select name='cat'>";
	foreach ($tableau as $id => $nom) $data .=$nom;
	return $data."</select>";
}

if(!empty($_GET["texte"]) && !empty($_GET["cat"])){
	echo $_GET["texte"];
		/*//Réglage problème entislashes
		$tit='"'.stripcslashes($_GET['tit']).'"';
		$pre='"'.stripcslashes($_GET['pre']).'"';
		$cui='"'.stripcslashes($_GET['cui']).'"';
		$ing='"'.stripcslashes($_GET['ing']).'"';
		$rec='"'.stripcslashes($_GET['rec']).'"';
		$rem='"'.stripcslashes($_GET['rem']).'"';
		//Création du contenu du fichier
		$chaine="tit=$tit
		pre=$pre
		cui=$cui
		ing=$ing
		rec=$rec
		rem=$rem";
		//nettoyage
		$nom= strtr(trim($_GET["tit"]),"ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ","aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn");
		$nom = preg_replace('/([^.a-z0-9]+)/i', '-', $nom);
		$nom = strtolower($nom);
		//Ecriture
		$monfichier = fopen("recettes/".$_GET['cat']."/$nom.rec.ini", 'w+');
		fputs($monfichier, $chaine); 
		fclose($monfichier);
		//Redirection
		echo"<script language='javascript'>top.location='index.php';</script>";*/
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<link rel="stylesheet" href="style.css" media="screen" />
	</head>
	<body>
		<h1>Importer un fichier</h1><br/>
		<form action="word.import.php" method="GET">
			<span class="FakeH2">Catégorie: </span><?php echo listeCat(); ?>
			<h2>Coller le contenu de la recette ici :</h2>
			<textarea name="texte" cols="45" rows="40"></textarea>
			<input type="submit" value="Valider"/>
		</form>
	</body>
</html>