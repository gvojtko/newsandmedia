<?php

namespace App\Controller\Rest;

use App\Form\Front\Product\ProductFilterFormType;
use App\Model\Category\CategoryFacade;
use App\Model\Product\Filter\ProductFilterConfigFactory;
use App\Model\Product\Filter\ProductFilterData;
use App\Model\Product\Listing\ProductListOrderingModeForListFacade;
use App\Model\Product\Listing\ProductListOrderingModeForSearchFacade;
use App\Model\Product\ProductFacade;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use App\Model\Category\Category;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends AbstractFOSRestController
{
    const PRODUCTS_PER_PAGE = 10;
    const SEARCH_TEXT_PARAMETER = 'q';
    const PAGE_QUERY_PARAMETER = 'page';

    /**
     * @var \App\Model\Product\Filter\ProductFilterConfigFactory
     */
    private $productFilterConfigFactory;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Product\Listing\ProductListOrderingModeForListFacade
     */
    private $productListOrderingModeForListFacade;

    /**
     * @var \App\Model\Product\Listing\ProductListOrderingModeForSearchFacade
     */
    private $productListOrderingModeForSearchFacade;

    /**
     * @param ProductFacade $productFacade
     * @param CategoryFacade $categoryFacade
     * @param ProductFilterConfigFactory $productFilterConfigFactory
     * @param ProductListOrderingModeForListFacade $productListOrderingModeForListFacade
     * @param ProductListOrderingModeForSearchFacade $productListOrderingModeForSearchFacade
     */
    public function __construct(
        ProductFacade $productFacade,
        CategoryFacade $categoryFacade,
        ProductFilterConfigFactory $productFilterConfigFactory,
        ProductListOrderingModeForListFacade $productListOrderingModeForListFacade,
        ProductListOrderingModeForSearchFacade $productListOrderingModeForSearchFacade
    )
    {
        $this->productFacade = $productFacade;
        $this->categoryFacade = $categoryFacade;
        $this->productFilterConfigFactory = $productFilterConfigFactory;
        $this->productListOrderingModeForListFacade = $productListOrderingModeForListFacade;
        $this->productListOrderingModeForSearchFacade = $productListOrderingModeForSearchFacade;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction()
    {
        $products = $this->productFacade->getAllOfferedProducts();

        return $this->handleView($this->view($products));
    }

    /**
     * @param Request $request
     * @param int $categoryId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listByCategoryAction(Request $request, int $categoryId)
    {
        $category = $this->categoryFacade->getById($categoryId);
        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $orderingModeId = $this->productListOrderingModeForListFacade->getOrderingModeIdFromRequest(
            $request
        );

        $productFilterData = new ProductFilterData();

        $productFilterConfig = $this->createProductFilterConfigForCategory($category);
        $filterForm = $this->createForm(ProductFilterFormType::class, $productFilterData, [
            'product_filter_config' => $productFilterConfig,
        ]);
        $filterForm->handleRequest($request);

        $paginationResult = $this->productFacade->getPaginatedProductsInCategory(
            $productFilterData,
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE,
            $categoryId
        );

        return $this->handleView($this->view($paginationResult));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction(Request $request)
    {
        $searchText = $request->query->get(self::SEARCH_TEXT_PARAMETER);
        $requestPage = $request->get(self::PAGE_QUERY_PARAMETER);
        $page = $requestPage === null ? 1 : (int)$requestPage;

        $orderingModeId = $this->productListOrderingModeForSearchFacade->getOrderingModeIdFromRequest(
            $request
        );

        $productFilterData = new ProductFilterData();

        $productFilterConfig = $this->createProductFilterConfigForSearch($searchText);
        $filterForm = $this->createForm(ProductFilterFormType::class, $productFilterData, [
            'product_filter_config' => $productFilterConfig,
        ]);
        $filterForm->handleRequest($request);

        $paginationResult = $this->productFacade->getPaginatedProductsForSearch(
            $searchText,
            $productFilterData,
            $orderingModeId,
            $page,
            self::PRODUCTS_PER_PAGE
        );

        return $this->handleView($this->view($paginationResult));
    }

    /**
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detailAction(int $id)
    {
        $product = $this->productFacade->getVisibleProductById($id);

        return $this->handleView($this->view($product));
    }

    /**
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Product\Filter\ProductFilterConfig
     */
    private function createProductFilterConfigForCategory(Category $category)
    {
        return $this->productFilterConfigFactory->createForCategory(
            $category
        );
    }

    /**
     * @param string|null $searchText
     * @return \App\Model\Product\Filter\ProductFilterConfig
     */
    private function createProductFilterConfigForSearch($searchText)
    {
        return $this->productFilterConfigFactory->createForSearch(
            $searchText
        );
    }
}
