<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $appDebug = config('app.debug');

        $this->call(RoleTableSeeder::class);
        $this->call(UserTableSeeder::class);

        if ($appDebug) {
            $this->call(ExciseStampTableSeeder::class);

            $this->call(ReadBarCodeTableSeeder::class);
            $this->call(OrderTableSeeder::class);
            $this->call(InvoiceTableSeeder::class);
            $this->call(ReturnedInvoiceTableSeeder::class);

            factory(App\Models\Order\Order::class,3)->create();
            //factory(App\Models\Order\OrderErrorLine::class,100)->create();
            //factory(App\Models\Invoice\Invoice::class,10)->create();


            //$this->call(CategoryTableSeeder::class);

            //$this->call([
            //    UsersTableSeeder::class,
            //    PostsTableSeeder::class,
            //    CommentsTableSeeder::class,
            //]);
        }
    }
}
