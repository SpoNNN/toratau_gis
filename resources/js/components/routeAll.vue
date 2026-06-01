<template>
  <div>
    <div id="map" class="map" tabindex="0" style="min-height: 850px"></div>

    <div class="legend" v-if="showAllRoutes && allRoutesLegend.length > 0">
      <div class="legend-item" v-for="route in allRoutesLegend" :key="route.id">
        <span
          class="legend-line"
          :style="{ backgroundColor: '#' + route.mapColor }"
        ></span>
        <span class="legend-label">{{ route.title }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted, watch, ref } from 'vue';
import axios from 'axios';
import Map from 'ol/Map';
import OSM from 'ol/source/OSM';
import TileLayer from 'ol/layer/Tile';
import View from 'ol/View';
import { fromLonLat } from 'ol/proj';
import 'ol/ol.css';
import { Vector as VectorLayer } from 'ol/layer';
import { Vector as VectorSource } from 'ol/source';
import { Style, Stroke } from 'ol/style';
import { Feature } from 'ol';
import { LineString } from 'ol/geom';

export default {
  name: 'MapWithPoints',

  props: {
    routeId: {
      type: Number,
      default: null,
    },
    points: {
      type: Array,
      default: () => [],
    },
    routeColor: {
      type: String,
      default: 'FFB800',
    },
  },

  setup(props) {
    let map = null;
    let vectorLayers = [];

    const showAllRoutes = ref(true);
    const allRoutesLegend = ref([]);

    const clearLayers = () => {
      vectorLayers.forEach((layer) => map.removeLayer(layer));
      vectorLayers = [];
    };

    const buildStraightLine = (points, color) => {
      const projected = points.map((p) =>
        fromLonLat([Number(p.lon), Number(p.lat)])
      );

      const lineFeature = new Feature({
        geometry: new LineString(projected),
      });

      lineFeature.setStyle(
        new Style({
          stroke: new Stroke({
            color: '#' + color,
            width: 4,
            lineDash: [8, 4],
          }),
        })
      );

      const layer = new VectorLayer({
        source: new VectorSource({
          features: [lineFeature],
        }),
        zIndex: 2,
      });

      map.addLayer(layer);
      vectorLayers.push(layer);
    };

    const buildRouteForPoints = async (points, color) => {
      if (!points || points.length < 2) return;

      const coordinates = points
        .map((p) => `${p.lon},${p.lat}`)
        .join(';');

      try {
        const response = await fetch(
          `https://router.project-osrm.org/route/v1/driving/${coordinates}?overview=full&geometries=geojson`
        );

        if (!response.ok) {
          throw new Error(`HTTP error ${response.status}`);
        }

        const data = await response.json();

        if (data.code !== 'Ok' || !data.routes?.length) {
          throw new Error('Route not found');
        }

        const projected = data.routes[0].geometry.coordinates.map((coord) =>
          fromLonLat(coord)
        );

        const lineFeature = new Feature({
          geometry: new LineString(projected),
        });

        lineFeature.setStyle(
          new Style({
            stroke: new Stroke({
              color: '#' + color,
              width: 5,
            }),
          })
        );

        const layer = new VectorLayer({
          source: new VectorSource({
            features: [lineFeature],
          }),
          zIndex: 2,
        });

        map.addLayer(layer);
        vectorLayers.push(layer);
      } catch (error) {
        console.error('OSRM error:', error);
        buildStraightLine(points, color);
      }
    };

    const loadAllRoutesFromDB = async () => {
      try {
        const response = await axios.get('/api/routes');
        const routes = response.data.data || [];

        allRoutesLegend.value = routes
          .filter((route) => route.mapColor || route.map_color)
          .map((route) => ({
            id: route.id,
            title: route.title,
            mapColor: (
              route.mapColor ||
              route.map_color ||
              'FFB800'
            ).replace('#', ''),
          }));

        for (const route of routes) {
          const points = route.point || [];

          if (!points.length) continue;

          const color = (
            route.mapColor ||
            route.map_color ||
            'FFB800'
          ).replace('#', '');

          await buildRouteForPoints(points, color);
        }
      } catch (error) {
        console.error('Ошибка загрузки маршрутов:', error);
      }
    };

    const loadSingleRouteFromDB = async (routeId) => {
      try {
        const response = await axios.get(`/api/routes/${routeId}`);

        const route = response.data.data || response.data;
        const points = route.point || [];

        const color = (
          route.mapColor ||
          route.map_color ||
          props.routeColor ||
          'FFB800'
        ).replace('#', '');

        if (!points.length) return;

        await buildRouteForPoints(points, color);
      } catch (error) {
        console.error('Ошибка загрузки маршрута:', error);
      }
    };

    const loadMap = async () => {
      clearLayers();

      if (props.points && props.points.length > 0) {
        showAllRoutes.value = false;

        const color = (props.routeColor || 'FFB800').replace('#', '');

        await buildRouteForPoints(props.points, color);
        return;
      }

      if (props.routeId) {
        showAllRoutes.value = false;
        await loadSingleRouteFromDB(props.routeId);
        return;
      }

      showAllRoutes.value = true;
      await loadAllRoutesFromDB();
    };

    onMounted(() => {
      map = new Map({
        target: 'map',
        layers: [
          new TileLayer({
            source: new OSM(),
          }),
        ],
        view: new View({
          center: fromLonLat([55.9721, 54.7388]),
          zoom: 13,
        }),
      });

      loadMap();
    });

    watch(
      () => [props.routeId, props.points, props.routeColor],
      () => {
        if (map) {
          loadMap();
        }
      },
      {
        deep: true,
      }
    );

    return {
      showAllRoutes,
      allRoutesLegend,
    };
  },
};
</script>

<style scoped>
.map {
  width: 100%;
  height: 850px;
  border: 1px solid #ccc;
  position: relative;
}

.legend {
  position: absolute;
  top: 60px;
  right: 10px;
  background-color: rgba(255, 255, 255, 0.9);
  padding: 10px;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  z-index: 1000;
}

.legend-item {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
}

.legend-item:last-child {
  margin-bottom: 0;
}

.legend-line {
  width: 40px;
  height: 4px;
  display: inline-block;
  margin-right: 10px;
  border-radius: 2px;
}

.legend-label {
  font-size: 14px;
  color: #333;
  font-weight: 500;
}
</style>