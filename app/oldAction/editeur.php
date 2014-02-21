<?php
function listeCat($default=""){
	$dir = opendir("recettes/.");
	$tableau = array();
	while($file = readdir($dir)) {
		if($file!="." && $file!=".." && is_dir('recettes/'.$file)){
			$tableau[] .= "<option>".utf8_encode($file)."</option>\n";
			$dir2 = opendir("recettes/".$file."/.");
			while($file2 = readdir($dir2)) { 
				if($file2!="." && $file2!=".." && is_dir("recettes/".$file."/".$file2)) 
					$tableau[] .= "<option>".utf8_encode($file)."/".utf8_encode($file2)."</option>\n"; 
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

function tableEdit($page,$tit="",$cat="",$pre="",$cui="",$ing="",$rec="",$rem=""){
	return '<form action="#" method="GET">
			<table>
				<tr><td>Le titre:</td><td><input name="tit" width="100%" cols="50" rows="8" value="'.$tit.'"/></td></tr>
				<tr><td>Cat√©gorie:</td><td>'.listeCat($cat).'</td></tr>
				<tr><td>Pr√©paration:</td><td><input name="pre" value="'.$pre.'"/></td></tr>
				<tr><td>Cuisson:</td><td><input name="cui" value="'.$cui.'"/></td></tr>
				<tr><td>Les Ingr√©dients :</td><td><textarea name="ing" cols="45" rows="5">'.$ing.'</textarea></td></tr>
			</table>
			<h2>La recette :</h2><textarea name="rec" cols="80" rows="10">'.$rec.'</textarea>
			<h2>Remarques :</h2><textarea name="rem" cols="80" rows="4">'.$rem.'</textarea><br/>
			<input type="hidden" name="action" value="editeur"/>
			<input type="hidden" name="page" value="'.$page.'"/>
			<input type="submit" value="Enregistrer"/>
		</form>';
}

if(empty($_GET["page"])){
	echo "<h1>Ajouter une recette</h1>".tableEdit("verif");
}else{
	if($_GET["page"]=="verif"){
		//R√©glage probl√®me entislashes
		$tit=stripcslashes($_GET['tit']);
		$pre=stripcslashes($_GET['pre']);
		$cui=stripcslashes($_GET['cui']);
		$ing=stripcslashes($_GET['ing']);
		$rec=stripcslashes($_GET['rec']);
		$rem=stripcslashes($_GET['rem']);
		$cat=$_GET["cat"];
		//Affichage
		echo "<h1>Vilsualisation</h1>";
		echo '<fieldset><legend align="center"><h1>'.$tit.'</h1></legend><table>
				<tr><td>Cat√©gorie:</td><td>'.$cat.'</td></tr>
				<tr><td>Pr√©paration:</td><td>'.$pre.'</td></tr>
				<tr><td>Cuisson:</td><td>'.$cui.'</td></tr>
				<tr><td>Les Ingr√©dients :</td><td>'.nl2br($ing).'</td></tr>
			</table>
			<h3>La recette :</h3>'.nl2br($rec).'<h3>Remarques :</h3>'.nl2br($rem).'</fieldset><br/>';
		//Validation
		echo '<form action="#" method="GET">';
		$type=array("tit","cat","pre","cui","ing","rec","rem");
		foreach($type as $key) echo '<input type="hidden" name="'.$key.'" value="'.$$key.'" />';
		echo '<input type="hidden" name="action" value="editeur"/><input type="hidden" name="page" value="enreg"/><input type="submit" value="Valider"/>';
		echo '</form><br/><hr/><br/>';
		//Modifier
		echo "<h1>Modification</h1>".tableEdit("verif",$tit,$_GET["cat"],$pre,$cui,$ing,$rec,$rem,true);
	}elseif($_GET["page"]=="modif"){
		if(!$tableau=parse_ini_file("recettes/".$_GET["nom"])) return "Impossible d'ouvrir le fichier.";
		echo "<h1>Modifier</h1>
			<form action='editeur.php' method='GET' ENCTYPE='x-www-form-urlencoded'>
				<h2>Le titre:</h2><input name='tit' width='100%' cols='50' rows='8' value='".$tableau["tit"]."'/>
				<h2>Cat√©gorie:</h2>".listeCat()."
				<h2>Pr√©paration:</h2><input name='pre' value='".$tableau["pre"]."'/>
				<h2>Cuisson:</h2><input name='cui' value='".$tableau["cui"]."'/>
				<h2>Les Ingr√©dients :</h2><textarea name='ing' cols='50' rows='8'>".$tableau["ing"]."</textarea>
				<h2>La Recette :</h2><textarea name='rec' cols='80' rows='20'>".$tableau["rec"]."</textarea>
				<h2>Remarques :</h2><textarea name='rem' cols='80' rows='4'>".$tableau["rem"]."</textarea><br/>
				<input type='hidden' name='page' value='verif'/><input type='submit' value='Enregistrer'/>
			</form>";
	}elseif($_GET["page"]=="enreg"){
		//RÈglage problËme entislashes
		$tit='"'.stripcslashes($_GET['tit']).'"';
		$pre='"'.stripcslashes($_GET['pre']).'"';
		$cui='"'.stripcslashes($_GET['cui']).'"';
		$ing='"'.stripcslashes($_GET['ing']).'"';
		$rec='"'.stripcslashes($_GET['rec']).'"';
		$rem='"'.stripcslashes($_GET['rem']).'"';
		//CrÈation du contenu du fichier
		$chaine="tit=$tit
		pre=$pre
		cui=$cui
		ing=$ing
		rec=$rec
		rem=$rem";
		//nettoyage
		$nom= strtr(trim($_GET["tit"]),"¿¡¬√ƒ≈‡·‚„‰Â“”‘’÷ÿÚÛÙıˆ¯»… ÀËÈÍÎ«ÁÃÕŒœÏÌÓÔŸ⁄€‹˘˙˚¸ˇ—Ò","aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn");
		$nom = preg_replace('/([^.a-z0-9]+)/i', '-', $nom);
		$nom = strtolower($nom);
		//Ecriture
		$monfichier = fopen("recettes/".$_GET['cat']."/$nom.rec.ini", 'w+');
		fputs($monfichier, $chaine); 
		fclose($monfichier);
	}
}
?>