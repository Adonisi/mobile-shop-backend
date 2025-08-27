<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
                [
                    'name' => 'Smartphones',
                    'description' => 'Derniers smartphones et téléphones portables',
                    'slug' => 'smartphones',
                ],
                [
                    'name' => 'Tablettes',
                    'description' => 'Tablettes et iPads',
                    'slug' => 'tablets',
                ],
                [
                    'name' => 'Ordinateurs portables',
                    'description' => 'Ordinateurs portables et notebooks',
                    'slug' => 'laptops',
                ],
                [
                    'name' => 'Accessoires',
                    'description' => 'Accessoires mobiles et gadgets',
                    'slug' => 'accessories',
                ],
            
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
