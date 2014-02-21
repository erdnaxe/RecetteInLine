<?php
if(!empty($_GET["valid"]) && $_GET["valid"]){
	if(!unlink("recettes/".$_GET["recette"])){
		Atomik::flash('Une erreur est survenue lors de la suppression !','error');
	}
	Atomik::redirect('TB');
}else{
?>
<h1>Voulez-vous vraiment suprimer  ?</h1>
Cela effacera : "<?php echo utf8_encode($_GET["recette"]); ?>".<br/>
<a href='<?php echo Atomik::url('suppr',array("recette" => $_GET["recette"], "valid" => "1")); ?>'>Cliquer ici</a> pour confirmer.
<?php } ?>