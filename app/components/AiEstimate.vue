<template>
    <v-container fluid class="pa-0 mt-4">
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
        </v-data-table>
    </v-container>
</template>

<script setup>
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


onMounted(() => {
    Object.values(groupHeaders.value).forEach(header => {
        header.toggleGroup(header.item)
    })
})
</script>