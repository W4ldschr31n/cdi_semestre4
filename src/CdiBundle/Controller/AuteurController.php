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

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('nom', 'text')
      ->add('prenom', 'text')
    ;
  }

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'OC\PlatformBundle\Entity\Auteur'
    ));

  }


  public function getName()
  {
    return 'oc_platformbundle_auteur';
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
    public function supprimerAction($id, $idRemplacement) {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $auteurRepository = $em->getRepository("CdiBundle:Auteur");
        $articleRepository = $em->getRepository("CdiBundle:Article");
        $auteur = $auteurRepository->find($id);
        $auteurDeRemplacement =  $auteurRepository->find($idRemplacement);

        // Dans le cas où l'auteur n'existe pas.
        if(is_null($auteur))
            return $this->redirect($this->generateUrl("auteur_liste"));

        // Sinon on le supprime avec les articles associés
        $articlesSupprimer = $articleRepository->findByAuteur($auteur);
        foreach ($articlesSupprimer as $article) {
          // echo "<script>alert('".$idRemplacement."')";
            $article->setAuteur($auteurDeRemplacement);
            $em->persist($article);
            $em->flush();
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
