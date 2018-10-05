<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    const TYPE_NORMAL = 'normal';
    const TYPE_CROWDFUNDING = 'crowdfunding';

    public static $typeMap = [
        self::TYPE_NORMAL => '普通商品',
        self::TYPE_CROWDFUNDING => '众筹商品',
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
}
