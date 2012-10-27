<?php
function listefichier($rep){
	$liste=array();
	$dir = opendir($rep);
	while($file = readdir($dir)) {
		if(preg_match("/.rec.ini/i", $rep.$file) && !is_dir($rep.$file)) $liste[].=$file;
	}
	closedir($dir);
	return $liste;
}
function listedossier($rep){
	$liste=array();
	$dir = opendir($rep);
	while($file = readdir($dir)) {
		if(is_dir($rep.$file)&&$file!="."&&$file!="..") $liste[]=$file;
	}
	closedir($dir);
	return $liste;
}
function TBEntry($addr){
	$result=parse_ini_file($addr);
	$tmp= '<li>';
	$tmp.= '<a href="'.Atomik::url('pdf',array("recette" => $addr)).'" class="pdf"></a>';
	$tmp.= '<a href="'.Atomik::url('editeur',array("recette" => $addr, "page" => "modif")).'" class="edit"></a>';
	$tmp.= '<a href="'.Atomik::url('suppr',array("recette" => $addr)).'" class="suppr"></a>';
	$tmp.= '<a href="'.Atomik::url('lire',array("recette" => $addr)).'">'.utf8_encode($result["tit"]).'</a>';
	$tmp.= '</li>';
	return $tmp;
}
function explore($dir, $nv="dir1"){
	$tmp="";
	foreach (listedossier($dir."/") as $nom){
		$tmp.= "<li>".utf8_encode($nom)."</li><ul class='".$nv."'>";
		$tmp.= explore($dir."/".$nom,"dir2");
		foreach (listefichier($dir."/".$nom) as $nomfichier) 
			$tmp.= TBEntry($dir."/".$nom."/".$nomfichier);
		$tmp.= "</ul>";
	}
	return $tmp;
}
?>
<h1>Table des matières</h1>
<a href="#">Imprimer la table des matières</a><br/><br/>
<ul class="TB"><?php echo explore("recettes"); ?></ul>