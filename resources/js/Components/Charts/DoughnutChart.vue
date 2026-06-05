<script setup>
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

defineProps({
    chartData: Object,
    chartOptions: {
        type: Object,
        default: () => ({})
    }
})
</script>

<template>
    <Doughnut :data="chartData" :options="chartOptions" />
</template>
