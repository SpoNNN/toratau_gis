<template>
  <div class="profile-page">
    <Header2
      :isLoggedIn="isLoggedIn"
      @navigateToWay="$router.push('/')"
      @navigateToFavorites="$router.push('/favorites')"
      @navigateToRegister="$router.push('/register')"
      @navigateToLogin="$router.push('/signin')"
      @logout="handleSignOut"
    />
    
    <div class="profile-container">
      <div v-if="isLoading" class="loading">Загрузка...</div>
      
      <div v-else class="profile-content">
   

      
        <div class="profile-main">
          <div class="tabs">
            <button 
              class="tab"
              :class="{ active: activeTab === 'favorites' }"
              @click="activeTab = 'favorites'"
            >
              Избранные<br>маршруты
            </button>
            <button 
              class="tab"
              :class="{ active: activeTab === 'saved' }"
              @click="activeTab = 'saved'"
            >
              Забронированные<br>маршруты
            </button>
            <button 
              class="tab"
              :class="{ active: activeTab === 'reviews' }"
              @click="activeTab = 'reviews'"
            >
              Отзывы
            </button>
            <button 
              class="tab"
              :class="{ active: activeTab === 'history' }"
              @click="activeTab = 'history'"
            >
              История посещений
            </button>
          </div>

    
          <div class="routes-list">
            <div 
              v-for="route in filteredRoutes" 
              :key="route.id"
              class="route-card"
              :style="{ backgroundColor: route.color }"
              @click="navigateToRoute(route.id)"
            >
              <div class="route-info">
                <h3>{{ route.title }}</h3>
           
              </div>
              <button 
                class="favorite-btn"
                :class="{ active: route.isFavorite }"
                @click.stop="toggleFavorite(route.id)"
              >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
              </button>
            </div>

            <div v-if="filteredRoutes.length === 0" class="empty-state">
              <p v-if="activeTab === 'favorites'">У вас пока нет избранных маршрутов</p>
              <p v-else>Пока нет маршрутов в этой категории</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Header2 from '../components/Header2.vue'

const router = useRouter()
const isLoggedIn = ref(true) 
const isLoading = ref(false)
const user = ref<any>(null)
const activeTab = ref('favorites')

interface Favorite {
  id: number
  user_id: number
  route_id: number
  created_at: string
  updated_at: string
  route?: {
    id: number
    title: string
    mapColor: string
    description: string
    slug: string
  }
}

interface Route {
  id: number
  title: string
  subtitle: string
  color: string
  isFavorite: boolean
  isBooked: boolean
  hasReview: boolean
  visited: boolean
  slug?: string
}

const favoriteRoutes = ref<Route[]>([])
const savedRoutes = ref<Route[]>([])
const reviewedRoutes = ref<Route[]>([])
const historyRoutes = ref<Route[]>([])

const filteredRoutes = computed(() => {
  console.log('filteredRoutes computed:')
  console.log('activeTab:', activeTab.value)
  console.log('favoriteRoutes:', favoriteRoutes.value)
  
  switch (activeTab.value) {
    case 'favorites':
      return favoriteRoutes.value
    case 'saved':
      return savedRoutes.value
    case 'reviews':
      return reviewedRoutes.value
    case 'history':
      return historyRoutes.value
    default:
      return []
  }
})


const fetchProfileData = async () => {
  try {
    isLoading.value = true
    console.log('Начинаем загрузку профиля...')
    
   
    const userResponse = await axios.get('/api/user')
    user.value = userResponse.data
    console.log('Пользователь загружен:', user.value)
    
   
    await fetchFavorites()
    
  } catch (error) {
    console.error('Ошибка загрузки данных профиля:', error)
    
    
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      console.log('Ошибка 401, перенаправляем на главную')
      router.push('/')
    }
  } finally {
    isLoading.value = false
  }
}


const fetchFavorites = async () => {
  try {
    console.log('Запрашиваем избранные...')
    const response = await axios.get('/api/favorites')
    console.log('Ответ API /favorites:', response.data)
    

    if (response.data && response.data.data) {
      console.log('Найдено избранных:', response.data.data.length)
      
      favoriteRoutes.value = response.data.data.map((favorite: Favorite) => {
        console.log('Обрабатываем favorite:', favorite)
        
        const route = favorite.route
        console.log('Данные маршрута:', route)
        
        
        const colorMap: Record<string, string> = {
          '1': '#F59E0B', // оранжевый
          '2': '#3B82F6', // синий
          '3': '#F97316', // оранжевый-красный
          '4': '#10B981', // зеленый
          '5': '#8B5CF6', // фиолетовый
          'default': '#F59E0B'
        }
        
        const color = route?.mapColor 
          ? colorMap[route.mapColor] || colorMap.default 
          : colorMap.default
        
        const result = {
          id: favorite.route_id || route?.id,
          title: route?.title || 'Маршрут без названия',
          subtitle: route?.description ? route.description.substring(0, 100) + '...' : '',
          color: color,
          slug: route?.slug,
          isFavorite: true,
          isBooked: false,
          hasReview: false,
          visited: false
        }
        
        console.log('Создан route для отображения:', result)
        return result
      })
      
      console.log('favoriteRoutes после обработки:', favoriteRoutes.value)
    } else {
      console.warn('Некорректный формат ответа от API')
      favoriteRoutes.value = []
    }
    
  } catch (error) {
    console.error('Ошибка загрузки избранных:', error)
    favoriteRoutes.value = []
  }
}


const toggleFavorite = async (routeId: number) => {
  try {
    console.log('Переключение избранного для routeId:', routeId)
    const route = favoriteRoutes.value.find(r => r.id === routeId)
    if (!route) {
      console.log('Маршрут не найден в favoriteRoutes')
      return
    }
    
    if (route.isFavorite) {
     
      console.log('Удаляем из избранного...')
      await axios.delete(`api/favorites/${routeId}`)
      
    
      favoriteRoutes.value = favoriteRoutes.value.filter(r => r.id !== routeId)
      console.log('Удалено, новый список:', favoriteRoutes.value)
    } else {
    
      console.log('Добавляем в избранное...')
      await axios.post('/favorites', {
        route_id: routeId
      })
      
      route.isFavorite = true
    }
    
  } catch (error) {
    console.error('Ошибка при изменении избранного:', error)
  }
}


const navigateToRoute = (routeId: number) => {
  console.log('Переход к маршруту:', routeId)
  const route = favoriteRoutes.value.find(r => r.id === routeId)
  
  if (route?.slug) {
    console.log('Переходим на маршрут:', route.slug)
    router.push(`/route/${route.slug}`)
  }
}

const handleSignOut = async () => {
  try {
    await axios.post('/logout')
  } catch (error) {
    console.error('Logout error:', error)
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    delete axios.defaults.headers.common['Authorization']
    router.push('/')
  }
}

onMounted(async () => {
  console.log('Profile.vue mounted')
  
  const token = localStorage.getItem('auth_token')
  console.log('Токен из localStorage:', token ? 'есть' : 'нет')
  
  if (!token) {
    console.log('Нет токена, перенаправляем на главную')
    router.push('/')
    return
  }

  
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  console.log('Установлен заголовок Authorization')

  await fetchProfileData()
})
</script>

<style scoped>
.profile-page {
  min-height: 100vh;
  background: #E5E7EB;
}

.profile-container {
  padding: 20px;
}

.loading {
  text-align: center;
  padding: 40px;
  font-size: 18px;
  color: #666;
}

.profile-content {
  display: flex;
  gap: 30px;
  max-width: 1400px;
  margin: 0 auto;
}

/* Левая панель */
.profile-sidebar {
  width: 250px;
  flex-shrink: 0;
}

.avatar-card {
  background: white;
  border: 3px solid #4F46E5;
  border-radius: 16px;
  padding: 30px 20px;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.avatar-circle {
  width: 120px;
  height: 120px;
  background: white;
  border: 3px solid #4F46E5;
  border-radius: 50%;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.avatar-smile {
  width: 60px;
  height: 60px;
}

.user-name {
  font-size: 18px;
  font-weight: 600;
  color: #1F2937;
  margin: 0 0 5px 0;
}

.user-email {
  font-size: 14px;
  color: #6B7280;
  margin: 0;
}

/* Правая панель */
.profile-main {
  flex: 1;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Табы */
.tabs {
  display: flex;
  border-bottom: 2px solid #E5E7EB;
  background: #F9FAFB;
}

.tab {
  flex: 1;
  padding: 16px 12px;
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #6B7280;
  transition: all 0.3s;
  text-align: center;
  line-height: 1.4;
}

.tab:hover {
  background: #F3F4F6;
  color: #374151;
}

.tab.active {
  background: white;
  border-bottom-color: #4F46E5;
  color: #1F2937;
  font-weight: 600;
}

/* Список маршрутов */
.routes-list {
  padding: 20px;
  max-height: calc(100vh - 250px);
  overflow-y: auto;
}

.route-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-radius: 12px;
  margin-bottom: 16px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  cursor: pointer;
}

.route-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
}

.route-info {
  flex: 1;
}

.route-info h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
  color: white;
  line-height: 1.4;
}

.route-info p {
  margin: 0;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.4;
}

.favorite-btn {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  transition: all 0.2s;
  flex-shrink: 0;
  margin-left: 16px;
}

.favorite-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.1);
}

.favorite-btn.active {
  background: rgba(255, 255, 255, 0.3);
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #6B7280;
}

.empty-state p {
  font-size: 16px;
  margin: 0;
}

/* Scrollbar */
.routes-list::-webkit-scrollbar {
  width: 8px;
}

.routes-list::-webkit-scrollbar-track {
  background: #F3F4F6;
  border-radius: 4px;
}

.routes-list::-webkit-scrollbar-thumb {
  background: #D1D5DB;
  border-radius: 4px;
}

.routes-list::-webkit-scrollbar-thumb:hover {
  background: #9CA3AF;
}

/* Responsive */
@media (max-width: 768px) {
  .profile-content {
    flex-direction: column;
  }

  .profile-sidebar {
    width: 100%;
  }

  .avatar-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    text-align: left;
  }

  .avatar-circle {
    width: 80px;
    height: 80px;
    margin: 0;
  }

  .avatar-smile {
    width: 50px;
    height: 50px;
  }

  .tabs {
    overflow-x: auto;
  }

  .tab {
    white-space: nowrap;
    min-width: 120px;
  }

  .route-card {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }

  .favorite-btn {
    margin-left: 0;
  }
}
</style>