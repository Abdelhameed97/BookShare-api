<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Fiction', 'type' => 'general'],
            ['name' => 'Non-Fiction', 'type' => 'general'],
            ['name' => 'Science Fiction', 'type' => 'general'],
            ['name' => 'Mystery', 'type' => 'general'],
            ['name' => 'Romance', 'type' => 'general'],
            ['name' => 'Thriller', 'type' => 'general'],
            ['name' => 'Biography', 'type' => 'general'],
            ['name' => 'History', 'type' => 'general'],
            ['name' => 'Science', 'type' => 'general'],
            ['name' => 'Technology', 'type' => 'general'],
            ['name' => 'Self-Help', 'type' => 'general'],
            ['name' => 'Business', 'type' => 'general'],
            ['name' => 'Philosophy', 'type' => 'general'],
            ['name' => 'Religion', 'type' => 'general'],
            ['name' => 'Children', 'type' => 'general'],
            ['name' => 'Young Adult', 'type' => 'general'],
            ['name' => 'Academic', 'type' => 'general'],
            ['name' => 'Textbook', 'type' => 'general'],
            ['name' => 'Reference', 'type' => 'general'],
            ['name' => 'Poetry', 'type' => 'general'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
