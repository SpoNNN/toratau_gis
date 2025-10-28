<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { doc, getDoc, updateDoc, arrayUnion, arrayRemove } from 'firebase/firestore'
import { getAuth, onAuthStateChanged } from 'firebase/auth'
import { initializeApp } from 'firebase/app'
import { getFirestore } from 'firebase/firestore'
import SearchInput from './SearchInput.vue'
import buttons from './buttons.vue'
import filterPop from './filterPop.vue'

interface Route {
  id: number
  title: string
  slug: string | null
  distance: number | null
  participants: number | null
  audience: string | null
  duration: number,
  description: String,
  isFavorite: boolean
}

interface Filters {
  minDistance: number | null
  maxParticipants: number | null
  selectedAudience: string
  minDuration: number
  maxDuration: number
}

const firebaseConfig = {
  apiKey: 'AIzaSyDnXXJ1R-lkoheA8LEJuHLzy2kjUvcC4-w',
  authDomain: 'myproject-35bc3.firebaseapp.com',
  projectId: 'myproject-35bc3',
  storageBucket: 'myproject-35bc3.firebasestorage.app',
  messagingSenderId: '1064017138140',
  appId: '1:1064017138140:web:b294ccd4f2b9c9762abf19'
}

const app = initializeApp(firebaseConfig)
const db = getFirestore(app)
const auth = getAuth(app)

const routes = ref<Route[]>([])
const searchQuery = ref('')
const showFilterPopup = ref(false)
const favoritesLoaded = ref(false)
const isLoading = ref(false)
const error = ref<string | null>(null)
const selectedRouteId = ref<number | null>(null)

const filters = ref({
  minDistance: null as number | null,
  maxParticipants: null as number | null,
  selectedAudience: '',
  minDuration: 0,
  maxDuration: 300
})

const loadRoutes = async () => {
  try {
    isLoading.value = true
    error.value = null
    
    const response = await axios.get('/api/routes')
    const dbRoutes = response.data.data || []
    
    routes.value = dbRoutes.map((dbRoute: any) => ({
      id: dbRoute.id,
      title: dbRoute.title,
      slug: dbRoute.slug,
      distance: dbRoute.distance || null,
      participants: dbRoute.participants || null,
      audience: dbRoute.audience || null,
      duration: dbRoute.duration || 0,
      description: dbRoute.description || '',
      isFavorite: false
    }))
    
    await loadFavorites()
    
  } catch (err) {
    console.error('Failed to load routes:', err)
    error.value = 'Не удалось загрузить маршруты'
  } finally {
    isLoading.value = false
    favoritesLoaded.value = true
  }
}

const loadFavorites = async () => {
  try {
    const user = auth.currentUser
    let favorites: any[] = []
    
    if (user) {
      const userDoc = await getDoc(doc(db, 'users', user.uid))
      if (userDoc.exists()) {
        favorites = userDoc.data().favorites || []
      }
    } else {
      favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
    }
    
    routes.value.forEach(route => {
      route.isFavorite = favorites.some((fav: any) => fav.id === route.id)
    })
    
  } catch (err) {
    console.error('Failed to load favorites:', err)
  }
}

const toggleFavorite = async (route: Route) => {
  try {
    route.isFavorite = !route.isFavorite
    const user = auth.currentUser
    
    const favoriteData = {
      id: route.id,
      title: route.title,
      slug: route.slug
    }
    
    if (user) {
      const userDocRef = doc(db, 'users', user.uid)
      
      if (route.isFavorite) {
        await updateDoc(userDocRef, {
          favorites: arrayUnion(favoriteData)
        })
      } else {
        await updateDoc(userDocRef, {
          favorites: arrayRemove(favoriteData)
        })
      }
    } else {
      let favorites = JSON.parse(localStorage.getItem('favorites') || '[]')
      
      if (route.isFavorite) {
        favorites.push(favoriteData)
      } else {
        favorites = favorites.filter((fav: any) => fav.id !== route.id)
      }
      
      localStorage.setItem('favorites', JSON.stringify(favorites))
    }
  } catch (err) {
    console.error('Failed to toggle favorite:', err)
    route.isFavorite = !route.isFavorite
  }
}

const applyFilters = (filteredRoutes: Route[]) => {
  routes.value = filteredRoutes
  showFilterPopup.value = false
}

const resetFilters = () => {
  searchQuery.value = ''
  filters.value = {
    minDistance: null,
    maxParticipants: null,
    selectedAudience: '',
    minDuration: 0,
    maxDuration: 300
  }
  localStorage.removeItem('filters')
  loadRoutes()
}

const filteredRoutes = computed(() => {
  return routes.value.filter(route => {
    const matchesSearch = route.title.toLowerCase()
      .includes(searchQuery.value.toLowerCase())
    
    const matchesDistance = filters.value.minDistance === null || 
      (route.distance !== null && route.distance <= filters.value.minDistance)
    
    const matchesParticipants = filters.value.maxParticipants === null || 
      (route.participants !== null && route.participants <= filters.value.maxParticipants)
    
    const matchesAudience = filters.value.selectedAudience === '' || 
      (route.audience && route.audience === filters.value.selectedAudience)
    
    const matchesDuration = route.duration >= filters.value.minDuration && 
      route.duration <= filters.value.maxDuration

    return matchesSearch && matchesDistance && 
           matchesParticipants && matchesAudience && 
           matchesDuration
  })
})

onMounted(() => {
  const savedFilters = JSON.parse(localStorage.getItem('filters') || '{}')
  if (savedFilters) {
    filters.value = { ...filters.value, ...savedFilters }
  }
  
  loadRoutes()
  
  onAuthStateChanged(auth, () => {
    loadFavorites()
  })
})

defineEmits(['selectRoute'])
</script>

<template>
  <div class="text-center mb-6">
    <span class="text-white text-3xl text-center">Научно-образовательные маршруты по Уфе</span>
  </div>

  <SearchInput
    v-model="searchQuery"
    placeholderText="найти маршрут"
    :showFilter="true"
    :points="[]"
    @click-filter="showFilterPopup = true"
  />

  <div v-if="isLoading" class="text-white text-center">Загрузка маршрутов...</div>

  <div v-else-if="error" class="text-red-500 text-center">
    {{ error }}
    <button @click="loadRoutes" class="text-white">Повторить</button>
  </div>

  <div v-else-if="favoritesLoaded" class="space-y-3 mt-4" style="width: 420px">
    <buttons
      v-for="route in filteredRoutes"
      :key="route.id"
      :title="route.title"
      :id="route.id"
      :is-favorite="route.isFavorite"
      @toggle-favorite="toggleFavorite(route)"
      @click="
        () => {
          if (route.id) {
            selectedRouteId = route.id
            $emit('selectRoute', route.id)
            axios.get('/api/routes/' + route.id)
          }
        }
      "
    />
  </div>

  <div class="mt-4 flex justify-center">
    <button @click="resetFilters" class="bg-red-500 text-white px-4 py-2 mt-4 rounded">
      <div class="text-white">Сбросить фильтры</div>
    </button>
  </div>

  <filterPop
    v-if="showFilterPopup"
    @filtered="applyFilters"
    @close="showFilterPopup = false"
  />
</template>