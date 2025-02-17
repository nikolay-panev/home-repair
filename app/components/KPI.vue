<template>
    <v-card :color="color" dark height="180" elevation="12">
        <v-list-item  class="text-center" three-line>
            <v-list-item-title class="text-overline my-3 text-white"><span>{{title}}</span></v-list-item-title>
            <v-list-item-title class="headline mb-2 text-white">
                <v-row>
                    <v-col cols="12">
                        <v-icon size="42" color="white">{{icon}}</v-icon> 
                    </v-col>
                    <v-col cols="12">
                        {{valuePrefix}}{{parseFloat(modelValue).toFixed(0)}} <span class="text-caption">{{valueSuffix}}</span>
                    </v-col>
                </v-row>
            </v-list-item-title>
            <v-divider></v-divider>
            <div v-if="change">
                <v-list-item-subtitle><v-icon :color="iconColor" :style="{transform: 'rotate('+iconRotate+'deg)'}" small>mdi-triangle</v-icon> {{changePrefix}}{{change}}{{changeSuffix}}</v-list-item-subtitle>
                <v-list-item-subtitle>{{changeText}}</v-list-item-subtitle>
            </div>
        </v-list-item>
    </v-card>

</template>

<script setup>
defineProps({
    title: {type: String, required:false},
    color: {type: String, required: false, default: "#29c0b1"},
    modelValue: {type: [Number, String], default: ""},
    icon: {type: String, default: ""},
    valuePrefix: {type: String, required:false, default:null},
    valueSuffix: {type: String, required:false, default:null},
    change: {type: Number, required:false, default:0},
    changePrefix: {type: String, required:false, default:null},
    changeSuffix: {type: String, required:false, default:null},
    changeText: {type: String, required: false, default:null}
})

const iconColor = computed(() => {
    if(change == 0) return "yellow"
    else if(change > 0) return "green"
    else return "red"
})
const iconRotate = computed(() => {
    if(change == 0) return 90
    else if(change > 0) return 0
    else return 180
})
</script>