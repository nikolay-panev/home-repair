<template>
    <v-container>
        <v-card flat class="mx-6 text-center cursor-pointer border" @click="uploadFiles()" v-on:drop.prevent="onDrop($event);" v-on:dragover.prevent>
            <div class="justify-self-center py-12"><v-icon size="72">mdi-cloud-upload-outline</v-icon></div>
        </v-card>
        <v-card flat class="mt-2 rounded-0">
            <v-row v-if="!!loading">
                <v-col cols="12">
                    <v-progress-linear indeterminate class="ma-1" :active="!!loading"></v-progress-linear>
                </v-col>
            </v-row>
            <v-row>
                <v-col v-for="(val, key) in images" :key="key" cols="6">
                    <v-img :src="val" v-if="unsupportedFormats.indexOf(String(val.split('.').pop().toLowerCase())) == -1">
                        <v-btn variant="flat" size="x-small" icon="mdi-close" @click="removeItem(key)" color="error" class="rounded-0 float-right" title="Remove image" aria-label="Remove image"></v-btn>
                    </v-img>
                    <v-img v-else src="images/no-preview.png">
                        <v-btn variant="flat" size="x-small" icon="mdi-close" @click="removeItem(key)" color="error" class="rounded-0 float-right" title="Remove image" aria-label="Remove image"></v-btn>
                    </v-img>
                </v-col>
            </v-row>
        </v-card>
    </v-container>
</template>

<script setup>
import heic2any from "heic2any"

const props = defineProps({
    modelValue: {required: true, default: () => []},
    autoResize: {type: Boolean, default: () => false, required: false},
    minSize: {type: Number, default: () => 400, required: false},
    maxSize: {type: Number, default: () => -1, required: false},
})
const emit = defineEmits(["update:modelValue"])

const app = useNuxtApp()
const loading = ref(false)
const images = ref([])
const unsupportedFormats = ref(["svg", "tiff", "tif", "bmp"])

const uploadFiles = () => {
    let input = null
    input = document.createElement("input")
    input.type = "file"
    input.accept = "image/*,image/heic,image/heif"
    input.multiple = true
    input.onchange = (event) => upload(event)
    input.click()
}
const onDrop = (ev) => {
    let files = Array.from(ev.dataTransfer.files).filter(item => ["jpg", "jpeg", "gif", "png", "bmp", "tiff", "tif", "svg", "webp", "heic", "heif"].indexOf(item.name.split(".").pop().toLowerCase()) != -1)

    // client-side resize all images
    let promises = [];
    for(let i=0; i<files.length; i++) {
        let resized = resize(files[i])
        if(resized != false) promises.push(resized)
    }

    _upload(promises)
}
const toBase64 = (file) => new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = () => resolve(reader.result)
    reader.onerror = reject
})
const resize = (image) => {
    return new Promise(async function(resolve) {
        let parts = image.name.split(".")
        let ext = null
        let name = null

        if(parts.length > 1) ext = parts.pop()

        // Apple's heic/heif images are converted to jpeg using 3rd party library heic2any
        if(ext == 'heic' || ext == 'heif') {
            image = await heic2any({ blob: image, toType: "image/jpeg" })
        }
        // The following image file types are not natively supported by canvas element and will be converted server-side
        if(unsupportedFormats.value.indexOf(String(ext).toLowerCase()) != -1) {
            parts.push(ext)
            name = parts.join(".")
            let b64 = await toBase64(image)

            resolve({'name': name, 'base64': b64})
        }

        let canvas = document.createElement('canvas')
        let img = new Image()
        const url = window.URL || window.webkitURL

        img.src = url.createObjectURL(new Blob([image]))
        img.onload = function(e) {
            let aspectRatio = Math.max(e.target.width, e.target.height)/Math.min(e.target.width, e.target.height)
            let canvasWidth = 0
            let canvasHeight = 0

            if((e.target.width < props.minSize || e.target.height < props.minSize) && props.minSize > 0) {
                alert("Image " + image.name + " too small. Please upload an image with a minimum resolution of "+props.minSize+"x"+props.minSize+"px")
                resolve(false)
            }

            if(e.target.width > e.target.height) {
                // Landscape image
                if(props.autoResize && e.target.width > props.maxSize && props.maxSize != -1) {
                    // Scale down
                    canvasWidth = props.maxSize
                    canvasHeight = parseFloat(props.maxSize/aspectRatio).toFixed(0)
                }
                else {
                    // Keep original size
                    canvasWidth = e.target.width
                    canvasHeight = e.target.height
                }
            }
            else {
                // Portrait image
                if(props.autoResize && e.target.height > props.maxSize && props.maxSize != -1) {
                    // Scale down
                    canvasWidth = parseFloat(props.maxSize/aspectRatio).toFixed(0)
                    canvasHeight = props.maxSize
                }
                else {
                    // Keep original size
                    canvasWidth = e.target.width
                    canvasHeight = e.target.height
                }
            }
                    
            canvas.width = canvasWidth
            canvas.height = canvasHeight
            let ctx = canvas.getContext('2d')
            ctx.drawImage(img, 0, 0, canvasWidth, canvasHeight)

            parts.push("jpg")
            name = parts.join(".")
            // promise returns base64 of resized jpeg image
            resolve({'name': name, 'base64': canvas.toDataURL('image/jpeg', 0.85)})
        }
    })
}
const DataURIToBlob = (dataURI) => {
    const parts = dataURI.split(',')
    const mimeType = parts[0].replace('data:', '').replace(';base64,', '')
    const binary = atob(parts[1])
    let array = new Uint8Array(binary.length)

    for(let i=0; i<binary.length; i++) {
        array[i] = binary.charCodeAt(i)
    }

    return new Blob([array], { type: mimeType })
}
const upload = (event) => {
    let files = event.target.files
   
    // client-side resize all images
    let promises = [];
    for(let i=0; i<files.length; i++) {
        let resized = resize(files[i])
        if(resized != false) promises.push(resized)
    }

    _upload(promises)
}

const _upload = (promises) => {
    let chunkSize = 2
    let uploaded = 0

    loading.value = true

    Promise.all(promises).then((results) => {
        let successful = results.filter(item => item != false)

        // Split files into chunks
        for(let i=0; i<successful.length; i+=chunkSize) {
            const formData = new FormData();
            formData.append("operation", "imageUpload");
            for(let j=i; j<Math.min(i+chunkSize, successful.length); j++) {
                formData.append("file[]", DataURIToBlob(successful[j].base64), successful[j].name)
            }

            app.$axios.post(app.$apiEndpoint+"upload.php", formData).then(res => {
                if(res.data?.items.length > 0) {
                    uploaded += res.data.items.length

                    res.data.items.forEach(img => {
                        images.value.push(app.$apiEndpoint + img)
                    })
                }
            }).finally(() => {
                emit('update:modelValue', images.value)
                if(files.length == uploaded) loading.value = false
            }).catch(() => {
                loading.value = false
            })
        }
    }).finally(() => {
        loading.value = false
    })
}
const removeItem = (index) => {
    images.value.splice(index,1)[0]
    emit('update:modelValue', images.value)
}

watch(() => props.modelValue, (newValue) => {
    if(newValue) {
        images.value = newValue
    }
}, {immediate: true})
</script>

<style scoped>
.drag-item img {
    width: 100%;
}

.drag-item {
    position: relative !important;
    opacity:1;
}
.remove-item {
    position: absolute;
    top: -7px;
    right: 0px;
    width: 15px;
    background:white;
    border-radius:50%;
    z-index: 5;
}
.no-preview {
    min-height: 280px;
}
</style>