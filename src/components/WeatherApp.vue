<template>
  <div :class="bgClass" class="min-h-screen p-6 transition-all duration-700">

    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold text-white drop-shadow">
        🌤 Weather Dashboard
      </h1>

      <button
        @click="toggleDark"
        class="bg-white/20 backdrop-blur px-4 py-2 rounded-lg text-white hover:bg-white/30 transition"
      >
        🌙 {{ dark ? 'Light' : 'Dark' }} Mode
      </button>
    </div>

    <!-- Search -->
    <div class="flex justify-center mb-8 gap-2">
      <input
        v-model="city"
        @keyup.enter="getWeather"
        placeholder="Enter city..."
        class="w-1/2 px-4 py-3 rounded-lg shadow focus:outline-none"
      />
      <button
        @click="getWeather"
        class="bg-black text-white px-6 py-3 rounded-lg shadow hover:scale-105 transition"
      >
        Search
      </button>
    </div>

    <!-- Loading Spinner -->
    <div v-if="loading" class="flex justify-center mt-10">
      <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-white"></div>
    </div>

    <!-- Current Weather -->
    <div
      v-if="current && !loading"
      class="max-w-md mx-auto bg-white/20 backdrop-blur-lg rounded-2xl shadow-xl p-6 text-center text-white animate-fade"
    >
      <img :src="getIcon(current.weather[0].main)" class="w-24 mx-auto mb-2" />
      <h2 class="text-xl font-semibold">
        {{ current.name }}, {{ current.sys.country }}
      </h2>
      <div class="text-4xl font-bold my-2">
        {{ current.main.temp.toFixed(1) }}°C
      </div>
      <div class="capitalize">
        {{ current.weather[0].description }}
      </div>
      <div class="text-sm mt-2">
        Humidity: {{ current.main.humidity }}% |
        Wind: {{ current.wind.speed }} m/s
      </div>
    </div>

    <!-- Hourly Chart -->
    <div
      v-if="current && !loading"
      class="mt-10 max-w-3xl mx-auto bg-white/20 backdrop-blur-lg p-6 rounded-2xl"
    >
      <h2 class="text-white text-lg mb-4 text-center">
        24 Hour Temperature
      </h2>
      <canvas id="hourlyChart"></canvas>
    </div>

    <!-- 5 Day Forecast -->
    <div
      v-if="forecast.length && !loading"
      class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-6 mt-10"
    >
      <div
        v-for="day in forecast"
        :key="day.dt"
        class="bg-white/20 backdrop-blur-lg text-white p-4 rounded-2xl text-center shadow hover:scale-105 transition"
      >
        <div class="font-semibold">
          {{ formatDate(day.dt) }}
        </div>

        <img :src="getIcon(day.weather[0].main)" class="w-14 mx-auto my-2" />

        <div class="text-lg font-bold">
          {{ day.main.temp.toFixed(1) }}°C
        </div>

        <div class="text-sm capitalize">
          {{ day.weather[0].description }}
        </div>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="text-white text-center mt-6">
      {{ error }}
    </div>

  </div>
</template>

<script>
import { ref, computed } from 'vue'
import api from '../api.js'
import Chart from 'chart.js/auto'

export default {
  setup() {
    const city = ref('')
    const current = ref(null)
    const forecast = ref([])
    const error = ref('')
    const dark = ref(false)
    const loading = ref(false)

    let chartInstance = null

    const getWeather = async () => {
      if (!city.value.trim()) return

      loading.value = true
      error.value = ''

      try {
        const resCurrent = await api.get('/weather/current', {
          params: { city: city.value }
        })

        const resForecast = await api.get('/weather/forecast', {
          params: { city: city.value }
        })

        current.value = resCurrent.data

        const list = resForecast.data.list

        forecast.value = list.filter((_, i) => i % 8 === 0)

        buildChart(list.slice(0, 8))

      } catch (err) {
        error.value = 'City not found or API error'
        current.value = null
        forecast.value = []
      } finally {
        loading.value = false
      }
    }

    const buildChart = (hourlyData) => {
      const ctx = document.getElementById('hourlyChart')

      const labels = hourlyData.map(item => {
        const date = new Date(item.dt * 1000)
        return date.getHours() + ':00'
      })

      const temps = hourlyData.map(item => item.main.temp)

      if (chartInstance) {
        chartInstance.destroy()
      }

      chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Temperature °C',
              data: temps,
              tension: 0.4,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              labels: { color: 'white' }
            }
          },
          scales: {
            x: { ticks: { color: 'white' } },
            y: { ticks: { color: 'white' } }
          }
        }
      })
    }

    const formatDate = (timestamp) => {
      const d = new Date(timestamp * 1000)
      return d.toLocaleDateString('en-US', { weekday: 'short' })
    }

    const getIcon = (condition) => {
      const icons = {
        Clear: 'https://cdn-icons-png.flaticon.com/512/869/869869.png',
        Clouds: 'https://cdn-icons-png.flaticon.com/512/414/414825.png',
        Rain: 'https://cdn-icons-png.flaticon.com/512/1163/1163624.png',
        Drizzle: 'https://cdn-icons-png.flaticon.com/512/1163/1163624.png',
        Thunderstorm: 'https://cdn-icons-png.flaticon.com/512/1146/1146869.png',
        Snow: 'https://cdn-icons-png.flaticon.com/512/642/642102.png',
        Mist: 'https://cdn-icons-png.flaticon.com/512/4005/4005901.png'
      }
      return icons[condition] || icons['Clouds']
    }

    const bgClass = computed(() => {
      if (!current.value)
        return 'bg-gradient-to-r from-blue-500 to-purple-600'

      const weather = current.value.weather[0].main

      if (weather === 'Clear')
        return 'bg-gradient-to-r from-yellow-400 to-orange-500'
      if (weather === 'Rain')
        return 'bg-gradient-to-r from-gray-600 to-blue-900'
      if (weather === 'Clouds')
        return 'bg-gradient-to-r from-gray-400 to-gray-700'
      if (weather === 'Snow')
        return 'bg-gradient-to-r from-blue-200 to-white'

      return 'bg-gradient-to-r from-blue-500 to-purple-600'
    })

    const toggleDark = () => {
      dark.value = !dark.value
      document.documentElement.classList.toggle('dark')
    }

    return {
      city,
      current,
      forecast,
      error,
      dark,
      loading,
      getWeather,
      formatDate,
      getIcon,
      bgClass,
      toggleDark
    }
  }
}
</script>

<style>
body {
  font-family: 'Inter', sans-serif;
}
</style>