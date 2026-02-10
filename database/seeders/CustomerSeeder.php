<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            ['name' => 'علی احمدی', 'phone' => '09121234567', 'national_id' => '0012345678', 'email' => 'ali@example.com'],
            ['name' => 'محمد رضایی', 'phone' => '09127654321', 'national_id' => '0098765432', 'email' => 'mohammad@example.com'],
            ['name' => 'عباس کریمی', 'phone' => '09131112222', 'national_id' => '0001122334', 'email' => 'sara@example.com'],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
