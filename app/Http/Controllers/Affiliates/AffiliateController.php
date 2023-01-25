<?php

namespace App\Http\Controllers\Affiliates;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class AffiliateController extends Controller
{
    public function __invoke()
    {
        $fileName = config('affiliates.file-name');
        //Retrieve data from storage
        if (!Storage::disk('local')->get($fileName)) {
            abort(404, 'Affiliates file not found');
        }

        $rawData = Storage::disk('local')->get($fileName);

        //Turn data into array for easier processing
        $arrayData = explode("\n", $rawData);
        $arrayData = Arr::map($arrayData, function ($value, $key) {
            return json_decode($value, true);
        });

        //Filter by distance from config
        $processedData = Arr::where($arrayData, function ($value, $key) {
            return $this->calculate($value);
        });


        //Sort by affiliate_id from data
        $data = array_values(Arr::sort($processedData, function ($value) {
            return $value['affiliate_id'];
        }));

        return view('index', compact('data'));
    }

    public function calculate($entry)
    {
        $distance = config('affiliates.distance-km');
        $lat = deg2rad(config('affiliates.latitude'));
        $long = deg2rad(config('affiliates.longitude'));

        $entryLat = deg2rad($entry['latitude']);
        $entryLong = deg2rad($entry['longitude']);

        //3963.19 is the radius of the earth in miles and 1.60934 is 1 mile in km
        $calc = acos(
            (sin($entryLat) * sin($lat))
             + (cos($entryLat) * cos($lat)* cos($long - $entryLong))
            ) * 3963.19 * 1.60934;

        return round($calc,2) <= $distance;
    }

}