<?php

namespace App\Services\Parameter;

use App\Filters\Parameter\FilterParameter;
use App\Models\Parameter\ParameterValue;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class ParameterService{

    public function allParameters($parameterOrder){

        $parameters = QueryBuilder::for(ParameterValue::class)
        ->allowedFilters([
            //AllowedFilter::custom('search', new FilterParameter()), // Add a custom search filter
            //AllowedFilter::exact('parameterOrder', 'parameter_id'),
        ])
        ->parameterOrder($parameterOrder)
        ->get();
        return $parameters;

    }

    public function createParameter(array $parameterData){

        $parameter = DB::table('parameters')->where('parameter_order', $parameterData['parameterOrder'])->first();

        $paramteterValue = ParameterValue::create([
            'parameter_id' => $parameter->id,
            'parameter_order' => $parameterData['parameterOrder'],
            'parameter_value' => $parameterData['parameterValue'],
            'description' => $parameterData['description'],
            'is_default' => $parameterData['isDefault']??0,
            'code' => $parameterData['code']??null,
            'description2' => $parameterData['descriptionTwo']??null,
            'description3' => $parameterData['descriptionThree']??null,
        ]);

        if(isset($parameterData['isDefault']) && $parameterData['isDefault'] == 1){
            ParameterValue::whereNot('id', $paramteterValue->id)->where('parameter_order', $paramteterValue->parameter_order)->where('is_default', 1)->update([
                'is_default' => 0
            ]);
        }

        return $paramteterValue;

    }

    public function editParameter(string $paramteterValueGuid){
        $parameterValue = ParameterValue::find($paramteterValueGuid);

       /* return response()->json([
            'parameterValues' => new ParameterValueResource($parameterValues)
        ], 200);*/

        return $parameterValue;

    }

    public function updateParameter(array $parameterData){
        $paramteterValue = ParameterValue::find($parameterData['parameterValueId']);

        $paramteterValue->fill([
            'parameter_value' => $parameterData['parameterValue'],
            'description' => $parameterData['description'],
            'code' => $parameterData['code']??null,
            'description2' => $parameterData['descriptionTwo']??null,
            'description3' => $parameterData['descriptionThree']??null,
        ]);

        $paramteterValue->parameter_value = $parameterData['parameterValue'];
        $paramteterValue->description = $parameterData['description'];
        $paramteterValue->code = $parameterData['code']??null;
        $paramteterValue->description2 = $parameterData['descriptionTwo']??null;
        $paramteterValue->description3 = $parameterData['descriptionThree']??null;

        if (isset($parameterData['isDefault'])) {
            $paramteterValue->is_default = $parameterData['isDefault'];

            ParameterValue::whereNot('id', $parameterData['parameterValueId'])->where('parameter_order', $paramteterValue->parameter_order)->where('is_default', 1)->update([
                'is_default' => 0
            ]);
        }

        $paramteterValue->save();

        return $paramteterValue;

    }

    public function deleteParameter(string $paramteterValueId){
        $parameter = ParameterValue::find($paramteterValueId);
        $parameter->delete();
        /*return response()->json([
            'message' => 'parameter has been deleted!'
        ], 200);*/

    }

}
