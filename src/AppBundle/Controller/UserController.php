<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use phpCAS;

class UserController extends Controller
{

  /**
  * @var string $ldapOuDocumentaliste Nom de l'unité organisationnelle des documentalistes dans l'annuaire LDAP de l'Université de Nantes.
  * @var Session $co Session de connection de l'utilisateur
  */
  public static $ldapOuDocumentaliste = "IUT DE NANTES/CENTRE DOCUMENTATION";


  /**
  * @Route("/connexion", name="connexion")
  * Utilisation de l'extension phpCAS (voir composer et https://wiki.jasig.org/display/casc/phpcas) pour se connecter au CAS de l'université afin de récupérer l'UID.
  *
  * @param Request $request Requête courante passée automatiquement en paramètre.
  * @return Response
  */
  public function connexionAction(Request $request){
    if(!self::isConnected()){
      // Authentification
      $root = $this->get('kernel')->getRootDir();
      $cas_serveur = "cas-ha.univ-nantes.fr";
      $cas_port = 443;
      $cas_repertoire = "esup-cas-server";
      $cas_ca_cert = $root."/config/digicert-terena-starcert.pem";  // fichier contenant la chaine de certificats de l'autorité de certification

      phpCAS::setDebug($root."/cache/cas/cas.log");

      phpCAS::client(CAS_VERSION_2_0,$cas_serveur,$cas_port,$cas_repertoire,FALSE);

      if (isset($cas_proxy_host)) {
        phpCAS::setExtraCurlOption(CURLOPT_PROXY,$cas_proxy_host);
      }
      if (isset($cas_proxy_port)) {
        phpCAS::setExtraCurlOption(CURLOPT_PROXYPORT,$cas_proxy_port);
      }

      if (isset($cas_ca_cert)) {
        phpCAS::setCasServerCACert($cas_ca_cert);
      } else {
        phpCAS::setNoCasServerValidation();
      }

      phpCAS::forceAuthentication();

      $uid = phpCAS::getUser();
      $session = new Session();
      $session->set('user', $uid);
      $session->set('status', self::getUserStatus($uid));

      return $this->redirect($this->generateUrl("redirection_accueil"));
    } else {
      return $this->redirect($this->generateUrl("redirection_accueil"));
    }
    return $this->render("AppBundle::accueil.html.twig");
  }


  /**
  * @Route("/deconnexion", name="deconnexion")
  *
  * @param Request $request Requête courante passée automatiquement en paramètre.
  * @return Response
  */

  public function deconnexionAction(){
    //Deconnection qui marche, mais univ nantes reconnection direct, voir deconnection de leur coté

    if(self::isConnected()){
      //Suppression des variable session
      $this->get('request')->getSession()->invalidate();

      //Deconnection de l'univ nantes
      $root = $this->get('kernel')->getRootDir();
      $cas_serveur = "cas-ha.univ-nantes.fr";
      $cas_port = 443;
      $cas_repertoire = "esup-cas-server";
      $cas_ca_cert = $root."/config/digicert-terena-starcert.pem";  // fichier contenant la chaine de certificats de l'autorité de certification
      $url = "http://infoweb.iut-nantes.univ-nantes.prive/~revue-articles/public_html/intranet-cdi-joffre/web/app_dev.php/"; //L'url de redirection apres la deconnection

      phpCAS::client(CAS_VERSION_2_0,$cas_serveur,$cas_port,$cas_repertoire,FALSE);

      if (isset($cas_proxy_host)) {
        phpCAS::setExtraCurlOption(CURLOPT_PROXY,$cas_proxy_host);
      }
      if (isset($cas_proxy_port)) {
        phpCAS::setExtraCurlOption(CURLOPT_PROXYPORT,$cas_proxy_port);
      }

      if (isset($cas_ca_cert)) {
        phpCAS::setCasServerCACert($cas_ca_cert);
      } else {
        phpCAS::setNoCasServerValidation();
      }
      phpCas::logoutWithRedirectService($url);
    }
    return $this->redirect($this->generateUrl("redirection_accueil"));
  }

  /**
  * @Route("/redirection", name="redirection_accueil")
  *
  * @param Request $request Requête courante passée automatiquement en paramètre.
  * @return Response
  */
  public function redirectAction(Request $request){
    // Redirection des administrateurs/documentalistes
    if(self::isAdmin())
    return $this->redirect($this->generateUrl('administration'));

    // Redirection des utilisateurs connectés ou des visiteurs vers l'accueil
    else
    return $this->redirect($this->generateUrl('accueil'));
  }

  /**
  *  Vérifie que la personne est connecté (qu'elle soit étudiante, documentaliste ou administrateur).
  *  @return Boolean Vrai si l'utilisateur est connecté, faux sinon.
  */
  public static function isConnected(){
    $session = new Session();
    $has = $session->has('user') && $session->has('status');
    $user = $session->get('user');
    return  $has && !empty($user);
  }

  /**
  *  Vérifie que la personne est administrateur(documentaliste ou administrateur).
  *  @return Boolean Vrai si l'utilisateur est administrateur, faux sinon.
  */
  public static function isAdmin(){
    $session = new Session();
    $has = $session->has('user') && $session->has('status');
    $user = $session->get('user');
    $status = $session->get('status');
    return  $has && !empty($user) && ($status == "administrateur" || $status == "documentaliste");
  }

  /**
  *  Récupère l'identifiant de l'utilisateur ou null si il n'est pas connecté
  *  @return String Idententifiant de l'utilisateur.
  */
  public static function getUid(){
    if(self::isConnected()){
      $session = new Session();
      return $session->get('user');
    } else{
      return null;
    }
  }

  /**
  *  Récupère les droits de l'utilisateur connecté dans le LDAP de l'université
  *  @param String $uid Identifiant utilisateur à chercher dans l'annuaire LDAP.
  *  @return String Status de l'utilisateur : "etudiant", "documentaliste" ou "administrateur"
  */
  public static function getUserStatus($uid){
    $ldaphost = "ldapauth.ha.univ-nantes.prive";
    $ldapconn = ldap_connect($ldaphost, 389) or die("Impossible de se connecter au serveur LDAP ".$ldaphost);

    $base_dn = "dc=univ-nantes,dc=fr";
    $filter =  "(&(objectClass=inetOrgPerson)(uid=". $uid ."))";

    $search = ldap_search($ldapconn, $base_dn, $filter);
    $res = ldap_get_entries($ldapconn, $search);

    //Recuperation de la session courante et de l'id de l'utilisateur pour son statut
    $session = new Session();
    $uid = $session->get('user');
    $uid = strtolower($uid);


    //Verification des pseudo et donc du status
    if(isset($res[0]['ou'][0]) && $res[0]['ou'][0] == self::$ldapOuDocumentaliste ){
      $status = "documentaliste";
    } else if($uid == "e155050s" || $uid == "e155024e" || $uid == "e159092b" || $uid == "e155242r" || $uid == "e155913w" || $uid == "e155059s"){
      $status = "administrateur";
    }  else {
      $status = "etudiant";
    }

    return $status;
  }
}
