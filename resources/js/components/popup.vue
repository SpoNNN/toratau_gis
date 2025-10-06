<template>
    <div>
      <div id="map" class="map" tabindex="0"></div>
  
      <!-- Popup -->
      <div v-if="popupVisible" :style="{ top: popupPosition.y + 'px', left: popupPosition.x + 'px' }" class="custom-popup">
        <button class="close-btn" @click="hidePopup">✖</button>
        <h3 class="popup-title">IT-UFA</h3>
        <p class="popup-subtitle">Наименование</p>
        <p class="popup-content">{{ popupText }} </p>
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
  import { onMounted, ref } from 'vue';
  import Map from 'ol/Map';
  import View from 'ol/View';
  import { fromLonLat, toLonLat } from 'ol/proj';
  import TileLayer from 'ol/layer/Tile';
  import OSM from 'ol/source/OSM';
  import { Vector as VectorLayer } from 'ol/layer';
  import { Vector as VectorSource } from 'ol/source';
  import { GeoJSON } from 'ol/format';
  import { Point } from 'ol/geom';
  import { Feature } from 'ol';
  import { Style, Stroke, Fill, Circle } from 'ol/style';


  export default {
    emits: ['navigateToUfarob', 'navigateToPage3', 'navigateToNots', 'navigateToPoligon', 
            'navigateToRostMuz', 'navigateToRosstel', 'navigateToTechPark', 'navigateToUust'], // Объявляем событие
    setup(_, {emit}) {
      const popupVisible = ref(false);
      const popupText = ref('Точка на карте');
      const popupPosition = ref({ x: 0, y: 0 });
      const popupOffset = ref({ x: 0, y: 0 });
      const popupAddress = ref(""); // Хранит адрес для текущей точки
      const popupUrl = ref("");
      const selectedFeature = ref(null); // Храним выделенную точку

      const defaultPointStyle = new Style({
        image: new Circle({
        radius: 7,
        fill: new Fill({ color: 'rgba(0, 148, 255, 1)' }),
        stroke: new Stroke({ color: 'black', width: 1 }),
        }),
      });

      const selectedPointStyle = new Style({
      image: new Circle({
      radius: 9,
      fill: new Fill({ color: 'red' }),
      stroke: new Stroke({ color: 'black', width: 2 }),
        }),
      });

      let map = null;
  
      onMounted(() => {
        const map = new Map({
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
  
        // === Слой маршрута ===
        const routeSource = new VectorSource();
        fetch('./ors__v2_directions_{profile}_geojson_post_1733420735317.geojson')
          .then((response) => response.json())
          .then((data) => {
            const geojsonFormat = new GeoJSON();
            const features = geojsonFormat.readFeatures(data, {
              featureProjection: 'EPSG:3857',
            });
  
            const routeStyle = new Style({
              stroke: new Stroke({
                color: 'rgba(0, 148, 255, 1)', // Синий цвет маршрута
                width: 4,
              }),
            });
  
            features.forEach((feature) => feature.setStyle(routeStyle));
            routeSource.addFeatures(features);
          });
  
        const routeLayer = new VectorLayer({
          source: routeSource,
          zIndex: 1,
        });
  
        map.addLayer(routeLayer);
  
        // === Слой точек ===
        const pointSource = new VectorSource();
        const coordinates = [
          [55.936081, 54.720401, "Кампус Евразийского НОЦ РБ", "ул. Заки Валиди, 32/2", "https://campus.nocrb.ru/"],
          [55.940732, 54.724981, "УУНиТ", "ул. Карла Маркса, 12", "https://uust.ru/"],
          [55.942299, 54.729234, "ООО НПП «Полигон»", "ул. Карла Маркса, 37/1", "https://plgn.ru/"],
          [55.947889, 54.726752, "Музей связи компании «Ростелеком»", "ул. Ленина, 30", "https://rostelecom-rb.ru/muzey-svyazi/"],
          [55.960789, 54.729599, "Детский технопарк «Кванториум Башкортостана»", "ул. Кирова, 43", "https://kvantoriumrb.02edu.ru/"],
          [55.983218, 54.726248, "Проектный офис цифровой трансформации «Ростелеком»", "ул. Кирова, 105", "https://rostelecom-rb.ru/"],
          [56.007203, 54.716375, "Центр роботизации бизнеса «Ufarobotics»", "ул. Менделеева, 134/7, 4 этаж", "https://ufarobotics.ru/"],
          [55.989414, 54.741021, "АО «Уфанет»", "пр. Октября, 4/3", "https://www.ufanet.ru/"],
        ];
  
        const pointStyle = new Style({
          image: new Circle({
            radius: 7,
            fill: new Fill({ color: 'rgba(0, 148, 255, 1)' }),
            stroke: new Stroke({ color: 'black', width: 1 }),
          }),
        });
  
        coordinates.forEach(([lon, lat, name, address, url]) => {
          const pointFeature = new Feature({
            geometry: new Point(fromLonLat([lon, lat])),
          });
          pointFeature.set('name', name);
          pointFeature.set('address', address); // Добавляем адрес
          pointFeature.set('url', url); // Добавляем URL для каждой точки
          pointFeature.setStyle(pointStyle);
          pointSource.addFeature(pointFeature);
        });
  
        const pointsLayer = new VectorLayer({
          source: pointSource,
          zIndex: 2,
        });
  
        map.addLayer(pointsLayer);
  
        // === Обработка кликов ===
        map.on('singleclick', (evt) => {
          let found = false;
          map.forEachFeatureAtPixel(evt.pixel, (feature) => {
            const name = feature.get('name');
            const address = feature.get('address'); // Получаем адрес точ
            const url = feature.get('url');

            if (name) {
      // Сброс стиля предыдущей выбранной точки
      if (selectedFeature.value) {
        selectedFeature.value.setStyle(defaultPointStyle);
      }

      // Установить стиль для новой выбранной
      feature.setStyle(selectedPointStyle);
      selectedFeature.value = feature;

      // Получаем координаты точки в пикселях относительно карты
      const coordinate = feature.getGeometry().getCoordinates();
      const pixel = map.getPixelFromCoordinate(coordinate);

      // Получаем элемент карты и его абсолютные координаты на странице
      const mapElement = document.getElementById('map');
      const mapRect = mapElement.getBoundingClientRect();
      const mapAbsoluteLeft = mapRect.left + window.pageXOffset;
      const mapAbsoluteTop = mapRect.top + window.pageYOffset;

      // Размеры popup (из CSS)
      const popupWidth = 280;
      const popupHeight = 200;

      // Рассчитываем позицию относительно карты (уже с учётом скролла)
      let popupX = pixel[0] - popupWidth / 2; // Центрирование по X
      let popupY = pixel[1] - popupHeight + 10; // Смещение по Y

      // Список объектов с особым смещением
      const specialObjects = [
        "Проектный офис цифровой трансформации «Ростелеком»",
        "Детский технопарк «Кванториум Башкортостана»",
        "Центр роботизации бизнеса «Ufarobotics»"
        ];

      // Дополнительное смещение для specialObjects
      if (specialObjects.includes(feature.get('name'))) {
        popupY -= 16; // Поднимаем ещё выше
      }

    // Проверка границ карты
    if (popupX < 0) popupX = 10;
    if (popupX + popupWidth > mapElement.offsetWidth) {
      popupX = mapElement.offsetWidth - popupWidth - 10;
    }
    if (popupY < 0) popupY = 10;

    // Итоговые координаты (относительно страницы)
    popupPosition.value = {
      x: mapAbsoluteLeft + popupX,
      y: mapAbsoluteTop + popupY
    };

      popupText.value = name;
      popupAddress.value = address;
      popupVisible.value = true;
      popupUrl.value = url;

      found = true;
    }

          });
        });
      });
  
      const hidePopup = () => {
      const popup = document.querySelector('.custom-popup');
      if (popup) {
        popup.classList.add('hidden');
      // Сброс стиля выделенной точки
      if (selectedFeature.value) {
        selectedFeature.value.setStyle(defaultPointStyle);
        selectedFeature.value = null;}

        setTimeout(() => {
        popupVisible.value = false;
      }, 300); // Ждем окончания анимации (300 мс)
      }};

        // Функция для перехода на page3
      const navigateToPage = () => {
        if (popupText.value === "Центр роботизации бизнеса «Ufarobotics»") {
          hidePopup(); // Плавно скрываем popup перед переходом
          setTimeout(() => {
            emit('navigateToUfarob'); // Эмитируем событие для перехода на ufarob.vue
          }, 300); // Ждем, чтобы popup скрылся
        } else if (popupText.value === "АО «Уфанет»") {
          hidePopup(); // Плавно скрываем popup перед переходом
          setTimeout(() => {
            emit('navigateToPage3'); // Эмитируем событие для перехода на ufanet.vue
          }, 300); // Ждем, чтобы popup скрылся
          }
          else if (popupText.value === "Кампус Евразийского НОЦ РБ") {
          hidePopup(); // Плавно скрываем popup перед переходом
          setTimeout(() => {
            emit('navigateToNots'); // Эмитируем событие для перехода на ufanet.vue
          }, 300); // Ждем, чтобы popup скрылся
          }
          else if (popupText.value === "ООО НПП «Полигон»") {
          hidePopup(); // Плавно скрываем popup перед переходом
          setTimeout(() => {
            emit('navigateToPoligon'); // Эмитируем событие для перехода на ufanet.vue
          }, 300); // Ждем, чтобы popup скрылся
          }
          else if (popupText.value === "Музей связи компании «Ростелеком»") {
          hidePopup(); // Плавно скрываем popup перед переходом
          setTimeout(() => {
            emit('navigateToRostMuz'); // Эмитируем событие для перехода на ufanet.vue
          }, 300); // Ждем, чтобы popup скрылся
          }
          else if (popupText.value === "Проектный офис цифровой трансформации «Ростелеком»") {
          hidePopup(); // Плавно скрываем popup перед переходом
          setTimeout(() => {
            emit('navigateToRosstel'); // Эмитируем событие для перехода на ufanet.vue
          }, 300); // Ждем, чтобы popup скрылся
          }
          else if (popupText.value === "Детский технопарк «Кванториум Башкортостана»") {
          hidePopup(); // Плавно скрываем popup перед переходом
          setTimeout(() => {
            emit('navigateToTechPark'); // Эмитируем событие для перехода на ufanet.vue
          }, 300); // Ждем, чтобы popup скрылся
          }
          else if (popupText.value === "УУНиТ") {
          hidePopup(); // Плавно скрываем popup перед переходом
          setTimeout(() => {
            emit('navigateToUust'); // Эмитируем событие для перехода на ufanet.vue
          }, 300); // Ждем, чтобы popup скрылся
          }
          };
  
      return { popupVisible, popupText, popupPosition, popupAddress, hidePopup, navigateToPage, popupUrl };
    },
  };
</script>
  
<style>
  .map {
    width: 100%;
    height: 740px;
    border: 1px solid #ccc;
  }
  
  /* Стили для popup */
  .custom-popup {
    position: absolute;
    background: white;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #ccc;
    box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.2);
    width: 280px;
    font-family: Arial, sans-serif;
    opacity: 1;
    transform: scale(1);
    transition: opacity 0.3s ease, transform 0.3s ease;
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
    
  }
  
  .popup-subtitle {
    font-size: 12px;
    color: gray;
    margin-bottom: 2px; /* Уменьшает расстояние до popupText но окно чуть выше от точки*/
     
  }
  
  .popup-content {
    font-size: 14px;
    
  }
  
  .popup-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
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
  }

  .custom-popup.hidden {
    opacity: 0;
    transform: scale(0.9);
  }
  
  .popup-btn:hover {
    background: #e0e0e0;
  }
</style>
  