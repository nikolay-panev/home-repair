<template>
    <v-container fluid class="pa-0 mt-4">
        <!--
        <v-data-table :items="props.data" :headers="headers" :group-by="[{key: 'area'}]" :items-per-page="-1" hide-default-footer density="compact" no-data-text="Estimate not available at this stage">
            <template #group-header="{ item, columns, toggleGroup, isGroupOpen }">
                <template :ref="(el) => { groupHeaders[item.value] = { item, toggleGroup, isGroupOpen } }" />
                    <tr>
                        <td :colspan="columns.length">
                            <v-btn :icon="isGroupOpen(item) ? '$expand' : '$next'" size="small" variant="plain" @click="toggleGroup(item)"></v-btn>
                            {{ item.value }} ({{ item.items.length }})
                        </td>
                    </tr>
            </template>
            <template #body.append>
                <tr v-if="props.data?.length > 0">
                    <td></td>
                    <td><strong>Total</strong></td>
                    <td></td>
                    <td></td>
                    <td><strong>{{ parseFloat(sumField('total_cost')).toFixed(2) }}</strong></td>
                </tr>
            </template>
        </v-data-table>
        -->
        <v-alert border="start" color="primary" variant="outlined" class="pa-1 pl-4">
            <span class ="text-caption text-justify">Based on the information you provided and the photos you uploaded, here are some home improvement projects you may want to consider. These prices are suggestions. Actual prices may vary depending on the condition of your home and the materials you select.</span>
        </v-alert>

        <v-expansion-panels class="mt-6" variant="accordion" multiple v-model="panel">
            <v-expansion-panel v-for="(item, key) in props.data" :key="key">
                <v-expansion-panel-title class="px-2">
                    <span><v-icon class="mr-2">{{ getIcon(item.room) }}</v-icon> <strong>{{ item.name }}</strong> - ${{ item.total_cost }}</span>
                </v-expansion-panel-title>
                <v-expansion-panel-text class="pa-0">
                    <table width="100%" cellspacing="5">
                        <thead>
                            <tr>
                                <th class="text-left">Description</th>
                                <th class="text-left">Qty</th>
                                <th class="text-left">PPU</th>
                                <th class="text-left">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, num) in item.data" :key="num">
                                <td class="text-body-2">{{ row.description }}</td>
                                <td class="text-body-2">{{ row.quantity }}</td>
                                <td class="text-body-2">{{ row.ppu }}</td>
                                <td class="text-body-2">{{ row.total_cost }}</td>
                            </tr>
                        </tbody>
                    </table>
                </v-expansion-panel-text>
            </v-expansion-panel>
        </v-expansion-panels>

        <p class="text-caption text-justify mt-6"><strong>Pro tip: </strong>Use these figures as a starting point for your conversation with our top-ranked pros.</p>
            <p class="text-center py-4"><strong>Click to get connected</strong></p>
    </v-container>
</template>

<script setup>
import { rooms } from '~/rooms';

const app = useNuxtApp()
const props = defineProps(['data'])

const headers = ref([
    {title: "", key: 'data-table-group'},
    {title: "Description", key: "description"},
    {title: "Qantity", key: "quantity"},
    {title: "PPU", key: "ppu"},
    {title: "Total Pro Cost", key: "total_cost"},
])
const groupHeaders = ref([])

const panel = ref([])

const toggleGroupHeaders = () => {
    Object.values(groupHeaders.value).forEach(header => {
        if(!header.isGroupOpen(header.item)) header.toggleGroup(header.item)
    })
}

const sumField = (key) => {
    return props.data.reduce((a, b) => a + (b[key] || 0), 0)
}

const getIcon = (room) => {
    return rooms.value.filter(item => item.value == room)[0]['icon']
}

onMounted(() => {
   setTimeout(toggleGroupHeaders(), 500)
})
</script>

<style>
.v-expansion-panel-text__wrapper {
    padding:0 !important;
}
</style>