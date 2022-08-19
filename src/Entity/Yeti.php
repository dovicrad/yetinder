<?php

namespace App\Entity;

use App\Repository\YetiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: YetiRepository::class)]
class Yeti
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/[\x{0020}-\x{0040}]|[\x{005B}-\x{0060}]|[\x{007B}-\x{007E}]/",
     *     htmlPattern = "/[\u0020-\u0040]|[\u005B-\u0060]|[\u007B-\u007E]/",
     *     match=false,
     *     message="This value has to be a word.",
     * )
     */
    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/[\x{0020}-\x{0040}]|[\x{005B}-\x{0060}]|[\x{007B}-\x{007E}]/",
     *     htmlPattern = "/[\u0020-\u0040]|[\u005B-\u0060]|[\u007B-\u007E]/",
     *     match=false,
     *     message="This value has to be a word.",
     * )
     */
    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    /**
     * @Assert\Valid
     */
    #[ORM\ManyToOne(cascade: ["persist"])]
    private ?Address $address = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^[0-9]{1,3}((,|\.)[0-9]{1,2})?$/",
     *     match=true,
     *     message="This value has to be weight in kg.",
     * )
     */
    #[ORM\Column]
    private ?float $weight = null;

    /**
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/^[0-9]{1,3}((,|\.)[0-9]{1,2})?$/",
     *     match=true,
     *     message="This value has to be height in cm.",
     * )
     */
    #[ORM\Column]
    private ?float $height = null;

    #[ORM\OneToMany(mappedBy: 'yeti', targetEntity: Rating::class, orphanRemoval: true)]
    private Collection $ratings;

    /**
     * @Assert\Image(
     *     maxSize = "2048k",
     *     mimeTypesMessage = "Please upload a valid image"
     * )
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function __construct()
    {
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(float $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): self
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings[] = $rating;
            $rating->setYeti($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): self
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getYeti() === $this) {
                $rating->setYeti(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return array converts all of yeti data into dictionary
     */
    #[ArrayShape(["yeti_id" => "int|null", "yeti_firstName" => "null|string", "yeti_lastName" => "null|string", "yeti_height" => "float|null", "yeti_weight" => "float|null", "yeti_image" => "null|string", "city_name" => "null|string", "country_name" => "null|string", "yeti_rating" => "int"])]
    public function ToDictionary(): array
    {
        return array(
            "yeti_id" => $this->getId(),
            "yeti_firstName"=> $this->getFirstName(),
            "yeti_lastName" => $this->getLastName(),
            "yeti_height" => $this->getHeight(),
            "yeti_weight" =>  $this->getWeight(),
            "yeti_image" => $this->getImage(),
            "city_name"=> $this->getAddress()->getCity()->getName(),
            "country_name"=> $this->getAddress()->getCity()->getCountry()->getName(),
            "yeti_rating" => $this->getRatingSum(),
        );
    }

    /**
     * @param $entityManager
     * @return void change foreign keys in database to existing records if they exists
     */
    public function linkAddressToExistingForeignKeys($entityManager): void
    {
        //get current data
        $CityName = $this->getAddress()->getCity()->getName();
        $CountryName = $this->getAddress()->getCity()->getCountry()->getName();

        //check if city already exists
        $databaseCity = $this->getDatabaseCity($entityManager, $CityName);
        $existingCity = false;
        if($databaseCity != null){
            if($databaseCity->getCountry()->getName() == $CountryName){
                $this->getAddress()->setCity($databaseCity);
                $existingCity = true;
            }

        }
        //check if country exists
        if(!$existingCity ){
            $databaseCountry = $this->getDatabaseCountry($entityManager, $CountryName);
            if($databaseCountry != null){
                $this->getAddress()->getCity()->setCountry($databaseCountry);
            }
        }
    }

    /**
     * @param $entityManager
     * @param $inputCountry mixed id of country to be found
     * @return mixed|null country object or null
     */
    public function getDatabaseCountry($entityManager, mixed $inputCountry): mixed
    {
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

    /**
     * @param $entityManager
     * @param $inputCity mixed id of city to be found
     * @return mixed|null city object or null
     */
    public function getDatabaseCity($entityManager, mixed $inputCity): mixed
    {
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

    public function getRatingSum(): int
    {
        $ratings = $this->getRatings();
        $ratingsTotal = 0;
        foreach($ratings as $rate){
            $ratingsTotal += $rate->getValue();
        }
        return $ratingsTotal;
    }
}
