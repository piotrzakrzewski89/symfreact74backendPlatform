<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Domain\Entity\Category;
use App\Domain\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api/categories', name: 'api_categories_')]
class CategoryController extends AbstractController
{
    public function __construct(
        private CategoryRepository $categoryRepository
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->findAllActive();
        
        return $this->json([
            'success' => true,
            'data' => array_map(fn(Category $cat) => [
                'id' => $cat->getId()->toString(),
                'name' => $cat->getName(),
                'description' => $cat->getDescription(),
                'isDefault' => $cat->isDefault(),
                'createdAt' => $cat->getCreatedAt()->format('Y-m-d H:i:s')
            ], $categories)
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $category = $this->categoryRepository->findById(Uuid::fromString($id));
            
            if (!$category) {
                return $this->json([
                    'success' => false,
                    'error' => 'Category not found'
                ], 404);
            }
            
            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $category->getId()->toString(),
                    'name' => $category->getName(),
                    'description' => $category->getDescription(),
                    'isDefault' => $category->isDefault(),
                    'createdAt' => $category->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid category ID'
            ], 400);
        }
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['name']) || empty(trim($data['name']))) {
                return $this->json([
                    'success' => false,
                    'error' => 'Category name is required'
                ], 400);
            }
            
            // Check if category already exists
            $existing = $this->categoryRepository->findByName($data['name']);
            if ($existing) {
                return $this->json([
                    'success' => false,
                    'error' => 'Category with this name already exists'
                ], 409);
            }
            
            $category = new Category(
                $data['name'],
                $data['description'] ?? null,
                false // Custom categories are not default
            );
            
            $this->categoryRepository->save($category);
            
            return $this->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => [
                    'id' => $category->getId()->toString(),
                    'name' => $category->getName(),
                    'description' => $category->getDescription(),
                    'isDefault' => $category->isDefault()
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to create category: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $category = $this->categoryRepository->findById(Uuid::fromString($id));
            
            if (!$category) {
                return $this->json([
                    'success' => false,
                    'error' => 'Category not found'
                ], 404);
            }
            
            // Don't allow editing default categories
            if ($category->isDefault()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Cannot edit default categories'
                ], 403);
            }
            
            $data = json_decode($request->getContent(), true);
            
            if (isset($data['name']) && !empty(trim($data['name']))) {
                // Check if new name conflicts with existing category
                $existing = $this->categoryRepository->findByName($data['name']);
                if ($existing && $existing->getId()->toString() !== $id) {
                    return $this->json([
                        'success' => false,
                        'error' => 'Category with this name already exists'
                    ], 409);
                }
                $category->setName($data['name']);
            }
            
            if (isset($data['description'])) {
                $category->setDescription($data['description']);
            }
            
            $this->categoryRepository->save($category);
            
            return $this->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => [
                    'id' => $category->getId()->toString(),
                    'name' => $category->getName(),
                    'description' => $category->getDescription(),
                    'isDefault' => $category->isDefault()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to update category: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(string $id): JsonResponse
    {
        try {
            $category = $this->categoryRepository->findById(Uuid::fromString($id));
            
            if (!$category) {
                return $this->json([
                    'success' => false,
                    'error' => 'Category not found'
                ], 404);
            }
            
            // Don't allow deleting default categories
            if ($category->isDefault()) {
                return $this->json([
                    'success' => false,
                    'error' => 'Cannot delete default categories'
                ], 403);
            }
            
            $this->categoryRepository->remove($category);
            
            return $this->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Failed to delete category: ' . $e->getMessage()
            ], 500);
        }
    }
}
