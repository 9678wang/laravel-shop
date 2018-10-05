<?php

namespace App\Services;

use App\Models\Product;
use App\SearchBuilders\ProductSearchBuilder;

class ProductService
{
	public function getSimilarProductIds(Product $product, $amount)
	{
		//如果商品没有商品属性，则直接返回空
		if(count($product->properties) === 0){
			return [];
		}

		//创建一个查询构造器，只搜索上架的商品，取搜索结果的前$amount个商品
        $builder = (new ProductSearchBuilder())->onSale()->paginate($amount, 1);
        //遍历当前商品的属性
        foreach($product->properties as $property){
            //添加到should条件中
            $builder->propertyFilter($property->name, $property->value, 'should');
        }
        //设置最少匹配一半属性
        $builder->minShouldMatch(ceil(count($product->properties) / 2));
        $params = $builder->getParams();
        //同时将当前商品的ID排除
        $params['body']['query']['bool']['must_not'] = [['term' => ['_id' => $product->id]]];
        $result = app('es')->search($params);
        
        return collect($result['hits']['hits'])->pluck('_id')->all();
	}
}