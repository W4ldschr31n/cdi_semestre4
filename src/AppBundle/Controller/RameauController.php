<?php
// Source : http://stackoverflow.com/questions/6366351/getting-dom-elements-by-classname
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Controller\UserController;

class RameauController extends Controller {

  var $motClef;
  var $toutlesnoticesitems;
  var $html;
  var $nbResultat;



  // changement du mot clef et maj de tout :
  function changementMotClefAction($motClef = ''){
    // Definition de l'attribut mot clef :
    $this->motClef=$motClef;

    // Creation d'un DOMDOcument :
    $dom = new DOMDocument();

    // Recuperation de la page sous forme de string
    $this->html = file_get_contents("http://catalogue.bnf.fr/resultats-autorite-avancee.do;jsessionid=B8E0E22A7E76FE9D7A127546CA41556B?mots1=ALL;0;0&mots0=FRM;-1;0;".$this->motClef."&typeAuto=2_RAM_PE;2_RAM_TP;2_RAM_TU;2_RAM_GE;2_RAM_SC;2_RAM_CO;2_RAM_NC;1_RAM&statutAuto=C&&pageRech=rat", false);

    // Enlevement du debut et de la fin de la page
    $this->trieBonnePartie();

    // MAJ de la var toutlesnoticesitems
    $this->tableauDepuisBonnePartie();

    return $this->toutlesnoticesitems;
  }



  // Recuperation du mot associer a la notice d'id donner
  function getMotNoticeID($id){
    return $this->toutlesnoticesitems[$id][0];
  }





  // Recuperation du type associer a la notice d'id donner
  function getTypeNoticeID($id){
    return $this->toutlesnoticesitems[$id][1];
  }



  // Recuperation du nombre de resultat:
  function getNbResultat(){
    return $this->nbResultat;
  }



  // Recuperation de la variable html
  function getHTML(){
    return $this->html;
  }




  // Recuperation du mot clef :
  function getMotClef(){
    return $motClef;
  }





  function tableauDepuisBonnePartie(){
    // Initialisation du tableau
    $this->toutlesnoticesitems = array();
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $this->html) as $line){

      // Recuperation du l'id de la notice
      if (substr($line, 0, 28)=='<span class="notice-numero">'){
        // calcule de son id
        $this->nbResultat = substr($line , 28, strpos($line, '</') - strpos($line, '>') -1);
      }

      // Recuperation du mot de la notice
      if (substr($line, 0, 5)=='<div>'){
        // Ajout du dans le tableau
        $this->toutlesnoticesitems[$this->nbResultat][0] = substr($line , strpos($line, '>')+1,  strpos($line, '</div>') - strpos($line, '>') -1);
      }

      // Recuperation du type de la notice
      if (substr($line, 0, 47)=='<div class="notice-nbnotice notice-index-auto">'){
        // Ajout du dans le tableau
        $this->toutlesnoticesitems[$this->nbResultat][1] = substr($line , strpos($line, '>')+1,  strpos($line, '</div>') - strpos($line, '>') -1);
      }
    }
  }





  function trieBonnePartie(){
    $debutTrouver = false;
    $total = '';
    foreach(preg_split("/((\r?\n)|(\r\n?))/", $this->html) as $line){
      $line_no_indent = substr($line , strpos($line, '<'));
      if($line_no_indent=='<div class="notice-groupe">'){
        $cpt = 0;
        $debutTrouver = true;
        $premierTour = true;
      }
      if($debutTrouver){
        $total .= substr($line , strpos($line, '<'))."\n";
        if (substr($line_no_indent, 0, 4)=='<div' or strpos($line_no_indent, '<div') !=0){
          $cpt++;
        }
        if (substr($line_no_indent, 0, 5)=='</div' or strpos($line_no_indent, '</div') !=0){
          $cpt--;
          $premierTour = false;
        }
        if ($cpt==1 & !$premierTour){
          $this->html=$total;
          return true;
        }
      }
    }
    $this->html=$total;
    return false;
  }
}
?>
