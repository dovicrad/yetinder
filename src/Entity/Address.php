<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    /**
     * @Assert\Valid
     */
    #[ORM\ManyToOne(cascade: ["persist"], inversedBy: 'addresses')]
    private ?City $city = null;

    /**
     * @Assert\NotBlank
     * @Assert\Regex(
     *     pattern="/[\x{0021}-\x{0040}]|[\x{005B}-\x{0060}]|[\x{007B}-\x{007E}]/",
     *     htmlPattern = "/[\u0020-\u0040]|[\u005B-\u0060]|[\u007B-\u007E]/",
     *     match=false,
     *     message="This value has contain letters only.",
     * )
     */
    #[ORM\Column(length: 255)]
    private ?string $street = null;

    /**
     * @Assert\NotBlank
     * @Assert\Range(
     *      min = 1,
     *      max = 100000,
     *      notInRangeMessage = "This value has to be within {{ min }} to {{ max }}",
     * )
     */
    #[ORM\Column]
    private ?int $streetNumber = null;

    /**
     * @Assert\NotBlank
     * @Assert\Range(
     *      min = 1,
     *      max = 99999,
     *      notInRangeMessage = "This value has to be within {{ min }} to {{ max }}",
     * )
     */
    #[ORM\Column(length: 255)]
    private ?string $zip = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getStreetNumber(): ?int
    {
        return $this->streetNumber;
    }

    public function setStreetNumber(int $streetNumber): self
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }
}
