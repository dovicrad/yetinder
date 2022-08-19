<?php
namespace App\Controller;
use App\Entity\Address;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\Rating;
use DateTime;
use Twig\Environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Yeti;
use App\Form\YetiFormType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class YetiController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(Request $request, EntityManagerInterface $entityManager,ChartBuilderInterface $chartBuilder): Response
    {
        //get session
        $session = $request->getSession();

        //get array of every yeti id in the database
        $availableYetiIds = $entityManager->createQueryBuilder()
            ->select("yeti.id")
            ->from(Yeti::class, "yeti")
            ->getQuery()->getSingleColumnResult();

        //get previously rated yeti ids
        $idHistory = $session->get("idHistory");
        $previousYetiId = $request->get('yetiId');

        //check if valid rating has occurred
        if(!is_null($previousYetiId) && in_array($previousYetiId, $availableYetiIds))
        {
            $ratingValue = $request->get('value');
            if($ratingValue == 1 || $ratingValue == -1){
                //create new rating and set its data
                $rating = new Rating();
                $rating->setYeti($this->getYetiById($entityManager, $previousYetiId));
                $rating->setYeti($this->getYetiById($entityManager, $previousYetiId));
                $rating->setValue($ratingValue);
                $rating->setDate(new DateTime());

                //push rating to database
                $entityManager->persist($rating);
                $entityManager->flush();

                //add just rated id to history in session
                $idHistory[] = $previousYetiId;
                $session->set("idHistory",$idHistory);
            }
        }

        //get new "your match" yeti
        $newYetiId = $this->pickNewYetiId($availableYetiIds, $idHistory);
        $newYeti = $this->getYetiById($entityManager, $newYetiId);

        //check if request is ajax
        if($request->isXmlHttpRequest()){
            //send ajax response
            return new JsonResponse(json_encode($newYeti?->toDictionary()));
        }

        //get leaderboard
        $leaderboard = $this->getYetiLeaderBoard($entityManager);

        //get statistics chart
        $chart = $this->getRatingChart($entityManager, $chartBuilder);
        //render view
        return $this->render('yetinder.html.twig', ['yeti' => $newYeti?->toDictionary(), 'leaderboard' => $leaderboard, 'chart' => $chart,]);
    }

    /**
     * @Route("/newYeti")
     */
    public function newYetiForm(Environment $twig, Request $request, EntityManagerInterface $entityManager): Response
    {
        //create yeti entity
        $yeti = new Yeti();

        //create form
        $form = $this->createForm(YetiFormType::class,$yeti);
        $form->handleRequest($request);

        //check for valid form submission
        if ($form->isSubmitted() && $form->isValid()){
            //alter entity and link foreign keys
            $yeti->linkAddressToExistingForeignKeys($entityManager);

            //alter entity and change image path
            $yeti->setImage($this->moveImage($form->get('image')->getData()));

            //push yeti to database
            $entityManager->persist($yeti);
            $entityManager->flush();

            //redirect to index
            return $this->redirect('/');
        }

        //render view
        return new Response($twig->render('newyeti.html.twig',['form'=> $form->createView()]));
    }

    /**
     * @param $availableYetiIds array of all possible ids
     * @param $idHistory null|array of all previously used ids
     * @return null|mixed returns either id which is not in history or null
     */
    public function pickNewYetiId(array $availableYetiIds, ?array $idHistory): ?String
    {
        $toBeChecked = count($availableYetiIds);
        //while every yeti has not been checked
        while($toBeChecked > 0)
        {
            $toBeChecked--;

            //select random id and remove it from array
            $newYetiIdIndex = rand(0,count($availableYetiIds)-1);
            $newYetiId = $availableYetiIds[$newYetiIdIndex];
            array_splice($availableYetiIds, $newYetiIdIndex, 1);

            //check if picked id is valid
            if($idHistory == null || !in_array($newYetiId,$idHistory)){
                return $newYetiId;
            }
        }
        return null;
    }

    /**
     * @param $entityManager
     * @param $chartBuilder
     * @return Object object filled with data
     */
    public function getRatingChart($entityManager, $chartBuilder): Object
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        //get data from database
        $ratingData = $entityManager->createQueryBuilder()
            ->setMaxResults(30)
            ->select('Count(rating.id) as ratingCount, Sum(rating.value) as ratingVal, day(rating.date) as day,month(rating.date) as month,year(rating.date) as year')
            ->from(Rating::class, 'rating')
            ->groupBy('day')
            ->addGroupBy('month')
            ->addGroupBy('year')
            ->orderBy('rating.date')
            ->getQuery()->getResult();

        //create and fill arrays with data
        $labels = array();
        $data = array();
        $dataPos = array();
        $dataNeg = array();
        foreach ($ratingData as $day){
            $labels[] = $day["day"] .'. '. $day["month"].".";

            $data[] = $day["ratingCount"];
            $dataPos[] = $day["ratingCount"]/2 + $day["ratingVal"]/2;
            $dataNeg[] = $day["ratingCount"]/2 - $day["ratingVal"]/2;
        }

        //chart setup
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'total number of ratings',
                    'backgroundColor' => 'rgb(74, 99, 222)',
                    'borderColor' => 'rgb(74, 99, 200)',
                    'data' => $data,
                ],
                [
                    'label' => 'number of positive ratings',
                    'backgroundColor' => 'rgb(74, 222, 74)',
                    'borderColor' => 'rgb(74, 200, 74)',
                    'data' => $dataPos,
                ],
                [
                    'label' => 'number of negative ratings',
                    'backgroundColor' => 'rgb(222, 88, 74)',
                    'borderColor' => 'rgb(200, 88, 74)',
                    'data' => $dataNeg,
                ]
            ],
        ]);
        return $chart;
    }

    /**
     * @param $entityManager
     * @return Array of 10 yetis with the greatest rating value
     */
    public function getYetiLeaderBoard($entityManager): Array
    {
        //get timestamp boundaries of current week
        $weekStart =  date("Y-m-d", strtotime("this week")) . ' 00:00:00';
        $now =  date("Y-m-d H:i:s");

        //execute query to get result array
        return $entityManager->createQueryBuilder()
            ->setMaxResults(10)
            ->select('SUM(rating.value) as ratingVal, yeti')
            ->from(Rating::class, 'rating')
            ->join(Yeti::class,'yeti', 'WITH', 'rating.yeti = yeti.id')
            ->where('rating.date BETWEEN :firstDate AND :lastDate')
            ->setParameter('firstDate', $weekStart)
            ->setParameter('lastDate', $now)
            ->groupBy('rating.yeti')
            ->orderBy('ratingVal', 'DESC')
            ->getQuery()->getScalarResult();
    }

    /**
     * @param $entityManager
     * @param $yetiId mixed desired yeti id
     * @return Yeti|null yeti entity  or null if not found
     */
    public function getYetiById($entityManager, mixed $yetiId): ?Yeti
    {
        if($yetiId != null){
            $yetiQuery = $entityManager->createQueryBuilder()
                ->select("yeti, address, city, country")
                ->from(Yeti::class,"yeti")
                ->join(Address::class,"address", 'WITH', 'yeti.address = address.id')
                ->join(City::class,"city",'WITH','address.city = city.id')
                ->join(Country::class,"country",'WITH','city.country = country.id')
                ->where("yeti.id =". $yetiId)
                ->getQuery();
            $result = $yetiQuery->getResult();
            return $result[0];
        }
        return null;
    }

    /**
     * @param $imageFile
     * @return null|String path to image after moving
     */
    public function moveImage($imageFile): ?String
    {
        $newFilename = null;
        if($imageFile != null){
            $newFilename = date('Ymd-His').'_'.rand(10000,99999).'.'.$imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('yeti_directory'),
                $newFilename
            );
        }
        return $newFilename;
    }
}