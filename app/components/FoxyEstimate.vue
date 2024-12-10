<template>
    <v-container fluid class="pa-0 mt-4" v-if="props.data?.length > 0">
        <v-alert border="start" color="primary" variant="outlined" class="pa-1 pl-4">
            <span class ="text-caption text-justify">Based on the information you provided and the photos you uploaded, here are some home improvement projects you may want to consider. These prices are suggestions. Actual prices may vary depending on the condition of your home and the materials you select.</span>
        </v-alert>

        <v-expansion-panels class="mt-6" variant="accordion" multiple v-model="panel">
            <v-expansion-panel v-for="(item, key) in props.data" :key="key" class="my-1">
                <v-expansion-panel-title class="px-2">
                    <span><v-icon class="mr-2">{{ getIcon(item.room) }}</v-icon> <strong>{{ item.name }}</strong> - ${{ parseFloat(item.total_cost).toFixed(2) }}</span>
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
                                <td class="text-body-2">{{ parseFloat(row.total_cost).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </v-expansion-panel-text>
            </v-expansion-panel>
        </v-expansion-panels>

        <p class="text-caption text-justify mt-6"><strong>Pro tip: </strong>Use these figures as a starting point for your conversation with our top-ranked pros.</p>
        <p class="text-center py-4"><strong>Click to get connected</strong></p>
    </v-container>
    <v-container fluid class="pa-0 mt-4"  v-else>
        <v-alert border="start" color="error" variant="outlined" class="pa-1 pl-4">
            <span class ="text-caption text-justify">Based on the information you provided and the photos you uploaded, we were unable to provide preliminary costs. You can still proceed and connect with qualified pros. They'll provide tailored estimates for your specific project needs.</span>
        </v-alert>

        <p class="text-center py-4"><strong>Click to get connected</strong></p>
    </v-container>
</template>

<script setup>
import { rooms } from '~/rooms';

const props = defineProps(['data'])
const panel = ref([])

const getIcon = (room) => {
    return rooms.value.filter(item => item.value == room)[0]['icon']
}
</script>

<style>
.v-expansion-panel-text__wrapper {
    padding:0 !important;
}
</style>