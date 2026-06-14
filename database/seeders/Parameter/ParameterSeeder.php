<?php

namespace Database\Seeders\Parameter;

use App\Models\Parameter\Parameter;
use App\Traits\MultiDatabaseArray;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParameterSeeder extends Seeder
{

    /**
     * List of parameter names to seed.
     * @var array
     */
    protected $parameters = [
        'addressType',
        'contactType',
        'ticketConnectionType',
        'paymentType',
        'paySteps',
        'serviceType'
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        foreach ($this->parameters as $index => $parameterName) {
            $parameterData[] = [
                'parameter_name' => $parameterName,
                'parameter_order' => $index + 1
            ];
        }

        DB::table('parameters')->insert($parameterData);
    }
}
