<script setup>
import { ref, defineProps } from 'vue';
import '../../css/app.css'
const props = defineProps({
  minDistance: { type: Number, default: null }, // Текущее значение расстояния
  maxParticipants: { type: Number, default: null }, // Текущее значение участников
  selectedAudience: { type: String, default: '' }, // Текущая аудитория
  minDuration: { type: Number, default: 0 },  // Новое свойство
  maxDuration: { type: Number, default: 300 } // Новое свойство
});

// Инициализируйте локальные состояния через props
const minDistance = ref(props.minDistance);
const maxParticipants = ref(props.maxParticipants);
const selectedAudience = ref(props.selectedAudience);
const durationRange = ref([props.minDuration, props.maxDuration]); // Значения слайдера

// Функция для форматирования минут в "часы:минуты"
const formatTime = (minutes) => {
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  return `${hours}ч ${mins}м`;
};

const emit = defineEmits(['apply', 'close']);

const applyFilters = () => {
  emit('apply', { minDistance: minDistance.value, 
                  maxParticipants: maxParticipants.value, 
                  selectedAudience: selectedAudience.value,
                  minDuration: durationRange.value[0],
                  maxDuration: durationRange.value[1]});
};
</script>
<style>

</style>
<template>
  <div class="fixed inset-0 bg-black flex items-center justify-center z-50" style="background-color: rgba(0, 0, 0, 0.3)">
    <div class="bg-white p-5 rounded-lg w-96">
      <h2 class="text-lg font-bold" style="font-weight: 600;">Фильтр маршрутов</h2>
      
      <label class="block mt-3">Максимальная протяженность (км)</label>
      <input v-model="minDistance" type="number"  style="border-color: grey;"  class="border  border-black-700 border-color-black-300 p-1 w-full">

      <label class="block mt-3">Макс. кол-во участников</label>
      <input v-model="maxParticipants" type="number" style="border-color: grey;"class="border p-1 w-full">

      <label class="block mt-3">Целевая аудитория</label>
      <select v-model="selectedAudience"style="border-color:#e9e9ed; background-color: #e9e9ed;" class="border p-1 w-full">
        <option :value="''">Все</option>
        <option value="12-18">12-18</option>
        <option value="15-17">15-17</option>
        <option value="16-18">16-18</option>
      </select>

      <label class="block mt-3">Продолжительность маршрута</label>
      <input type="range" v-model="durationRange[0]" :min="0" :max="300" step="5" class="w-full">
      <input type="range" v-model="durationRange[1]" :min="0" :max="300" step="5" class="w-full">
      <p>{{ formatTime(durationRange[0]) }} - {{ formatTime(durationRange[1]) }}</p>

      <div class="flex justify-end mt-4 space-x-2 text-white">
        <button @click="applyFilters" class="bg-blue-500 text-white px-4  py-2 rounded "> Применить</button>
        <div class="w-1"></div>
        <button @click="$emit('close')" class="bg-gray-500 text-white px-4 py-2 rounded">Закрыть</button>
      </div>
    </div>
  </div>
</template>
