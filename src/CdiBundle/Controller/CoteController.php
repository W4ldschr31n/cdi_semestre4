<?php

namespace CdiBundle\Controller;

use CdiBundle\Entity\Cote;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\UserController;

class CoteController extends Controller
{
    /**
     * @Route("/admin/cotes", name="cote_liste")
     *
     * @return Response
     */
    public function listeAction() {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $coteRepository = $em->getRepository("CdiBundle:Cote");
        $cotes = $coteRepository->findAll();

        $viewParams = ["cotes" => $cotes];
        return $this->render('CdiBundle:Cote:liste.html.twig', $viewParams);
    }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('nom', 'text')
    ;
  }

  public function setDefaultOptions(OptionsResolverInterface $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'OC\PlatformBundle\Entity\Cote'
    ));

  }


  public function getName()
  {
    return 'oc_platformbundle_cote';
  }



    /**
     * @Route("/admin/coter/ajout", name="cote_ajout")
     *
     * @return Response
     */
    public function ajoutAction() {
        // Tous les non-administrateurs sont redirigés.
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));


        $cote = new Cote();
        $formBuilder = $this->get('form.factory')->createBuilder('form', $cote);
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
            $em->persist($cote);
            $cote->setNom(strToLower($cote->getNom()));
            $cote->setActif(true);
            $em->flush();

            // Returning to task list.
            return $this->redirect($this->generateUrl("cote_liste"));
        }

        // Creation de la vue
        $viewParams = ["form" => $form->createView()];
        return $this->render('CdiBundle:Cote:ajout.html.twig', $viewParams);
    }



    /**
     * @Route("/admin/cote/{id}/supprimer", name="cote_supprimer")
     *
     * @param int $id Identifiant de la cote à supprimer.
     * @return Response
     */
    public function supprimerAction($id, $idRemplacement) {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $coteRepository = $em->getRepository("CdiBundle:Cote");
        $articleRepository = $em->getRepository("CdiBundle:Article");
        $cote = $coteRepository->find($id);
        $coteDeRemplacement =  $coteRepository->find($idRemplacement);

        // Dans le cas où l'auteur n'existe pas.
        if(is_null($cote))
            return $this->redirect($this->generateUrl("cote_liste"));

        // Sinon on le supprime avec les articles associés
        $articlesSupprimer = $articleRepository->findByCote($cote);
        foreach ($articlesSupprimer as $article) {
          // echo "<script>alert('".$idRemplacement."')";
            $article->setCote($coteDeRemplacement);
            $em->persist($article);
            $em->flush();
        }

        $em->remove($cote);
        $em->flush();

        return $this->redirect($this->generateUrl("cote_liste"));
    }




    /**
    * @Route("/admin/cote/{id}/editer", name="cote_editer")
    *
    * @param int $id Identifiant de la cote à éditer.
    * @return Response
    */
    public function editerAction($id) {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $coteRepository = $em->getRepository("CdiBundle:Cote");
        $cote = $coteRepository->find($id);

        // Dans le cas où la cote n'existe pas.
        if(is_null($cote))
            return $this->redirect($this->generateUrl("cote_liste"));

        // Affichage de la cote avec les majuscules
        $cote->setNom(strToUpper($cote->getNom()));

        // Création du formulaire
        $formBuilder = $this->get('form.factory')->createBuilder('form', $cote);
        $formBuilder
            ->add("nom", "text")
            ->add("ajout", "submit");
        $form = $formBuilder->getForm();


        // Validation du formulaire
        $request = Request::createFromGlobals();
        $form->handleRequest($request);

        if($form->isValid()){
            // Hydrate object
            $cote->setNom(strToLower($cote->getNom()));
            $em->flush();

            // Returning to task list.
            return $this->redirect($this->generateUrl("cote_editer"));
        }

        // Creation de la vue
        $viewParams = ["form" => $form->createView()];
        return $this->render('CdiBundle:Cote:editer.html.twig', $viewParams);
    }
}
