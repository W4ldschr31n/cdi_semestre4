<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Controller\UserController;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="accueil")
     *
     * @param Request $request Requête courante passée automatiquement en paramètre.
     * @return Response
     */
    public function indexAction(Request $request)
    {
        // Redirection vers l'interface d'administration
        if(UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        // Affichage du champs de recherche pour les étudiants.
        if(UserController::isConnected())
            return $this->render("AppBundle::recherche.html.twig");

        // Affichage de l'accueil pour les visiteurs (avec bouton de connexion etc...)
        else
            return $this->render("AppBundle::accueil.html.twig");
    }


    /**
     * @Route("/admin", name="administration")
     *
     * @return Response
     */
    public function administrationAction()
    {
        // Tous les non-administrateurs sont redirigés.
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        return $this->render("AppBundle::administration.html.twig");
    }

    public function contactAction()
    {
        return $this->render("AppBundle::contact.html.twig");
    }

    public function proposAction()
    {
        return $this->render("AppBundle::propos.html.twig");
    }
}
