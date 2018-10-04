<?php

namespace App\Http\ViewComposers;

use App\Services\CategoryService;
use Illuminate\View\View;

class CategoryTreeComposer
{
	protected $categoryService;

	public function __construct(CategoryService $categoryService)
	{
		$this->categoryService = $categoryService;
	}

	//当渲染指定的模版时，laravel会调用compose方法
	public function compose(View $view)
	{
		$view->with('categoryTree', $this->categoryService->getCategoryTree());
	}
}