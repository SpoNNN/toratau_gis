import { ref, computed } from 'vue'
import axios from 'axios'

const isAuthenticated = ref(false)
const isChecking = ref(false)

export function useAuthCheck() {
  const checkAuth = async () => {
    if (typeof window === 'undefined') {
      isAuthenticated.value = false
      return false
    }
    
    const token = localStorage.getItem('auth_token')
    console.log('useAuthCheck - checkAuth - token:', token ? 'ЕСТЬ' : 'НЕТ')
    
    if (!token) {
      console.log('useAuthCheck - токен отсутствует')
      isAuthenticated.value = false
      return false
    }

    try {
      isChecking.value = true
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      
      console.log('useAuthCheck - запрос к /api/user...')
      const response = await axios.get('/api/user')
      console.log('useAuthCheck - ответ получен:', response.data)
      
      isAuthenticated.value = true
      console.log('useAuthCheck - ✅ авторизован')
      return true
      
    } catch (error: any) {
      console.error('useAuthCheck - ❌ ошибка:', error.response?.status)
      isAuthenticated.value = false
      
      if (axios.isAxiosError(error) && error.response?.status === 401) {
        console.log('useAuthCheck - токен невалидный, очищаем')
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
        delete axios.defaults.headers.common['Authorization']
      }
      
      return false
    } finally {
      isChecking.value = false
    }
  }


  const checkToken = () => {
    if (typeof window === 'undefined') {
      isAuthenticated.value = false
      return false
    }
    
    const token = localStorage.getItem('auth_token')
    const hasToken = !!token
    
    console.log('useAuthCheck - checkToken:', hasToken ? 'ЕСТЬ' : 'НЕТ')
    isAuthenticated.value = hasToken
    
    if (hasToken) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    }
    
    return hasToken
  }


  const setAuthenticated = (value: boolean) => {
    console.log('useAuthCheck - setAuthenticated:', value)
    isAuthenticated.value = value
  }


  return {
    isAuthenticated: computed(() => isAuthenticated.value),
    isChecking: computed(() => isChecking.value),
    checkAuth,
    checkToken,
    setAuthenticated
  }
}