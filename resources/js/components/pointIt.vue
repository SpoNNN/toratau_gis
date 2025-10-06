<template>
    <div>
      <div id="map" class="map" tabindex="0"></div>
    </div>
  </template>
  
<script>
    import { onMounted } from 'vue';
    import Map from 'ol/Map';
    import OSM from 'ol/source/OSM';
    import TileLayer from 'ol/layer/Tile';
    import View from 'ol/View';
    import { fromLonLat } from 'ol/proj';
    import 'ol/ol.css';
    import { Point } from 'ol/geom'; // Для создания геометрии точки
    import { Feature } from 'ol'; // Для создания объекта (Feature)
    import { Vector as VectorLayer } from 'ol/layer'; // Для создания векторного слоя
    import { Vector as VectorSource } from 'ol/source'; // Для создания источника векторных объектов
    import { Style, Fill, Stroke, Circle } from 'ol/style'; // Для стилизации точек
  
    export default {
      name: 'MapComponent',
      setup() {
        let map = null;
  
        const zoomIn = () => {
          const view = map.getView();
          const zoom = view.getZoom();
          view.setZoom(zoom + 1);
        };
  
        const zoomOut = () => {
          const view = map.getView();
          const zoom = view.getZoom();
          view.setZoom(zoom - 1);
        };
  
        onMounted(() => {
          // Массив координат для точек
          const coordinates = [
            [55.936081, 54.720401],
            [55.940732, 54.724981],
            [55.942299, 54.729234],
            [55.947889, 54.726752],
            [55.960789, 54.729599],
            [55.983218, 54.726248],
            [56.007203, 54.716375],
            [55.989414, 54.741021],
          ];
  
          // Создание источника для векторного слоя
          const vectorSource = new VectorSource();
  
          // Создание стиля для точки
          const pointStyle = new Style({
            image: new Circle({
              radius: 7, // Размер точки
              fill: new Fill({
                color: 'rgba(0, 148, 255, 1)', // Цвет точки
              }),
              stroke: new Stroke({
                color: 'black', // Цвет обводки
                width: 1, // Толщина обводки
              }),
            }),
          });
  
          // Добавляем точки в источник
          coordinates.forEach(coord => {
            const point = new Point(fromLonLat(coord)); // Преобразуем координаты в формат, нужный для OpenLayers
            const pointFeature = new Feature({
              geometry: point,
            });
            pointFeature.setStyle(pointStyle); // Применяем стиль
            vectorSource.addFeature(pointFeature); // Добавляем точку в источник
          });
  
          // Создание векторного слоя с точками
          const vectorLayer = new VectorLayer({
            source: vectorSource,
          });
  
          // Инициализация карты
          map = new Map({
            target: 'map',
            layers: [
              new TileLayer({
                source: new OSM(),
              }),
              vectorLayer, // Добавляем векторный слой с точками
            ],
            view: new View({
              center: fromLonLat([55.9721, 54.7388]), // Центр карты (Уфа)
              zoom: 13, // Начальный зум
            }),
          });
        });
  
        return {
          zoomIn,
          zoomOut,
        };
      },
    };
</script>
  
<style>
    .map {
      width: 100%;
      height: 740px;
      border: 1px solid #ccc;
    }
    .controls {
      margin-top: 10px;
    }
</style>
  

