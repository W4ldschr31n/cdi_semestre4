<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
class AjaxController extends Controller
{
  //Variables pour rameau
  var $motClef;
  var $toutlesnoticesitems;
  var $html;
  var $nbResultat;


  /**
  * @Route("/ajax/recherche/auteur/{prenom}/{nom}", name="ajax_recherche_auteur")
  *
  * @return Response
  */
  public function rechercheAuteurAction($nom)
  {
    $request = Request::createFromGlobals();

    // Si ce n'est pas une requête ajax, on redirige vers l'accueil
    if(!$request->isXmlHttpRequest()){
      return $this->redirect($this->generateUrl('accueil'));
    }

    // Si la requête est bien AJAX.
    else{
      $em = $this->getDoctrine()->getManager();

      // Cas où le prénom/nom n'est pas renseigné

      if($nom == "null")
      $nom = null;
      else
      $nom = strToLower($nom);

      // Recherche via le repository
      $auteurRepository = $em->getRepository("CdiBundle:Auteur");
      $res = $auteurRepository->recherche($nom);


      // Generation du tableau d'auteur et retour en json
      $auteurs = [];
      foreach($res as $key => $auteur){
        $tab = ["nom" => $auteur->getNom(), "id" => $auteur->getId()];
        array_push($auteurs, $tab);
      }
      $response = new JsonResponse();
      $response->setData($auteurs);
      return $response;
    }
  }


  /**
  * @Route("/ajax/recherche/periodique/{nom}", name="ajax_recherche_periodique")
  *
  * @return Response
  */
  public function recherchePeriodiqueAction($nom)
  {
    $request = Request::createFromGlobals();

    // Si ce n'est pas une requête ajax ou que le paramètre est vide, on redirige vers l'accueil
    if(!$request->isXmlHttpRequest() || $nom == ""){
      return $this->redirect($this->generateUrl('accueil'));
    }

    // Si la requête est bien AJAX.
    else{
      $em = $this->getDoctrine()->getManager();

      // Recherche via le repository
      $periodiqueRepository = $em->getRepository("CdiBundle:Periodique");
      $res = $periodiqueRepository->recherche($nom);

      // Generation du tableau de périodiques et retour en json
      $periodiques = [];
      foreach($res as $key => $periodique){
        $tab = ["nom" => $periodique->getNom(), "id" => $periodique->getId()];
        array_push($periodiques, $tab);
      }

      $response = new JsonResponse();
      $response->setData($periodiques);
      return $response;
    }
  }


  /**
  * @Route("/ajax/recherche/cote/{nom}", name="ajax_recherche_cote")
  *
  * @return Response
  */
  public function rechercheCoteAction($nom)
  {
    $request = Request::createFromGlobals();

    // Si ce n'est pas une requête ajax, on redirige vers l'accueil
    if(!$request->isXmlHttpRequest()){
      return $this->redirect($this->generateUrl('accueil'));
    }

    // Si la requête est bien AJAX.
    else{
      $em = $this->getDoctrine()->getManager();

      // Cas où le nom n'est pas renseigné
      if($nom == "null")
      $nom = null;
      else
      $nom = strToLower($nom);

      // Recherche via le repository
      $coteRepository = $em->getRepository("CdiBundle:Cote");
      $res = $coteRepository->recherche($nom);


      // Generation du tableau d'auteur et retour en json
      $cotes = [];
      foreach($res as $key => $cote){
        $tab = ["nom" => $cote->getNom(), "id" => $cote->getId()];
        array_push($cotes, $tab);
      }
      $response = new JsonResponse();
      $response->setData($cotes);
      return $response;
    }
  }



  public function rechercheMotClefAction($motClef){

    //retourner resultat liste ajax
    $request = Request::createFromGlobals();

    // Si ce n'est pas une requête ajax ou que le paramètre est vide, on redirige vers l'accueil
    if(!$request->isXmlHttpRequest() || $motClef == ""){
      return $this->redirect($this->generateUrl('accueil'));
    }

    // Si la requête est bien AJAX.
    else{
      $em = $this->getDoctrine()->getManager();

      /*    // Recherche via le repository
      $periodiqueRepository = $em->getRepository("CdiBundle:Periodique");
      $res = $periodiqueRepository->recherche($nom);
      */

      //les notices
      $res = $this->changementMotClef($motClef);

      $response = new JsonResponse();
      $response->setData($res);
      return $response;
    }
  }


  // changement du mot clef et maj de tout :
  function changementMotClef($motClef = ''){
    // Definition de l'attribut mot clef :
    $this->motClef=$motClef;

    // Creation d'un DOMDOcument :
    $dom = new \DOMDocument();

    //Definition du temp de time out pour rameau (ici 5sec)
    $ctx = stream_context_create(array(
      'http' => array(
        'timeout' => 2
      )
    )
  );

  // Recuperation de la page sous forme de string
  $url = "http://catalogue.bnf.fr/resultats-autorite-avancee.do;jsessionid=B8E0E22A7E76FE9D7A127546CA41556B?mots1=ALL;0;0&mots0=FRM;-1;0;".$this->motClef."&typeAuto=2_RAM_PE;2_RAM_TP;2_RAM_TU;2_RAM_GE;2_RAM_SC;2_RAM_CO;2_RAM_NC;1_RAM&statutAuto=C&&pageRech=rat";
  if (!(@$this->html = file_get_contents($url, false, $ctx))) {
    echo 'Il y a eu une erreur lors de l\'acces a rameau.=_-_-_=
    <a href="'.$url.'" onclick="window.open(this.href); return false;"><li class="link">Acces direct a la recherche rameau</li></a>';
    $res = "err";
    exit;
  }

  // Enlevement du debut et de la fin de la page
  $this->trieBonnePartie();

  // MAJ de la var toutlesnoticesitems
  $this->tableauDepuisBonnePartie();

  if(!isset($res)){
    $res = $this->toutlesnoticesitems;
  }

  return $res;
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

///Méthodes rameau
}
