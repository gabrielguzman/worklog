<script setup>
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

defineProps({
    chartData: Object,
    chartOptions: {
        type: Object,
        default: () => ({})
    }
})
</script>

<template>
    <Bar :data="chartData" :options="chartOptions" />
</template>
