<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use Elasticsearch\Client;
use App\Model\Product\Filter\ParameterFilterData;
use App\Model\Product\Filter\ProductFilterCountData;
use App\Model\Product\Filter\ProductFilterData;

class ProductFilterCountDataElasticsearchRepository
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * @var \App\Model\Product\Search\ProductFilterDataToQueryTransformer
     */
    protected $productFilterDataToQueryTransformer;

    /**
     * @var \App\Model\Product\Search\AggregationResultToProductFilterCountDataTransformer
     */
    protected $aggregationResultToCountDataTransformer;

    /**
     * @param \Elasticsearch\Client $client
     * @param \App\Model\Product\Search\ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer
     * @param \App\Model\Product\Search\AggregationResultToProductFilterCountDataTransformer $aggregationResultToCountDataTransformer
     */
    public function __construct(
        Client $client,
        ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer,
        AggregationResultToProductFilterCountDataTransformer $aggregationResultToCountDataTransformer
    ) {
        $this->client = $client;
        $this->productFilterDataToQueryTransformer = $productFilterDataToQueryTransformer;
        $this->aggregationResultToCountDataTransformer = $aggregationResultToCountDataTransformer;
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Product\Search\FilterQuery $baseFilterQuery
     * @return \App\Model\Product\Filter\ProductFilterCountData
     */
    public function getProductFilterCountDataInSearch(ProductFilterData $productFilterData, FilterQuery $baseFilterQuery): ProductFilterCountData
    {
        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery(
            $productFilterData,
            $baseFilterQuery
        );
        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery(
            $productFilterData,
            $absoluteNumbersFilterQuery
        );

        $aggregationResult = $this->client->search($absoluteNumbersFilterQuery->getAbsoluteNumbersAggregationQuery());
        $countData = $this->aggregationResultToCountDataTransformer->translateAbsoluteNumbers($aggregationResult);

        if (count($productFilterData->flags) > 0) {
            $plusFlagsQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery(
                $productFilterData,
                $baseFilterQuery
            );
            $countData->countByFlagId = $this->calculateFlagsPlusNumbers($productFilterData, $plusFlagsQuery);
        }

        if (count($productFilterData->brands) > 0) {
            $plusBrandsQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery(
                $productFilterData,
                $baseFilterQuery
            );
            $countData->countByBrandId = $this->calculateBrandsPlusNumbers($productFilterData, $plusBrandsQuery);
        }

        return $countData;
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Product\Search\FilterQuery $baseFilterQuery
     * @return \App\Model\Product\Filter\ProductFilterCountData
     */
    public function getProductFilterCountDataInCategory(ProductFilterData $productFilterData, FilterQuery $baseFilterQuery): ProductFilterCountData
    {
        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery(
            $productFilterData,
            $baseFilterQuery
        );
        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery(
            $productFilterData,
            $absoluteNumbersFilterQuery
        );
        $absoluteNumbersFilterQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery(
            $productFilterData,
            $absoluteNumbersFilterQuery
        );

        $aggregationResult = $this->client->search(
            $absoluteNumbersFilterQuery->getAbsoluteNumbersWithParametersQuery()
        );
        $countData = $this->aggregationResultToCountDataTransformer->translateAbsoluteNumbersWithParameters(
            $aggregationResult
        );

        if (count($productFilterData->flags) > 0) {
            $plusFlagsQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery(
                $productFilterData,
                $baseFilterQuery
            );
            $plusFlagsQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery(
                $productFilterData,
                $plusFlagsQuery
            );
            $countData->countByFlagId = $this->calculateFlagsPlusNumbers($productFilterData, $plusFlagsQuery);
        }

        if (count($productFilterData->brands) > 0) {
            $plusBrandsQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery(
                $productFilterData,
                $baseFilterQuery
            );
            $plusBrandsQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery(
                $productFilterData,
                $plusBrandsQuery
            );
            $countData->countByBrandId = $this->calculateBrandsPlusNumbers($productFilterData, $plusBrandsQuery);
        }

        if (count($productFilterData->parameters) > 0) {
            $plusParametersQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery(
                $productFilterData,
                $baseFilterQuery
            );
            $plusParametersQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery(
                $productFilterData,
                $plusParametersQuery
            );

            $this->replaceParametersPlusNumbers($productFilterData, $countData, $plusParametersQuery);
        }

        return $countData;
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Product\Search\FilterQuery $plusFlagsQuery
     * @return int[]
     */
    protected function calculateFlagsPlusNumbers(ProductFilterData $productFilterData, FilterQuery $plusFlagsQuery): array
    {
        $flagIds = [];
        foreach ($productFilterData->flags as $flag) {
            $flagIds[] = $flag->getId();
        }
        $flagsPlusNumberResult = $this->client->search($plusFlagsQuery->getFlagsPlusNumbersQuery($flagIds));
        return $this->aggregationResultToCountDataTransformer->translateFlagsPlusNumbers($flagsPlusNumberResult);
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Product\Search\FilterQuery $plusFlagsQuery
     * @return int[]
     */
    protected function calculateBrandsPlusNumbers(ProductFilterData $productFilterData, FilterQuery $plusFlagsQuery): array
    {
        $brandsIds = [];
        foreach ($productFilterData->brands as $brand) {
            $brandsIds[] = $brand->getId();
        }
        $brandsPlusNumberResult = $this->client->search($plusFlagsQuery->getBrandsPlusNumbersQuery($brandsIds));
        return $this->aggregationResultToCountDataTransformer->translateBrandsPlusNumbers($brandsPlusNumberResult);
    }

    /**
     * When calculating plus numbers for a parameter, this parameter must be excluded from filter query (clone and unset)
     *
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param \App\Model\Product\Filter\ProductFilterCountData $countData
     * @param \App\Model\Product\Search\FilterQuery $plusParametersQuery
     */
    protected function replaceParametersPlusNumbers(ProductFilterData $productFilterData, ProductFilterCountData $countData, FilterQuery $plusParametersQuery): void
    {
        foreach ($productFilterData->parameters as $key => $currentParameterFilterData) {
            $currentFilterData = clone $productFilterData;
            unset($currentFilterData->parameters[$key]);

            $currentQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery(
                $currentFilterData,
                $plusParametersQuery
            );

            $plusParameterNumbers = $this->calculateParameterPlusNumbers($currentParameterFilterData, $currentQuery);
            $this->mergeParameterCountData(
                $countData,
                $plusParameterNumbers,
                $currentParameterFilterData->parameter->getId()
            );
        }
    }

    /**
     * @param \App\Model\Product\Filter\ParameterFilterData $parameterFilterData
     * @param \App\Model\Product\Search\FilterQuery $parameterFilterQuery
     * @return array
     */
    protected function calculateParameterPlusNumbers(
        ParameterFilterData $parameterFilterData,
        FilterQuery $parameterFilterQuery
    ): array {
        $parameterId = $parameterFilterData->parameter->getId();
        $valuesIds = [];
        foreach ($parameterFilterData->values as $parameterValue) {
            $valuesIds[] = $parameterValue->getId();
        }

        $currentQueryResult = $this->client->search(
            $parameterFilterQuery->getParametersPlusNumbersQuery($parameterId, $valuesIds)
        );
        return $this->aggregationResultToCountDataTransformer->translateParameterValuesPlusNumbers(
            $currentQueryResult
        );
    }

    /**
     * Plus numbers are not replaced as expected, they are "added" to meet the original SQL implementation
     *
     * @param \App\Model\Product\Filter\ProductFilterCountData $countData
     * @param array $plusParameterNumbers
     * @param int $parameterId
     */
    protected function mergeParameterCountData(ProductFilterCountData $countData, array $plusParameterNumbers, int $parameterId): void
    {
        if (isset($countData->countByParameterIdAndValueId[$parameterId])) {
            $countData->countByParameterIdAndValueId[$parameterId] += $plusParameterNumbers;
        } else {
            $countData->countByParameterIdAndValueId[$parameterId] = $plusParameterNumbers;
        }
    }
}
