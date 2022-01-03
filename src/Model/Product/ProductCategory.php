<?php

namespace App\Model\Product;

use Doctrine\ORM\Mapping as ORM;
use App\Model\Category\Category;

/**
 * @ORM\Table(
 *     name="product_categories",
 *     indexes={@ORM\Index(columns={"product_id", "category_id"})}
 * )
 * @ORM\Entity
 */
class ProductCategory
{
    /**
     * @var \App\Model\Product\Product
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product", inversedBy="productCategories")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var \App\Model\Category\Category
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Model\Category\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $category;

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Category\Category $category
     */
    public function __construct(Product $product, Category $category)
    {
        $this->product = $product;
        $this->category = $category;
    }

    /**
     * @return \App\Model\Category\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param \App\Model\Product\Product $product
     */
    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }
}
