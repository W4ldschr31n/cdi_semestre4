<?php

namespace CdiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CdiBundle\Repository\RedactionRepository;
/**
* ArticleRepository
*
* This class was generated by the Doctrine ORM. Add your own custom
* repository methods below.
*/
class ArticleRepository extends EntityRepository
{




  public function getArticlesPdf($dateDeb, $dateFin){
    $queryBuilder = $this->createQueryBuilder('a');

    $queryBuilder->select(['a', 'p', 'au', 'c']);
    $queryBuilder->leftJoin("a.periodique", "p");
    $queryBuilder->leftJoin("a.auteur", "au");
    $queryBuilder->leftJoin("a.cote", "c");
    $queryBuilder->andWhere('a.dateParution >= :dateDeb');
    $queryBuilder->andWhere('a.dateParution <= :dateFin');
    $queryBuilder->setParameter('dateDeb', $dateDeb, \Doctrine\DBAL\Types\Type::DATETIME);
    $queryBuilder->setParameter('dateFin', $dateFin, \Doctrine\DBAL\Types\Type::DATETIME);

    $res = $queryBuilder->getQuery()->getResult();
    return $res;
  }
  /**
  * Méthode de recherche
  */
  public function recherche(array $recherche){
    $queryBuilder = $this->createQueryBuilder('a');


    /*
    if(isset($recherche[4])){
    $strDate = 'a.dateparution > ' .$recherche[4] . " AND ";
  }
  else{*/
  $strDate ="";
  /*
}
*/

// Gestion du premier champs de recherche
if(is_array($recherche[0])){
  $type = $recherche[0][0];
  $valeur = $recherche[0][1];


  $queryBuilder->select(['a', 'p', 'au', 'c']);
  $queryBuilder->leftJoin("a.periodique", "p");
  $queryBuilder->leftJoin("a.auteur", "au");
  $queryBuilder->leftJoin("a.auteur2", "au2");
  $queryBuilder->leftJoin("a.auteur3", "au3");
  $queryBuilder->leftJoin("a.cote", "c");
  // $queryBuilder->add("from",RedactionRepository::lien());
  /*            $queryBuilder->leftJoin("a.id", "r");
  $queryBuilder->leftJoin("r.auteur_id", "au");

  /*
  $queryBuilder->select('a');
  $queryBuilder->from('article', 'art');
  $querBuilder->from('periodique', 'p', "WITH", "p.id=a.periodique_id");
  $queryBuilder->from('cote','c',"WITH","c.id=a.cote_id");
  $queryBuilder->from('redaction', 'r', 'WITH', 'r.article_id=a.id');
  $queryBuilder->from('auteur','au','WITH','r.auteur_id=au.id');



  $queryBuilder->add('select', 'a, p, au, c')
  ->add('from', 'CdiBundle\Entity\Article a, CdiBundle\Entity\Periodique p, CdiBundle\Entity\Cote c, CdiBundle\Entity\Auteur au')
  ->add('where', 'a.id=r.article_id and r.auteur_id=au.id and p.id=a.periodique_id and c.id=a.cote_id');

  */

  $auteur = split(" ", $valeur, 2);

  $chaineTousChamps = $strDate . '(a.titre LIKE :valeur OR a.resume LIKE :valeur OR p.nom LIKE :valeur OR c.nom LIKE :valeur) ';

  $chaineAuteur =(count($auteur) >= 2
  ? 'OR (au.prenom LIKE :prenom AND au.nom LIKE :nom) OR (au.prenom LIKE :nom AND au.nom LIKE :prenom)
  OR (au2.prenom LIKE :prenom AND au2.nom LIKE :nom) OR (au2.prenom LIKE :nom AND au2.nom LIKE :prenom)
  OR (au3.prenom LIKE :prenom AND au3.nom LIKE :nom) OR (au3.prenom LIKE :nom AND au3.nom LIKE :prenom)'
  :'OR (au.prenom LIKE :auteur OR au.nom LIKE :auteur)
  OR (au2.prenom LIKE :auteur OR au2.nom LIKE :auteur)
  OR (au3.prenom LIKE :auteur OR au3.nom LIKE :auteur)');



  switch($type){
    case "tousChamps":
    $queryBuilder->where($chaineTousChamps . $chaineAuteur);
    $queryBuilder->setParameter('valeur', "%".$valeur."%");
    if(count($auteur) >= 2){
      $queryBuilder->setParameter('prenom', "%".$auteur[0]."%");
      $queryBuilder->setParameter('nom', "%".$auteur[1]."%");
    }
    if(count($auteur)==1){
      $queryBuilder->setParameter('auteur', "%".$auteur[0]."%");
    }
    break;
    case "titre":
    $queryBuilder->where('a.titre LIKE :valeur');
    $queryBuilder->setParameter('valeur', "%".$valeur."%");
    break;
    case "periodique":
    $queryBuilder->where('p.nom LIKE :valeur');
    $queryBuilder->setParameter('valeur', "%".$valeur."%");
    break;
    case "cote":
    $queryBuilder->where('c.nom LIKE :valeur');
    $queryBuilder->setParameter('valeur', "%".$valeur."%");
    break;
    case "motclef":
    $queryBuilder->where('a.motClef LIKE :valeur');
    $queryBuilder->setParameter('valeur', "%".$valeur."%");
    break;
    case "auteur":

    // Si on a eu une saisie prenom + nom où nom + prenom.
    if(count($auteur) >= 2){
      $queryBuilder->where('(au.prenom LIKE :prenom AND au.nom LIKE :nom) OR (au.prenom LIKE :nom AND au.nom LIKE :prenom)');
      $queryBuilder->setParameter('prenom', "%".$auteur[0]."%");
      $queryBuilder->setParameter('nom', "%".$auteur[1]."%");
    }

    // Si on a eu une saisie prenom où nom seulement.
    else if(count($auteur) == 1){
      $queryBuilder->where('(au.prenom LIKE :auteur OR au.nom LIKE :auteur)');
      $queryBuilder->setParameter('auteur', "%".$auteur[0]."%");
    }
    break;
  }
}

// Gestion du deuxième champ de recherche
if(!empty($recherche[1])){
  $operateur2 = $recherche[1][0];
  $type2 = $recherche[1][1];
  $valeur2 = $recherche[1][2];
  $auteur2 = split(" ", $valeur2, 2);

  $chaineTousChamps2 = '(a.titre LIKE :valeur2 OR a.resume LIKE :valeur2 OR p.nom LIKE :valeur2 OR c.nom LIKE :valeur2) ';

  $chaineAuteur2 =(count($auteur2) >= 2
  ? 'OR (au.prenom LIKE :prenom2 AND au.nom LIKE :nom2) OR (au.prenom LIKE :nom2 AND au.nom LIKE :prenom2)
  OR (au2.prenom LIKE :prenom2 AND au2.nom LIKE :nom2) OR (au2.prenom LIKE :nom2 AND au2.nom LIKE :prenom2)
  OR (au3.prenom LIKE :prenom2 AND au3.nom LIKE :nom2) OR (au3.prenom LIKE :nom2 AND au3.nom LIKE :prenom2)'
  :'OR (au.prenom LIKE :auteur2 OR au.nom LIKE :auteur2)
  OR (au2.prenom LIKE :auteur2 OR au2.nom LIKE :auteur2)
  OR (au3.prenom LIKE :auteur2 OR au3.nom LIKE :auteur2)');


  switch($type2){
    case "tousChamps":
    if($operateur2 == "et")
    $queryBuilder->andWhere($chaineTousChamps2 . $chaineAuteur2);
    else if($operateur2 == "ou")
    $queryBuilder->orWhere($chaineTousChamps2 . $chaineAuteur2);
    else if($operateur2 == "sauf")
    $queryBuilder->andWhere('NOT ('.$chaineTousChamps2 . $chaineAuteur2 .')');
    $queryBuilder->setParameter('valeur2', "%".$valeur2."%");
    if(count($auteur2) >= 2){
      $queryBuilder->setParameter('prenom2', "%".$auteur2[0]."%");
      $queryBuilder->setParameter('nom2', "%".$auteur2[1]."%");
    }
    if(count($auteur2)==1){
      $queryBuilder->setParameter('auteur2', "%".$auteur2[0]."%");
    }
    break;
    case "titre":
    $chaineTitre2 = 'a.titre LIKE :titre2';
    if($operateur2 == "et")
    $queryBuilder->andWhere($chaineTitre2);
    else if($operateur2 == "ou")
    $queryBuilder->orWhere($chaineTitre2);
    else if($operateur2 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chaineTitre2);
    $queryBuilder->setParameter('titre2', "%" . $valeur2."%");
    break;
    case "periodique":
    $chainePerio2 = 'p.nom LIKE :periodique2';
    if($operateur2 == "et")
    $queryBuilder->andWhere($chainePerio2);
    else if($operateur2 == "ou")
    $queryBuilder->orWhere($chainePerio2);
    else if($operateur2 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chainePerio2);

    $queryBuilder->setParameter('periodique2', "%".$valeur2."%");
    break;

    case "cote":
    $chaineCote2 = 'c.nom LIKE :valeur2';
    if($operateur2 == "et")
    $queryBuilder->andWhere($chaineCote2);
    else if($operateur2 == "ou")
    $queryBuilder->orWhere($chaineCote2);
    else if($operateur2 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chaineCote2);
    $queryBuilder->setParameter('valeur2', "%".$valeur2."%");
    break;
    case "motclef":
    $chaineMotClef2 = 'a.motClef LIKE :valeur2';
    if($operateur2 == "et")
    $queryBuilder->andWhere($chaineMotClef2);
    else if($operateur2 == "ou")
    $queryBuilder->orWhere($chaineMotClef2);
    else if($operateur2 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chaineMotClef2);
    $queryBuilder->setParameter('valeur2', "%".$valeur2."%");
    break;
    case "auteur":
    // Si on a eu une saisie prenom + nom où nom + prenom.
    $chaineAuteurBi2 = '(au.prenom LIKE :prenom2 AND au.nom LIKE :nom2) OR (au.prenom LIKE :nom2 AND au.nom LIKE :prenom2)';
    if(count($auteur2) >= 2){
      if($operateur2 == "et")
      $queryBuilder->andWhere($chaineAuteurBi2);
      else if($operateur2 == "ou")
      $queryBuilder->orWhere($chaineAuteurBi2);
      else if($operateur2 == "sauf")
      $queryBuilder->andWhere('NOT (' . $chaineAuteurBi2 . ')');

      $queryBuilder->setParameter('prenom2', "%".$auteur2[0]."%");
      $queryBuilder->setParameter('nom2', "%".$auteur2[1]."%");
    }

    // Si on a eu une saisie prenom où nom seulement.
    else if(count($auteur2) == 1){
      $chaineAuteurUni = '(au.prenom LIKE :auteur2 OR au.nom LIKE :auteur2)';
      if($operateur2 == "et")
      $queryBuilder->andWhere($chaineAuteurUni);
      else if($operateur2 == "ou")
      $queryBuilder->orWhere($chaineAuteurUni);
      else if($operateur2 == "sauf")
      $queryBuilder->andWhere('NOT (' . $chaineAuteurUni . ')');

      $queryBuilder->setParameter('auteur2', "%".$auteur2[0]."%");
    }
    break;
  }
}

// Gestion du troisième champ de recherche
if(!empty($recherche[2])){
  $operateur3 = $recherche[1][0];
  $type3 = $recherche[1][1];
  $valeur3 = $recherche[1][2];
  $auteur3 = split(" ", $valeur3, 2);

  $chaineTousChamps3 = '(a.titre LIKE :valeur3 OR a.resume LIKE :valeur3 OR p.nom LIKE :valeur3) ';

  $chaineAuteur3 =(count($auteur3) >= 2
  ? 'OR (au.prenom LIKE :prenom3 AND au.nom LIKE :nom3) OR (au.prenom LIKE :nom3 AND au.nom LIKE :prenom3)
  OR (au2.prenom LIKE :prenom3 AND au2.nom LIKE :nom3) OR (au2.prenom LIKE :nom3 AND au2.nom LIKE :prenom3)
  OR (au3.prenom LIKE :prenom3 AND au3.nom LIKE :nom3) OR (au3.prenom LIKE :nom3 AND au3.nom LIKE :prenom3)'
  :'OR (au.prenom LIKE :auteur3 OR au.nom LIKE :auteur3)
  OR (au2.prenom LIKE :auteur3 OR au2.nom LIKE :auteur3)
  OR (au3.prenom LIKE :auteur3 OR au3.nom LIKE :auteur3)');


  switch($type3){
    case "tousChamps":
    if($operateur3 == "et")
    $queryBuilder->andWhere($chaineTousChamps3 . $chaineAuteur3);
    else if($operateur3 == "ou")
    $queryBuilder->orWhere($chaineTousChamps3 . $chaineAuteur3);
    else if($operateur3 == "sauf")
    $queryBuilder->andWhere('NOT ('.$chaineTousChamps3 . $chaineAuteur3 .')');
    $queryBuilder->setParameter('valeur3', "%".$valeur3."%");
    if(count($auteur3) >= 2){
      $queryBuilder->setParameter('prenom3', "%".$auteur3[0]."%");
      $queryBuilder->setParameter('nom3', "%".$auteur3[1]."%");
    }
    if(count($auteur3)==1){
      $queryBuilder->setParameter('auteur3', "%".$auteur3[0]."%");
    }
    break;
    case "titre":
    $chaineTitre3 = 'a.titre LIKE :titre3';
    if($operateur3 == "et")
    $queryBuilder->andWhere($chaineTitre3);
    else if($operateur3 == "ou")
    $queryBuilder->orWhere($chaineTitre3);
    else if($operateur3 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chaineTitre3);
    $queryBuilder->setParameter('titre3', "%" . $valeur3."%");
    break;
    case "periodique":
    $chainePerio3 = 'p.nom LIKE :periodique3';
    if($operateur3 == "et")
    $queryBuilder->andWhere($chainePerio3);
    else if($operateur3 == "ou")
    $queryBuilder->orWhere($chainePerio3);
    else if($operateur3 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chainePerio3);

    $queryBuilder->setParameter('periodique3', "%".$valeur3."%");
    break;
    case "motclef":
    $chaineMotClef3 = 'a.motClef LIKE :valeur3';
    if($operateur3 == "et")
    $queryBuilder->andWhere($chaineMotClef3);
    else if($operateur3 == "ou")
    $queryBuilder->orWhere($chaineMotClef3);
    else if($operateur3 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chaineMotClef3);
    $queryBuilder->setParameter('valeur3', "%".$valeur3."%");
    break;
    case "cote":
    $chainceCote3 = 'c.nom LIKE :valeur3';
    if($operateur3 == "et")
    $queryBuilder->andWhere($chaineCote3);
    else if($operateur3 == "ou")
    $queryBuilder->orWhere($chaineCote3);
    else if($operateur3 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chaineCote3);
    $queryBuilder->setParameter('valeur3', "%".$valeur3."%");
    break;
    case "auteur":
    // Si on a eu une saisie prenom + nom où nom + prenom.
    $chaineAuteurBi3 = '(au.prenom LIKE :prenom3 AND au.nom LIKE :nom3) OR (au.prenom LIKE :nom3 AND au.nom LIKE :prenom3)';
    if(count($auteur3 >= 2)){
      if($operateur3 == "et")
      $queryBuilder->andWhere($chaineAuteurBi3);
      else if($operateur3 == "ou")
      $queryBuilder->orWhere($chaineAuteurBi3);
      else if($operateur3 == "sauf")
      $queryBuilder->andWhere('NOT (' . $chaineAuteurBi3 . ')');

      $queryBuilder->setParameter('prenom3', "%".$auteur3[0]."%");
      $queryBuilder->setParameter('nom3', "%".$auteur3[1]."%");
    }

    // Si on a eu une saisie prenom où nom seulement.
    else if(count($auteur3) == 1){
      $chaineAuteurUni = '(au.prenom LIKE :auteur3 OR au.nom LIKE :auteur3)';
      if($operateur3 == "et")
      $queryBuilder->andWhere($chaineAuteurUni);
      else if($operateur3 == "ou")
      $queryBuilder->orWhere($chaineAuteurUni);
      else if($operateur3 == "sauf")
      $queryBuilder->andWhere('NOT (' . $chaineAuteurUni . ')');

      $queryBuilder->setParameter('auteur3', "%".$auteur3[0]."%");
    }
    break;
  }
}

// Gestion du 4deuxième champs de recherche
if(!empty($recherche[3])){
  $operateur4 = $recherche[1][0];
  $type4 = $recherche[1][1];
  $valeur4 = $recherche[1][2];
  $auteur4 = split(" ", $valeur4, 2);

  $chaineTousChamps4 = '(a.titre LIKE :valeur4 OR a.resume LIKE :valeur4 OR p.nom LIKE :valeur4) ';

  $chaineAuteur4 =(count($auteur4) >= 2
  ? 'OR (au.prenom LIKE :prenom4 AND au.nom LIKE :nom4) OR (au.prenom LIKE :nom4 AND au.nom LIKE :prenom4)
  OR (au2.prenom LIKE :prenom4 AND au2.nom LIKE :nom4) OR (au2.prenom LIKE :nom4 AND au2.nom LIKE :prenom4)
  OR (au3.prenom LIKE :prenom4 AND au3.nom LIKE :nom4) OR (au3.prenom LIKE :nom4 AND au3.nom LIKE :prenom4)'
  :'OR (au.prenom LIKE :auteur4 OR au.nom LIKE :auteur4)
  OR (au2.prenom LIKE :auteur4 OR au2.nom LIKE :auteur4)
  OR (au3.prenom LIKE :auteur4 OR au3.nom LIKE :auteur4)');


  switch($type4){
    case "tousChamps":
    if($operateur4 == "et")
    $queryBuilder->andWhere($chaineTousChamps4 . $chaineAuteur4);
    else if($operateur4 == "ou")
    $queryBuilder->orWhere($chaineTousChamps4 . $chaineAuteur4);
    else if($operateur4 == "sauf")
    $queryBuilder->andWhere('NOT ('.$chaineTousChamps4 . $chaineAuteur4 .')');
    $queryBuilder->setParameter('valeur4', "%".$valeur4."%");
    if(count($auteur4) >= 2){
      $queryBuilder->setParameter('prenom4', "%".$auteur4[0]."%");
      $queryBuilder->setParameter('nom4', "%".$auteur4[1]."%");
    }
    if(count($auteur4)==1){
      $queryBuilder->setParameter('auteur4', "%".$auteur4[0]."%");
    }
    break;
      case "titre":
    $chaineTitre4 = 'a.titre LIKE :titre4';
    if($operateur4 == "et")
    $queryBuilder->andWhere($chaineTitre4);
    else if($operateur4 == "ou")
    $queryBuilder->orWhere($chaineTitre4);
    else if($operateur4 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chaineTitre4);
    $queryBuilder->setParameter('titre4', "%" . $valeur4."%");
    break;
      case "periodique":
    $chainePerio4 = 'p.nom LIKE :periodique4';
    if($operateur4 == "et")
    $queryBuilder->andWhere($chainePerio4);
    else if($operateur4 == "ou")
    $queryBuilder->orWhere($chainePerio4);
    else if($operateur4 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chainePerio4);
    $queryBuilder->setParameter('periodique4', "%".$valeur4."%");
    break;
      case "cote":
    $chaineCote4 = 'c.nom LIKE :valeur4';
    if($operateur4 == "et")
    $queryBuilder->andWhere($chaineCote4);
    else if($operateur4 == "ou")
    $queryBuilder->orWhere($chaineCote4);
    else if($operateur4 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chaineCote4);
    $queryBuilder->setParameter('valeur4', "%".$valeur4."%");
    break;
    case "motclef":
    $chaineMotClef4 = 'a.motClef LIKE :valeur4';
    if($operateur4 == "et")
    $queryBuilder->andWhere($chaineMotClef4);
    else if($operateur4 == "ou")
    $queryBuilder->orWhere($chaineMotClef4);
    else if($operateur4 == "sauf")
    $queryBuilder->andWhere('NOT ' . $chaineMotClef4);
    $queryBuilder->setParameter('valeur4', "%".$valeur4."%");
    break;
    case "auteur":
    // Si on a eu une saisie prenom + nom où nom + prenom.
    $chaineAuteurBi4 = '(au.prenom LIKE :prenom4 AND au.nom LIKE :nom4) OR (au.prenom LIKE :nom4 AND au.nom LIKE :prenom4)';
    if(count($auteur4) >= 2){
      if($operateur4 == "et")
      $queryBuilder->andWhere($chaineAuteurBi4);
      else if($operateur4 == "ou")
      $queryBuilder->orWhere($chaineAuteurBi4);
      else if($operateur4 == "sauf")
      $queryBuilder->andWhere('NOT (' . $chaineAuteurBi4 . ')');

      $queryBuilder->setParameter('prenom4', "%".$auteur4[0]."%");
      $queryBuilder->setParameter('nom4', "%".$auteur4[1]."%");
    }

    // Si on a eu une saisie prenom où nom seulement.
    else if(count($auteur4) == 1){

      $chaineAuteurUni = '(au.prenom LIKE :auteur4 OR au.nom LIKE :auteur4)';
      if($operateur4 == "et")
      $queryBuilder->andWhere($chaineAuteurUni);
      else if($operateur4 == "ou")
      $queryBuilder->orWhere($chaineAuteurUni);
      else if($operateur4 == "sauf")
      $queryBuilder->andWhere('NOT (' . $chaineAuteurUni . ')');

      $queryBuilder->setParameter('auteur4', "%".$auteur4[0]."%");
    }
    break;
  }
}

if(!empty($recherche[4])){ //Recherche avec les dates
  $typeSearch = $recherche[4][0];
  if (!empty($recherche[4][1])) { // On ne fait la requête que si le champ n'est pas vide
    if($typeSearch > 0){
      $dateDeb = \DateTime::createFromFormat('d/m/Y', $recherche[4][1]);
    }
    if($typeSearch == 1){ //Depuis
      $queryBuilder->andWhere('a.dateParution >= :dateDeb');
      $queryBuilder->setParameter('dateDeb', $dateDeb, \Doctrine\DBAL\Types\Type::DATETIME);
    } else if($typeSearch == 2){ //Jusqua
      $queryBuilder->andWhere('a.dateParution <= :dateDeb');
      $queryBuilder->setParameter('dateDeb', $dateDeb, \Doctrine\DBAL\Types\Type::DATETIME);
    } else if($typeSearch == 3 && !empty($recherche[4][2])){ //Entre
      $dateFin = \DateTime::createFromFormat('d/m/Y', $recherche[4][2]);
      $queryBuilder->andWhere('a.dateParution >= :dateDeb');
      $queryBuilder->andWhere('a.dateParution <= :dateFin');
      $queryBuilder->setParameter('dateDeb', $dateDeb, \Doctrine\DBAL\Types\Type::DATETIME);
      $queryBuilder->setParameter('dateFin', $dateFin, \Doctrine\DBAL\Types\Type::DATETIME);
    }
  }

}

$res = $queryBuilder->getQuery()->getResult();
return $res;
}

public function count(){
  return $this->createQueryBuilder('a')->select('COUNT(a)')->getQuery()->getSingleScalarResult();
}

public function getArticlesFromPage($page, $nbArticlesParPage = 20){
  // Gestion première page
  if($page == null)
  $page = 1;

  // Récupération et calcul des infos
  $count = $this->count();
  $premierArticle = ($page-1) * $nbArticlesParPage;

  // Vérification
  if($premierArticle > $count || $premierArticle < 0)
  return false;


  $queryBuilder = $this->createQueryBuilder('a');
  $queryBuilder->setMaxResults($nbArticlesParPage)->setFirstResult($premierArticle)->orderBy('a.dateParution', 'DESC');

  $res = $queryBuilder->getQuery()->getResult();
  return $res;
}
}