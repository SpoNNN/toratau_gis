<script setup lang="ts">
import SearchInput from './SearchInput.vue'
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { UploadOutlined } from '@ant-design/icons-vue'
import { InfoCircleOutlined } from '@ant-design/icons-vue'

const open = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const error = ref<string | null>(null)

interface RoutePoint {
  lon: number
  lat: number
  name: string
  address: string
  url: string
  point_name: string
  description: string
  images: string[]
}

interface Route {
  id: number
  title: string
  description: string
  map_color: string | null
  slug: string
  info_items: any
  point: RoutePoint[]
}

const route = ref<Route | null>(null)

// Получаем ID из пропсов
const props = defineProps<{
  routeId: number
}>()

const loadRoute = async (id: number) => {
  try {
    isLoading.value = true
    error.value = null
    
    const response = await axios.get(`/api/routes/${id}`)
    // Разворачиваем Proxy объект
    route.value = JSON.parse(JSON.stringify(response.data.data))
    
    console.log('Загружен маршрут:', route.value)
    
  } catch (err) {
    console.error('Failed to load route:', err)
    error.value = 'Не удалось загрузить маршрут'
  } finally {
    isLoading.value = false
  }
}

// Загружаем маршрут при изменении routeId
watch(() => props.routeId, (newId) => {
  if (newId) {
    loadRoute(newId)
  }
}, { immediate: true })

const showModal = () => {
  open.value = true
}

const emit = defineEmits(['select-object', 'navigate', 'create-object'])

const activeTab = ref('about')
const searchQuery = ref('')
const isSearching = ref(false)

const shouldHideContent = computed(() => {
  return searchQuery.value.length > 0
})

function openPoint(point: RoutePoint): void {
  emit('select-object', point)
}

function handleSearchBlur(): void {
  setTimeout(() => {
    isSearching.value = false
  }, 200)
}

function navigateToPage(point: RoutePoint) {
  setTimeout(() => {
    emit('navigate', point)
  }, 300)
}

const formState = ref<RoutePoint>({
  name: '',
  description: '',
  address: '',
  url: '',
  point_name: '',
  lon: 0,
  lat: 0,
  images: [],
})

const onFinish = (values: RoutePoint) => {
  emit('create-object', formState.value)
  open.value = false
  formState.value = {
    name: '',
    description: '',
    address: '',
    url: '',
    lon: 0,
    lat: 0,
    point_name: '',
    images: [],
  }
}

const onFinishFailed = (errorInfo: any) => {
  console.log('errorInfo:', errorInfo)
}
</script>

<template>
  <div class="container">
    <div v-if="isLoading" class="loading">Загрузка маршрута...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else-if="route" class="route-content">
      <h1 class="text-white text-3xl text-center">{{ route.title }}</h1>
      <p class="text-white">ID маршрута: {{ route.id }}</p>
      
      <!-- Проверяем есть ли точки -->
      <div v-if="route.point && route.point.length > 0">
        <h2 class="text-white">Координаты точек:</h2>
        <div v-for="(point, index) in route.point" :key="index" class="text-white">
          <p>Точка {{ index + 1 }}: lon={{ point.lon }}, lat={{ point.lat }}</p>
          <p>Название: {{ point.name }}</p>
          <p>Адрес: {{ point.address }}</p>
          <p>Описание: {{ point.description }}</p>
        </div>
      </div>
      <div v-else class="text-white">
        Нет точек маршрута
      </div>

      <div style="margin: 10px">
        <SearchInput
          v-model="searchQuery"
          :points="route.point || []"
          placeholderText="найти объект"
          @focus="isSearching = true"
          @blur="handleSearchBlur"
          @select-point="openPoint"
        />
      </div>

      <div v-if="!shouldHideContent" class="w-full">
        <div class="flex justify-between border-b border-gray-300 px-10">
          <button
            class="p-2 focus:outline-none"
            :class="activeTab === 'about' ? 'border-b-2 border-blue-500 text-blue-500 font-semibold' : 'text-white'"
            @click="activeTab = 'about'"
          >
            <div class="text-white">о маршруте</div>
          </button>
          <button
            class="p-2 focus:outline-none"
            :class="activeTab === 'objects' ? 'border-b-2 border-blue-500 text-blue-500 font-semibold' : 'text-white'"
            @click="activeTab = 'objects'"
          >
            <div class="text-white">объекты маршрута</div>
          </button>
        </div>

        <div class="mt-5">
          <div v-if="activeTab === 'about'">
            <h2 class="text-white" style="margin: 10px">Описание маршрута</h2>
            <p class="text-white" style="margin: 10px">{{ route.description }}</p>
            
            <h2 class="text-white" style="margin: 10px">Информация о маршруте</h2>
            <div class="bg-blue-900 rounded-xl p-4 space-y-3" style="background-color: #405394; margin: 0px 10px">
              <dl class="text-white">
                <dt><strong>ID маршрута:</strong></dt>
                <dd>{{ route.id }}</dd>
                <dt><strong>Slug:</strong></dt>
                <dd>{{ route.slug }}</dd>
                <template v-if="route.info_items && Object.keys(route.info_items).length > 0">
                  <template v-for="(infoItem, key) in route.info_items" :key="key">
                    <dt><strong>{{ infoItem.label }}:</strong></dt>
                    <dd>{{ infoItem.value }}</dd>
                  </template>
                </template>
              </dl>
            </div>
          </div>

          <div v-if="activeTab === 'objects'" class="px-3" style="display: flex; flex-direction: column; align-items: center">
            <h2 class="text-white">Точки маршрута</h2>
            <div v-if="route.point && route.point.length > 0" class="timeline-container">
              <div class="timeline-item" v-for="(point, index) in route.point" :key="index">
                <div class="timeline-number">{{ index + 1 }}</div>
                <div class="timeline-text">
                  <dd>{{ point.name  }}</dd>
                  <h3>{{ point.name }}</h3>
                  <p>lon={{ point.lon }}, lat={{ point.lat }}</p>
                  <p>{{ point.description }}</p>
                  <p>Адрес: {{ point.address }}</p>
                </div>
                <div @click="navigateToPage(point)" style="cursor: pointer; padding: 7px">
                  <InfoCircleOutlined />
                </div>
                <div
                  style="color: white; font-size: 13px; margin-left: auto; cursor: pointer; border: 1px solid white; padding: 5px; border-radius: 5px;"
                  @click="openPoint(point)"
                >
                  На карте
                </div>
              </div>
            </div>
            <div v-else class="text-white">
              Нет точек маршрута
            </div>

            <a-button type="primary" @click="showModal" style="margin: 10px">Добавить объект</a-button>

            <a-modal v-model:open="open" title="Добавить объект">
              <a-form :model="formState" @finish="onFinish" @finishFailed="onFinishFailed">
                <a-form-item name="name">
                  <a-input v-model:value="formState.name" placeholder="Название" />
                </a-form-item>
                <a-form-item name="description">
                  <a-input v-model:value="formState.description" placeholder="Описание" />
                </a-form-item>
                <a-form-item name="address">
                  <a-input v-model:value="formState.address" placeholder="Адрес" />
                </a-form-item>
                <a-form-item name="url">
                  <a-input v-model:value="formState.url" placeholder="URL" />
                </a-form-item>
                <a-form-item name="point_name">
                  <a-input v-model:value="formState.point_name" placeholder="Идентификатор точки" />
                </a-form-item>
                <a-form-item name="lon" label="Долгота">
                  <a-input-number v-model:value="formState.lon" placeholder="Долгота" :step="0.000001" :precision="6" />
                </a-form-item>
                <a-form-item name="lat" label="Широта">
                  <a-input-number v-model:value="formState.lat" placeholder="Широта" :step="0.000001" :precision="6" />
                </a-form-item>
                <a-button type="primary" htmlType="submit">Создать</a-button>
              </a-form>
            </a-modal>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.loading, .error {
  color: white;
  text-align: center;
  padding: 50px;
}

.route-content {
  width: 100%;
}

input {
  margin: 5px;
  margin-right: 20px;
  width: 80%;
  padding: 5px;
  border-radius: 5px;
  border: 1px solid #ccc;
}

.timeline-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  position: relative;
  padding: 10px;
  background-color: #405394;
  border-radius: 10px;
  min-width: 435px;
}

.timeline-item {
  display: flex;
  align-items: center;
  margin-bottom: 25px;
  position: relative;
}

.timeline-number {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background-color: rgba(255, 184, 0, 255);
  color: rgba(50, 54, 142, 255);
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 16px;
  font-weight: bold;
  margin-right: 20px;
  z-index: 1;
}

.timeline-text {
  color: white;
  font-size: 13px;
  width: 100%;
  max-width: 315px;
}
</style>