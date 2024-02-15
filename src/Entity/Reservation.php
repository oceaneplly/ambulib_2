<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Reservation
 *
 * @ORM\Table(name="reservation", indexes={@ORM\Index(name="IDX_42C849551CE947F9", columns={"etablissement"}), @ORM\Index(name="IDX_42C84955597DF5D4", columns={"societe"}), @ORM\Index(name="IDX_42C84955C6EE5C49", columns={"utilisateur"})})
 * @ORM\Entity(repositoryClass="App\Repository\ReservationRepository")
 */
class Reservation
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
     * @var \DateTime
     *
     * @ORM\Column(name="date_rdv", type="date", nullable=false)
     * @Serializer\Groups({"detail", "list"})
     */
    private $dateRdv;

    /**
     * @var string
     *
     * @ORM\Column(name="bontransport", type="string", length=255, nullable=false)
     * @Serializer\Groups({"detail", "list"})
     */
    private $bontransport;

    /**
     * @var string
     *
     * @ORM\Column(name="type_sejour", type="string", length=255, nullable=false)
     * @Serializer\Groups({"detail", "list"})
     */
    private $typeSejour;

    /**
     * @var string
     *
     * @ORM\Column(name="etat", type="string", length=255, nullable=false)
     * @Serializer\Groups({"detail", "list"})
     */
    private $etat;

    /**
     * @var \Etablissement
     *
     * @ORM\ManyToOne(targetEntity="Etablissement")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="etablissement", referencedColumnName="id")
     * })
     * @Serializer\Groups({"detail", "list"})
     */
    private $etablissement;

    /**
     * @var \Societe
     *
     * @ORM\ManyToOne(targetEntity="Societe")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="societe", referencedColumnName="id")
     * })
     * @Serializer\Groups({"detail", "list"})
     */
    private $societe;

    /**
     * @var \Utilisateur
     *
     * @ORM\ManyToOne(targetEntity="Utilisateur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="utilisateur", referencedColumnName="id")
     * })
     * @Serializer\Groups({"detail", "list"})
     */
    private $utilisateur;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateRdv(): ?\DateTimeInterface
    {
        return $this->dateRdv;
    }

    public function setDateRdv(\DateTimeInterface $dateRdv): self
    {
        $this->dateRdv = $dateRdv;

        return $this;
    }

    public function getBontransport(): ?string
    {
        return $this->bontransport;
    }

    public function setBontransport(string $bontransport): self
    {
        $this->bontransport = $bontransport;

        return $this;
    }

    public function getTypeSejour(): ?string
    {
        return $this->typeSejour;
    }

    public function setTypeSejour(string $typeSejour): self
    {
        $this->typeSejour = $typeSejour;

        return $this;
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

    public function getEtablissement(): ?Etablissement
    {
        return $this->etablissement;
    }

    public function setEtablissement(?Etablissement $etablissement): self
    {
        $this->etablissement = $etablissement;

        return $this;
    }

    public function getSociete(): ?Societe
    {
        return $this->societe;
    }

    public function setSociete(?Societe $societe): self
    {
        $this->societe = $societe;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }


}
