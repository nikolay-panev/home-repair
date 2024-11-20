<template>
    <v-container class="pa-0 ma-0">
        <v-card flat class="text-center cursor-pointer border pa-0 ma-0" @click="uploadFiles()" v-on:drop.prevent="onDrop($event);" v-on:dragover.prevent>
            <div class="justify-self-center py-12">
                <v-icon size="72" class="text-lightblue">mdi-cloud-upload-outline</v-icon>
                <p class="text-lightblue">Drag & drop or click here</p>
            </div>

            <v-progress-linear indeterminate class="ma-1" :active="loading"></v-progress-linear>
        </v-card>
        
        <v-card flat class="mt-2 rounded-0">
            <v-row>
                <v-col v-for="(obj, key) in images" :key="key" cols="12" sm="12" md="6" lg="4">
                    <v-img :src="obj.url">
                        <v-btn variant="flat" size="x-small" icon="mdi-close" @click="removeItem(key)" color="error" class="rounded-0 float-right" title="Remove image" aria-label="Remove image"></v-btn>
                    </v-img>
                    <v-select v-model="obj.tag" chips clearable variant="underlined" :items="rooms" :error="obj.tag==null" :error-messages="obj.tag==null? 'This is a required field' : ''"></v-select>
                </v-col>
            </v-row>
        </v-card>
    </v-container>
</template>

<script setup>
import heic2any from "heic2any"
import { rooms } from '~/rooms';

const props = defineProps({
    modelValue: {required: true, default: () => []},
    autoResize: {type: Boolean, default: () => false, required: false},
    minSize: {type: Number, default: () => 400, required: false},
    maxSize: {type: Number, default: () => -1, required: false},
    address: {type: String, default: () => "", required: false},
    group_id: {type: String, required: true}
})
const emit = defineEmits(["update:modelValue", "upload-error"])

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
    let files = Array.from(ev.dataTransfer.files).filter(item => ["jpg", "jpeg", "gif", "png", "bmp", "tiff", "tif", "webp", "heic", "heif"].indexOf(item.name.split(".").pop().toLowerCase()) != -1)

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
   
    // client-side resize & convert all images
    let promises = [];
    for(let i=0; i<files.length; i++) {
        let resized = resize(files[i])
        if(resized != false) promises.push(resized)
    }

    _upload(promises)
}

const _upload = (promises) => {
    let uploaded = 0

    loading.value = true

    Promise.all(promises).then((results) => {
        let successful = results.filter(item => item != false)

        const formData = new FormData()
        for(let i=0; i<successful.length; i++) {
            formData.append("operation", "imageUpload")
            formData.append("file[]", DataURIToBlob(successful[i].base64), successful[i].name)
        }

        app.$axios.post(app.$apiEndpoint+"upload.php", formData).then(res => {
            if(res.data?.items.length > 0) {
                uploaded += res.data.items.length

                createImages(res.data.items.map(img => app.$apiEndpoint + img)).then((r) => {
                    images.value = r.data

                    emit('update:modelValue', images.value)
                    loading.value = false
                }).catch(() => {
                    emit('upload-error', "Unable to create image(s)")
                    loading.value = false
                })
            }
            else {
                emit('upload-error', "Unable to upload image(s)")
                loading.value = false
            }
        }).catch(() => {
            emit('upload-error', "Unable to upload image(s)")
            loading.value = false
        })
    })
}

const createImages = (img) => {
    return app.$axios.post(app.$apiEndpoint + "request.php", {
        'api': 'create_image',
        'group_id': props.group_id,
        'images': img
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
.text-lightblue {
    color: #56adff;
}
</style>