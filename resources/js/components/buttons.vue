<script setup>
import { defineProps, defineEmits } from 'vue'

defineProps({
  title: String,
  
  isFavorite: Boolean, // Добавьте проп для отслеживания избранного
})

const emit = defineEmits(['click', 'toggle-favorite'])
</script>

<template>
  <div class="pb-1"><button
    class="relative w-full py-2 pr-8 border-2 border-green-500 text-green-500 rounded-md hover:bg-green-500 hover:text-white transition overflow-visible"
    @click="$emit('click')"
  >
   <div class="text-white hover:bg-white-500 "> {{ title }}</div>
    <img
      :src="isFavorite ? 'heart_fill_red.svg' : 'heart_empty.svg'"
      @click.stop="$emit('toggle-favorite')"
      class="w-5 h-5 absolute top-1 right-1 cursor-pointer"
    />
  </button></div>
</template>
