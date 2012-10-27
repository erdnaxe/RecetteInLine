<?php
require(Atomik::asset('fpdf/fpdf.php'));
if(!$tableau=parse_ini_file("recettes/".$_GET["nom"])) return "Impossible d'ouvrir le fichier.";
$pdf = new FPDF();
$pdf->AddPage();
//titre
$pdf->SetFont('Arial','',15); // Police Arial gras 15
$pdf->SetTextColor(255,150,0);
$pdf->Cell(40); // Décalage à droite
$pdf->Cell(30,10,$tableau["tit"]); // Titre
$pdf->Ln(20);  // Saut de lign
$pdf->SetFont('Arial','',12);
if($tableau["pre"]!=""){
	$pdf->SetTextColor(255,150,0);
	$pdf->Cell(30,10,"Préparation :");
	$pdf->SetTextColor(0,0,0);
	$pdf->MultiCell(0, 10, $tableau["pre"]);
}
if($tableau["cui"]!=""){
	$pdf->SetTextColor(255,150,0);
	$pdf->Cell(30,10,"Cuisson :");
	$pdf->SetTextColor(0,0,0);
	$pdf->MultiCell(0, 10, $tableau["cui"]);
}
if($tableau["ing"]!=""){
	$pdf->SetTextColor(255,150,0);
	$pdf->Cell(30,7,"Ingrédients :");
	$pdf->SetTextColor(0,0,0);
	$pdf->MultiCell(0, 7, $tableau["ing"]);
}
if($tableau["rec"]!=""){
	$pdf->SetTextColor(255,150,0);
	$pdf->Cell(30,7,"Recette :");
	$pdf->SetTextColor(0,0,0);
	$pdf->MultiCell(0, 7, $tableau["rec"]);
}
if($tableau["rem"]!=""){
	$pdf->SetTextColor(255,150,0);
	$pdf->Cell(30,7,"Remarque :");
	$pdf->SetTextColor(0,0,0);
	$pdf->MultiCell(0, 7, $tableau["rem"]);
}
$pdf->Output();
?>