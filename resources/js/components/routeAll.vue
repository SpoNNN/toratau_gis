<template>
  <div>
    <div id="map" class="map" tabindex="0"></div>
    <div class="legend">
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(0, 148, 255, 1)"></span>
        <span class="legend-label">Маршрут Пифагор</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(243, 211, 46, 1)"></span>
        <span class="legend-label">Маршрут Авиценна</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(150, 105, 200, 1)"></span>
        <span class="legend-label">Маршрут Вернадский</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(0, 168, 221, 1)"></span>
        <span class="legend-label">Маршрут Менделеев</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(255, 184, 0, 1)"></span>
        <span class="legend-label">Маршрут Сократ</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(255, 107, 0, 1)"></span>
        <span class="legend-label">Маршрут Циолковский</span>
      </div>
      <div class="legend-item">
        <span class="legend-line" style="background-color: rgba(52, 215, 215, 1)"></span>
        <span class="legend-label">Маршрут Шень Ко</span>
      </div>
    </div>
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
  import { GeoJSON } from 'ol/format'; // Для работы с GeoJSON
  import { Vector as VectorLayer } from 'ol/layer'; // Для создания векторного слоя
  import { Vector as VectorSource } from 'ol/source'; // Для создания источника векторных объектов
  import { Style, Stroke, Fill } from 'ol/style'; // Для стилизации

  export default {
    name: 'MapComponent',
    setup() {
      let map = null;
      let highlightedFeature = null; // Переменная для сохранения выделенного маршрута
      let vectorLayers = []; // Сюда будем добавлять все слои с маршрутами
      let highlightedLayer = null; // Новый слой для выделенного маршрута

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
        // Инициализация карты
        map = new Map({
          target: 'map',
          layers: [
            new TileLayer({
              source: new OSM(),
            }),
          ],
          view: new View({
            center: fromLonLat([55.9721 + 0.03, 54.7388]), // Центр карты (Уфа)
            zoom: 13, // Начальный зум
          }),
        });

        // Путь к файлам GeoJSON с маршрутами
        const geoJsonFiles = [
          { file: 'ors__v2_directions_{profile}_geojson_post_1733420735317.geojson', style: 'blue' },  // Первый маршрут
          { file: 'avicena.geojson', style: 'orange' },  // Второй маршрут
          { file: 'vernadsky.geojson', style: 'green' },  // Третий маршрут
          { file: 'mendeleev.geojson', style: 'purple' },   // Четвертый маршрут
          { file: 'sokrat.geojson', style: 'red' },      // Пятый маршрут
          { file: 'tsiolkovsky.geojson', style: 'yellow' },   // Шестой маршрут
          { file: 'shen_ko.geojson', style: 'pink' },     // Седьмой маршрут
        ];

        geoJsonFiles.forEach((route) => {
          // Загрузка GeoJSON
          fetch(route.file)
            .then((response) => response.json())
            .then((data) => {
              // Используем GeoJSON формат OpenLayers для создания векторных объектов
              const geojsonFormat = new GeoJSON();
              const features = geojsonFormat.readFeatures(data, {
                featureProjection: 'EPSG:3857', // Убедитесь, что проекция соответствует OpenLayers
              });

              // Создание стиля для маршрута в зависимости от типа маршрута
              let routeStyle;
              if (route.style === 'blue') {
                routeStyle = new Style({
                  stroke: new Stroke({
                    color: 'rgba(0, 148, 255, 1)', // Цвет линии маршрута для первого маршрута
                    width: 4, // Толщина линии
                  }),
                  fill: new Fill({
                    color: 'rgba(255, 255, 255, 0.4)',
                  }),
                });
              } else if (route.style === 'orange') {
                routeStyle = new Style({
                  stroke: new Stroke({
                    color: 'rgba(243,211,46,255)', // Цвет линии маршрута для второго маршрута
                    width: 4, // Толщина линии
                  }),
                  fill: new Fill({
                    color: 'rgba(255, 255, 255, 0.4)',
                  }),
                });
              } else if (route.style === 'green') {
                routeStyle = new Style({
                  stroke: new Stroke({
                    color: 'rgba(150,105,200,255)', // Цвет линии маршрута для третьего маршрута
                    width: 4, // Толщина линии
                  }),
                  fill: new Fill({
                    color: 'rgba(255, 255, 255, 0.4)',
                  }),
                });
              } else if (route.style === 'purple') {
                routeStyle = new Style({
                  stroke: new Stroke({
                    color: 'rgba(0,168,221,255)', // Цвет линии маршрута для четвертого маршрута
                    width: 4, // Толщина линии
                  }),
                  fill: new Fill({
                    color: 'rgba(255, 255, 255, 0.4)',
                  }),
                });
              } else if (route.style === 'red') {
                routeStyle = new Style({
                  stroke: new Stroke({
                    color: '#37b800', // Цвет линии маршрута для пятого маршрута
                    width: 4, // Толщина линии
                  }),
                  fill: new Fill({
                    color: 'rgba(255, 255, 255, 0.4)',
                  }),
                });
              } else if (route.style === 'yellow') {
                routeStyle = new Style({
                  stroke: new Stroke({
                    color: 'rgba(255,107,0,255)', // Цвет линии маршрута для шестого маршрута
                    width: 4, // Толщина линии
                  }),
                  fill: new Fill({
                    color: 'rgba(255, 255, 255, 0.4)',
                  }),
                });
              } else if (route.style === 'pink') {
                routeStyle = new Style({
                  stroke: new Stroke({
                    color: 'rgba(52,215,215,255)', // Цвет линии маршрута для седьмого маршрута
                    width: 4, // Толщина линии
                  }),
                  fill: new Fill({
                    color: 'rgba(255, 255, 255, 0.4)',
                  }),
                });
              }
              
              // Применение стиля ко всем объектам маршрута
              features.forEach((feature) => {
                feature.setStyle(routeStyle); // Применяем стиль к каждому объекту маршрута
              });

              // Создание источника для векторного слоя
              const vectorSource = new VectorSource({
                features: features,
              });

              // Создание векторного слоя
              const vectorLayer = new VectorLayer({
                source: vectorSource,
              });

              // Добавление векторного слоя на карту
              map.addLayer(vectorLayer);
              vectorLayers.push(vectorLayer); // Добавляем слой в список слоев

              // Слушатель событий клика
              map.on('singleclick', (event) => {
                const feature = map.forEachFeatureAtPixel(event.pixel, (feat) => feat);

                if (feature) {
                  // Если новый объект, выделяем его
                  if (highlightedFeature !== feature) {
                    // Если был выделен предыдущий маршрут, сбрасываем стиль
                    if (highlightedFeature) {
                      highlightedFeature.setStyle(highlightedFeature.originalStyle);
                    }

                    // Запоминаем новый объект
                    highlightedFeature = feature;
                    highlightedFeature.originalStyle = feature.getStyle(); // Сохраняем оригинальный стиль

                    // Создаем новый слой для выделенного маршрута и добавляем его поверх
                    if (!highlightedLayer) {
                      highlightedLayer = new VectorLayer({
                        source: new VectorSource({
                          features: [feature],
                        }),
                        zIndex: 200, // Этот слой будет поверх всех других
                      });
                      map.addLayer(highlightedLayer);
                    }

                    // Подсвечиваем маршрут
                    feature.setStyle(new Style({
                      stroke: new Stroke({
                        color: feature.getStyle().getStroke().getColor(), // Оставляем тот же цвет
                        width: 7,
                        lineCap: 'round',
                        lineJoin: 'round',
                      }),
                    }));
                  }
                } else {
                  // Если кликнули в пустое место, убираем подсветку
                  if (highlightedFeature) {
                    highlightedFeature.setStyle(highlightedFeature.originalStyle);
                    highlightedFeature = null;

                    // Убираем слой для выделенного маршрута
                    if (highlightedLayer) {
                      map.removeLayer(highlightedLayer);
                      highlightedLayer = null;
                    }
                  }
                }
              });
            })
            .catch((error) => console.error('Ошибка при загрузке GeoJSON:', error));
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
    position: relative; /* Чтобы легенда могла быть абсолютно спозиционирована */
  }

  .controls {
    margin-top: 10px;
  }

  .legend {
    position: absolute;
    top: 60px;
    right: 3px;
    background-color: rgba(255, 255, 255, 0.8);
    padding: 10px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    z-index: 1000; /* Убедитесь, что легенда находится поверх карты */
  }

  .legend-item {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
  }

  .legend-line {
    width: 40px; /* Длина линии */
    height: 4px; /* Толщина линии */
    display: inline-block;
    margin-right: 10px;
  }

  .legend-label {
    font-size: 14px;
  }
</style>