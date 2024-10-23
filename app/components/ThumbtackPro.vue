<template>
    <v-card :elevation="0" class="pa-2 my-4 cursor-pointer" color="rgb(245,245,245)" @click.prevent="clickIntercept">
        <v-row>
            <v-col cols="3">
                <v-img :src="props.pro.image_url" rounded="lg" :eager="true"></v-img>
            </v-col>
            <v-col cols="9">
                <p class="text-uppercase font-weight-bold text-body-2">{{ props.pro.business_name }}</p>
                <p class="text-body-2"><v-icon>mdi-map-marker-outline</v-icon> {{ props.pro.business_location }}</p>
                <v-container fluid class="pa-1">
                    <p><v-rating :length="5" size="24" half-increments readonly :model-value="Math.round(props.pro.rating,1)" color="orange" density="compact"></v-rating>
                   <span class="text-caption ml-2 text-super" title="Reviews">({{ props.pro.num_reviews }})</span></p>
                </v-container>
                <div>
                    <v-chip v-for="(text, key) in props.pro.pills" :key="key" size="x-small" color="primary" class="mx-1">{{ pills[text]??text }}</v-chip>
                </div>
            </v-col>
        </v-row>
        <v-row dense>
            <v-col cols="12">
                
                <v-card-text class="pt-0">
                    <p>
                        <span class="font-weight-bold">${{ props.pro?.quote?.starting_cost }}</span>
                        <span class="font-weight-bold" v-if="props.pro?.quote?.cost_unit"> / {{ props.pro.quote.cost_unit }}</span>
                        <span v-else> Starting cost</span>
                    </p>
                    
                    <p>{{ props.pro.introduction }}</p>
                </v-card-text>
                <v-btn size="small" block="" variant="tonal" color="primary">Connect</v-btn>
            </v-col>
        </v-row>
    </v-card>

    <v-dialog :fullscreen="true" v-if="dialog" v-model="dialog">
        <v-card :flat="true">
            <v-toolbar density="compact" color="primary" :elevation="0">
                <v-toolbar-title>
                    <span>Thumbtack Pro</span>
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn icon variant="plain" size="small" @click="dialog=false"><v-icon>mdi-close</v-icon></v-btn>
            </v-toolbar>
            <v-card-text class="px-1">
                <iframe sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox" :src="props.pro.request_flow_iframe_url"></iframe>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script setup>
const props = defineProps(['pro'])
const app = useNuxtApp()
const route = useRoute()
const pills = ref({
    'popular': "In high demand",
    'licensed': "Licensed pro",
    'low_price': "Great value",
    'remote': "Remote",
})
const dialog = ref(false)

const clickIntercept = () => {
    app.$axios.post(app.$apiEndpoint, {
        operation: "insert",
        table: "leads",
        data: [{"customer": route.params.src, "event": "click", "url": props.pro.request_flow_iframe_url}]
    })
    
    //window.open(props.pro.request_flow_iframe_url, "_blank")
    dialog.value = true
}
const handleMessageEvent = (event) => {
    if(event.data === 'THUMBTACK_RF_CLOSE') {
        dialog.value = false
    }
}

onMounted(() => {
    window.addEventListener('message', handleMessageEvent)
})
</script>

<style scoped>
iframe {
    border: none;
    height: 100%;
    width: 100%;
    overflow-y: auto;
}
.text-super {
    vertical-align: super;
}
</style>