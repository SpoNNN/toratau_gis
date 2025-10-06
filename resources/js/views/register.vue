<template>
    <h1 class="text-white text-xl">Создать аккаунт</h1>
    <p><input class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 pr-10 shadow-sm 
                   focus:border-blue-500 focus:ring-2 focus:ring-blue-300 
                   transition duration-300 ease-in-out" 
                   type="text" placeholder="Email" v-model="email" /></p>

    <p class="relative">
        <input 
            class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 shadow-sm 
                   focus:border-blue-500 focus:ring-2 focus:ring-blue-300 
                   transition duration-300 ease-in-out pr-10" 
            :type="showPassword ? 'text' : 'password'" 
            placeholder="Пароль" v-model="password" 
        />

        <!-- Кнопка "глаз" для скрытия/показа пароля -->
        <button 
            type="button"
            class="absolute inset-y-0 right-1 flex items-center px-2 text-gray-600"
            @click="togglePassword"
        >
        <img v-if="showPassword" src="/open_eye.svg" alt="Hide password" class="h-5 w-5">
        <img v-else src="/hide_eye.svg" alt="Show password" class="h-5 w-5">
        </button>
    </p>

    <p>
        <button 
            @click="register" 
            class="bg-blue-500 text-white font-semibold px-4 py-2 rounded-lg 
                   hover:bg-blue-600 transition duration-300 ease-in-out 
                   shadow-md hover:shadow-lg active:scale-95"> Создать
        </button>
    </p>
</template>

<script setup>
import { ref } from 'vue';
import { getAuth, createUserWithEmailAndPassword } from 'firebase/auth';


const email = ref('');
const password = ref('');
const showPassword = ref(false); // Флаг для скрытия/показа пароля

// Функция переключения пароля
const togglePassword = () => {
    showPassword.value = !showPassword.value;
};


const register = () => {
    const auth = getAuth(); // Исправил getAut() -> getAuth()

    createUserWithEmailAndPassword(auth, email.value, password.value)
        .then((userCredential) => {
            console.log("Successfully registered!");
            console.log(userCredential.user); // Теперь userCredential содержит пользователя
            // window.location.href = '/';
            return auth.signOut(); // Разлогиниваем пользователя после регистрации
        })
        .then(() => {
            console.log("User signed out after registration");
            window.location.href = '/'; // Перенаправляем на главную страницу
        })
        .catch((error) => {
            console.log("Error:", error.code);
            alert(error.message);
        });
};
</script>
