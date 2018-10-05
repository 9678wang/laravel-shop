<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Exceptions\InvalidRequestException;
use App\Models\OrderItem;
use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = 16;

        //构建查询
        $params = [
            'index' => 'products',
            'type' => '_doc',
            'body' => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['on_sale' => true]],
                        ],
                    ],
                ],
            ],
        ];

    	//是否有提交order参数，如果有就赋值给$order变量
    	//order参数用来控制商品的排序规则
    	if($order = $request->input('order', '')){
    		//是否是以_asc或者_desc结尾
    		if(preg_match('/^(.+)_(asc|desc)$/', $order, $m)){
    			//如果字符串的开头上这3个字符串之一，说明是一个合法的排序值
    			if(in_array($m[1], ['price', 'sold_count', 'rating'])){
    				//根据传入的排序值来构造排序参数
    				$params['body']['sort'] = [$m[1] => $m[2]];
    			}
    		}
    	}
        if($request->input('category_id') && $category = Category::find($request->input('category_id'))){
            if($category->is_directory){
                //如果是一个父类目，则使用category_path来筛选
                $params['body']['query']['bool']['filter'][] = [
                    'prefix' => ['category_path' => $category->path.$category->id.'-'],
                ];
            }else{
                //否则直接通过category_id筛选
                $params['body']['query']['bool']['filter'][] = ['term' => ['category_id' => $category->id]];
            }
        }
        if($search = $request->input('search', '')){
            //将搜索词根据空格拆分成数组，并过滤掉空项
            $keywords = array_filter(explode(' ', $search));
            $params['body']['query']['bool']['must'] = [];
            foreach($keywords as $keyword){
                $params['body']['query']['bool']['must'][] = [
                    'multi_match' => [
                        'query' => $keyword,
                        'fields' => [
                            'title^3',
                            'long_title^2',
                            'category^2',
                            'description',
                            'skus_title',
                            'skus_description',
                            'properties_value',
                        ],
                    ],
                ];
            }
        }
        //只有当用户有输入搜索词或者使用了类目筛选的时候才会做聚合
        if($search || isset($category)){
            $params['body']['aggs'] = [
                //这里的properties是我们给这个聚合操作的命名
                //可以说其他字符串，与商品结构里的properties没有必然联系
                'properties' => [
                    //由于我们要聚合的属性是在nested类型字段下的属性，需要在外面套一层nested聚合查询
                    'nested' => [
                        //代表我们要查询的nested字段名为properties
                        'path' => 'properties',
                    ],
                    //在nested聚合下嵌套聚合
                    'aggs' => [
                        //聚合名称
                        'properties' => [
                            //terms聚合，用于聚合相同的值
                            'terms' => [
                                //我们要聚合的字段名
                                'field' => 'properties.name',
                            ],
                            'aggs' => [
                                'value' => [
                                    'terms' => [
                                        'field' => 'properties.value',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
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
                $params['body']['query']['bool']['filter'][] = [
                    //由于我们要筛选的是nested类型下的属性，因此需要用nested查询
                    'nested' => [
                        'path' => 'properties',
                        'query' => [
                            ['term' => ['properties.name' => $name]], 
                            ['term' => ['properties.value' => $value]],
                        ],
                    ],
                ];
            }
        }
    	$result = app('es')->search($params);
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
