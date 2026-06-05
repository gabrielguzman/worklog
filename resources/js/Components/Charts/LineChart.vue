<script setup>
import { Line } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler } from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

defineProps({
    chartData: Object,
    chartOptions: {
        type: Object,
        default: () => ({})
    }
})
</script>

<template>
    <Line :data="chartData" :options="chartOptions" />
</template>
