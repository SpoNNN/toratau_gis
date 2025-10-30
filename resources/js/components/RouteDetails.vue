<script setup lang="ts">
import SearchInput from './SearchInput.vue'
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import { InfoCircleOutlined, PlusOutlined } from '@ant-design/icons-vue'

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

interface InfoItem {
  label: string
  value: string
}

interface Route {
  id: number
  title: string
  description: string
  map_color: string | null
  slug: string
  info_items: Record<string, InfoItem>
  point: RoutePoint[]
}

const route = ref<Route | null>(null)


const props = defineProps<{
  routeId?: number
  route?: Route 
}>()

const emit = defineEmits(['select-object', 'navigate', 'create-object'])


const vueRoute = useRoute()

const actualRouteId = computed(() => {

  if (props.routeId) {
    return props.routeId
  }
  

  if (props.route && props.route.id) {
    return props.route.id
  }
  

  const idFromUrl = vueRoute.params.id || vueRoute.params.routeId
  if (idFromUrl) {
    return Number(idFromUrl)
  }
  

  return 1
})



const loadRoute = async (id: number) => {
  try {
    isLoading.value = true
    error.value = null
    
    const response = await axios.get(`/api/routes/${id}`)
    if (response.data && response.data.success && response.data.data) {
      route.value = response.data.data
    } else if (response.data && !response.data.data) {
      route.value = response.data
    }

    
  } catch (err) {

    error.value = 'Не удалось загрузить маршрут: ' + (err as any).message
  } finally {
    isLoading.value = false

    console.log('error:', error.value)
    console.log('route:', route.value)
  }
}


watch(actualRouteId, (newId) => {
  if (newId) {
    loadRoute(newId)
  }
}, { immediate: true })


onMounted(() => {
  if (actualRouteId.value && !route.value) {
    loadRoute(actualRouteId.value)
  }
})

const showModal = () => {
  open.value = true
}

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

function navigateToPage(point: RoutePoint): void {
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

const isSubmitting = ref(false)
const fileList = ref<any[]>([])
const imageModalVisible = ref(false)
const currentImages = ref<string[]>([])
const currentImageIndex = ref(0)

const openImageModal = (images: string[], startIndex: number = 0) => {
  currentImages.value = images
  currentImageIndex.value = startIndex
  imageModalVisible.value = true
}

const handleBeforeUpload = (file: File) => {

  const isImage = file.type.startsWith('image/')
  if (!isImage) {
    alert('Можно загружать только изображения!')
    return false
  }


  const isLt5M = file.size / 1024 / 1024 < 1
  if (!isLt5M) {
    alert('Изображение должно быть меньше 5MB!')
    return false
  }

  const reader = new FileReader()
  reader.onload = (e) => {
    const base64 = e.target?.result as string
    formState.value.images.push(base64)
  }
  reader.readAsDataURL(file)

  return false 
}

const handleRemoveImage = (file: any) => {
  const index = fileList.value.indexOf(file)
  if (index > -1) {
    fileList.value.splice(index, 1)
    formState.value.images.splice(index, 1)
  }
}

const onFinish = async () => {
  try {
    isSubmitting.value = true
    
    
const routeId = actualRouteId.value
    
    if (!routeId) {
      throw new Error('ID маршрута не найден')
    }
    
    const response = await axios.post(`/api/routes/${routeId}/points`, {
      name: formState.value.name,
      description: formState.value.description,
      address: formState.value.address,
      url: formState.value.url,
      point_name: formState.value.point_name,
      lon: formState.value.lon,
      lat: formState.value.lat,
      images: JSON.stringify(formState.value.images)
    })
    
    await loadRoute(actualRouteId.value)
    

    emit('create-object', response.data.data)
    

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
    fileList.value = []
  } catch (err) {
    console.error('Ошибка создания точки:', err)
    const errorMessage = (err as any).response?.data?.message || (err as any).message
    alert('Не удалось создать точку: ' + errorMessage)
  } finally {
    isSubmitting.value = false
  }
}

const onFinishFailed = (errorInfo: any) => {
  console.log('Ошибка формы:', errorInfo)
}
</script>

<template>
  <div class="container">
    <div v-if="isLoading" class="loading">Загрузка маршрута...</div>
    
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <div v-else-if="route" class="route-content">
      <h1 class="text-white text-3xl text-center mb-4">{{ route.title }}</h1>
      
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
          <!-- Вкладка "О маршруте" -->
          <div v-if="activeTab === 'about'">
            <h2 class="text-white" style="margin: 10px">Описание маршрута</h2>
            <p class="text-white" style="margin: 10px">{{ route.description }}</p>
            
            <h2 class="text-white" style="margin: 10px">Информация о маршруте:</h2>
            <div class="info-block">
              <dl class="text-white">

                
                <template v-if="route.info_items && Object.keys(route.info_items).length > 0">
                  <template v-for="(infoItem, key) in route.info_items" :key="key">
                    <dt><strong>{{ infoItem.label }}:</strong></dt>
                    <dd>{{ infoItem.value }}</dd>
                  </template>
                </template>
              </dl>
            </div>
          </div>

          <!-- Вкладка "Объекты маршрута" -->
          <div v-if="activeTab === 'objects'" class="objects-tab">
            <h2 class="text-white mb-4">Точки маршрута</h2>
            
            <div v-if="route.point && route.point.length > 0" class="timeline-container">
              <div class="timeline-item" v-for="(point, index) in route.point" :key="index">
                <div class="timeline-number">{{ index + 1 }}</div>
                
                <div class="timeline-text">
                  <h3>{{ point.name }}</h3>
                  <p>{{ point.description }}</p>
                  <p class="coordinates">lon={{ point.lon }}, lat={{ point.lat }}</p>
                  <p>Адрес: {{ point.address }}</p>
                  
                  <!-- Отображение изображений -->
                  <div v-if="point.images && point.images.length > 0" class="point-images">
                    <div class="images-grid">
                      <img 
                        v-for="(image, imgIndex) in point.images.slice(0, 3)" 
                        :key="imgIndex"
                        :src="image" 
                        :alt="`${point.name} - изображение ${imgIndex + 1}`"
                        class="point-image"
                        @click="() => openImageModal(point.images, imgIndex)"
                      />
                      <div 
                        v-if="point.images.length > 3" 
                        class="more-images"
                        @click="() => openImageModal(point.images, 3)"
                      >
                        +{{ point.images.length - 3 }}
                      </div>
                    </div>
                  </div>
                </div>
                
                <div 
                  @click="navigateToPage(point)" 
                  class="info-icon"
                  title="Подробнее"
                >
                  <InfoCircleOutlined />
                </div>
                
                <div
                  class="map-button"
                  @click="openPoint(point)"
                >
                  На карте
                </div>
              </div>
            </div>
            
            <div v-else class="text-white text-center py-4">
              Нет точек маршрута
            </div>

            <a-button type="primary" @click="showModal" style="margin: 10px">
              Добавить объект
            </a-button>

            <!-- Модальное окно добавления объекта -->
            <a-modal 
              v-model:open="open" 
              title="Добавить точку маршрута" 
              :footer="null"
              width="600px"
            >
              <a-form 
                :model="formState" 
                layout="vertical"
                @finish="onFinish" 
                @finishFailed="onFinishFailed"
              >
                <a-form-item 
                  name="name" 
                  label="Название точки"
                  :rules="[{ required: true, message: 'Введите название' }]"
                >
                  <a-input 
                    v-model:value="formState.name" 
                    placeholder="Например: БГПУ им. М. Акмуллы" 
                  />
                </a-form-item>
                
                <a-form-item name="description" label="Описание">
                  <a-textarea 
                    v-model:value="formState.description" 
                    placeholder="Подробное описание точки маршрута"
                    :rows="4"
                  />
                </a-form-item>
                
                <a-form-item 
                  name="address" 
                  label="Адрес"
                  :rules="[{ required: true, message: 'Введите адрес' }]"
                >
                  <a-input 
                    v-model:value="formState.address" 
                    placeholder="Например: ул. Октябрьской Революции, 3а" 
                  />
                </a-form-item>

                <a-row :gutter="16">
                  <a-col :span="12">
                    <a-form-item 
                      name="lon" 
                      label="Долгота (Longitude)"
                      :rules="[{ required: true, message: 'Введите долготу' }]"
                    >
                      <a-input-number 
                        v-model:value="formState.lon" 
                        placeholder="54.123456" 
                        :step="0.000001" 
                        :precision="6"
                        style="width: 100%"
                      />
                    </a-form-item>
                  </a-col>
                  <a-col :span="12">
                    <a-form-item 
                      name="lat" 
                      label="Широта (Latitude)"
                      :rules="[{ required: true, message: 'Введите широту' }]"
                    >
                      <a-input-number 
                        v-model:value="formState.lat" 
                        placeholder="55.123456" 
                        :step="0.000001" 
                        :precision="6"
                        style="width: 100%"
                      />
                    </a-form-item>
                  </a-col>
                </a-row>
                
                <a-form-item name="url" label="Ссылка (необязательно)">
                  <a-input 
                    v-model:value="formState.url" 
                    placeholder="https://example.com" 
                  />
                </a-form-item>
                
                <a-form-item name="point_name" label="Идентификатор точки (необязательно)">
                  <a-input 
                    v-model:value="formState.point_name" 
                    placeholder="bspu_point_1" 
                  />
                </a-form-item>

                <a-form-item name="images" label="Изображения (необязательно)">
                  <a-upload
                    v-model:file-list="fileList"
                    list-type="picture-card"
                    :before-upload="handleBeforeUpload"
                    @remove="handleRemoveImage"
                    accept="image/*"
                    :multiple="true"
                  >
                    <div v-if="fileList.length < 8">
                      <plus-outlined />
                      <div style="margin-top: 8px">Загрузить</div>
                    </div>
                  </a-upload>
                  <div style="color: #999; font-size: 12px; margin-top: 8px;">
                    Можно загрузить до 8 изображений. Максимальный размер файла: 5MB
                  </div>
                </a-form-item>

                <a-form-item style="margin-bottom: 0; margin-top: 24px;">
                  <a-space>
                    <a-button type="primary" html-type="submit" :loading="isSubmitting">
                      {{ isSubmitting ? 'Создание...' : 'Создать точку' }}
                    </a-button>
                    <a-button @click="open = false">
                      Отмена
                    </a-button>
                  </a-space>
                </a-form-item>
              </a-form>
            </a-modal>

            <!-- Модалка для просмотра изображений -->
            <a-modal
              v-model:open="imageModalVisible"
              :footer="null"
              width="80%"
              style="max-width: 1000px;"
            >
              <div class="image-viewer">
                <img 
                  v-if="currentImages[currentImageIndex]"
                  :src="currentImages[currentImageIndex]" 
                  alt="Изображение точки"
                  class="modal-image"
                />
                <div v-if="currentImages.length > 1" class="image-navigation">
                  <a-button 
                    @click="currentImageIndex = Math.max(0, currentImageIndex - 1)"
                    :disabled="currentImageIndex === 0"
                  >
                    ← Предыдущее
                  </a-button>
                  <span class="image-counter">
                    {{ currentImageIndex + 1 }} / {{ currentImages.length }}
                  </span>
                  <a-button 
                    @click="currentImageIndex = Math.min(currentImages.length - 1, currentImageIndex + 1)"
                    :disabled="currentImageIndex === currentImages.length - 1"
                  >
                    Следующее →
                  </a-button>
                </div>
              </div>
            </a-modal>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="text-white text-center py-8">
      Маршрут не найден
    </div>
  </div>
</template>

<style scoped>
.container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  width: 100%;
}

.loading, 
.error {
  color: white;
  text-align: center;
  padding: 50px;
  font-size: 18px;
}

.error {
  color: #ff4d4f;
}

.route-content {
  width: 100%;
  max-width: 800px;
}

.info-block {
  background-color: #405394;
  border-radius: 12px;
  padding: 20px;
  margin: 0 10px;
}

.info-block dl {
  margin: 0;
}

.info-block dt {
  margin-top: 12px;
}

.info-block dt:first-child {
  margin-top: 0;
}

.info-block dd {
  margin-left: 0;
  margin-bottom: 0;
  color: rgba(255, 255, 255, 0.9);
}

.objects-tab {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 12px;
}

.timeline-container {
  width: 100%;
  max-width: 500px;
  display: flex;
  flex-direction: column;
  position: relative;
  padding: 20px;
  background-color: #405394;
  border-radius: 12px;
  margin-bottom: 20px;
}

.timeline-item {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  margin-bottom: 25px;
  position: relative;
}

.timeline-item:last-child {
  margin-bottom: 0;
}

.timeline-number {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: #FFB800;
  color: #32368E;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 16px;
  font-weight: bold;
  z-index: 1;
}

.timeline-text {
  flex: 1;
  color: white;
  font-size: 14px;
  min-width: 0;
}

.timeline-text h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
  color: white;
}

.timeline-text p {
  margin: 4px 0;
  line-height: 1.5;
}

.timeline-text .coordinates {
  font-size: 12px;
  opacity: 0.8;
  font-family: monospace;
}

.info-icon {
  flex-shrink: 0;
  color: white;
  font-size: 18px;
  cursor: pointer;
  padding: 8px;
  transition: color 0.2s;
}

.info-icon:hover {
  color: #FFB800;
}

.map-button {
  flex-shrink: 0;
  color: white;
  font-size: 13px;
  cursor: pointer;
  border: 1px solid white;
  padding: 6px 12px;
  border-radius: 6px;
  transition: all 0.2s;
  white-space: nowrap;
}

.map-button:hover {
  background-color: rgba(255, 255, 255, 0.1);
  border-color: #FFB800;
  color: #FFB800;
}

/* Стили для табов */
.flex {
  display: flex;
}

.justify-between {
  justify-content: space-between;
}

.border-b {
  border-bottom: 1px solid;
}

.border-gray-300 {
  border-color: #d1d5db;
}

.px-10 {
  padding-left: 2.5rem;
  padding-right: 2.5rem;
}

.p-2 {
  padding: 0.5rem;
}

.focus\:outline-none:focus {
  outline: none;
}

.border-b-2 {
  border-bottom-width: 2px;
}

.border-blue-500 {
  border-color: #3b82f6;
}

.text-blue-500 {
  color: #3b82f6;
}

.font-semibold {
  font-weight: 600;
}

.text-white {
  color: white;
}

.w-full {
  width: 100%;
}

.mt-5 {
  margin-top: 1.25rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.text-3xl {
  font-size: 1.875rem;
  line-height: 2.25rem;
}

.text-center {
  text-align: center;
}

.py-4 {
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.py-8 {
  padding-top: 2rem;
  padding-bottom: 2rem;
}

/* Стили для изображений точек */
.point-images {
  margin-top: 12px;
}

.images-grid {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.point-image {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.2s;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.point-image:hover {
  transform: scale(1.05);
  border-color: #FFB800;
}

.more-images {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: bold;
  color: white;
  cursor: pointer;
  transition: background 0.2s;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.more-images:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: #FFB800;
}

/* Стили для модалки с изображениями */
.image-viewer {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.modal-image {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  border-radius: 8px;
}

.image-navigation {
  display: flex;
  align-items: center;
  gap: 20px;
}

.image-counter {
  font-size: 16px;
  color: #666;
  min-width: 80px;
  text-align: center;
}
</style>