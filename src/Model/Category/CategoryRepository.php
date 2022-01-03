<?php

declare(strict_types=1);

namespace App\Model\Category;

use App\Model\Product\ProductCategory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use App\Component\EntityExtension\EntityNameResolver;
use App\Component\Paginator\QueryPaginator;
use App\Component\String\DatabaseSearching;
use App\Model\Category\Exception\CategoryNotFoundException;
use App\Model\Category\Exception\RootCategoryNotFoundException;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Product;
use App\Model\Product\ProductRepository;

class CategoryRepository extends NestedTreeRepository
{
    public const MOVE_DOWN_TO_BOTTOM = true;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Component\EntityExtension\EntityNameResolver $entityNameResolver
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductRepository $productRepository,
        EntityNameResolver $entityNameResolver
    ) {
        $this->em = $em;
        $this->productRepository = $productRepository;

        $resolvedClassName = $entityNameResolver->resolve(Category::class);
        $classMetadata = $this->em->getClassMetadata($resolvedClassName);

        parent::__construct($this->em, $classMetadata);
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getCategoryRepository()
    {
        return $this->em->getRepository(Category::class);
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getAllQueryBuilder()
    {
        return $this->getCategoryRepository()
            ->createQueryBuilder('c')
            ->where('c.parent IS NOT NULL')
            ->orderBy('c.lft');
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllVisibleQueryBuilder()
    {
        $queryBuilder = $this->getAllQueryBuilder()
            ->andWhere('c.visible = TRUE');

        return $queryBuilder;
    }

    /**
     * @return \App\Model\Category\Category[]
     */
    public function getAll()
    {
        return $this->getAllQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \App\Model\Category\Category[] $selectedCategories
     * @return \App\Model\Category\Category[]
     */
    public function getAllCategoriesOfCollapsedTree(array $selectedCategories)
    {
        $openedParentsQueryBuilder = $this->getCategoryRepository()
            ->createQueryBuilder('c')
            ->select('c.id')
            ->where('c.parent IS NULL');

        foreach ($selectedCategories as $selectedCategory) {
            $where = sprintf('c.lft < %d AND c.rgt > %d', $selectedCategory->getLft(), $selectedCategory->getRgt());
            $openedParentsQueryBuilder->orWhere($where);
        }

        $openedParentIds = array_column($openedParentsQueryBuilder->getQuery()->getScalarResult(), 'id');

        return $this->getAllQueryBuilder()
            ->select('c, cd, ct')
            ->join('c.domains', 'cd')
            ->join('c.translations', 'ct')
            ->where('c.parent IN (:openedParentIds)')
            ->setParameter('openedParentIds', $openedParentIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return int[]
     */
    public function getAllIds()
    {
        $result = $this->getAllQueryBuilder()
            ->select('c.id')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }

    /**
     * @return \App\Model\Category\Category
     */
    public function getRootCategory()
    {
        $rootCategory = $this->getCategoryRepository()->findOneBy(['parent' => null]);

        if ($rootCategory === null) {
            $message = 'Root category not found';
            throw new RootCategoryNotFoundException($message);
        }

        return $rootCategory;
    }

    /**
     * @param int $categoryId
     * @return \App\Model\Category\Category|null
     */
    public function findById($categoryId)
    {
        /** @var \App\Model\Category\Category|null $category */
        $category = $this->getCategoryRepository()->find($categoryId);
        if ($category !== null && $category->getParent() === null) {
            // Copies logic from getAllQueryBuilder() - excludes root category
            // Query builder is not used to be able to get the category from identity map if it was loaded previously
            return null;
        }

        return $category;
    }

    /**
     * @param int $categoryId
     * @return \App\Model\Category\Category
     */
    public function getById($categoryId)
    {
        $category = $this->findById($categoryId);

        if ($category === null) {
            $message = 'Category with ID ' . $categoryId . ' not found.';
            throw new CategoryNotFoundException($message);
        }

        return $category;
    }

    /**
     * @param string $uuid
     * @return \App\Model\Category\Category
     */
    public function getOneByUuid(string $uuid): Category
    {
        $category = $this->getCategoryRepository()->findOneBy(['uuid' => $uuid]);

        if ($category === null) {
            throw new CategoryNotFoundException('Category with UUID ' . $uuid . ' does not exist.');
        }

        return $category;
    }

    /**
     * @param string $locale
     * @return \App\Model\Category\Category[]
     */
    public function getPreOrderTreeTraversalForAllCategories($locale)
    {
        $queryBuilder = $this->getAllQueryBuilder();
        $this->addTranslation($queryBuilder, $locale);

        $queryBuilder
            ->andWhere('c.level >= 1')
            ->orderBy('c.lft');

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string|null $searchText
     */
    protected function filterBySearchText(QueryBuilder $queryBuilder, $searchText)
    {
        $queryBuilder->andWhere(
            'NORMALIZE(ct.name) LIKE NORMALIZE(:searchText)'
        );
        $queryBuilder->setParameter('searchText', DatabaseSearching::getFullTextLikeSearchString($searchText));
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Category\Category|null
     */
    public function findProductMainCategory(Product $product)
    {
        $qb = $this->getAllVisibleQueryBuilder()
            ->join(
                ProductCategory::class,
                'pcd',
                Join::WITH,
                'pcd.product = :product
                    AND pcd.category = c
                    AND pcd.domainId = :domainId'
            )
            ->orderBy('c.level DESC, c.lft')
            ->setMaxResults(1);

        $qb->setParameters([
            'product' => $product,
        ]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Category\Category
     */
    public function getProductMainCategory(Product $product)
    {
        $productMainCategory = $this->findProductMainCategory($product);
        if ($productMainCategory === null) {
            throw new CategoryNotFoundException(
                sprintf(
                    'Main category for product id `%d` was not found',
                    $product->getId(),
                )
            );
        }

        return $productMainCategory;
    }

    /**
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Category\Category[]
     */
    public function getVisibleCategoriesInPathFromRoot(Category $category)
    {
        $qb = $this->getAllVisibleQueryBuilder()
            ->andWhere('c.lft <= :lft')->setParameter('lft', $category->getLft())
            ->andWhere('c.rgt >= :rgt')->setParameter('rgt', $category->getRgt())
            ->orderBy('c.lft');

        return $qb->getQuery()->getResult();
    }


    /**
     * @param int[] $categoryIds
     * @return \App\Model\Category\Category[]
     */
    public function getCategoriesByIds(array $categoryIds)
    {
        $queryBuilder = $this->getAllQueryBuilder();
        $queryBuilder
            ->andWhere('c.id IN (:categoryIds)')
            ->setParameter('categoryIds', $categoryIds);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \App\Model\Category\Category[] $categories
     * @return \App\Model\Category\Category[]
     */
    public function getCategoriesWithVisibleChildren(array $categories)
    {
        $queryBuilder = $this->getAllVisibleQueryBuilder();

        $queryBuilder
            ->join(Category::class, 'cc', Join::WITH, 'cc.parent = c')
            ->andWhere('ccd.visible = TRUE')
            ->andWhere('c IN (:categories)')
            ->setParameter('categories', $categories);

        return $queryBuilder->getQuery()->getResult();
    }
}
