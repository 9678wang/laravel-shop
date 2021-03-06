<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    const TYPE_NORMAL = 'normal';
    const TYPE_CROWDFUNDING = 'crowdfunding';
    const TYPE_SECKILL = 'seckill';

    public static $typeMap = [
        self::TYPE_NORMAL => '普通商品',
        self::TYPE_CROWDFUNDING => '众筹商品',
        self::TYPE_SECKILL => '秒杀商品',
    ];

    protected $fillable = [
    	'title',
        'long_title', 
        'description',
        'image', 
        'on_sale',
    	'rating', 
        'sold_count', 
        'review_count', 
        'price', 
        'type',
    ];

    protected $casts = [
    	'on_sale' => 'boolean',
    ];

    public function skus()
    {
    	return $this->hasMany(ProductSku::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getImageUrlAttribute()
    {
        //如果image字段本身就已经是完整的URL就直接返回
        if(Str::startsWith($this->attributes['image'], ['http://', 'https://'])){
            return $this->attributes['image'];
        }

        return \Storage::disk('public')->url($this->attributes['image']);
    }

    public function crowdfunding()
    {
        return $this->hasOne(CrowdfundingProduct::class);
    }

    public function properties()
    {
        return $this->hasMany(ProductProperty::class);
    }

    public function getGroupedPropertiesAttribute()
    {
        return $this->properties
            //按照属性名聚合，返回的集合的key是属性名，value是包含该属性名的所有属性集合
            ->groupBy('name')
            ->map(function($properties){
                //使用map方法将属性集合变为属性值集合
                return $properties->pluck('value')->all();
            });
    }

    public function toESArray()
    {
        //只取出需要的字段
        $arr = array_only($this->toArray(), [
            'id',
            'type',
            'title',
            'category_id',
            'long_title',
            'on_sale',
            'rating',
            'sold_count',
            'review_count', 
            'price',
        ]);

        //如果商品由类目，则category字段位类目名数组，否则为空字符串
        $arr['category'] = $this->category ? explode(' - ', $this->category->full_name) : '';
        //类目的path字段
        $arr['category_path'] = $this->category ? $this->category->path : '';
        $arr['description'] = strip_tags($this->description);
        $arr['skus'] = $this->skus->map(function(ProductSku $sku){
            return array_only($sku->toArray(), ['title', 'description', 'price']);
        });
        $arr['properties'] = $this->properties->map(function(ProductProperty $property){
            return array_merge(array_only($property->toArray(), ['name', 'value']), [
                'search_value' => $property->name.':'.$property->value,
            ]);
        });

        return $arr;
    }

    public function scopeByIds($query, $ids)
    {
        return $query->whereIn('id', $ids)->orderByRaw(sprintf("FIND_IN_SET(id, '%s')", join(',', $ids)));
    }

    public function seckill()
    {
        return $this->hasOne(SeckillProduct::class);
    }
}
