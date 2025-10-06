
import '../assets/main.css'
import '../css/app.css';

import { createApp } from 'vue'
import App from './App2.vue'
import router from './router'
import Antd from 'ant-design-vue'
import 'ant-design-vue/dist/reset.css'
import { createPinia } from 'pinia'

// Import the functions you need from the SDKs you need
import { initializeApp } from '@firebase/app'
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: 'AIzaSyDnXXJ1R-lkoheA8LEJuHLzy2kjUvcC4-w',
  authDomain: 'myproject-35bc3.firebaseapp.com',
  projectId: 'myproject-35bc3',
  storageBucket: 'myproject-35bc3.firebasestorage.app',
  messagingSenderId: '1064017138140',
  appId: '1:1064017138140:web:b294ccd4f2b9c9762abf19',
}

initializeApp(firebaseConfig)

const app = createApp(App)

app.use(router)

app.use(Antd).mount('#app')

// const pinia = createPinia()

// app.use(pinia)
