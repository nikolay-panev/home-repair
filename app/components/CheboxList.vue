<template>
    <v-container fluid>
        <v-row density="compact" :class="hasError ? 'text-error' : ''">
            <slot name="label">
                <div v-if="label" :style="'color:'+color">{{label}}</div>
            </slot>
        </v-row>
        <v-row density="compact" v-if="hasError">
            <slot name="message"><span class="text-error caption">{{errorBucket[0]}}</span></slot>
        </v-row>
        <v-row density="compact">
            <v-col v-for="(item, index) of items" :cols="cols ? cols : 'auto'" :sm="sm ? sm : 6" :md="md ? md : 3" :lg="lg ? lg : 2" :xl="xl ? xl : 2" class="px-0">
                <v-checkbox multiple v-model="selectedItems" :label="item[itemTitle] ? item[itemTitle] : item" :id="String(item[itemValue] ? item[itemValue] : item)" :value="item[itemValue] ? item[itemValue] : item" @change="onChange" hide-details :rules="rules" :value-comparator="comparator"></v-checkbox>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref, watch} from 'vue'

const props = defineProps({
    items: {type: Array, required: true},
    modelValue: {required: true, default: () => JSON.stringify([])},
    itemTitle: {type: String, default: () => 'text'},
    itemValue: {type: [Number, String], default: () => 'value'},
    label: {type: String},
    color: {type: String, required: false, default: () => 'rgba(0,0,0,0.6);'},
    cols: {type: [String, Number]},
    sm: {type: [String, Number]},
    md: {type: [String, Number]},
    lg: {type: [String, Number]},
    xl: {type: [String, Number]},
    rules: {type: Array, required: false}
})
const emit = defineEmits(["update:modelValue"])

const selectedItems = ref([])
const errorBucket = ref ([])
const valid = ref(false)

const onChange = () => {
    emit("update:modelValue", JSON.stringify(selectedItems.value))
    validate()
}
const validate = () => {
    errorBucket.value = []
    let value = selectedItems.value

    if(props.rules?.length) {
        for(let index = 0; index < props.rules.length; index++) {
            const rule = props.rules[index]
            const valid = typeof rule === 'function' ? rule(value) : rule

            if(valid === false || typeof valid === 'string') {
                errorBucket.value.push(valid || '')
            }
            else if (typeof valid !== 'boolean') {
                console.log(`Rules should return a string or boolean, received '${typeof valid}' instead`)
            }
        }
    }
    
    valid.value = errorBucket.value.length === 0

    return valid.value
}
const comparator = (a, b) => {
    return String(a) === String(b)
}

const hasError = computed(() => {
    return errorBucket.value.length > 0
})

watch(() => props.modelValue, (newValue) => {
    if(newValue) {
        selectedItems.value = JSON.parse(props.modelValue)
    }
}, {immediate: true})
</script>
<style scoped>
.v-input--selection-controls {
    padding-top:0;
}
</style>