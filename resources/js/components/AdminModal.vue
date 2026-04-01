<template>
  <a-modal
    :open="visible"
    title="Административная панель"
    :footer="null"
    width="700px"
    :centered="true"
    @cancel="close"
  >

    <div class="tabs">
      <div :class="['tab', activeTab === 'create' && 'active']" @click="activeTab = 'create'">
        Новая заявка
      </div>
      <div :class="['tab', activeTab === 'list' && 'active']" @click="activeTab = 'list'">
        Список заявок
      </div>
    </div>

    <div v-if="activeTab === 'create'" class="form-container">
      <h3>Новая заявка на научно-образовательный маршрут</h3>

      <a-form layout="vertical">

        <a-form-item label="Дата и время проведения маршрута">
          <a-date-picker
            placeholder="Выберите дату"
            show-time
            format="DD.MM.YYYY HH:mm"
            v-model:value="formState.date"
            style="width: 100%"
          />
        </a-form-item>

        <a-form-item label="Маршрут">
          <a-select v-model:value="formState.route_id" placeholder="Выберите маршрут">
            <a-select-option
              v-for="route in routes"
              :key="route.id"
              :value="route.id"
            >
              {{ route.title }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item label="Количество мест">
          <a-input-number
            v-model:value="formState.max_users"
            :min="1"
            :max="100"
            style="width: 100%"
          />
        </a-form-item>

        <a-button type="primary" block @click="submitForm">
          Отправить заявку
        </a-button>
      </a-form>
    </div>
  <div v-if="activeTab === 'list'">
      <a-spin :spinning="isLoading">

        <a-table
          :dataSource="events"
          :pagination="false"
          rowKey="id"
        >
          <a-table-column title="Маршрут">
            <template #default="{ record }">
              {{ record.route?.title }}
            </template>
          </a-table-column>

          <a-table-column title="Дата">
            <template #default="{ record }">
              {{ formatDate(record.date) }}
            </template>
          </a-table-column>

  
          <a-table-column title="Места">
            <template #default="{ record }">
              {{ record.ordered_users }}/{{ record.max_users }}
            </template>
          </a-table-column>

          
          <a-table-column title="Статус">
            <template #default="{ record }">
              <span :class="getStatusClass(record)">
                {{ getStatusText(record) }}
              </span>
            </template>
          </a-table-column>

        </a-table>

      </a-spin>
    </div>

  </a-modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { message } from 'ant-design-vue'
import dayjs from 'dayjs'

const props = defineProps({
  visible: Boolean,
  routes: Array
})

const emit = defineEmits(['update:visible'])

const activeTab = ref('create')
const events = ref([])
const isLoading = ref(false)

const formState = ref({
  route_id: null,
  max_users: 1,
  date: null
})


const loadEvents = async () => {
  try {
    isLoading.value = true
    const res = await axios.get('/api/admin/events')
    events.value = res.data.data
  } catch (e) {
    console.error(e)
    message.error('Ошибка загрузки')
  } finally {
    isLoading.value = false
  }
}


const submitForm = async () => {
  try {
    await axios.post('/api/admin/events', {
      route_id: formState.value.route_id,
      max_users: formState.value.max_users,
      date: formState.value.date
        ? formState.value.date.format('YYYY-MM-DD HH:mm:ss')
        : null
    })

    message.success('Событие создано')

   
    formState.value = {
      route_id: null,
      max_users: 1,
      date: null
    }

  
    await loadEvents()
    activeTab.value = 'list'

  } catch (error) {
    console.error(error)
    message.error('Ошибка создания')
  }
}



const formatDate = (date) => {
  return dayjs(date).format('DD.MM.YYYY HH:mm')
}

const getStatusText = (event) => {
  const now = dayjs()

  if (dayjs(event.date).isBefore(now)) {
    return 'Завершен'
  }

  if (event.ordered_users >= event.max_users) {
    return 'Заполнен'
  }

  return 'Открыт'
}

const getStatusClass = (event) => {
  const status = getStatusText(event)

  return {
    'text-green': status === 'Открыт',
    'text-red': status === 'Заполнен',
    'text-gray': status === 'Завершен'
  }
}



watch(activeTab, (val) => {
  if (val === 'list') {
    loadEvents()
  }
})



const close = () => {
  emit('update:visible', false)
}
</script>

<style scoped>
.tabs {
  display: flex;
  margin-bottom: 20px;
}

.tab {
  flex: 1;
  text-align: center;
  padding: 10px;
  border-bottom: 2px solid #ccc;
  cursor: pointer;
}

.tab.active {
  border-bottom: 2px solid #1890ff;
  font-weight: bold;
}

.form-container {
  border: 1px solid #ddd;
  padding: 20px;
  border-radius: 10px;
}

.text-green {
  color: #52c41a;
}

.text-red {
  color: #ff4d4f;
}

.text-gray {
  color: #999;
}
</style>