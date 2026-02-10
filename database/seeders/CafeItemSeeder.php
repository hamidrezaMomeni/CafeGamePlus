<?php

namespace Database\Seeders;

use App\Models\CafeItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CafeItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['name' => 'قهوه اسپرسو', 'category' => 'نوشیدنی گرم', 'price' => 25000],
            ['name' => 'کاپوچینو', 'category' => 'نوشیدنی گرم', 'price' => 30000],
            ['name' => 'لاته', 'category' => 'نوشیدنی گرم', 'price' => 32000],
            ['name' => 'چای', 'category' => 'نوشیدنی گرم', 'price' => 15000],
            ['name' => 'نسکافه', 'category' => 'نوشیدنی گرم', 'price' => 20000],
            ['name' => 'نوشابه', 'category' => 'نوشیدنی سرد', 'price' => 10000],
            ['name' => 'آب معدنی', 'category' => 'نوشیدنی سرد', 'price' => 5000],
            ['name' => 'دلستر', 'category' => 'نوشیدنی سرد', 'price' => 12000],
            ['name' => 'چیپس', 'category' => 'اسنک', 'price' => 15000],
            ['name' => 'پفک', 'category' => 'اسنک', 'price' => 12000],
            ['name' => 'شکلات', 'category' => 'اسنک', 'price' => 18000],
            ['name' => 'ساندویچ', 'category' => 'غذا', 'price' => 45000],
            ['name' => 'پیتزا', 'category' => 'غذا', 'price' => 60000],
        ];

        foreach ($items as $item) {
            CafeItem::create($item);
        }
    }
}
