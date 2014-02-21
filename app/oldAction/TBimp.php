<?php
if(require(Atomik::asset('fpdf/fpdf.php'))){
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
	$pdf = new FPDF();
	$pdf->AddPage();
	$pdf->SetFont('Arial','',15); // Police Arial gras 15
	$pdf->SetTextColor(255,150,0);
	$pdf->Cell(40); // Décalage à droite
	$pdf->Cell(30,10,"Table des matières"); // Titre
	$pdf->Ln(20);  // Saut de ligne
	$pdf->SetFont('Arial','',12);
	foreach (listedossier("recettes/") as $id => $nom){
		$pdf->SetTextColor(255,150,0);
		$pdf->Cell(0,7,$nom);
		$pdf->Ln();
		//On liste les sous-dossiers
		foreach (listedossier("recettes/".$nom."/") as $id => $nomdossier){
			$pdf->SetTextColor(100,150,0);
			$pdf->Cell(10);
			$pdf->Cell(0,7,$nomdossier);
			$pdf->Ln();
			//On liste les recettes dans le sous-dossier
			$pdf->SetTextColor(0,0,0);
			foreach (listefichier("recettes/".$nom."/".$nomdossier."/") as $id => $nomsous){
				$result=parse_ini_file("recettes/".$nom."/".$nomdossier."/".$nomsous);
				$pdf->Cell(20);
				$pdf->Cell(0, 10, $result["tit"]);
				$pdf->Ln();
			}
		} 
		//On liste les recettes
		$pdf->SetTextColor(0,0,0);
		foreach (listefichier("recettes/".$nom."/") as $id => $nomfichier){
			$result=parse_ini_file("recettes/".$nom."/".$nomfichier);
			$pdf->Cell(10);
			$pdf->Cell(0, 10, $result["tit"]);
			$pdf->Ln();
		}
	} 
	$pdf->Output();
}else echo "D&eacute;sol&eacute;, mais la library php \"fpdf\" n'est pas install&eacute;e."
?>
