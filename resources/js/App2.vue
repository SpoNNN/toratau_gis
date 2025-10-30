<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from 'vue'
import axios from 'axios'
import { initializeApp } from '@firebase/app'
import { getAuth, onAuthStateChanged, signOut } from '@firebase/auth'

// Components
import Header2 from './components/Header2.vue'
import selectedObject from './components/selectedObject.vue'
import way from './components/way.vue'
import routeAll from './components/routeAll.vue'
import register from './views/register.vue'
import signIn from './views/signIn.vue'
import RouteDetails from './components/RouteDetails.vue'
import favoritesPage from './components/favoritesPage.vue'
import GenericPopup from './components/Socrat/GenericPopup.vue'

// Types
interface Point {
  id: number
  lon: number
  lat: number
  name: string
  address: string
  url: string
  pointName: string
  description: string
  images: string[]
}

interface RouteInfo {
  id: number
  key: string
  label: string
  value: string
}

interface Route {
  id: number
  title: string
  mapColor: string
  description: string
  slug: string
  infoItems: Record<string, RouteInfo>
  points: Point[]
  duration?: number
  distance?: number
  participants?: number
  audience?: string
}

interface RoutesData {
  [key: string]: Route
}

// Firebase config
const firebaseConfig = {
  apiKey: 'AIzaSyDnXXJ1R-lkoheA8LEJuHLzy2kjUvcC4-w',
  authDomain: 'myproject-35bc3.firebaseapp.com',
  projectId: 'myproject-35bc3',
  storageBucket: 'myproject-35bc3.firebasestorage.app',
  messagingSenderId: '1064017138140',
  appId: '1:1064017138140:web:b294ccd4f2b9c9762abf19'
}

// App state
const API_BASE_URL = '/api'
const app = initializeApp(firebaseConfig)
const auth = getAuth(app)
const routesData = ref<RoutesData>({})
const currentPage = ref<string>('way')
const currentRoute = ref<string>('routeAll')
const isLoggedIn = ref<boolean>(false)
const isLoading = ref<boolean>(true)
const error = ref<string | null>(null)
const currentPointData = ref<Point | null>(null)
const isPathObjectOpened = ref<boolean>(false)
const genericPopupRef = ref<any>(null)
const wayRef = ref<any>(null)
const selectedRouteId = ref<number | null>(null)

// Methods
const fetchRoutes = async () => {
  try {
    isLoading.value = true
    const response = await axios.get(`${API_BASE_URL}/routes`)
    
    const transformedData: RoutesData = {}
    response.data.data.forEach((route: any) => {
      const routeKey = `route${route.slug}`
      
 
      const infoItems = Array.isArray(route.route_infos) 
        ? route.route_infos.reduce((acc: any, info: any) => {
            acc[info.key] = {
              id: info.id,
              key: info.key,
              label: info.label,
              value: info.value
            }
            return acc
          }, {}) 
        : {}

      // Обработка points
      let points: Point[] = []
      if (Array.isArray(route.points)) {
        points = route.points.map((point: any) => ({
          id: point.id,
          lon: parseFloat(point.lon),
          lat: parseFloat(point.lat),
          name: point.name,
          address: point.address,
          url: point.url,
          pointName: point.point_name || '',
          description: point.description || '',
          images: point.images ? (typeof point.images === 'string' ? JSON.parse(point.images) : point.images) : []
        }))
      } else if (route.points && typeof route.points === 'object') {
        points = [{
          id: route.points.id,
          lon: parseFloat(route.points.lon),
          lat: parseFloat(route.points.lat),
          name: route.points.name,
          address: route.points.address,
          url: route.points.url,
          pointName: route.points.point_name || '',
          description: route.points.description || '',
          images: route.points.images ? (typeof route.points.images === 'string' ? JSON.parse(route.points.images) : route.points.images) : []
        }]
      }
      
      transformedData[routeKey] = {
        id: route.id,
        title: route.title,
        mapColor: route.map_color,
        description: route.description,
        slug: route.slug,
        infoItems,
        points
      }
    })
    
    routesData.value = transformedData
    error.value = null
  } catch (err) {
    console.error('Ошибка загрузки маршрутов:', err)
    error.value = 'Не удалось загрузить данные маршрутов'
  } finally {
    isLoading.value = false
  }
}

const handleSelectRoute = (routeId: number) => {
  console.log('=== handleSelectRoute вызван ===')
  console.log('Получен routeId:', routeId, 'тип:', typeof routeId)
  selectedRouteId.value = routeId
  console.log('selectedRouteId.value установлен:', selectedRouteId.value)
  currentPage.value = 'RouteDetails.vue'
  console.log('currentPage установлен:', currentPage.value)
}

const handleCreateObject = (object: any) => {
  console.log('Создан объект:', object)
  
}

const updateClick = (data: Point, route: Route) => {
  const routeKey = `route${route.slug}`
  currentRoute.value = routeKey

  nextTick(() => {
    if (genericPopupRef.value) {
      genericPopupRef.value.openPopupByName(data.name)
    }
  })
}

const handleNavigate = (page: string) => {
  if (page === 'way') {
    currentRoute.value = 'routeAll'
  }
  currentPage.value = page
}

const handleNavigateBack = () => {
  currentPage.value = 'RouteDetails.vue'
  isPathObjectOpened.value = false
}

const handleSignOut = () => {
  signOut(auth).then(() => {
    isLoggedIn.value = false
  })
}

// Computed
const currentRouteData = computed<Route>(() => {
  if (currentRoute.value in routesData.value) {
    return routesData.value[currentRoute.value]
  }
  
  return {
    id: 0,
    title: '',
    description: '',
    points: [],
    infoItems: {},
    mapColor: '',
    slug: ''
  }
})

// Lifecycle hooks
onMounted(() => {
  fetchRoutes()
  
  onAuthStateChanged(auth, (user) => {
    isLoggedIn.value = !!user
  })
})
</script>

<template>
  <Header2
    :isLoggedIn="isLoggedIn"
    @navigateToWay="
      () => {
        currentPage = 'way'
        currentRoute = 'routeAll'
        isPathObjectOpened = false
      }
    "
    @navigateToFavorites="currentPage = 'favorites'"
    @navigateToRegister="currentPage = 'register'"
    @navigateToLogin="currentPage = 'signIn'"
    @logout="handleSignOut"
  />

  <div v-if="isLoading" class="loading-overlay">
    <div class="loading-spinner"></div>
    <p>Загрузка данных...</p>
  </div>

  <div v-else-if="error" class="error-message">
    {{ error }}
    <button @click="fetchRoutes">Повторить попытку</button>
  </div>

  <div v-else class="app-container">
    <div class="sidebar">
      <way
        v-if="currentPage === 'way'"
        ref="wayRef"
        :routes="Object.values(routesData)"
        @navigate="handleNavigate"
        @selectRoute="handleSelectRoute"
      />

   

      <RouteDetails 
        v-if="currentPage === 'RouteDetails.vue' && selectedRouteId !== null" 
        :route-id="selectedRouteId"  
        @select-object="(point: Point) => updateClick(point, currentRouteData)"
        @navigate="(point: Point) => {
          isPathObjectOpened = true
          currentPointData = point
          currentPage = 'Details Opened'
        }"
        @create-object="handleCreateObject"
      />

      <register v-if="currentPage === 'register'" />
      <signIn v-if="currentPage === 'signIn'" />

      <favoritesPage
        v-if="currentPage === 'favorites'"
        :routes="Object.values(routesData)"
        @navigate="handleNavigate"
        @selectRoute="handleSelectRoute"
      />

      <selectedObject
        v-if="isPathObjectOpened && currentPointData"
        :point-data="currentPointData"
        @navigateBack="handleNavigateBack"
      />
    </div>

    <div class="map-container">
      <routeAll v-if="currentRoute === 'routeAll'" />

      <template v-else>
        <GenericPopup
          v-for="(route, id) in routesData"
          :key="id"
          v-show="currentRoute === id"
          ref="genericPopupRef"
          :points="route.points"
          :route-geo-json-url="`./${route.slug}.geojson`"
          @navigate="
            (point) => {
              isPathObjectOpened = true
              currentPointData = point
              currentPage = 'Details Opened'
            }
          "
        />
      </template>
    </div>
  </div>
</template>

<style scoped>
.app-container {
  display: flex;
  min-height: calc(100vh - 60px);
}

.sidebar {
  width: 500px;
  background-color: #3730a3;
  padding: 20px;
  overflow-y: auto;
}

.map-container {
  flex-grow: 1;
  background-color: white;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.loading-spinner {
  border: 5px solid #f3f3f3;
  border-top: 5px solid #3498db;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  padding: 20px;
  background: #ffebee;
  color: #c62828;
  text-align: center;
  margin: 20px;
  border-radius: 4px;
}

.error-message button {
  margin-top: 10px;
  padding: 8px 16px;
  background: #c62828;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
</style>