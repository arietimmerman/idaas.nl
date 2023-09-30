<?php

namespace App\Console\Commands;

use Faker\Generator as Faker;
use Illuminate\Console\Command;

class TestUsers extends NewTenant
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tesst:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a list of test users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function clean($var)
    {
        return str_replace(['"'], [''], $var);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $faker = resolve(Faker::class);

        $i = 0;

        $out = fopen('php://output', 'w');

        while ($i < 3000) {
            fputcsv(
                $out,
                [$this->clean($faker->username), $this->clean($faker->password), $this->clean($faker->email)]
            );
            $i++;
        }

        fclose($out);
    }
}
