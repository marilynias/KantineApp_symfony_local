<?php

namespace Shared\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shared\Repository\TagsRepository;

#[ORM\Entity(repositoryClass: TagsRepository::class)]
class Tags
{
    public function __toString()
    {
        return $this->name;
    }



    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, Costumer>
     */
    #[ORM\ManyToMany(targetEntity: Costumer::class, mappedBy: 'tags')]
    private Collection $costumers;

    public function __construct()
    {
        $this->costumers = new ArrayCollection();
    }
    // public ?string $name = null
    // {
    //     get => $this->name;
    //     set(?string $name){
    //         $this->name = $name ?? $this->name;    // trimmed and lowercase
    //     }
    // }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Costumer>
     */
    public function getCostumers(): Collection
    {
        return $this->costumers;
    }

    public function addCostumer(Costumer $costumer): static
    {
        if (!$this->costumers->contains($costumer)) {
            $this->costumers->add($costumer);
            $costumer->addTag($this);
        }

        return $this;
    }

    public function removeCostumer(Costumer $costumer): static
    {
        if ($this->costumers->removeElement($costumer)) {
            $costumer->removeTag($this);
        }

        return $this;
    }
}
