<?php

namespace CdiBundle\Controller;

use CdiBundle\Entity\Auteur;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\UserController;

class AuteurController extends Controller
{
    /**
     * @Route("/admin/auteurs", name="auteur_liste")
     *
     * @return Response
     */
    public function listeAction() {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $auteurRepository = $em->getRepository("CdiBundle:Auteur");
        $auteurs = $auteurRepository->findAll();

        $viewParams = ["auteurs" => $auteurs];
        return $this->render('CdiBundle:Auteur:liste.html.twig', $viewParams);
    }


    /**
     * @Route("/admin/auteur/ajout", name="auteur_ajout")
     *
     * @return Response
     */
    public function ajoutAction() {
        // Tous les non-administrateurs sont redirigés.
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));


        $auteur = new Auteur();
        $formBuilder = $this->get('form.factory')->createBuilder('form', $auteur);
        $formBuilder
            ->add("nom", "text")
            ->add("prenom", "text")
            ->add("ajout", "submit");

        $form = $formBuilder->getForm();


        // Validation du formulaire
        $request = Request::createFromGlobals();
        $form->handleRequest($request);

        if($form->isValid()){
            // Hydrate object
            $em = $this->getDoctrine()->getManager();
            $em->persist($auteur);
            $auteur->setNom(strToLower($auteur->getNom()));
            $auteur->setPrenom(strToLower($auteur->getPrenom()));
            $em->flush();

            //TEST
            //on stocke la vue à convertir en PDF, en n'oubliant pas les paramètres twig si la vue comporte des données dynamiques

            $html = $auteur->getNom() . " " . $auteur->getPrenom();


        //on instancie la classe Html2Pdf_Html2Pdf en lui passant en paramètre
        //le sens de la page "portrait" => p ou "paysage" => l
        //le format A4,A5...
        //la langue du document fr,en,it...
        $html2pdf = new \Html2Pdf_Html2Pdf('P','A4','fr');

        //SetDisplayMode définit la manière dont le document PDF va être affiché par l’utilisateur
        //fullpage : affiche la page entière sur l'écran
        //fullwidth : utilise la largeur maximum de la fenêtre
        //real : utilise la taille réelle
        $html2pdf->pdf->SetDisplayMode('real');

        //writeHTML va tout simplement prendre la vue stocker dans la variable $html pour la convertir en format PDF
        $html2pdf->writeHTML($html);

        //Output envoit le document PDF au navigateur internet avec un nom spécifique qui aura un rapport avec le contenu à convertir (exemple : Facture, Règlement…)
        $html2pdf->Output('test.pdf', 'D');
         //FINTEST

            // Returning to task list.
            return $this->redirect($this->generateUrl("auteur_liste"));
        }

        // Creation de la vue
        $viewParams = ["form" => $form->createView()];
        return $this->render('CdiBundle:Auteur:ajout.html.twig', $viewParams);
    }



    /**
     * @Route("/admin/auteur/{id}/supprimer", name="auteur_supprimer")
     *
     * @param int $id Identifiant de l'auteur à supprimer.
     * @return Response
     */
    public function supprimerAction($id) {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $auteurRepository = $em->getRepository("CdiBundle:Auteur");
        $articleRepository = $em->getRepository("CdiBundle:Article");
        $auteur = $auteurRepository->find($id);

        // Dans le cas où l'auteur n'existe pas.
        if(is_null($auteur))
            return $this->redirect($this->generateUrl("auteur_liste"));

        // Sinon on le supprime avec les articles associés
        $articlesSupprimer = $articleRepository->findByAuteur($auteur);
        foreach ($articlesSupprimer as $article) {
            $em->remove($article);
        }

        $em->remove($auteur);
        $em->flush();

        return $this->redirect($this->generateUrl("auteur_liste"));
    }




    /**
    * @Route("/admin/auteur/{id}/editer", name="auteur_editer")
    *
    * @param int $id Identifiant de l'auteur à éditer.
    * @return Response
    */
    public function editerAction($id) {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $auteurRepository = $em->getRepository("CdiBundle:Auteur");
        $auteur = $auteurRepository->find($id);

        // Dans le cas où l'auteur n'existe pas.
        if(is_null($auteur))
            return $this->redirect($this->generateUrl("auteur_liste"));

        // Affichage de l'auteur avec les majuscules
        $auteur->setPrenom(ucfirst($auteur->getPrenom()));
        $auteur->setNom(strToUpper($auteur->getNom()));

        // Création du formulaire
        $formBuilder = $this->get('form.factory')->createBuilder('form', $auteur);
        $formBuilder
            ->add("nom", "text")
            ->add("prenom", "text")
            ->add("ajout", "submit");
        $form = $formBuilder->getForm();


        // Validation du formulaire
        $request = Request::createFromGlobals();
        $form->handleRequest($request);

        if($form->isValid()){
            // Hydrate object
            $auteur->setNom(strToLower($auteur->getNom()));
            $auteur->setPrenom(strToLower($auteur->getPrenom()));
            $em->flush();

            // Returning to task list.
            return $this->redirect($this->generateUrl("auteur_liste"));
        }

        // Creation de la vue
        $viewParams = ["form" => $form->createView()];
        return $this->render('CdiBundle:Auteur:editer.html.twig', $viewParams);
    }
}
