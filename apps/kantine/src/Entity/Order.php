<?php

namespace Kantine\Entity;

use Kantine\Repository\OrderRepository;
use Kantine\Validator\UniqueDateForCostumer;
use DateTime;
use Doctrine\DBAL\Schema\DefaultExpression\CurrentTimestamp;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shared\Entity\Costumer;


#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]

// only validate for unique date when creating new. when updating
#[UniqueDateForCostumer(datefield: 'order_dateTime', costumerfield: 'Costumer'/*, groups: ['create']*/)]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    public function __construct()
    {
        // $this->order_dateTime = new DateTime();
    }

    #[ORM\Column(options: ['default' => new CurrentTimestamp()], insertable: false, updatable: false)]
    private ?\DateTime $order_dateTime = null;

    #[ORM\ManyToOne(inversedBy: 'orders', cascade: ['persist', 'persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Costumer $Costumer = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2)]
    private ?string $ordered_item = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $tax = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCostumer(): ?Costumer
    {
        return $this->Costumer;
    }

    public function setCostumer(?Costumer $Costumer): static
    {
        $this->Costumer = $Costumer;

        return $this;
    }

    public function getOrderFormatted(): ?string
    {
        return number_format($this->ordered_item, 2, ',', '.').'€';
    }

    public function getOrderNum(): ?float
    {
        return (float)$this->ordered_item;
    }

    public function getOrderedItem(): ?string
    {
        return $this->ordered_item;
    }

    public function setOrderedItem(string $ordered_item): static
    {
        $this->ordered_item = $ordered_item;

        return $this;
    }

    public function getTax(): ?int
    {
        return $this->tax;
    }

    public function setTax(int $tax): static
    {
        $this->tax = $tax;

        return $this;
    }

    public function getOrderDateTime(): ?\DateTime
    {
        return $this->order_dateTime;
    }

    public function setOrderDateTime(\DateTime $order_dateTime): static
    {
        $this->order_dateTime = $order_dateTime;

        return $this;
    }
}
