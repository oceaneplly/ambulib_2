<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Trajet
 *
 * @ORM\Table(name="trajet", uniqueConstraints={@ORM\UniqueConstraint(name="UNIQ_2B5BA98C85542AE1", columns={"reservation"})}, indexes={@ORM\Index(name="IDX_2B5BA98C262F5BEA", columns={"ambulancier"}), @ORM\Index(name="IDX_2B5BA98CA40B286D", columns={"voiture"})})
 * @ORM\Entity(repositoryClass="App\Repository\TrajetRepository")
 */
class Trajet
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @Serializer\Groups({"detail", "list"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="etat", type="string", length=255, nullable=false)
     * @Serializer\Groups({"detail", "list"})
     */
    private $etat;

    /**
     * @var \Utilisateur
     *
     * @ORM\ManyToOne(targetEntity="Utilisateur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ambulancier", referencedColumnName="id")
     * })
     * @Serializer\Groups({"detail", "list"})
     */
    private $ambulancier;

    /**
     * @var \Reservation
     *
     * @ORM\ManyToOne(targetEntity="Reservation")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="reservation", referencedColumnName="id")
     * })
     * @Serializer\Groups({"detail", "list"})
     */
    private $reservation;

    /**
     * @var \Voiture
     *
     * @ORM\ManyToOne(targetEntity="Voiture")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="voiture", referencedColumnName="id")
     * })
     * @Serializer\Groups({"detail", "list"})
     */
    private $voiture;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getAmbulancier(): ?Utilisateur
    {
        return $this->ambulancier;
    }

    public function setAmbulancier(?Utilisateur $ambulancier): self
    {
        $this->ambulancier = $ambulancier;

        return $this;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): self
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getVoiture(): ?Voiture
    {
        return $this->voiture;
    }

    public function setVoiture(?Voiture $voiture): self
    {
        $this->voiture = $voiture;

        return $this;
    }


}
