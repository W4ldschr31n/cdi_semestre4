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
use CdiBundle\Entity\Cote;
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

    $nbPage = ((int) ($articleRepository->count()));
    $articles = $articleRepository->getArticlesFromPage($page, PHP_INT_MAX);

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
    $prenom = $request->recheMotClefAction();
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
      }
      $session->set('auteur', $auteur->getId());

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
    // Fait appelle à la derniere page de formulaire
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
    $formulaireData = array();

    $form = $this->createFormBuilder($formulaireData)
     ->add("titre", "text")
     ->add("cote", "text")
     ->add("motclef", "text")

     ->add("groupement", "text", ['required' => false])
     ->add("prenomAuteur", "text", ['required' => false])
     ->add("nomAuteur", "text", ['required' => false])
     ->add("prenomAuteur2", "text", ['required' => false])
     ->add("nomAuteur2", "text", ['required' => false])
     ->add("prenomAuteur3", "text", ['required' => false])
     ->add("nomAuteur3", "text", ['required' => false])

     ->add("resume", "textarea")
     ->add("dateParution", "date", ['widget' => 'single_text', 'format' => 'yyyy-MM-dd', "data" => new \DateTime()])

     ->add("numeroPeriodique", "text")
     ->add("pageDebut", "text")
     ->add("pageFin", "text", ['required' => false])
     ->add("save", "submit")
     ->getForm();


     // $article = new Article();
     // $formBuilder = $this->get('form.factory')->createBuilder('form', $article);
     // $formBuilder
     // ->add("titre", "text")
     // ->add("resume", "textarea")
     // ->add("dateParution", "date", ['widget' => 'single_text', 'format' => 'yyyy-MM-dd', "data" => new \DateTime()])
     // ->add("numeroPeriodique", "text")
     // ->add("pageDebut", "text")
     // ->add("pageFin", "text", ['required' => false])
     // ->add("save", "submit");
     // $form = $formBuilder->getForm();
     //
     // // Validation du formulaire
     // $request = Request::createFromGlobals();
     //
     // //Verification auteur
     // if(isset($_POST["prenomAuteur"]) && isset($_POST["nomAuteur"])){
     //   //On recup les var
     //   $prenom = strtolower($_POST["prenomAuteur"]);
     //   $nom = strtolower($_POST["nomAuteur"]);
     //
     //   //On verifie qu'il existe dans la bdd
     //   $em = $this->getDoctrine()->getManager();
     //   $auteur = $em->getRepository("CdiBundle:Auteur")->findOneBy(["prenom" => $prenom, "nom" => $nom]);
     //
     //   // Si l'auteur n'existe pas on le créer à partir des infos
     //   if(is_null($auteur)){
     //     $auteur = new Auteur();
     //     $auteur->setPrenom($prenom);
     //     $auteur->setNom($nom);
     //     $em->persist($auteur);
     //     $em->flush();
     //   }
     //
     //   //On ajoute l'auteur a l'article
     //   $article->setAuteur($auteur);
     // }
     //
     // //Meme verification pour le motclef
     // if(isset($_POST["motClef"])){
     //   //On recup les var
     //   $motClef = $_POST["motClef"];
     //
     //   //On ajoute le periodique a l'article
     //   $article->setMotClef($motClef);
     // }
     //
     // //Meme verification pour le periodique
     // if(isset($_POST["nomPeriodique"])){
     //   //On recup les var
     //   $nomPeriodique = $_POST["nomPeriodique"];
     //   $em = $this->getDoctrine()->getManager();
     //
     //   $periodique = $em->getRepository("CdiBundle:Periodique")->findOneBy(["nom" => $nomPeriodique]);
     //
     //   // Si le périodique n'existe pas on le créer à partir des infos
     //   if(is_null($periodique)){
     //     $periodique = new Periodique();
     //     $periodique->setNom($nomPeriodique);
     //     $em->persist($periodique);
     //     $em->flush();
     //   }
     //
     //   //On ajout les var
     //   $coteNom = $_POST["cote"];
     //   $em = $this->getDoctrine()->getManager();
     //
     //   $cote = $em->getRepository("CdiBundle:Cote")->findOneBy(["nom" => $coteNom]);
     //
     //   // Si le périodique n'existe pas on le créer à partir des infos
     //   if(is_null($cote)){
     //     $cote = new Cote();
     //     $cote->setNom($coteNom);
     //     $cote->setActif(1);
     //     $em->persist($cote);
     //     $em->flush();
     //   }
     //
     //   //On ajoute le periodique a l'article
     //   $article->setCote($cote);
     // }
     //
     // // Formattage de la date
     // if(isset($request->request->get('form')["dateParution"]) && !is_null($request->request->get('form')["dateParution"])){
     //   $submittedForm = $request->request->get('form');
     //   $submittedDate = \DateTime::createFromFormat('d/m/Y', $submittedForm["dateParution"]);
     //
     //   if($submittedDate != false){
     //     $submittedForm["dateParution"] = $submittedDate->format('Y-m-d');
     //     $request->request->set('form', $submittedForm);
     //   }
     // }
     //
     //
     // $form->handleRequest($request);
     // if($form->isSubmitted() && $form->isValid()){
     //   //Definition de la date d'enregistrement
     //   $dateEnregistrement = new \DateTime();
     //   $article->setDateEnregistrement($dateEnregistrement);
     //     // Enregistrement de l'article dans les sessions
     //   $session = new Session();
     //   $session->set('article', $article);
     //   $em = $this->getDoctrine()->getManager();
     //   $em->persist($article);
     //   $em->flush();
     //
     //   return $this->redirect($this->generateUrl('article_ajout'));
     // }





     $viewParams = ["form" => $form->createView()];
     return $this->render('CdiBundle:Article:ajoutArticle.html.twig', $viewParams);
   }

    //  if($request->isMethod("POST")){
    //    $form->handleRequest($request);
    //    $data = $form->getData();
     //
    //    if(isset($data["dateParution"]) && !is_null($data["dateParution"])){
    //      $submittedDate = \DateTime::createFromFormat('d/m/Y', $data["dateParution"]);
    //      if($submittedDate != false){
    //        $data["dateParution"] = $submittedDate->format('Y-m-d');
    //        $request->request->set('form', $data);
    //      }
    //    }
     //
    //    $article = new Article();
    //
    //    //Verification auteur
    //    if(isset($data["prenomAuteur"]) && isset($dat["nomAuteur"])){
    //      //On recup les var
    //      $prenom = strtolower($data["prenomAuteur"]);
    //      $nom = strtolower($data["nomAuteur"]);
    //
    //      //On verifie qu'il existe dans la bdd
    //      $em = $this->getDoctrine()->getManager();
    //      $auteur = $em->getRepository("CdiBundle:Auteur")->findOneBy(["prenom" => $prenom, "nom" => $nom]);
    //
    //      // Si l'auteur n'existe pas on le créer à partir des infos
    //      if(is_null($auteur)){
    //        $auteur = new Auteur();
    //        $auteur->setPrenom($prenom);
    //        $auteur->setNom($nom);
    //        $em->persist($auteur);
    //        $em->flush();
    //      }
    //
    //      //On ajoute l'auteur a l'article
    //      $article->setAuteur($auteur);
    //    }
    //
    //    //Meme verification pour le motclef
    //    if(isset($data["motClef"])){
    //      //On recup les var
    //      $motClef = $data["motClef"];
    //
    //      //On ajoute le periodique a l'article
    //      $article->setMotClef($motClef);
    //    }
    //
    //    //Meme verification pour le periodique
    //    if(isset($data["nomPeriodique"])){
    //      //On recup les var
    //      $nomPeriodique = $data["nomPeriodique"];
    //      $em = $this->getDoctrine()->getManager();
    //
    //      $periodique = $em->getRepository("CdiBundle:Periodique")->findOneBy(["nom" => $nomPeriodique]);
    //
    //      // Si le périodique n'existe pas on le créer à partir des infos
    //      if(is_null($periodique)){
    //        $periodique = new Periodique();
    //        $periodique->setNom($nomPeriodique);
    //        $em->persist($periodique);
    //        $em->flush();
    //      }
    //
    //      //On ajout les var
    //      $coteNom = $data["cote"];
    //      $em = $this->getDoctrine()->getManager();
    //
    //      $cote = $em->getRepository("CdiBundle:Cote")->findOneBy(["nom" => $coteNom]);
    //
    //      // Si le périodique n'existe pas on le créer à partir des infos
    //      if(is_null($cote)){
    //        $cote = new Cote();
    //        $cote->setNom($coteNom);
    //        $cote->setActif(1);
    //        $em->persist($cote);
    //        $em->flush();
    //      }
    //
    //      //On ajoute le periodique a l'article
    //      $article->setCote($cote);






//TODO
    // $article = new Article();
    // $formBuilder = $this->get('form.factory')->createBuilder('form', $article);
    // $formBuilder
    // ->add("titre", "text")
    // ->add("resume", "textarea")
    // ->add("dateParution", "date", ['widget' => 'single_text', 'format' => 'yyyy-MM-dd', "data" => new \DateTime()])
    // ->add("numeroPeriodique", "text")
    // ->add("pageDebut", "text")
    // ->add("pageFin", "text", ['required' => false])
    // ->add("save", "submit");
    // $form = $formBuilder->getForm();
    //
    // // Validation du formulaire
    // $request = Request::createFromGlobals();
    //
    // //Verification auteur
    // if(isset($_POST["prenomAuteur"]) && isset($_POST["nomAuteur"])){
    //   //On recup les var
    //   $prenom = strtolower($_POST["prenomAuteur"]);
    //   $nom = strtolower($_POST["nomAuteur"]);
    //
    //   //On verifie qu'il existe dans la bdd
    //   $em = $this->getDoctrine()->getManager();
    //   $auteur = $em->getRepository("CdiBundle:Auteur")->findOneBy(["prenom" => $prenom, "nom" => $nom]);
    //
    //   // Si l'auteur n'existe pas on le créer à partir des infos
    //   if(is_null($auteur)){
    //     $auteur = new Auteur();
    //     $auteur->setPrenom($prenom);
    //     $auteur->setNom($nom);
    //     $em->persist($auteur);
    //     $em->flush();
    //   }
    //
    //   //On ajoute l'auteur a l'article
    //   $article->setAuteur($auteur);
    // }
    //
    // //Meme verification pour le motclef
    // if(isset($_POST["motClef"])){
    //   //On recup les var
    //   $motClef = $_POST["motClef"];
    //
    //   //On ajoute le periodique a l'article
    //   $article->setMotClef($motClef);
    // }
    //
    // //Meme verification pour le periodique
    // if(isset($_POST["nomPeriodique"])){
    //   //On recup les var
    //   $nomPeriodique = $_POST["nomPeriodique"];
    //   $em = $this->getDoctrine()->getManager();
    //
    //   $periodique = $em->getRepository("CdiBundle:Periodique")->findOneBy(["nom" => $nomPeriodique]);
    //
    //   // Si le périodique n'existe pas on le créer à partir des infos
    //   if(is_null($periodique)){
    //     $periodique = new Periodique();
    //     $periodique->setNom($nomPeriodique);
    //     $em->persist($periodique);
    //     $em->flush();
    //   }
    //
    //   //On ajout les var
    //   $coteNom = $_POST["cote"];
    //   $em = $this->getDoctrine()->getManager();
    //
    //   $cote = $em->getRepository("CdiBundle:Cote")->findOneBy(["nom" => $coteNom]);
    //
    //   // Si le périodique n'existe pas on le créer à partir des infos
    //   if(is_null($cote)){
    //     $cote = new Cote();
    //     $cote->setNom($coteNom);
    //     $cote->setActif(1);
    //     $em->persist($cote);
    //     $em->flush();
    //   }
    //
    //   //On ajoute le periodique a l'article
    //   $article->setCote($cote);
    // }
    //
    // // Formattage de la date
    // if(isset($request->request->get('form')["dateParution"]) && !is_null($request->request->get('form')["dateParution"])){
    //   $submittedForm = $request->request->get('form');
    //   $submittedDate = \DateTime::createFromFormat('d/m/Y', $submittedForm["dateParution"]);
    //
    //   if($submittedDate != false){
    //     $submittedForm["dateParution"] = $submittedDate->format('Y-m-d');
    //     $request->request->set('form', $submittedForm);
    //   }
    // }
    //
    //
    // $form->handleRequest($request);
    // if($form->isSubmitted() && $form->isValid()){
    //   //Definition de la date d'enregistrement
    //   $dateEnregistrement = new \DateTime();
    //   $article->setDateEnregistrement($dateEnregistrement);
    //     // Enregistrement de l'article dans les sessions
    //   $session = new Session();
    //   $session->set('article', $article);
    //   $em = $this->getDoctrine()->getManager();
    //   $em->persist($article);
    //   $em->flush();
    //
    //   return $this->redirect($this->generateUrl('article_ajout'));
    // }




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
    $rechercheAvancee1 = $request->request->get('rechercheAvancee');
    $typeRechercheAvancee1 = $request->request->get('typeAvancee');
    $operateur1 = $request->request->get('operateur');

    //Récupération des champs de la recherche avancée 2
    $rechercheAvancee2 = $request->request->get('rechercheAvancee2');
    $typeRechercheAvancee2 = $request->request->get('typeAvancee2');
    $operateur2 = $request->request->get('operateur2');

    //Récupération des champs de la recherche avancée 3
    $rechercheAvancee3 = $request->request->get('rechercheAvancee3');
    $typeRechercheAvancee3 = $request->request->get('typeAvancee3');
    $operateur3 = $request->request->get('operateur3');

    /*
    Types des champs :
    - tousChamps
    - titre
    - periodique
    - auteur
    - motclef
    */

    $avancee1 = [];
    $avancee2 = [];
    $avancee3 = [];

    if(empty($rechercheAvancee1)) {
      $avancee1 = null;
      $operateur1 = null;
    } else {
      $avancee1 = [$operateur1, $typeRechercheAvancee1, $rechercheAvancee1];
      if(empty($rechercheAvancee2)) {
        $avancee2 = null;
        $operateur2 = null;
      } else {
        $avancee2 = [$operateur2, $typeRechercheAvancee2, $rechercheAvancee2];
        if(empty($rechercheAvancee3)) {
          $avancee3 = null;
          $operateur3 = null;
        } else {
          $avancee3 = [$operateur3, $typeRechercheAvancee3, $rechercheAvancee3];
        }
      }
    }

    $dateType = $request->request->get('dateSearchType');
    if($dateType > 0){
      $dateDeb = $request->request->get('dateDebut');
      if($dateType > 2){
        $dateFin = $request->request->get('dateFin');
      } else {
        $dateFin = null;
      }
    } else {
      $dateDeb = null;
      $dateFin = null;
    }

    $date = [$dateType, $dateDeb, $dateFin];

    $donneesRecherche = [  [$typeRecherche, $recherche],
    $avancee1,
    $avancee2,
    $avancee3,
    $date];

    $em = $this->getDoctrine()->getManager();
    $articleRepository = $em->getRepository("CdiBundle:Article");

    $articles = $articleRepository->recherche($donneesRecherche);



    return $this->render("CdiBundle:Article:resultatRecherche.html.twig",
    ['articles' => $articles, 'recherche' => $recherche, 'recherche1' => $rechercheAvancee1,'recherche2' => $rechercheAvancee2,'recherche3' => $rechercheAvancee3]);
  }

  public function pdfAction($id){
    $em = $this->getDoctrine()->getManager();
    $articleRepository = $em->getRepository("CdiBundle:Article");
    $article = $articleRepository->find($id);
    PdfController::generatePDF($article);
    return $this->voirAction($id);
  }

  public function dossierAction(){
    // On redirige les utilisateurs non-connectés
    if(!UserController::isConnected())
    return $this->redirect($this->generateUrl("redirection_accueil"));

    // Récupération des champs
    $request = Request::createFromGlobals();
    $dateDeb = \DateTime::createFromFormat('d/m/Y', $request->request->get('dateDebut'));

    // Si le champ date fin n'a pas ete rempli
    $tmp = $request->request->get('dateFin');
    if (empty($tmp)){
      $dateFin = \DateTime::createFromFormat('d/m/Y', $request->request->get('dateDebut'));
      $dateFin->add(new \DateInterval('P1M'));
    }
    else{
      $dateFin = \DateTime::createFromFormat('d/m/Y', $tmp);
    }

    // Recuperation des articles
    $em = $this->getDoctrine()->getManager();
    $articleRepository = $em->getRepository("CdiBundle:Article");
    $articles = $articleRepository->getArticlesPdf($dateDeb, $dateFin);

    // Generation du pdf
    PdfController::generateDossierPDFAction($articles, $dateDeb, $dateFin);



    return $this->listeAction();
  }

  // Affiche la la vue de la liste des articles pour un périodique
  public function listeArticlePeriodiqueAction($periodique)
  {
    // On redirige les utilisateurs non-connectés
    if(!UserController::isConnected())
    return $this->redirect($this->generateUrl("redirection_accueil"));

    // Récupération du nom du périodique
    $periodique;

    $donneesRecherche = [  ["periodique", $periodique],
    NULL,
    NULL];

    $em = $this->getDoctrine()->getManager();
    $articleRepository = $em->getRepository("CdiBundle:Article");

    $articles = $articleRepository->recherche($donneesRecherche);

    return $this->render("CdiBundle:Article:resultatRecherche.html.twig", ['articles' => $articles]);
  }

  // Affiche la la vue de la liste des articles pour un auteur
  public function listeArticleAuteurAction($prenom, $nom)
  {
    // On redirige les utilisateurs non-connectés
    if(!UserController::isConnected())
    return $this->redirect($this->generateUrl("redirection_accueil"));

    // Récupération du nom de l'auteur
    $auteur = $prenom." ".$nom;

    $donneesRecherche = [  ["auteur", $auteur],
    NULL,
    NULL];

    $em = $this->getDoctrine()->getManager();
    $articleRepository = $em->getRepository("CdiBundle:Article");

    $articles = $articleRepository->recherche($donneesRecherche);

    return $this->render("CdiBundle:Article:resultatRecherche.html.twig", ['articles' => $articles]);
  }

  // Affiche la la vue de la liste des articles pour une cote
  public function listeArticleCoteAction($nom)
  {
    // On redirige les utilisateurs non-connectés
    if(!UserController::isConnected())
    return $this->redirect($this->generateUrl("redirection_accueil"));

    $donneesRecherche = [  ["cote", $nom],
    NULL,
    NULL];

    $em = $this->getDoctrine()->getManager();
    $articleRepository = $em->getRepository("CdiBundle:Article");

    $articles = $articleRepository->recherche($donneesRecherche);

    return $this->render("CdiBundle:Article:resultatRecherche.html.twig", ['articles' => $articles]);
  }
}
