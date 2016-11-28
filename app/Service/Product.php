<?php

namespace App\Service;

use Core\Database\PDO;
use Core\Exception\Exception;
use Core\Orm;

class Product
{
	public static function filterBy($catalogId = null, $filters = [], $sort = '')
	{
		$db = PDO::getInstance();

		$productFilters = ['active' => 1];
		$params = [];

		if (!empty($sort)) {
			$params = ['sort' => explode(':', $sort)];
		}

		if ($catalogId) {
			$catalog = Orm::load('Catalog', $catalogId);
			if (!$catalog) {
				throw new Exception('Incorrect catalog');
			}

			$productIds = $catalog->getRelated('products')->getValues('id');

			if (is_array($filters) && !empty($filters)) {

				$filterProducts = $db->rows('select Product, group_concat(Filter) as Filter from Product__Filter where Product in('.implode(',', $productIds).') group By Product');

				foreach ($filterProducts as $filterProduct) {
					$filterInProduct = explode(',', $filterProduct['Filter']);
					$matching = true;

					foreach ($filters as $filter) {
						if (!in_array($filter, $filterInProduct)) {
							$matching = false;
						}
					}

					if ($matching) {
						if (!isset($productFilters['id'])) $productFilters['id'] = [];
						array_push($productFilters['id'], $filterProduct['Product']);
					}
				}
			} else {
				$productFilters['id'] = $productIds;
			}
		}

		return Orm::find('Product', array_keys($productFilters), array_values($productFilters), $params);
	}

	public static function getAvailableFiltersDataForCatalog($catalogId, $products)
	{
		$catalog = Orm::load('Catalog', $catalogId);
		if (!$catalog) return [];

		$filterSets = [];
		$filterSetsCollection = $catalog->getRelated('filter_sets');

		foreach ($filterSetsCollection->getCollection() as $filterSet) {
			$filtersCollection = Orm::find('Filter', ['filterSetId'], [$filterSet->getId()]);
			$filterSets[$filterSet->getId()] = $filterSet->getValues();

			foreach ($filtersCollection->getCollection() as $filter) {
				$filterSets[$filterSet->getId()]['filters'][$filter->getId()] = $filter->getValues();
				$filterSets[$filterSet->getId()]['filters'][$filter->getId()]['count'] = 0;
			}
		}

		foreach ($products->getCollection() as $product) {
			foreach ($product->getFilters()->getCollection() as $filter) {
				if ($filterSets[$filter->getValue('filterSetId')]) {
					$filterSets[$filter->getValue('filterSetId')]['filters'][$filter->getId()]['count']++;
				}
			}
		}


		return $filterSets;
	}

	public static function getAvailableFiltersData($products)
	{
		$filters = [];
		$productFilters = [];

		foreach ($products->getCollection() as $product) {
			foreach ($product->getFilters()->getCollection() as $filter) {
				array_push($filters, $filter->getValues());

				isset($productFilters[$filter->getValue('id')])
					? $productFilters[$filter->getValue('id')]++
					: $productFilters[$filter->getValue('id')] = 1;
			}
		}

		$filterSets = [];

		foreach ($filters as $filter) {
			$set = Orm::load('FilterSet', $filter['filterSetId'])->getValues();

			if (!isset($filterSets[$set['id']])) {
				$filterSets[$set['id']] = $set;
			}

			$filter['count'] = $productFilters[$filter['id']];
			$filterSets[$set['id']]['filters'][$filter['id']] = $filter;
		}

		return $filterSets;
	}
}