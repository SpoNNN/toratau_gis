<template>
  <a-modal
    :open="visible"
    title="Административная панель согласования маршрутов"
    :footer="null"
    width="900px"
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

    <!-- ФОРМА СОЗДАНИЯ ЗАЯВКИ -->
    <div v-if="activeTab === 'create'" class="form-container">
      <h3>Создание заявки на согласование маршрута</h3>
      <a-form layout="vertical">
        <a-form-item label="Выберите маршрут" required>
          <a-select v-model:value="form.route_id" placeholder="Выберите маршрут">
            <a-select-option v-for="route in routes" :key="route.id" :value="route.id">
              {{ route.title }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="Дата маршрута" required>
              <a-date-picker v-model:value="form.proposed_date" format="DD.MM.YYYY" style="width: 100%" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="Время начала маршрута" required>
              <a-time-picker v-model:value="form.start_time" format="HH:mm" style="width: 100%" />
            </a-form-item>
          </a-col>
        </a-row>

        <a-form-item label="Дедлайн голосования" required>
          <a-date-picker v-model:value="form.deadline" show-time format="DD.MM.YYYY HH:mm" style="width: 100%" />
        </a-form-item>

        <a-button type="primary" block @click="submitRequest" :loading="loading">
          Создать заявку и отправить письма
        </a-button>
      </a-form>
    </div>

    <!-- СПИСОК ЗАЯВОК -->
    <div v-if="activeTab === 'list'">
      <a-spin :spinning="loading">
        <a-table :dataSource="requests" :pagination="false" rowKey="id">
          <a-table-column title="Маршрут">
            <template #default="{ record }">
              <a @click="viewRequest(record)">{{ record.route?.title }}</a>
            </template>
          </a-table-column>
          <a-table-column title="Дата маршрута">
            <template #default="{ record }">
              {{ formatDate(record.proposed_date) }} {{ record.start_time }}
            </template>
          </a-table-column>
          <a-table-column title="Дедлайн">
            <template #default="{ record }">
              {{ formatDateTime(record.deadline) }}
            </template>
          </a-table-column>
          <a-table-column title="Статус">
            <template #default="{ record }">
              <a-tag :color="getStatusColor(record.computed_status || record.status)">
    {{ getStatusText(record.computed_status || record.status) }}
</a-tag>
         
            </template>
          </a-table-column>
          <a-table-column title="Действия">
            <template #default="{ record }">
              <a-button size="small" @click="viewRequest(record)">Детали</a-button>
              <a-button size="small" danger @click="cancelRequest(record)" v-if="record.computed_status === 'pending'">Отменить</a-button>
              <a-button size="small" danger @click="deleteRequest(record)" v-if="canDelete(record)">Удалить</a-button>
            </template>
          </a-table-column>
        </a-table>
      </a-spin>
    </div>

    <!-- МОДАЛЬНОЕ ОКНО С ДЕТАЛЯМИ ЗАЯВКИ -->
    <a-modal v-model:open="detailsVisible" title="Детали заявки" width="800px" :footer="null">
      <div v-if="selectedRequest">
        <h4>Информация о маршруте</h4>
        <p><strong>Маршрут:</strong> {{ selectedRequest.route?.title }}</p>
        <p><strong>Дата:</strong> {{ formatDate(selectedRequest.proposed_date) }} {{ selectedRequest.start_time }}</p>
        <p><strong>Дедлайн:</strong> {{ formatDateTime(selectedRequest.deadline) }}</p>
        <p><strong>Статус заявки:</strong> <a-tag :color="getStatusColor(selectedRequest.computed_status || selectedRequest.status)">{{ getStatusText(selectedRequest.computed_status || selectedRequest.status) }}</a-tag></p>
        
        <h4>Таблица организаций (точек маршрута)</h4>
        <a-table :dataSource="selectedRequest.request_points || selectedRequest.requestPoints || []" :pagination="false" rowKey="id">
          <a-table-column title="Организация">
            <template #default="{ record }">{{ record.point?.name }}</template>
          </a-table-column>
          <a-table-column title="Email">
            <template #default="{ record }">{{ record.point?.email || 'не указан' }}</template>
          </a-table-column>
          <a-table-column title="Статус">
            <template #default="{ record }">
              <a-tag :color="getVoteStatusColor(record.status)">
                {{ getVoteStatusText(record.status) }}
              </a-tag>
            </template>
          </a-table-column>
          <a-table-column title="Комментарий">
            <template #default="{ record }">{{ record.comment || '-' }}</template>
          </a-table-column>
          <a-table-column title="Проголосовано">
            <template #default="{ record }">{{ record.voted_at ? formatDateTime(record.voted_at) : '-' }}</template>
          </a-table-column>
        </a-table>

        <h4 v-if="timeline.length">Временной график маршрута</h4>
        <a-table :dataSource="timeline" :pagination="false" rowKey="point_id" v-if="timeline.length">
          <a-table-column title="Организация">
              <template #default="{ record }">{{ record.point?.name || record.point?.name }}</template>
          </a-table-column>
          <a-table-column title="Время прибытия">
            <template #default="{ record }">{{ record.arrival_time }}</template>
          </a-table-column>
          <a-table-column title="Длительность (мин)">
            <template #default="{ record }">{{ record.duration_minutes }}</template>
          </a-table-column>
        </a-table>
      </div>
    </a-modal>
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
const requests = ref([])
const loading = ref(false)
const detailsVisible = ref(false)
const selectedRequest = ref(null)
const timeline = ref([])

const form = ref({
  route_id: null,
  proposed_date: null,
  start_time: null,
  deadline: null
})

const loadRequests = async () => {
  try {
    loading.value = true
    const res = await axios.get('/api/admin/approval-requests')
    requests.value = res.data.data
  } catch (e) {
    console.error(e)
    message.error('Ошибка загрузки заявок')
  } finally {
    loading.value = false
  }
}

const submitRequest = async () => {
  if (!form.value.route_id || !form.value.proposed_date || !form.value.start_time || !form.value.deadline) {
    message.error('Заполните все поля')
    return
  }
  
  // ДОБАВЬТЕ ЭТУ СТРОКУ:
  console.log('Отправляемые данные:', {
    route_id: form.value.route_id,
    proposed_date: form.value.proposed_date.format('YYYY-MM-DD'),
    start_time: form.value.start_time.format('HH:mm'),
    deadline: form.value.deadline.format('YYYY-MM-DD HH:mm:ss')
  })

  try {
    loading.value = true
    await axios.post('/api/admin/approval-requests', {
      route_id: form.value.route_id,
      proposed_date: form.value.proposed_date.format('YYYY-MM-DD'),
      start_time: form.value.start_time.format('HH:mm'),
      deadline: form.value.deadline.format('YYYY-MM-DD HH:mm:ss')
    })
    message.success('Заявка создана, письма отправлены')
    form.value = { route_id: null, proposed_date: null, start_time: null, deadline: null }
    await loadRequests()
    activeTab.value = 'list'
  } catch (e) {
    console.error(e)
    message.error('Ошибка создания заявки')
  } finally {
    loading.value = false
  }
}

const viewRequest = async (record) => {
  try {
    loading.value = true
    const res = await axios.get(`/api/admin/approval-requests/${record.id}`)
    console.log('Полный ответ API:', res.data)  // ← ПОСМОТРИТЕ В КОНСОЛЬ
    selectedRequest.value = res.data.data
    timeline.value = res.data.timeline || []
    detailsVisible.value = true
  } catch (e) {
    console.error(e)
    message.error('Ошибка загрузки деталей')
  } finally {
    loading.value = false
  }
}

const deleteRequest = async (record) => {
  if (!confirm('Удалить заявку?')) return
  try {
    await axios.delete(`/api/admin/approval-requests/${record.id}`)
    message.success('Заявка удалена')
    loadRequests()
  } catch (e) {
    message.error(e.response?.data?.message || 'Ошибка удаления')
  }
}

const cancelRequest = async (record) => {
  if (!confirm('Отменить заявку? Все организации получат уведомление об отмене.')) return
  try {
    await axios.delete(`/api/admin/approval-requests/${record.id}/cancel`)
    message.success('Заявка отменена, уведомления отправлены')
    loadRequests()
  } catch (e) {
    message.error(e.response?.data?.message || 'Ошибка отмены')
  }
}

const canDelete = (record) => {
  const status = record.computed_status || record.status
  return status === 'cancelled' || status === 'completed'
}

const formatDate = (date) => dayjs(date).format('DD.MM.YYYY')
const formatDateTime = (date) => dayjs(date).format('DD.MM.YYYY HH:mm')

const getStatusColor = (status) => {
  const map = { pending: 'gold', approved: 'green', cancelled: 'red', completed: 'gray' }
  return map[status] || 'default'
}

const getStatusText = (status) => {
  const map = { pending: 'На согласовании', approved: 'Согласован', cancelled: 'Отменён', completed: 'Завершён' }
  return map[status] || status
}

const getVoteStatusColor = (status) => {
  const map = { waiting: 'gold', confirmed: 'green', rejected: 'red' }
  return map[status] || 'default'
}

const getVoteStatusText = (status) => {
  const map = { waiting: 'Ожидание', confirmed: 'Подтверждён', rejected: 'Отклонён' }
  return map[status] || status
}

watch(activeTab, (val) => {
  if (val === 'list') loadRequests()
})

const close = () => emit('update:visible', false)
</script>

<style scoped>
.tabs { display: flex; margin-bottom: 20px; }
.tab { flex: 1; text-align: center; padding: 10px; border-bottom: 2px solid #ccc; cursor: pointer; }
.tab.active { border-bottom: 2px solid #1890ff; font-weight: bold; }
.form-container { border: 1px solid #ddd; padding: 20px; border-radius: 10px; }
h4 { margin: 20px 0 10px; }
</style>