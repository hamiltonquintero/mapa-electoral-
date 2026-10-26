import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/main.css'

// MapLibre GL JS CSS
import 'maplibre-gl/dist/maplibre-gl.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
