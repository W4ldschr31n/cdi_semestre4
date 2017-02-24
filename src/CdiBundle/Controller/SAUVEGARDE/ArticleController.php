<?php

namespace CdiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use CdiBundle\Entity\Auteur;
use CdiBundle\Entity\Periodique;
use CdiBundle\Entity\Article;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use AppBundle\Controller\UserController;

class ArticleController extends Controller{

    /**
    * @Route("/article/{id}", name="article_voir")
    *
    * @return Response
    */
    public function voirAction($id) {
        // On redirige les utilisateurs non-connectés
        if(!UserController::isConnected())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $articleRepository = $em->getRepository("CdiBundle:Article");
        $article = $articleRepository->find($id);

        if(is_null($article))
            return $this->redirect($this->generateUrl('accueil'));


        $viewParams = ["article" => $article];
        return $this->render('CdiBundle:Article:voir.html.twig', $viewParams);
    }


    /**
    * @Route("/admin/articles/{page}", name="article_liste")
    *
    * @return Response
    */
    public function listeAction($page = null) {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        // Récupération des informations de la base de donnée
        $em = $this->getDoctrine()->getManager();
        $articleRepository = $em->getRepository("CdiBundle:Article");

        if(is_null($page))
            $page = 1;

        $nbPage = ((int) ($articleRepository->count() / 10));
        $articles = $articleRepository->getArticlesFromPage($page, 10);

        $viewParams = ["articles" => $articles, "nbPage" => $nbPage, "currentPage" => $page];
        return $this->render('CdiBundle:Article:liste.html.twig', $viewParams);
    }


    /**
     * @Route("/admin/article/ajout/auteur", name="article_ajout_auteur")
     *
     * @return Response
     */
    public function ajoutAuteurAction() {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $request = Request::createFromGlobals();
        $prenom = $request->request->get('prenom');
        $nom = $request->request->get('nom');

        // Cas où il y a eu une validation sans auteur saisi, on passe au périodique
        if(!is_null($prenom) && !is_null($nom) && empty($prenom) && empty($nom))
            return $this->redirect($this->generateUrl("article_ajout_periodique"));


        // Dans le cas où un auteur a été saisi (qu'il existe ou non dans la base de données).
        if(!is_null($prenom) && !is_null($nom)){
            $prenom = strtolower($prenom);
            $nom = strtolower($nom);

            // On récupère l'auteur saisie en BD
            $em = $this->getDoctrine()->getManager();
            $session = new Session();
            $auteur = $em->getRepository("CdiBundle:Auteur")->findOneBy(["prenom" => $prenom, "nom" => $nom]);

            // Si l'auteur n'existe pas on le créer à partir des infos
            if(is_null($auteur)){
                $auteur = new Auteur();
                $auteur->setPrenom($prenom);
                $auteur->setNom($nom);
                $em->persist($auteur);
                $em->flush();
                $session->set('auteur', $auteur->getId());
            } else{
                $session->set('auteur', $auteur->getId());
            }



            return $this->redirect($this->generateUrl("article_ajout_periodique"));
        }

        return $this->render('CdiBundle:Article:ajoutAuteur.html.twig');
    }


    /**
     * @Route("/admin/article/ajout/periodique", name="article_ajout_periodique")
     *
     * @return Response
     */
    public function ajoutPeriodiqueAction() {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $request = Request::createFromGlobals();

        // Si le formulaire est soumis
        $nom = $request->request->get('nom');
        if(!is_null($nom) && !empty($nom)){
            // On récupère le periodique saisi dans la base de données
            $em = $this->getDoctrine()->getManager();
            $session = new Session();

            $periodique = $em->getRepository("CdiBundle:Periodique")->findOneBy(["nom" => $nom]);

            // Si le périodique n'existe pas on le créer à partir des infos
            if(is_null($periodique)){
                $periodique = new Periodique();
                $periodique->setNom($nom);
                $em->persist($periodique);
                $em->flush();
                $session->set('periodique', $periodique->getId());
            } else{
                $session->set('periodique', $periodique->getId());
            }

            return $this->redirect($this->generateUrl("article_ajout_informations"));
        }

        return $this->render('CdiBundle:Article:ajoutPeriodique.html.twig');
    }


     /**
     * @Route("/admin/article/ajout/informations", name="article_ajout_informations")
     *
     * @return Response
     */
    public function ajoutInformationsAction() {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        // Récupération des formulaires
        $request = Request::createFromGlobals();

        // Création du formulaire
        $article = new Article();
        $formBuilder = $this->get('form.factory')->createBuilder('form', $article);
        $formBuilder
            ->add("titre", "text")
            ->add("resume", "textarea")
            ->add("dateParution", "date", ['widget' => 'single_text', 'format' => 'yyyy-MM-dd', "data" => new \DateTime()])
            ->add("numeroPeriodique", "text")
            ->add("pageDebut", "text")
            ->add("pageFin", "text", ['required' => false])
            ->add("save", "submit");
        $form = $formBuilder->getForm();

        // Validation du formulaire
        $request = Request::createFromGlobals();

        // Formattage de la date
        if(isset($request->request->get('form')["dateParution"]) && !is_null($request->request->get('form')["dateParution"])){
            $submittedForm = $request->request->get('form');
            $submittedDate = \DateTime::createFromFormat('d/m/Y', $submittedForm["dateParution"]);

            if($submittedDate != false){
                $submittedForm["dateParution"] = $submittedDate->format('Y-m-d');
                $request->request->set('form', $submittedForm);
            }
        }

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            // Enregistrement de l'article dans les sessions
            $session = new Session();
            $session->set('article', $article);

            return $this->redirect($this->generateUrl('article_ajout'));
        }

        $viewParams = ["form" => $form->createView()];
        return $this->render('CdiBundle:Article:ajoutArticle.html.twig', $viewParams);
    }


    /**
     * @Route("/admin/article/ajout", name="article_ajout")
     *
     * @return Response
     */
    public function ajoutAction() {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        // Récupération des sessions
        $sessions = new Session();

        // Si aucun article n'a été soumis
        if(!$sessions->has('article') || !$sessions->has('periodique')){
            // On supprime les sessions existantes
            $sessions->remove('article');
            $sessions->remove('auteur');
            $sessions->remove('periodique');

            return $this->redirect($this->generateUrl("administration"));
        }


        // Si on a bien un article de soumis
        else{
            $em = $this->getDoctrine()->getManager();

            // On récupère les données dans les sessions et on les persistes
            $article = $sessions->get('article');
            $em->persist($article);

            // Periodique
            $periodique = $em->getRepository("CdiBundle:Periodique")->find($sessions->get('periodique'));
            $article->setPeriodique($periodique);

            if($sessions->has('auteur')){
                $auteur = $em->getRepository("CdiBundle:Auteur")->find($sessions->get('auteur'));
                $article->setAuteur($auteur);
            }

            // On enregistre en base de données
            $em->flush();

            // On supprime les sessions
            $sessions->remove('article');
            $sessions->remove('periodique');
            $sessions->remove('auteur');

            // Redirection vers la liste des articles
            return $this->redirect($this->generateUrl('article_liste'));
        }
    }


    /**
    * @Route("admin/article/{id}/supprimer", name="article_supprimer")
    *
    * @return Response
    */
    public function supprimerAction($id) {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $articleRepository = $em->getRepository("CdiBundle:Article");
        $article = $articleRepository->find($id);

        if(is_null($article))
            return $this->redirect($this->generateUrl('article_liste'));

        $em->remove($article);
        $em->flush();

        return $this->redirect($this->generateUrl('article_liste'));
    }


    /**
    * @Route("admin/article/{id}/editer", name="article_editer")
    *
    * @return Response
    */
    public function editerAction($id) {
        // On redirige les utilisateurs non-admins
        if(!UserController::isAdmin())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        $em = $this->getDoctrine()->getManager();
        $articleRepository = $em->getRepository("CdiBundle:Article");
        $article = $articleRepository->find($id);

        if(is_null($article))
            return $this->redirect($this->generateUrl('article_liste'));


        // Création du formulaire
        $formBuilder = $this->get('form.factory')->createBuilder('form', $article);
        $formBuilder
            ->add("titre", "text")
            ->add("resume", "textarea")
            ->add("dateParution", "date", ['widget' => 'single_text', 'format' => 'yyyy-MM-dd'])
            ->add("numeroPeriodique", "text")
            ->add("pageDebut", "text", ['required' => false])
            ->add("pageFin", "text", ['required' => false])
            ->add("save", "submit");
        $form = $formBuilder->getForm();

        // Validation du formulaire
        $request = Request::createFromGlobals();

        // Formattage de la date
        if(isset($request->request->get('form')["dateParution"]) && !is_null($request->request->get('form')["dateParution"])){
            $submittedForm = $request->request->get('form');
            $submittedDate = \DateTime::createFromFormat('d/m/Y', $submittedForm["dateParution"]);

            if($submittedDate != false){
                $submittedForm["dateParution"] = $submittedDate->format('Y-m-d');
                $request->request->set('form', $submittedForm);
            }
        }


        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            // Enregistrement de l'article
            $em->flush();

            return $this->redirect($this->generateUrl('article_voir', ["id" => $id]));
        }


        // Creation de la vue
        $viewParams = ["form" => $form->createView()];
        return $this->render('CdiBundle:Article:editer.html.twig', $viewParams);
    }


    /**
     * @Route("/articles/recherche", name="article_recherche")
     *
     * @return Response
     */
    public function rechercheAction() {
        // On redirige les utilisateurs non-connectés
        if(!UserController::isConnected())
            return $this->redirect($this->generateUrl("redirection_accueil"));

        // Récupération des champs
        $request = Request::createFromGlobals();
        $typeRecherche = $request->request->get('type');
        $recherche = $request->request->get('recherche');

        //Récupération des champs de la recherche avancée
        $rechercheAvancee = $request->request->get('rechercheAvancee');
        $typeRechercheAvancee = $request->request->get('typeAvancee');
        $operateur = $request->request->get('operateur');

        /*
            Types des champs :
                - tousChamps
                - titre
                - periodique
                - auteur
        */

        $avancee = [];
        if(empty($rechercheAvancee)) {
            $avancee = null;
            $operateur = null;
        } else {
            $avancee = [$typeRechercheAvancee, $rechercheAvancee];
        }

        $donneesRecherche = [  [$typeRecherche, $recherche],
                                $avancee,
                                $operateur];

        $em = $this->getDoctrine()->getManager();
        $articleRepository = $em->getRepository("CdiBundle:Article");

        $articles = $articleRepository->recherche($donneesRecherche);

        return $this->render("CdiBundle:Article:resultatRecherche.html.twig", ['articles' => $articles]);
    }
}
