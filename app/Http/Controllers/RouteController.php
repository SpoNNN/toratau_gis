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
            'data' => $routes->map(function ($route) {
                return $this->transformRoute($route);
            })
        ]);
    }

    public function getRoute($id)
    {
        $route = Route::with('routeInfos')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $this->transformRoute($route)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'map_color'    => 'nullable|string|max:50',
            'distance'     => 'nullable|numeric|min:0',
            'duration'     => 'nullable|integer|min:0',
            'participants' => 'nullable|integer|min:0',
            'audience'     => 'nullable|string|max:100',
            'slug'         => 'nullable|string|max:255|unique:route,slug',
        ]);

        $route = Route::create([
            'title'        => $request->title,
            'mapColor'     => $request->map_color,  
            'description'  => $request->description,
            'distance'     => $request->distance,
            'duration'     => $request->duration,
            'participants' => $request->participants,
            'audience'     => $request->audience,
            'slug'         => $request->slug,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->transformRoute($route->load('point', 'routeInfos')),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $route = Route::findOrFail($id);

        $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'description'  => 'nullable|string',
            'map_color'    => 'nullable|string|max:50',
            'distance'     => 'nullable|numeric|min:0',
            'duration'     => 'nullable|integer|min:0',
            'participants' => 'nullable|integer|min:0',
            'audience'     => 'nullable|string|max:100',
            'slug'         => 'nullable|string|max:255|unique:route,slug,' . $id,
        ]);

        $route->update([
            'title'        => $request->input('title',        $route->title),
            'mapColor'     => $request->input('map_color',    $route->mapColor),
            'description'  => $request->input('description',  $route->description),
            'distance'     => $request->input('distance',     $route->distance),
            'duration'     => $request->input('duration',     $route->duration),
            'participants' => $request->input('participants',  $route->participants),
            'audience'     => $request->input('audience',     $route->audience),
            'slug'         => $request->input('slug',         $route->slug),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->transformRoute($route->fresh()->load('point', 'routeInfos')),
        ]);
    }

    public function storeOrUpdateInfo(Request $request, $routeId)
    {
        $request->validate([
            'value' => 'required|string',
        ]);

       
        $info = RouteInfo::updateOrCreate(
            [
                'route_id' => $routeId,
                'label'    => 'Программа обслуживания и посещения',
            ],
            [
                'value' => $request->value,
            ]
        );

        return response()->json([
            'success' => true,
            'data'    => $info,
        ]);
    }

    public function destroy($id)
    {
        $route = Route::findOrFail($id);
        $route->delete();

        return response()->json([
            'success' => true,
            'message' => 'Маршрут удалён',
        ]);
    }

    private function transformRoute(Route $route)
    {
        return [
            'id'           => $route->id,
            'title'        => $route->title,
            'map_color'    => $route->mapColor,   
            'description'  => $route->description,
            'distance'     => $route->distance,
            'duration'     => $route->duration,
            'audience'     => $route->audience,
            'participants' => $route->participants,
            'slug'         => $route->slug,
            'info_items'   => $route->routeInfos
                ? $route->routeInfos->mapWithKeys(function ($info) {
                    return [
                        $info->key => [
                            'label' => $info->label,
                            'value' => $info->value,
                        ]
                    ];
                })
                : [],
            'point' => $route->point
                ? $route->point->map(function ($point) {
                    return [
                        'lon'         => (float) $point->lon,
                        'lat'         => (float) $point->lat,
                        'name'        => $point->name,
                        'address'     => $point->address,
                        'url'         => $point->url,
                        'point_name'  => $point->pointName,
                        'description' => $point->description,
                        'images'      => json_decode($point->images) ?? [],
                    ];
                })
                : [],
        ];
    }
}