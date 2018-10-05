<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Exceptions\InvalidRequestException;
use App\Models\OrderItem;
use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use App\SearchBuilders\ProductSearchBuilder;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = 16;

        //新建查询构造器对象，设置只搜索上架商品，设置分页
        $builder = (new ProductSearchBuilder())->onSale()->paginate($perPage, $page);

        if($request->input('category_id') && $category = Category::find($request->input('category_id'))){
            $builder->category($category);
        }

        if($search = $request->input('search', '')){
            //将搜索词根据空格拆分成数组，并过滤掉空项
            $keywords = array_filter(explode(' ', $search));
            $builder->keywords($keywords);
        }

        //只有当用户有输入搜索词或者使用了类目筛选的时候才会做聚合
        if($search || isset($category)){
            $builder->aggregateProperties();
        }

        $propertyFilters = [];
        //从用户请求参数获取filters
        if($filterString = $request->input('filters')){
            //将获取到的字符串用符号|拆分成数组
            $filterArray = explode('|', $filterString);
            foreach($filterArray as $filter){
                //将字符串用符号:拆分成两部分并且分别赋值给$name和$value连个变量
                list($name, $value) = explode(':', $filter);
                //将用筛选的属性添加到数组中
                $propertyFilters[$name] = $value;
                //追加到filter类型中
                $builder->propertyFilter($name, $value);
            }
        }

    	//是否有提交order参数，如果有就赋值给$order变量
    	//order参数用来控制商品的排序规则
    	if($order = $request->input('order', '')){
    		//是否是以_asc或者_desc结尾
    		if(preg_match('/^(.+)_(asc|desc)$/', $order, $m)){
    			//如果字符串的开头上这3个字符串之一，说明是一个合法的排序值
    			if(in_array($m[1], ['price', 'sold_count', 'rating'])){
    				//根据传入的排序值来构造排序参数
    				$builder->orderBy($m[1], $m[2]);
    			}
    		}
    	}
              
    	$result = app('es')->search($builder->getParams());
        //通过colect函数将返回结果转为集合，并通过集合的pluck方法取到返回的商品ID数组
        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderByRaw(sprintf("FIND_IN_SET(id, '%s')", join(',', $productIds)))
            ->get();
        $pager = new LengthAwarePaginator($products, $result['hits']['total'], $perPage, $page, [
            'path' => route('products.index', false),
        ]);

        $properties = [];
        //如果返回结果里有aggregations字段，说明做了分面搜索
        if(isset($result['aggregations'])){
            //使用collect函数将返回值转为集合
            $properties = collect($result['aggregations']['properties']['properties']['buckets'])
                ->map(function($bucket){
                    return [
                        'key' => $bucket['key'],
                        'values' => collect($bucket['value']['buckets'])->pluck('key')->all(),
                    ];
                })
                ->filter(function($property) use($propertyFilters){
                    //过滤掉只剩下一个值或者已经在筛选条件里的属性
                    return count($property['values']) > 1 && !isset($propertyFilters[$property['key']]);
                });
        }

    	return view('products.index', [
    		'products' => $pager,
    		'filters' => [
    			'search' => $search,
    			'order' => $order,
    		],
            'category' => $category ?? null,
            'properties' => $properties,
            'propertyFilters' => $propertyFilters,
    	]);
    }

    public function show(Product $product, Request $request)
    {
    	//判断商品是否已经上架，如果没有上架则抛出异常
    	if(!$product->on_sale){
    		throw new InvalidRequestException('商品未上架');
    		
    	}
        $favored = false;
        //用户未登录时返回的是null，已登录时返回的是对应的用户对象
        if($user = $request->user()){
            //从当前用户已收藏的商品中搜索id为当前商品id的商品
            //boolval()函数用于把值转为布尔值
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        $reviews = OrderItem::query()
            ->with(['order.user', 'productSku'])
            ->where('product_id', $product->id)
            ->whereNotNull('reviewed_at')
            ->orderBy('reviewed_at', 'desc')
            ->limit(10)
            ->get();

    	return view('products.show', [
            'product' => $product, 
            'favored' => $favored,
            'reviews' => $reviews,
        ]);
    }

    public function favor(Product $product, Request $request)
    {
        $user = $request->user();
        if($user->favoriteProducts()->find($product->id)){
            return [];
        }

        $user->favoriteProducts()->attach($product);

        return [];
    }

    public function disfavor(Product $product, Request $request)
    {
        $user = $request->user();
        $user->favoriteProducts()->detach($product);

        return [];
    }

    public function favorites(Request $request)
    {
        $products = $request->user()->favoriteProducts()->paginate(16);

        return view('products.favorites', ['products' => $products]);
    }
}
