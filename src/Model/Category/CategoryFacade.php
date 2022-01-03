<?php

namespace App\Model\Category;

use Doctrine\ORM\EntityManagerInterface;
use App\Component\Router\FriendlyUrl\FriendlyUrlFacade;

class CategoryFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Model\Category\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlFacade
     */
    protected $friendlyUrlFacade;

    /**
     * @var \App\Model\Category\CategoryFactoryInterface
     */
    protected $categoryFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Category\CategoryRepository $categoryRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \App\Model\Category\CategoryFactoryInterface $categoryFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        CategoryRepository $categoryRepository,
        FriendlyUrlFacade $friendlyUrlFacade,
        CategoryFactoryInterface $categoryFactory
    ) {
        $this->em = $em;
        $this->categoryRepository = $categoryRepository;
        $this->friendlyUrlFacade = $friendlyUrlFacade;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * @param int $categoryId
     * @return \App\Model\Category\Category
     */
    public function getById($categoryId)
    {
        return $this->categoryRepository->getById($categoryId);
    }

    /**
     * @param int[] $categoryIds
     * @return \App\Model\Category\Category[]
     */
    public function getByIds(array $categoryIds): array
    {
        return $this->categoryRepository->getCategoriesByIds($categoryIds);
    }

    /**
     * @return \App\Model\Category\Category[]
     */
    public function getAll(): array
    {
        return $this->categoryRepository->getAll();
    }

    /**
     * @param string $categoryUuid
     * @return \App\Model\Category\Category
     */
    public function getByUuid(string $categoryUuid): Category
    {
        return $this->categoryRepository->getOneByUuid($categoryUuid);
    }

    /**
     * @param \App\Model\Category\CategoryData $categoryData
     * @return \App\Model\Category\Category
     */
    public function create(CategoryData $categoryData)
    {
        $rootCategory = $this->getRootCategory();
        $category = $this->categoryFactory->create($categoryData, $rootCategory);
        $this->em->persist($category);
        $this->em->flush($category);
        $this->friendlyUrlFacade->createFriendlyUrls('front_product_list', $category->getId(), $category->getNames());
        $this->imageFacade->manageImages($category, $categoryData->image);

        $this->pluginCrudExtensionFacade->saveAllData('category', $category->getId(), $categoryData->pluginData);

        $this->categoryVisibilityRecalculationScheduler->scheduleRecalculationWithoutDependencies();

        return $category;
    }

    /**
     * @param int $categoryId
     * @param \App\Model\Category\CategoryData $categoryData
     * @return \App\Model\Category\Category
     */
    public function edit($categoryId, CategoryData $categoryData)
    {
        $rootCategory = $this->getRootCategory();
        $category = $this->categoryRepository->getById($categoryId);
        $originalNames = $category->getNames();

        $category->edit($categoryData);
        if ($category->getParent() === null) {
            $category->setParent($rootCategory);
        }
        $this->em->flush();
        $this->friendlyUrlFacade->saveUrlListFormData('front_product_list', $category->getId(), $categoryData->urls);
        $this->createFriendlyUrlsWhenRenamed($category, $originalNames);

        $this->imageFacade->manageImages($category, $categoryData->image);

        $this->pluginCrudExtensionFacade->saveAllData('category', $category->getId(), $categoryData->pluginData);

        $this->categoryVisibilityRecalculationScheduler->scheduleRecalculation($category);

        return $category;
    }

    /**
     * @param int $categoryId
     */
    public function deleteById($categoryId)
    {
        $category = $this->categoryRepository->getById($categoryId);

        $this->categoryVisibilityRecalculationScheduler->scheduleRecalculation($category);

        foreach ($category->getChildren() as $child) {
            $child->setParent($category->getParent());
        }
        // Normally, UnitOfWork performs UPDATEs on children after DELETE of main entity.
        // We need to update `parent` attribute of children first.
        $this->em->flush();

        $this->pluginCrudExtensionFacade->removeAllData('category', $category->getId());

        $this->em->remove($category);
        $this->friendlyUrlFacade->removeFriendlyUrlsForAllDomains('front_product_list', $category->getId());
        $this->em->flush();
    }
}
