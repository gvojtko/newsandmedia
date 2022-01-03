<?php

namespace App\Model\Category;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="categories")
 * @ORM\Entity
 */
class Category
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="guid", unique=true)
     */
    protected $uuid;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var \App\Model\Category\Category|null
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="App\Model\Category\Category", inversedBy="children")
     * @ORM\JoinColumn(nullable=true, name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @var \App\Model\Category\Category[]|\Doctrine\Common\Collections\Collection
     * @ORM\OneToMany(targetEntity="App\Model\Category\Category", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    protected $children;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected $seoTitle;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected $seoMetaDescription;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected $seoH1;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $enabled;

    /**
     * @var int
     * @Gedmo\TreeLevel
     * @ORM\Column(type="integer")
     */
    protected $level;

    /**
     * @var int
     * @Gedmo\TreeLeft
     * @ORM\Column(type="integer")
     */
    protected $lft;

    /**
     * @var int
     * @Gedmo\TreeRight
     * @ORM\Column(type="integer")
     */
    protected $rgt;

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    public function __construct(CategoryData $categoryData)
    {
        $this->enabled = true;
        $this->visible = false;

        $this->children = new ArrayCollection();

        $this->uuid = $categoryData->uuid ?: Uuid::uuid4()->toString();
        $this->setData($categoryData);
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    public function edit(CategoryData $categoryData)
    {
        $this->setData($categoryData);
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     */
    protected function setData(CategoryData $categoryData): void
    {
        $this->parent = $categoryData->parent;
        $this->enabled = $categoryData->enabled;
        $this->seoTitle = $categoryData->seoTitle;
        $this->seoH1 = $categoryData->seoH1;
        $this->seoMetaDescription = $categoryData->seoMetaDescription;
        $this->name = $categoryData->name;
        $this->description = $categoryData->description;
    }

    /**
     * @param \App\Model\Category\Category|null $parent
     */
    public function setParent(?self $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return \App\Model\Category\Category|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Method does not lazy load children
     *
     * @return bool
     */
    public function hasChildren()
    {
        return $this->getRgt() - $this->getLft() > 1;
    }

    /**
     * @return \App\Model\Category\Category[]
     */
    public function getChildren()
    {
        return $this->children->toArray();
    }

    /**
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @return string|null
     */
    public function getSeoMetaDescription()
    {
        return $this->seoMetaDescription;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }
}
