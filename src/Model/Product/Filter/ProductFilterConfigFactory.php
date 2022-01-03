<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use App\Model\Category\Category;
use App\Model\Customer\User\CurrentCustomerUser;
use App\Model\Product\Brand\Brand;

class ProductFilterConfigFactory
{
    /**
     * @var \App\Model\Product\Filter\ParameterFilterChoiceRepository
     */
    protected $parameterFilterChoiceRepository;

    /**
     * @var \App\Model\Product\Filter\FlagFilterChoiceRepository
     */
    protected $flagFilterChoiceRepository;

    /**
     * @var \App\Model\Customer\User\CurrentCustomerUser
     */
    protected $currentCustomerUser;

    /**
     * @var \App\Model\Product\Filter\BrandFilterChoiceRepository
     */
    protected $brandFilterChoiceRepository;

    /**
     * @var \App\Model\Product\Filter\PriceRangeRepository
     */
    protected $priceRangeRepository;

    /**
     * @param \App\Model\Product\Filter\ParameterFilterChoiceRepository $parameterFilterChoiceRepository
     * @param \App\Model\Product\Filter\FlagFilterChoiceRepository $flagFilterChoiceRepository
     * @param \App\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Product\Filter\BrandFilterChoiceRepository $brandFilterChoiceRepository
     * @param \App\Model\Product\Filter\PriceRangeRepository $priceRangeRepository
     */
    public function __construct(
        ParameterFilterChoiceRepository $parameterFilterChoiceRepository,
        FlagFilterChoiceRepository $flagFilterChoiceRepository,
        BrandFilterChoiceRepository $brandFilterChoiceRepository,
        PriceRangeRepository $priceRangeRepository
    ) {
        $this->parameterFilterChoiceRepository = $parameterFilterChoiceRepository;
        $this->flagFilterChoiceRepository = $flagFilterChoiceRepository;
        $this->currentCustomerUser = $currentCustomerUser;
        $this->brandFilterChoiceRepository = $brandFilterChoiceRepository;
        $this->priceRangeRepository = $priceRangeRepository;
    }

    /**
     * @param \App\Model\Category\Category $category
     * @return \App\Model\Product\Filter\ProductFilterConfig
     */
    public function createForCategory(Category $category)
    {
        $pricingGroup = $this->currentCustomerUser->getPricingGroup();
        $parameterFilterChoices = $this->parameterFilterChoiceRepository
            ->getParameterFilterChoicesInCategory($pricingGroup, $category);
        $flagFilterChoices = $this->flagFilterChoiceRepository
            ->getFlagFilterChoicesInCategory($pricingGroup, $category);
        $brandFilterChoices = $this->brandFilterChoiceRepository
            ->getBrandFilterChoicesInCategory($pricingGroup, $category);
        $priceRange = $this->priceRangeRepository->getPriceRangeInCategory($pricingGroup, $category);

        return new ProductFilterConfig($parameterFilterChoices, $flagFilterChoices, $brandFilterChoices, $priceRange);
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @param string|null $searchText
     * @return \App\Model\Product\Filter\ProductFilterConfig
     */
    public function createForSearch($searchText)
    {
        $parameterFilterChoices = [];
        $pricingGroup = $this->currentCustomerUser->getPricingGroup();
        $flagFilterChoices = $this->flagFilterChoiceRepository
            ->getFlagFilterChoicesForSearch($pricingGroup, $searchText);
        $brandFilterChoices = $this->brandFilterChoiceRepository
            ->getBrandFilterChoicesForSearch($pricingGroup, $searchText);
        $priceRange = $this->priceRangeRepository->getPriceRangeForSearch(
            $pricingGroup,
            $searchText
        );

        return new ProductFilterConfig($parameterFilterChoices, $flagFilterChoices, $brandFilterChoices, $priceRange);
    }

    /**
     * @return \App\Model\Product\Filter\ProductFilterConfig
     */
    public function createForAll(): ProductFilterConfig
    {
        $pricingGroup = $this->currentCustomerUser->getPricingGroup();
        $flagFilterChoices = $this->flagFilterChoiceRepository
            ->getFlagFilterChoicesForAll($pricingGroup);
        $priceRange = $this->priceRangeRepository->getPriceRangeForAll($pricingGroup);
        $brandFilterChoices = $this->brandFilterChoiceRepository
            ->getBrandFilterChoicesForAll($pricingGroup);

        return new ProductFilterConfig([], $flagFilterChoices, $brandFilterChoices, $priceRange);
    }
}
