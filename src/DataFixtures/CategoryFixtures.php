<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $defaultCategories = [
            [
                'name' => 'Fantastyka',
                'description' => 'Książki fantasy, science fiction i inne gatunki fantastyczne'
            ],
            [
                'name' => 'Kryminał',
                'description' => 'Powieści kryminalne, thrillery i sensacyjne'
            ],
            [
                'name' => 'Romans',
                'description' => 'Powieści romantyczne i obyczajowe'
            ],
            [
                'name' => 'Biografia',
                'description' => 'Biografie, autobiografie i pamiętniki'
            ],
            [
                'name' => 'Popularnonaukowa',
                'description' => 'Książki popularnonaukowe z różnych dziedzin'
            ],
            [
                'name' => 'Biznes',
                'description' => 'Książki o biznesie, zarządzaniu i rozwoju osobistym'
            ],
            [
                'name' => 'Historia',
                'description' => 'Książki historyczne i dokumentalne'
            ],
            [
                'name' => 'Dla dzieci',
                'description' => 'Książki dla dzieci i młodzieży'
            ],
            [
                'name' => 'Poradniki',
                'description' => 'Poradniki praktyczne z różnych dziedzin'
            ],
            [
                'name' => 'Klasyka',
                'description' => 'Klasyka literatury polskiej i światowej'
            ]
        ];

        foreach ($defaultCategories as $categoryData) {
            $category = new Category(
                $categoryData['name'],
                $categoryData['description'],
                true // Mark as default
            );
            
            $manager->persist($category);
        }

        $manager->flush();
    }
}
