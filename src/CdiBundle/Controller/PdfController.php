<?php
namespace CdiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Controller\UserController;
use CdiBundle\Entity\Auteur;
use CdiBundle\Entity\Periodique;
use CdiBundle\Entity\Article;
use CdiBundle\Entity\Cote;

class PdfController extends Controller
{

  public function generationInterfaceAction(){
    // On redirige les utilisateurs non-connectés
    if(!UserController::isConnected())
    return $this->redirect($this->generateUrl("redirection_accueil"));

    return $this->render('CdiBundle:Pdf:generation.html.twig');
  }


  //passer un array d'articles
//$html .= $pdf->unArticle($article);
//SetDisplayMode définit la manière dont le document PDF va être affiché par l’utilisateur
//fullpage : affiche la page entière sur l'écran
//fullwidth : utilise la largeur maximum de la fenêtre
//real : utilise la taille réelle
// on génère le sommaire
  //writeHTML va tout simplement prendre la vue stocker dans la variable $html pour la convertir en format PDF
//Output envoit le document PDF au navigateur internet avec un nom spécifique qui aura un rapport avec le contenu à convertir (exemple : Facture, Règlement…)
  //FINTEST
  public static function generatePDF($article){
    $pdf =new pdf($article);
    $html ="Article :" . $article->getTitre() ."<br />Resume : ". $article->getResume();

  $html2pdf = new \Html2Pdf_Html2Pdf('P','A4','fr');
  $html2pdf->createIndex('Sommaire', 25, 12, true, true);


  $html2pdf->pdf->SetDisplayMode('real');


  $html2pdf->writeHTML($html);

  $html2pdf->Output("test.pdf", 'D');

  }

  public static function generateDossierPDFAction($articles, $dateDebut, $dateFin){

  /*  $em = $this->getDoctrine()->getManager();
    $articleRepository = $em->getRepository("CdiBundle:Article");

    $articles = $articleRepository->articlesPdf();*/
    $pdf = new pdf($articles, $dateDebut, $dateFin);

  }
}

class pdf {

    private $articles;
    private $articlesTrier;
    private $cotes;
    private $dateDebut;
    private $dateFin;
    private $page;


    public function __construct($articles, $dateDebut="dateInconnue", $dateFin="dateInconnue"){
        // Initialisation des variables :
        $this->articles = $articles;
        $this->cotes = array();
        $this->articlesTrier = $this->trieParCote();
        $this->dateFin = $dateFin;
        $this->dateDebut = $dateDebut;

        $this->page = "";

        // Traitement
        $this->trieParCote();
        $this->fullPdf();
        $html = $this->page;

        // Generation du PDF
        $html2pdf = new \Html2Pdf_Html2Pdf('P','A4','fr');
        $html2pdf->pdf->SetDisplayMode('real');
        $html2pdf->writeHTML($html);
        $html2pdf->Output("dossierPDF.pdf", 'D');


    }



    public function pageDeGarde(){
      return '<page A4>

        <div style="text-align : center; position: absolute; left : 0%;">
          ici il devrait y avoir l image de l iut
          <!--<img src="http://www.iutnantes.univ-nantes.fr/images/logos/iutNantes.jpg?v=20150403" alt="logoIut" style="position: absolute; top: 0px; left: 0px;width:200;height:200;">-->
        </div>

        <div style="text-align : center; position: absolute; right : 0%;" >
          <h4>Centre de documentation</h4>
          Sites Joffre et La Fleuriaye<br>
          02 40 30 60 04 ou 02 28 09 20 79<br>
          http://biblio.iut-nantes.univ-nantes.prive/<br>
        </div>

        <div style="text-align : center; position : relative; top : 30%; ">
          <h1 style="font-size: 55px;">Revue d\'articles de periodiques</h1>
          <h2>'.$this->dateDebut->format("Y-m-d").' au '.$this->dateFin->format("Y-m-d").'</h2>
        </div>

        <div style="text-align : center; position : relative; top : 20%; ">
          <h3>Départements :</h3>
          Informatique<br>
          Gestion des entreprises et administration<br>
          Genie electrique et informatique industrielle<br>
          Genie mecanique et productique<br>
          Genie thermique et energie<br>
          Science et genie des materiaux<br>
          Qualite logistique industrielle et organisation<br>
        </div>

      </page>';
    }

    public function pagePeriodiqueDepouille(){
        // Création d'un tableau trier comptenant tout les periodiques trier :
        $tabPeriodique = array();
        foreach ($this->articles as $article) {
          if (!in_array($article->getPeriodique()->getNom(), $tabPeriodique)){
            array_push($tabPeriodique, $article->getPeriodique()->getNom());
          }
        }
        array_unique($tabPeriodique);
        sort($tabPeriodique);

        // Création de l'html correspondant :
        $html = "<page A4>";
        $html .= "<h1 style='text-align : center; font-size: 40px;'>Periodiques dépouillés :</h1>";
        foreach ($tabPeriodique as $row){
            $html .= ''.$row.'<br>';
        }

        return $html."</page>";
    }

    public function fullPdf(){
        $this->page .= $this->pageDeGarde();
        $this->page .= $this->sommaire();
        $this->page .= $this->pagePeriodiqueDepouille();
        $this->page .= $this->pagesArticles();
    }

    public function sommaire(){
        $html = "<page A4>";
        $page = 3;
        $html .= "<h1 style='text-align : center; font-size: 40px;'>Sommaire :</h1>";
        $html .= "<table style='width : 100%;'>";
        foreach ($this->articlesTrier as $categorie) {
            $html .= "<tr><td style='text-align: left; width : 24%;'>".strtoupper($categorie[0]->getCote()->getNom())."</td>";
            $html .= "<td style='text-align : center; right : 25%; width : 50%;'> ------------------------------------------------------------------------------------------ </td>";
            $html .= "<td style='text-align: right; right : 0%; width : 24%;'> Page : ".$page."</td></tr>";
            $page += ceil(count($categorie)/3);
        }
        $html .= "</table>";
        return $html."</page>";
    }

    public function pagesArticles(){
        $html = "";
        foreach ($this->articlesTrier as $categorie) {
            $nbr = 0;
            $html .= "<page A4>";
            $html .= "<h1 style='text-align : center; font-size: 40px;'>".strtoupper($categorie[0]->getCote()->getNom())." :</h1><br>";
            foreach ($categorie as $elmt){
                $html .= $this->unArticle($elmt);
                $nbr ++;
                if ($nbr%3 == 0){ // Si on est au 3eme article alors on change de page
                    $html .= "</page><page A4>";
                }
            }
            $html .= "</page>";
        }
        return $html;
    }

    public function unArticle($article){
        $htmlarticle = "<div class='article' style='height : 30%; vertical-align:middle;'>";
        $htmlarticle .= '<h1 class="titreArticle" style="text-align : center; font-size: 20px;">'.$article->getTitre().'</h1>';
        $htmlarticle .= '<h3 class="motClef" style="text-align : center; font-size: 10px; font-style:italic;">'.$article->getMotClef().'</h3>';
        $htmlarticle .= '<br><div class="resume" style="text-align:justify;">'.$article->getResume().'</div>';
        $htmlarticle .= '<br><table style="width : 100%;""><tr><td style="width : 33%;"><div class="auteur" style="text-align : center;">'.$article->getAuteur()->getPrenom()." ".$article->getAuteur()->getNom().'</div></td>';
        $htmlarticle .= '<td style="width : 33%;"><div class="nomDeLaRevue" style="text-align : center;">'.$article->getPeriodique()->getNom().' ( revue n°'.$article->getNumeroPeriodique().' page n°'.$article->getPageDebut().' )</div></td>';
        $htmlarticle .= '<td style="width : 33%;"><div class="dateDeParution" style="text-align : center;">'.$article->getDateParution()->format('d-m-Y').'</div></td></tr></table>';
        $htmlarticle .= "</div>";

        return $htmlarticle;
    }

    public function trieParCote(){

        $grosTableau = array();

        // Créer les catégories
        foreach ($this->articles as $article) {
          $cote =$article->getCote()->getNom();
            if(!isset($grosTableau[$cote])){
              $grosTableau[$cote] = array();
              $this->cotes[] = $cote;
            }
            array_push($grosTableau[$cote], $article);
        }

        return $grosTableau;
    }

}

 ?>
