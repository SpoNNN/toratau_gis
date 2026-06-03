<template>
  <div>
    <div id="map" class="map" tabindex="0" style="min-height: 850px"></div>

    <!-- КНОПКА ДЛЯ ОТКРЫТИЯ ЛЕГЕНДЫ -->
    <div class="legend-toggle" @click="legendVisible = !legendVisible" v-if="showAllRoutes">
      <span v-if="!legendVisible">📋 Легенда</span>
      <span v-else>✖ Закрыть</span>
    </div>

    <!-- ЛЕГЕНДА (скрывается/показывается) -->
    <div class="legend" v-if="legendVisible && allRoutesLegend.length > 0">
      <div class="legend-header">
        <h4>Легенда маршрутов</h4>
      </div>
      <div class="legend-items">
        <div class="legend-item" v-for="route in allRoutesLegend" :key="route.id">
          <span
            class="legend-line"
            :style="{ backgroundColor: route.mapColor }"
          ></span>
          <span class="legend-label">{{ route.title }}</span>
        </div>
      </div>
    </div>

    <!-- POPUP -->
    <div
      v-if="popupVisible"
      :style="{ top: popupPosition.y + 'px', left: popupPosition.x + 'px' }"
      class="custom-popup"
    >
      <button class="close-btn" @click="hidePopup">✖</button>
      <h3 class="popup-title">{{ popupText }}</h3>
      <p class="popup-subtitle">Адрес</p>
      <p class="popup-content">{{ popupAddress }}</p>
      <div class="popup-actions">
        <a :href="popupUrl" target="_blank" class="popup-btn" v-if="popupUrl">
          <span>🔗</span> Перейти на сайт
        </a>
        <button class="popup-btn" @click="navigateToPage">
          <span>ℹ️</span> Подробнее
        </button>
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
import { Style, Stroke, Fill, Circle as CircleStyle } from 'ol/style';
import { Feature } from 'ol';
import { Point, LineString } from 'ol/geom';

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

  emits: ['navigate'],

  setup(props, { emit }) {
    let map = null;
    let vectorLayers = [];
    let pointsLayer = null;
    let selectedFeature = null;

    const showAllRoutes = ref(true);
    const allRoutesLegend = ref([]);
    const legendVisible = ref(false); // ← легенда скрыта по умолчанию
    
    // POPUP переменные
    const popupVisible = ref(false);
    const popupText = ref('');
    const popupAddress = ref('');
    const popupUrl = ref('');
    const popupPosition = ref({ x: 0, y: 0 });
    const currentPoint = ref(null);

    // Стиль для точек
    const defaultPointStyle = new Style({
      image: new CircleStyle({
        radius: 7,
        fill: new Fill({ color: '#' + (props.routeColor || 'FFB800') }),
        stroke: new Stroke({ color: '#fff', width: 2 }),
      }),
    });

    const selectedPointStyle = new Style({
      image: new CircleStyle({
        radius: 9,
        fill: new Fill({ color: '#FF6B00' }),
        stroke: new Stroke({ color: '#fff', width: 3 }),
      }),
    });

    const clearLayers = () => {
      vectorLayers.forEach((layer) => map.removeLayer(layer));
      vectorLayers = [];
      
      if (pointsLayer) {
        map.removeLayer(pointsLayer);
        pointsLayer = null;
      }
    };

    // Отрисовка точек на карте
    const loadPoints = () => {
      if (!props.points || props.points.length === 0) return;

      const features = props.points.map((point) => {
        const feature = new Feature({
          geometry: new Point(fromLonLat([point.lon, point.lat])),
        });
        feature.set('properties', point);
        feature.setStyle(defaultPointStyle);
        return feature;
      });

      const vectorSource = new VectorSource({ features });
      pointsLayer = new VectorLayer({
        source: vectorSource,
        zIndex: 4,
      });
      map.addLayer(pointsLayer);
    };

    // Построение маршрута по точкам
    const buildRouteForPoints = async (points, color) => {
      if (!points || points.length < 2) return;

      const coordinates = points.map((p) => `${p.lon},${p.lat}`).join(';');

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
          source: new VectorSource({ features: [lineFeature] }),
          zIndex: 2,
        });

        map.addLayer(layer);
        vectorLayers.push(layer);
      } catch (error) {
        console.error('OSRM error:', error);
      }
    };

    const loadAllRoutesFromDB = async () => {
      try {
        const response = await axios.get('/api/routes');
        const routes = response.data.data || [];

        allRoutesLegend.value = routes
          .filter((route) => route.map_color)
          .map((route) => ({
            id: route.id,
            title: route.title,
            mapColor: '#' + route.map_color.replace('#', ''),
          }));

        for (const route of routes) {
          const points = route.point || [];
          if (!points.length) continue;

          const color = (route.map_color || 'FFB800').replace('#', '');
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

        const color = (route.map_color || props.routeColor || 'FFB800').replace('#', '');
        
        if (points.length) {
          await buildRouteForPoints(points, color);
        }
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
        loadPoints();
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

    // Обработчик клика по карте
    const handleMapClick = (evt) => {
      if (!map) return;

      map.forEachFeatureAtPixel(evt.pixel, (feature) => {
        const properties = feature.get('properties');
        if (properties && properties.name) {
          if (selectedFeature) {
            selectedFeature.setStyle(defaultPointStyle);
          }
          feature.setStyle(selectedPointStyle);
          selectedFeature = feature;

          const geometry = feature.getGeometry();
          if (geometry instanceof Point) {
            const mapElement = document.getElementById('map');
            const coordinate = geometry.getCoordinates();
            const pixel = map.getPixelFromCoordinate(coordinate);
            const mapRect = mapElement.getBoundingClientRect();

            popupPosition.value = {
              x: mapRect.left + window.pageXOffset + pixel[0] - 140,
              y: mapRect.top + window.pageYOffset + pixel[1] - 190,
            };

            popupText.value = properties.name;
            popupAddress.value = properties.address;
            popupUrl.value = properties.url;
            currentPoint.value = properties;
            popupVisible.value = true;
          }
        }
      });
    };

    const hidePopup = () => {
      popupVisible.value = false;
      if (selectedFeature) {
        selectedFeature.setStyle(defaultPointStyle);
        selectedFeature = null;
      }
      currentPoint.value = null;
    };

    const navigateToPage = () => {
      const point = currentPoint.value;
      hidePopup();
      setTimeout(() => {
        emit('navigate', point);
      }, 300);
    };

    const openPopupByName = (name) => {
      if (!pointsLayer) return;
      const features = pointsLayer.getSource().getFeatures();
      const feature = features.find(f => f.get('properties').name === name);
      
      if (feature) {
        if (selectedFeature) {
          selectedFeature.setStyle(defaultPointStyle);
        }
        feature.setStyle(selectedPointStyle);
        selectedFeature = feature;

        const geometry = feature.getGeometry();
        if (geometry instanceof Point) {
          const mapElement = document.getElementById('map');
          const coordinate = geometry.getCoordinates();
          const pixel = map.getPixelFromCoordinate(coordinate);
          const mapRect = mapElement.getBoundingClientRect();

          popupPosition.value = {
            x: mapRect.left + window.pageXOffset + pixel[0] - 140,
            y: mapRect.top + window.pageYOffset + pixel[1] - 190,
          };

          const properties = feature.get('properties');
          popupText.value = properties.name;
          popupAddress.value = properties.address;
          popupUrl.value = properties.url;
          currentPoint.value = properties;
          popupVisible.value = true;
        }
      }
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

      map.on('singleclick', handleMapClick);
      loadMap();
    });

    watch(
      () => [props.routeId, props.points, props.routeColor],
      () => {
        if (map) {
          loadMap();
        }
      },
      { deep: true }
    );

    return {
      showAllRoutes,
      allRoutesLegend,
      legendVisible, // ← добавляем в return
      popupVisible,
      popupText,
      popupAddress,
      popupUrl,
      popupPosition,
      hidePopup,
      navigateToPage,
      openPopupByName,
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

/* КНОПКА ДЛЯ ОТКРЫТИЯ ЛЕГЕНДЫ */
.legend-toggle {
  position: absolute;
  top: 70px;
  right: 10px;
  background: white;
  padding: 6px 12px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  color: #333;
  z-index: 1000;
  transition: all 0.2s;
  border: 1px solid #e0e0e0;
}

.legend-toggle:hover {
  background: #f0f0f0;
}

/* ЛЕГЕНДА - на старом месте */
.legend {
  position: absolute;
  top: 110px;
  right: 10px;
  background-color: rgba(255, 255, 255, 0.95);
  padding: 10px;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  z-index: 1000;
  min-width: 180px;
  max-width: 250px;
  max-height: 350px;
  overflow-y: auto;
}

.legend-header h4 {
  margin: 0 0 10px 0;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  border-bottom: 1px solid #e0e0e0;
  padding-bottom: 5px;
}

.legend-items {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.legend-item {
  display: flex;
  align-items: flex-start;   /* ← выравнивание по верхнему краю */
  gap: 10px;
  width: 100%;
  margin-bottom: 8px;
}

.legend-line {
  width: 30px;
  height: 4px;
  display: inline-block;
  border-radius: 2px;
  flex-shrink: 0;
  margin-top: 5px;           /* ← выравнивание с текстом */
}

.legend-label {
  font-size: 12px;
  color: #333;
  font-weight: 500;
  word-break: break-word;
  white-space: normal;
  line-height: 1.3;
  flex: 1;
}

/* Остальные стили без изменений */
.custom-popup {
  position: fixed;
  background: white;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #ccc;
  box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.2);
  width: 280px;
  z-index: 1000;
}

.close-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  background: none;
  border: none;
  font-size: 16px;
  cursor: pointer;
}

.popup-title {
  font-size: 16px;
  font-weight: bold;
  margin-bottom: 8px;
}

.popup-subtitle {
  font-size: 12px;
  color: gray;
  margin-bottom: 4px;
}

.popup-content {
  font-size: 14px;
  margin-bottom: 12px;
}

.popup-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.popup-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  padding: 6px 10px;
  border: none;
  border-radius: 6px;
  background: #f3f3f3;
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}

.popup-btn:hover {
  background: #e0e0e0;
}
</style>