<?php
namespace App\Controller;
use App\Entity\Address;
use App\Entity\City;
use App\Entity\Country;
use App\Entity\Rating;
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
        $session = $request->getSession();

        $availableYetiIds = $entityManager->createQueryBuilder()
            ->select("yeti.id")
            ->from(Yeti::class, "yeti")
            ->getQuery()->getSingleColumnResult();

        $idHistory = $session->get("idHistory");
        $previousYetiId = $request->get('yetiId');

        if(!is_null($previousYetiId) && in_array($previousYetiId, $availableYetiIds) && abs($request->get('value') == 1))
        {
            $rating = new Rating();
            $rating->setYeti($this->getYetiById($entityManager, $previousYetiId));
            $rating->setValue($request->get('value'));
            $rating->setDate(new \DateTime());

            $entityManager->persist($rating);
            $entityManager->flush();

            $idHistory[] = $previousYetiId;
            $session->set("idHistory",$idHistory);
        }

        $isIdValid = false;
        $newYetiId = $this->pickNewYetiId($)
        while(!$isIdValid)
        {
            $newYetiId = null;
            if(count($availableYetiIds) == 0){
                break;
            }

            $newYetiIdIndex = rand(0,count($availableYetiIds)-1);
            $newYetiId = $availableYetiIds[$newYetiIdIndex];
            array_splice($availableYetiIds, $newYetiIdIndex, 1);
            if($idHistory == null || !in_array($newYetiId,$idHistory)){
                $isIdValid = true;
            }
        }

        $newYeti = $this->getYetiById($entityManager, $newYetiId);
        $leaderboard = $this->getYetiLeaderBoard($entityManager);

        if($request->isXmlHttpRequest()){
            return new JsonResponse(json_encode($newYeti->toDictionary()));
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $this->getRatingChart($entityManager, $chart);

        return $this->render('yetinder.html.twig', ['yeti' => $newYeti->toDictionary(), 'leaderboard' => $leaderboard, 'chart' => $chart,]);
    }

    public function getRatingChart($entityManager, $chart){
        $ratingData = $entityManager->createQueryBuilder()
            ->setMaxResults(30)
            ->select('Count(rating.id) as ratingCount, Sum(rating.value) as ratingVal, day(rating.date) as day,month(rating.date) as month,year(rating.date) as year')
            ->from(Rating::class, 'rating')
            ->groupBy('day')
            ->addGroupBy('month')
            ->addGroupBy('year')
            ->orderBy('rating.date')
            ->getQuery()->getResult();

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
    }

    public function getYetiLeaderBoard($entityManager){
        $weekStart =  date("Y-m-d", strtotime("this week")) . ' 00:00:00';
        $now =  date("Y-m-d H:i:s");

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

    public function getYetiById($entityManager, $yetiId){
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
     * @Route("/newYeti")
     */
    public function newYetiForm(Environment $twig, Request $request, EntityManagerInterface $entityManager): Response
    {
        $yeti = new Yeti();

        $form = $this->createForm(YetiFormType::class,$yeti);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $yeti = $this->linkAddressToExistingForeignKeys($entityManager, $yeti);
            $yeti->setImage($this->moveImage($form->get('image')->getData()));


            $entityManager->persist($yeti);
            $entityManager->flush();
            return $this->redirect('/');
        }


        return new Response($twig->render('newyeti.html.twig',['form'=> $form->createView()]));
    }

    public function moveImage($imageFile): string
    {
        $newFilename = date('Ymd-His').'_'.rand(10000,99999).'.'.$imageFile->guessExtension();
        $imageFile->move(
            $this->getParameter('yeti_directory'),
            $newFilename
        );
        return $newFilename;
    }

    public function linkAddressToExistingForeignKeys($entityManager, $yeti){
        $formCityName = $yeti->getAddress()->getCity()->getName();
        $formCountryName = $yeti->getAddress()->getCity()->getCountry()->getName();

        $databaseCity = $this->getDatabaseCity($entityManager, $formCityName);
        $existingCity = false;
        if($databaseCity != null){
            if($databaseCity->getCountry()->getName() == $formCountryName){
                $yeti->getAddress()->setCity($databaseCity);
                $existingCity = true;
            }
            
        }
        if(!$existingCity ){
            $databaseCountry = $this->getDatabaseCountry($entityManager, $formCountryName);
            if($databaseCountry != null){
                $yeti->getAddress()->getCity()->setCountry($databaseCountry);
            }
        }
        return $yeti;
    }

    public function getDatabaseCountry($entityManager, $inputCountry){
        $databaseCountries = $entityManager->createQuery(
            "Select country
                from App\Entity\Country country
                where country.name = '". $inputCountry ."'"
        )->getResult();

        if(count($databaseCountries) >= 1){
            return $databaseCountries[0];
        }
        return null;
    }

    public function getDatabaseCity($entityManager, $inputCity){
        $databaseCities = $entityManager->createQuery(
            "Select city
                from App\Entity\City city
                where city.name = '". $inputCity ."'"
        )->getResult();

        if(count($databaseCities) >= 1){
            return $databaseCities[0];
        }
        return null;
    }
}