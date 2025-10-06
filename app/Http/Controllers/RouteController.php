<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Point;
use App\Models\RouteInfo;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function getRoutes()
    {
        $routes = Route::with(['point', 'RouteInfos'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $routes->map(function($route) {
                return $this->transformRoute($route);
            })
        ]);
    }

    public function getRoute($id)
    {
 
    $route = Route::with('routeInfos') // Use the correct relationship name
                ->findOrFail($id); // Use findOrFail for single ID lookup
    
                    
        return response()->json([
            'success' => true,
            'data' => $this->transformRoute($route)
        ]);
    }

    public function addPoint(Request $request, $slug)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'lon' => 'required|numeric',
            'lat' => 'required|numeric',
            // другие поля
        ]);
        
        $route = Route::where('slug', $slug)->firstOrFail();
        
        $point = new Point($request->all());
        $route->points()->save($point);
        
        return response()->json([
            'success' => true,
            'data' => $point
        ]);
    }

    private function transformRoute(Route $route)
    {
        return [
            'id' => $route->id,
            'title' => $route->title,
            'map_color' => $route->map_color,
            'description' => $route->description,
            'slug' => $route->slug,
            'info_items' => $route->routeInfos ? $route->routeInfos->mapWithKeys(function($info) {
                return [$info->key => [
                    'label' => $info->label,
                    'value' => $info->value
                ]];
            }) : [],
            'point' => $route->point ? $route->point->map(function($point) {
                return [
                    'lon' => (float)$point->lon,
                   
                    'lat' => (float)$point->lat,
                    'name' => $point->name,
                    'address' => $point->address,
                    'url' => $point->url,
                    'point_name' => $point->pointName,
                    'description' => $point->description,
                    'images' => json_decode($point->images) ?? []
                ];
            }) : []
        ];
    }
}