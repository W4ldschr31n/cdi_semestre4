<?php

namespace CdiBundle\Controller;

use CdiBundle\Entity\Periodique;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\UserController;

class PeriodiqueController extends Controller
{
    /**
     * @Route("/admin/periodiques", name="periodique_liste")
     *
     * @return Response
     */
    public function listeAction() {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $periodiqueRepository = $em->getRepository("CdiBundle:Periodique");
        $periodiques = $periodiqueRepository->findAll();

        $viewParams = ["periodiques" => $periodiques];
        return $this->render('CdiBundle:Periodique:liste.html.twig', $viewParams);
    }


    /**
     * @Route("/admin/auteur/ajout", name="auteur_ajout")
     *
     * @return Response
     */
    public function ajoutAction() {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $periodique = new Periodique();
        $formBuilder = $this->get('form.factory')->createBuilder('form', $periodique);
        $formBuilder
            ->add("nom", "text")
            ->add("ajout", "submit");

        $form = $formBuilder->getForm();


        // Validation du formulaire
        $request = Request::createFromGlobals();
        $form->handleRequest($request);

        if($form->isValid()){
            // Hydrate object
            $em = $this->getDoctrine()->getManager();
            $em->persist($periodique);
            $em->flush();

            // Returning to task list.
            return $this->redirect($this->generateUrl("periodique_liste"));
        }

        // Creation de la vue
        $viewParams = ["form" => $form->createView()];
        return $this->render('CdiBundle:Periodique:ajout.html.twig', $viewParams);
    }



    /**
     * @Route("/admin/periodique/{id}/supprimer", name="periodique_supprimer")
     *
     * @param int $id Identifiant du périodique à supprimer.
     * @return Response
     */
    public function supprimerAction($id) {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $periodiqueRepository = $em->getRepository("CdiBundle:Periodique");
        $articleRepository = $em->getRepository("CdiBundle:Article");
        $periodique = $periodiqueRepository->find($id);

        // Dans le cas où l'auteur n'existe pas.
        if(is_null($periodique))
            return $this->redirect($this->generateUrl("periodique_liste"));

        // Sinon on le supprime avec les articles qui lui sont associés
        $articlesSupprimer = $articleRepository->findByPeriodique($periodique);
        foreach ($articlesSupprimer as $article) {
            $em->remove($article);
        }

        $em->remove($periodique);
        $em->flush();

        return $this->redirect($this->generateUrl("periodique_liste"));
    }

    /**
     * @Route("/admin/periodique/{id}/editer", name="periodique_editer")
     *
     * @param int $id Identifiant du périodique à éditer.
     * @return Response
     */
    public function editerAction($id) {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $periodiqueRepository = $em->getRepository("CdiBundle:Periodique");
        $periodique = $periodiqueRepository->find($id);

        // Dans le cas où l'auteur n'existe pas.
        if(is_null($periodique))
            return $this->redirect($this->generateUrl("periodique_liste"));

        // Création du formulaire
        $formBuilder = $this->get('form.factory')->createBuilder('form', $periodique);
        $formBuilder
            ->add("nom", "text")
            ->add("ajout", "submit");
        $form = $formBuilder->getForm();


        // Validation du formulaire
        $request = Request::createFromGlobals();
        $form->handleRequest($request);

        if($form->isValid()){
            $em->flush();

            // Returning to task list.
            return $this->redirect($this->generateUrl("periodique_liste"));
        }

        // Creation de la vue
        $viewParams = ["form" => $form->createView()];
        return $this->render('CdiBundle:Periodique:editer.html.twig', $viewParams);
    }
}
