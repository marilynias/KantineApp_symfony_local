<?php

namespace Shared\Entity;

use Shared\Repository\CostumerRepository;
use Kantine\Entity\Order;
use DateInterval;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Choice;

#[ORM\Entity(repositoryClass: CostumerRepository::class)] #
#[UniqueEntity(
    fields: ['firstname', 'lastname'],
    message: "A costumer with this name already exists"
)]
class Costumer
{
    // name_to_display => name in database
    public const DEPARTMENTS = [
        "IT" => "IT",
        "IT Tempus/Alia" => "IT-TEMPUS",
        "Brevia" => "BREVIA",
        "Vario" => "VARIO",
        "Service" => "HWS",
        "Büme" => "BÜME",
        "Büme Tempus/Alia" => "BÜME-TEMPUS",
        "Tischlerei" => "TISCHLEREI",
        "Tischlerei Tempus/Alia" => "TISCHLEREI-TEMPUS",
        "Malerei" => "MALEREI",
        "Media" => "MEDIA",
        "BVB" => "BVB",
        "Aperio" => "APERIO",
        "dept.none" => ""
        ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;
    public function getId() {return $this->id;}

    public function getFirstname() {return $this->firstname;}
    public function setFirstname(string $firstname):static {$this->firstname = $firstname; return $this;}
    #[ORM\Column(length: 50)]
    private string $firstname;
    // public string $firstname = ''
    // {
    //     get => ucwords(trim($this->firstname));
    //     set(string $firstname){
    //         $this->firstname = strtolower(trim($firstname));    // trimmed and lowercase
    //     }
    // }

    public function getLastname() {return $this->lastname;}
    public function setLastname(string $lastname):static {$this->lastname = $lastname; return $this;}
    #[ORM\Column(length: 50)]
    private string $lastname;
    // public string $lastname = ''
    // {
    //     get => ucwords(trim($this->lastname));
    //     set(string $lastname){
    //         $this->lastname = strtolower(trim($lastname));      // trimmed and lowercase
    //     }
    // }

    public function getActive() {return $this->active;}
    public function setActive(bool $active):static {$this->active = $active; return $this;}
    #[ORM\Column]
    private bool $active;
    // public bool $active = false{
    //     get => $this->active;
    //     set(bool $active){
    //         $this->active = $active;
    //         $now = new DateTime();
    //         // if set to inactive, set enddate to now, if set to active set to 4Y from now
    //         $this->enddate = $active ? $now->add(new DateInterval("P4Y")) : $now;
    //     }
    // }

    public function getEnddate() {return $this->enddate;}
    public function setEnddate(?\DateTime $enddate):static {$this->enddate = $enddate; return $this;}
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $enddate;
    // public ?\DateTime $enddate = null{
    //     get => $this->enddate;
    //     set(?\DateTime $enddate){
    //         $this->enddate = $enddate;
    //     }
    // }

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'Costumer')]
    private Collection $orders;

    // #[ORM\ManyToMany(targetEntity: Tags::class)]
    // private Collection $tags;

    protected File $Barcode;

    #[Choice(choices: Costumer::DEPARTMENTS, message: '{{ value }} not a valid department. Possible departments: {{ choices }}')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Department = null;

    /**
     * @var Collection<int, Tags>
     */
    #[ORM\ManyToMany(targetEntity: Tags::class, inversedBy: 'costumers')]
    private Collection $tags;

    public function __tostring()
    {
        return sprintf('[%s] %s', $this->getDepartment()??'None', $this->getFullName()?:'None');
    }

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $now = new DateTime();
        $this->enddate = $now->add(new DateInterval("P4Y")); // 4 Years
    }

   

    

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setCostumer($this);
        }

        return $this;
    }

    // public function removeTags(Tags $tag): static
    // {
    //     if ($this->orders->removeElement($tags)) {
    //         // set the owning side to null (unless already changed)
    //         if ($tags->getCostumer() === $this) {
    //             $tags->setCostumer(null);
    //         }
    //     }

    //     return $this;
    // }

    // /**
    //  * @return Collection<int, Order>
    //  */
    // public function getTags(): Collection
    // {
    //     return $this->orders;
    // }

    // public function addOrder(Order $order): static
    // {
    //     if (!$this->orders->contains($order)) {
    //         $this->orders->add($order);
    //         $order->setCostumer($this);
    //     }

    //     return $this;
    // }

    // public function removeOrder(Order $order): static
    // {
    //     if ($this->orders->removeElement($order)) {
    //         // set the owning side to null (unless already changed)
    //         if ($order->getCostumer() === $this) {
    //             $order->setCostumer(null);
    //         }
    //     }

    //     return $this;
    // }

    public function getBarcode(): string
    {
        if (!$this->id) {
            return '';
        }
        $dirname = 'barcodes';
        // look in public/barcodes/${id}.svg
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755);
        }

        // 
        $filename = join(DIRECTORY_SEPARATOR, [$dirname, (string)$this->id . '.svg']);
        if (!file_exists($filename)) {
            $barcode = (new BarcodeGeneratorSVG())->getBarcode($this->id, BarcodeGeneratorSVG::TYPE_CODE_128);
            file_put_contents($filename, $barcode);
        }

        return $filename;
    }

    // public function getDepartment(): ?string
    // {
    //     $ret = array_find_key(static::DEPARTMENTS, 
    //         fn(?string $value, string $key)=>$this->Department===$value
    //     );
    //     return $ret;
    // }

    public function getDepartment(): ?string
    {
        return $this->Department;
    }

    public function setDepartment(?string $Department): static
    {
        $this->Department = $Department;

        return $this;
    }

    public function getFullName()
    {
        return $this->firstname. " ". $this->lastname;
    }

    /**
     * @return Collection<int, Tags>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tags $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tags $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}


/* OBSOLETE, numbers should not be shown anymore*/
class SvgHelper
{
    static public function generateBarcode(int $id): string
    {
        // save image
        $barcode = (new BarcodeGeneratorSVG())->getBarcode($id, BarcodeGeneratorSVG::TYPE_CODE_128);
        SvgHelper::addNumberBarcode($barcode, $id);
        return $barcode;
    }

    static public function getSvgValue(string $svg, string $name, string $class): string
    {
        // <$class**$name="$toGet">
        $pattern = sprintf('/(?<=<%s).+?\K(?<=%s=\").+?(?=")/', $class, $name);
        //'/(?<=%s=\\").+?(?=")/', $name);
        $matches = [];
        $entries = preg_match($pattern, $svg, $matches);
        assert($entries && sizeof($matches) >= 1, "No matches");
        return $matches[0];
    }

    static public function setSvgValue(string &$barcode, string $name, string $value, $class): string
    {
        // <$class**$name="$toReplace">
        $pattern = sprintf('/(?<=<%s).+?\K(?<=%s=\").+?(?=")/', $class, $name);
        // replace ** with $value
        $replaced = preg_replace($pattern, $value, $barcode, 1);
        assert($replaced, "error in the pattern");
        assert($replaced != $barcode, "No changes");
        $barcode = $replaced;
        return $barcode;
    }

    static public function setSvgValues(string &$barcode, array $replacements, string $class): string
    {
        foreach ($replacements as $key => $value) {
            $barcode = SvgHelper::setSvgValue($barcode, $key, $value, $class);
        }
        return $barcode;
    }

    static public function insertAfter(string &$barcode, string $toInsert, string $afterRegex): string
    {
        $inserted = str_replace($afterRegex, $afterRegex . $toInsert, $barcode);
        assert($inserted, "Error in regex expression");
        assert($inserted != $barcode, "No matches found");
        $barcode = $inserted;
        return $barcode;
    }

    static public function addNumberBarcode(string &$barcode, int $id, int $barcodeHeight = 30, string $color = 'black', int $fontheight = 10, $sep = 0): string
    {
        $width = SvgHelper::getSvgValue($barcode, "width", 'svg');
        assert(ctype_digit($width), "width must be a number");
        $height = $barcodeHeight + $fontheight + $sep;
        SvgHelper::setSvgValues($barcode, ["height" => $height, "viewBox" => sprintf("0 0 %u %u", $width, $height), "text-anchor"=>"middle"], 'svg');

        //dominant-baseline="middle" text-anchor="middle"
        $numberSVG = sprintf(
            '<text x="50%%" y="%u" font-size="%u" fill="%s">%u</text>',
            $barcodeHeight + $fontheight,
            $fontheight,
            $color,
            $id
        );

        // assumes only one </g>
        SvgHelper::insertAfter($barcode, "\n\t" . $numberSVG, '</g>');
        return $barcode;
    }
}
