<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useAuthCheck } from '../utils/useAuthCheck'
import { PlusOutlined, DeleteOutlined, EditOutlined } from '@ant-design/icons-vue'
import SearchInput from './SearchInput.vue'
import buttons from './buttons.vue'
import filterPop from './filterPop.vue'

interface Route {
  id: number
  title: string
  slug: string | null
  distance: string | null
  participants: number | null
  audience: string | null
  duration: string
  description: string
  map_color: string | null
  route_info_value?: string
  isFavorite: boolean
}

const props = defineProps<{
  isLoggedIn?: boolean
  isAdmin?: boolean
}>()

const emit = defineEmits<{
  selectRoute: [id: number]
  navigate: [page: string]
}>()

const { isAuthenticated, checkAuth } = useAuthCheck()

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

const userIsLoggedIn = computed(() => isAuthenticated.value)

const modalVisible = ref(false)
const isEdit = ref(false)
const isSubmitting = ref(false)

const formState = ref({
  id: null as number | null,
  title: '',
  map_color: '',
  description: '',
  distance: null as number | null,
  duration: null as number | null,
  participants: null as number | null,
  audience: '',
  slug: '',
  route_info_value: ''
})

const colorPickerValue = computed({
  get: () => '#' + (formState.value.map_color || 'FFB800'),
  set: (val: string) => {
    formState.value.map_color = val.replace('#', '')
  }
})

const openCreateModal = () => {
  isEdit.value = false
  formState.value = {
    id: null, title: '', map_color: 'FFB800', description: '',
    distance: null, duration: null, participants: null, audience: '', slug: '',
    route_info_value: ''
  }
  modalVisible.value = true
}

const openEditModal = (route: Route) => {
  isEdit.value = true
  formState.value = {
    id: route.id,
    title: route.title,
    map_color: (route.map_color || 'FFB800').replace('#', ''),
    description: String(route.description ?? ''),
    distance: route.distance ? Number(route.distance) : null,
    duration: route.duration ? Number(route.duration) : null,
    participants: route.participants ?? null,
    audience: route.audience ?? '',
    slug: route.slug ?? '',
    route_info_value: route.route_info_value ?? ''
  }
  modalVisible.value = true
}

const onFinish = async () => {
  try {
    isSubmitting.value = true
    const payload = {
      title: formState.value.title,
      map_color: formState.value.map_color.replace('#', '') || null,
      description: formState.value.description || null,
      distance: formState.value.distance ?? null,
      duration: formState.value.duration ?? null,
      participants: formState.value.participants ?? null,
      audience: formState.value.audience || null,
      slug: formState.value.slug || null,
    }
    let savedRoute: any
    if (isEdit.value && formState.value.id) {
      const res = await axios.put(`/api/admin/routes/${formState.value.id}`, payload)
      savedRoute = res.data.data
    } else {
      const res = await axios.post('/api/admin/routes', payload)
      savedRoute = res.data.data
    }
    if (formState.value.route_info_value.trim() && savedRoute?.id) {
      await axios.post(`/api/admin/routes/${savedRoute.id}/route-info`, {
        value: formState.value.route_info_value.trim()
      })
    }
    modalVisible.value = false
    await loadRoutes()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Ошибка сохранения')
  } finally {
    isSubmitting.value = false
  }
}

const onFinishFailed = (errorInfo: any) => {
  console.log('Ошибка формы:', errorInfo)
}

const deleteRoute = async (id: number) => {
  if (!confirm('Удалить маршрут? Это действие нельзя отменить.')) return
  try {
    await axios.delete(`/api/admin/routes/${id}`)
    await loadRoutes()
  } catch {
    alert('Не удалось удалить маршрут')
  }
}

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
      map_color: dbRoute.map_color || null,
      route_info_value: (() => {
        if (!dbRoute.info_items) return ''
        const found = Object.values(dbRoute.info_items).find(
          (item: any) => item.label === 'Программа обслуживания и посещения'
        ) as any
        return found?.value ?? ''
      })(),
      isFavorite: false
    }))
    await loadFavorites()
  } catch {
    error.value = 'Не удалось загрузить маршруты'
  } finally {
    isLoading.value = false
    favoritesLoaded.value = true
  }
}

const loadFavorites = async () => {
  if (!userIsLoggedIn.value) {
    routes.value.forEach(r => { r.isFavorite = false })
    return
  }
  try {
    const response = await axios.get('/favorites')
    const favorites = response.data.data || []
    routes.value.forEach(route => {
      route.isFavorite = favorites.some((f: any) =>
        f.route_id === route.id || f.route?.id === route.id
      )
    })
  } catch {
    routes.value.forEach(r => { r.isFavorite = false })
  }
}

const toggleFavorite = async (route: Route) => {
  if (!userIsLoggedIn.value) {
    alert('Для добавления в избранное необходимо войти в аккаунт')
    return
  }
  const wasFavorite = route.isFavorite
  route.isFavorite = !wasFavorite
  try {
    if (route.isFavorite) {
      await axios.post('/favorites', { route_id: route.id })
    } else {
      await axios.delete(`/favorites/${route.id}`)
    }
  } catch (err) {
    route.isFavorite = wasFavorite
    if (axios.isAxiosError(err) && err.response?.status === 401) {
      alert('Сессия истекла. Пожалуйста, войдите снова.')
    }
  }
}

const applyFilters = (filteredRoutes: Route[]) => {
  routes.value = filteredRoutes
  showFilterPopup.value = false
}

const resetFilters = () => {
  searchQuery.value = ''
  filters.value = { minDistance: null, maxParticipants: null, selectedAudience: '', minDuration: 0, maxDuration: 300 }
  localStorage.removeItem('filters')
  loadRoutes()
}

const filteredRoutes = computed(() => {
  return routes.value.filter(route => {
    const matchesSearch = route.title.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesDistance = filters.value.minDistance === null ||
      (route.distance !== null && route.distance <= filters.value.minDistance)
    const matchesParticipants = filters.value.maxParticipants === null ||
      (route.participants !== null && route.participants <= filters.value.maxParticipants)
    const matchesAudience = filters.value.selectedAudience === '' ||
      (route.audience && route.audience === filters.value.selectedAudience)
    const matchesDuration = route.duration >= filters.value.minDuration &&
      route.duration <= filters.value.maxDuration
    return matchesSearch && matchesDistance && matchesParticipants && matchesAudience && matchesDuration
  })
})

onMounted(async () => {
  await checkAuth()
  const savedFilters = JSON.parse(localStorage.getItem('filters') || '{}')
  if (savedFilters) filters.value = { ...filters.value, ...savedFilters }
  const token = localStorage.getItem('auth_token')
  if (token) axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  loadRoutes()
})
</script>

<template>
  <div class="text-center mb-6">
    <span class="text-white text-3xl text-center">Научно-образовательные маршруты по Уфе</span>
  </div>

  <SearchInput v-model="searchQuery" placeholderText="найти маршрут" :showFilter="true" :points="[]"
    @click-filter="showFilterPopup = true" />

  <div v-if="isLoading" class="text-white text-center">Загрузка маршрутов...</div>

  <div v-else-if="error" class="text-red-500 text-center">
    {{ error }}
    <button @click="loadRoutes" class="text-white ml-2">Повторить</button>
  </div>

  <div v-else-if="favoritesLoaded" class="routes-list">
    <div v-for="route in filteredRoutes" :key="route.id" class="route-row"
      :class="{ 'route-row--admin': isAuthenticated && isAdmin }">
      <div class="route-button-wrap">
        <buttons :title="route.title" :route-id="route.id" :is-favorite="route.isFavorite"
          :is-logged-in="userIsLoggedIn" @toggle-favorite="() => toggleFavorite(route)"
          @click="() => { selectedRouteId = route.id; emit('selectRoute', Number(route.id)) }" />
      </div>

      <div v-if="isAuthenticated && isAdmin" class="admin-actions">
        <div class="edit-button" @click.stop="openEditModal(route)" title="Редактировать маршрут">
          <EditOutlined />
        </div>
        <div class="delete-button" @click.stop="deleteRoute(route.id)" title="Удалить маршрут">
          <DeleteOutlined />
        </div>
      </div>
    </div>
  </div>

  <div class="mt-4 flex justify-center">
    <button @click="resetFilters" class=" button_reset bg-red-500 text-white px-4 py-2 mt-4 rounded">
      Сбросить фильтры
    </button>
    <a-button v-if="isAuthenticated && isAdmin" type="primary" @click="openCreateModal" style="margin-left: 2.5rem;">
      <PlusOutlined />
      Добавить маршрут
    </a-button>
  </div>

  <filterPop v-if="showFilterPopup" @filtered="applyFilters" @close="showFilterPopup = false" />

  <a-modal v-model:open="modalVisible" :title="isEdit ? 'Редактировать маршрут' : 'Добавить маршрут'" :footer="null"
    width="600px">
    <a-form :model="formState" layout="vertical" @finish="onFinish" @finishFailed="onFinishFailed">

      <a-form-item name="title" label="Название маршрута"
        :rules="[{ required: true, message: 'Введите название маршрута' }]">
        <a-input v-model:value="formState.title" placeholder="Например: Маршрут по историческому центру Уфы" />
      </a-form-item>

      <a-form-item name="description" label="Описание">
        <a-textarea v-model:value="formState.description" placeholder="Подробное описание маршрута" :rows="4" />
      </a-form-item>

      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item name="distance" label="Протяжённость (км)">
            <a-input-number v-model:value="formState.distance" placeholder="0.0" :min="0" :step="0.1" :precision="1"
              style="width: 100%" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item name="duration" label="Длительность (мин)">
            <a-input-number v-model:value="formState.duration" placeholder="0" :min="0" style="width: 100%" />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item name="participants" label="Кол-во участников (макс)">
            <a-input-number v-model:value="formState.participants" placeholder="0" :min="0" style="width: 100%" />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item name="audience" label="Целевая аудитория">
            <a-input v-model:value="formState.audience" placeholder="14-18, 19-20 ..." />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="16">
        <a-col :span="12">
  
          <a-form-item name="map_color" label="Цвет на карте">
            <div class="color-picker-row">
              <input type="color" :value="colorPickerValue"
                @input="(e) => colorPickerValue = (e.target as HTMLInputElement).value" class="color-swatch" />
              <a-input v-model:value="formState.map_color" placeholder="FFB800" :maxlength="6" style="flex: 1"
                @input="(e: any) => formState.map_color = e.target.value.replace('#', '')">
                <template #prefix>#</template>
              </a-input>
            </div>
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item name="slug" label="Slug (URL-идентификатор)">
            <a-input v-model:value="formState.slug" placeholder="istoricheskiy-marshrut" />
          </a-form-item>
        </a-col>
      </a-row>

      <a-form-item name="route_info_value" label="Программа обслуживания и посещения">
        <a-textarea v-model:value="formState.route_info_value"
          placeholder="Описание программы обслуживания и посещения маршрута" :rows="3" />
      </a-form-item>

      <a-form-item style="margin-bottom: 0; margin-top: 24px;">
        <a-space>
          <a-button type="primary" html-type="submit" :loading="isSubmitting">
            {{ isSubmitting ? 'Сохранение...' : (isEdit ? 'Сохранить изменения' : 'Создать маршрут') }}
          </a-button>
          <a-button @click="modalVisible = false">
            Отмена
          </a-button>
        </a-space>
      </a-form-item>

    </a-form>
  </a-modal>
</template>

<style scoped>
.routes-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 16px;
  width: 420px;
}

/* Строка маршрута — flex, кнопки рядом */
.route-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Кнопка маршрута занимает всё доступное место */
.route-button-wrap {
  flex: 1;
  min-width: 0;
}

/* Когда нет admin-кнопок — растягиваем на всю ширину */
.route-row:not(.route-row--admin) .route-button-wrap {
  width: 100%;
}

/* Блок с иконками редактирования/удаления */
.admin-actions {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex-shrink: 0;
}

.edit-button,
.delete-button {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 15px;
  color: rgba(255, 255, 255, 0.6);
  transition: all 0.2s;
}

.edit-button:hover {
  background-color: rgba(255, 184, 0, 0.15);
  border-color: #FFB800;
  color: #FFB800;
}

.delete-button:hover {
  background-color: rgba(255, 77, 79, 0.15);
  border-color: #ff4d4f;
  color: #ff4d4f;
}

/* Color picker row */
.color-picker-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-swatch {
  width: 38px;
  height: 32px;
  border: 1px solid #d9d9d9;
  border-radius: 6px;
  padding: 2px;
  cursor: pointer;
  background: none;
  flex-shrink: 0;
}

.color-swatch::-webkit-color-swatch-wrapper {
  padding: 0;
}

.color-swatch::-webkit-color-swatch {
  border: none;
  border-radius: 4px;
}

.button_reset {
  color: white;
}
</style>