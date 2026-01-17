<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function save(Category $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }

    public function remove(Category $category): void
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }

    public function findById(Uuid $id): ?Category
    {
        return $this->find($id);
    }

    public function findByName(string $name): ?Category
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function findAllActive(): array
    {
        return $this->findAll();
    }

    public function findDefaultCategories(): array
    {
        return $this->findBy(['isDefault' => true]);
    }
}
