<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Parameter\ParameterValue;

class MigrateBankNamesToParameterValuesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get unique bank names from client_bank_accounts where banca is not null
        $uniqueBankNames = DB::table('client_bank_accounts')
            ->whereNotNull('banca')
            ->where('banca', '!=', '')
            ->distinct()
            ->pluck('banca');

        echo "Found " . $uniqueBankNames->count() . " unique bank names\n";

        // Get the max parameter_order for parameter_id = 10
        $maxOrder = DB::table('parameter_values')
            ->where('parameter_id', 10)
            ->max('parameter_order') ?? 0;

        // Create parameter_values for each unique bank name
        $bankMapping = [];

        foreach ($uniqueBankNames as $bankName) {
            // Check if this bank name already exists in parameter_values
            $existingParam = ParameterValue::where('parameter_id', 10)
                ->where('parameter_value', $bankName)
                ->first();

            if ($existingParam) {
                $bankMapping[$bankName] = $existingParam->id;
                echo "Bank already exists: {$bankName} (ID: {$existingParam->id})\n";
            } else {
                // Increment order for new entry
                $maxOrder++;

                // Create new parameter_value
                $parameterValue = ParameterValue::create([
                    'parameter_id' => 10,
                    'parameter_value' => $bankName,
                    'description' => $bankName,
                    'parameter_order' => $maxOrder,
                    'is_default' => 0
                ]);

                $bankMapping[$bankName] = $parameterValue->id;
                echo "Created new bank: {$bankName} (ID: {$parameterValue->id}, Order: {$maxOrder})\n";
            }
        }

        // Update client_bank_accounts with the corresponding bank_id
        foreach ($bankMapping as $bankName => $parameterId) {
            $updated = DB::table('client_bank_accounts')
                ->where('banca', $bankName)
                ->update(['bank_id' => $parameterId]);

            echo "Updated {$updated} records for bank: {$bankName}\n";
        }

        echo "\nMigration completed successfully!\n";
    }
}
