<?php

namespace App\Model\Category;

class CategoryData
{
    /**
     * @var string
     */
    public $uuid;

    /**
     * @var string
     */
    public $name;

    /**
     * @var \App\Model\Category\Category|null
     */
    public $parent;

    /**
     * @var \App\Model\Category\Category[]|\Doctrine\Common\Collections\Collection
     */
    public $children;

    /**
     * @var string|null
     */
    public $seoTitle;

    /**
     * @var string|null
     */
    public $seoMetaDescription;

    /**
     * @var string|null
     */
    public $seoH1;

    /**
     * @var string|null
     */
    public $description;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var int
     */
    public $level;

    /**
     * @var int
     */
    public $lft;

    /**
     * @var int
     */
    public $rgt;

    public function __construct()
    {
        $this->enabled = true;
        $this->visible = false;
    }
}
