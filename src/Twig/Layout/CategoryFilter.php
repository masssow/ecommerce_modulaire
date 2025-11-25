<?php

namespace App\Twig\Layout;

use App\Repository\CategoryRepository;

class CategoryFilter
{
    public function __construct(private CategoryRepository $repo) {}

    public function get(): array
    {
        return $this->repo->findBy([], ['name' => 'ASC'], 4);
    }
}
