<template>
    <v-container class="">
        <v-snackbar v-model="snackbar.show" :timeout="snackbar.timeout" :color="snackbar.color" location="top right">{{ snackbar.text }}</v-snackbar>

        <v-container>
            <v-row>
                <v-col cols="12" sm="12" md="4" lg="3">
                    <KPI color="#ad2656" title="Total Customers" icon="mdi-account-group" :modelValue="kpi['customers']"></KPI>
                </v-col>
                <v-col cols="12" sm="12" md="4" lg="3">
                    <KPI color="#eeb229" title="Total Projects" icon="mdi-briefcase" :modelValue="kpi['projects']"></KPI>
                </v-col>
                <v-col cols="12" sm="12" md="4" lg="3">
                    <KPI color="green" title="Thumbtack Clicks" icon="mdi-cursor-default-click" :modelValue="kpi['clicks']"></KPI>
                </v-col>
                <v-col cols="12" sm="12" md="4" lg="3">
                    <KPI color="primary" title="Total Impressions" icon="mdi-counter" :modelValue="kpi['hits']"></KPI>
                </v-col>
            </v-row>
        </v-container>

        <v-container>
            <v-card-title class="text-center">Projects (Last 30 Days)</v-card-title>
            <v-card height="300">
                <Bar :data="chartData" :options="{responsive: true, maintainAspectRatio: false}" :key="rerender" />
            </v-card>
        </v-container>
    </v-container>
</template>

<script setup>
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const app = useNuxtApp()
const kpi = ref({'customers': 0, 'projects': 0, 'clicks': 0, 'hits': 0})
const rerender = ref(0)
const chartData = ref({
    labels: [],
    datasets: [{
        label: 'Projects',
        backgroundColor: 'rgb(0, 132, 255)',
        data: [],
    },],
})

const getKPIs = () => {
     app.$axios.post(app.$apiEndpoint, {
        operation: 'select',
        table: 'reports',
        fields: [
            'COUNT(*) AS projects',
            '(SELECT COUNT(*) FROM `leads` WHERE `deleted` IS NULL) AS clicks',
            '(SELECT COUNT(*) FROM `customers` WHERE `deleted` IS NULL) AS customers',
            '(SELECT COUNT(*) FROM `hits` WHERE `deleted` IS NULL) AS hits',
        ],
        where: [{'deleted':{'is':null}}]
    }).then(res => {
        kpi.value = res.data.data[0]
    })
}
const getChartData = () => {
    app.$axios.post(app.$apiEndpoint, {
        operation: 'select',
        table: 'reports',
        fields: ['COUNT(*) AS number', 'DATE(created) AS dt'],
        where: [{'deleted':{'is':null}}],
        group: ['DATE(created)'],
        limit: 30
    }).then(res => {
        if(res.data?.data?.length > 0) {
            let labels = []
            let data = []
            for(let i=0; i<res.data.data.length; i++) {
                labels.push(res.data.data[i]['dt'])
                data.push(res.data.data[i]['number'])
            }

            chartData.value.labels = labels
            chartData.value.datasets[0].data = data

            rerender.value++

            console.log(chartData.value)
        }
    })
}

const snackbar = ref({
    show: false,
    text: null,
    color: 'success',
    timeout: 2000
})

getKPIs()
getChartData()

definePageMeta({
  layout: 'admin'
})
</script>