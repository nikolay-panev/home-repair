<template>
    <v-container class="fill-height">
        <app-loader v-if="loading"></app-loader>

        <v-toolbar v-if="step>0 && step<4" color="transparent" class="align-self-start">
            <v-btn variant="plain" @click="goBack()"><v-icon size="24">mdi-arrow-left</v-icon></v-btn>
            <v-spacer></v-spacer>
            <v-progress-linear :model-value="progress" color="primary" height="10" rounded></v-progress-linear>
            <v-spacer></v-spacer>
            <v-btn variant="plain" @click="cancel()"><v-icon size="24">mdi-close</v-icon></v-btn>
        </v-toolbar>
        
        <v-container v-if="step==0" class="align-self-center">
            <h1 class="text-h4 font-weight-black py-4 mb-6">Home repair,<br/>made easy.</h1>
            <v-form ref="form">
                <v-row>
                    <v-col cols="12">
                        <p class="text-h6 font-weight-bold py-2">Get a price. Find a Pro</p>
                        <v-autocomplete variant="outlined" density="compact" label="Describe your project or problem" v-model="project.category" :items="categoryItems" :rules="[fieldRequired]">
                            <template v-slot:append>
                                <v-btn size="48" class="rounded-ts-0 rounded-bs-0 px-6" color="primary" elevation="0" style="margin-left:-20px" @click="goForward()"><v-icon color="white" size="large">mdi-magnify</v-icon></v-btn>
                            </template>
                        </v-autocomplete>
                    </v-col>
                </v-row>
            </v-form>
        </v-container>

        <v-container v-if="step==1" class="align-self-start mt-1">
            <v-form ref="form">
                <v-row>
                    <v-col cols="12">
                        <v-autocomplete variant="outlined" label="Describe your project or problem" v-model="project.category" :items="categoryItems" menu-icon="" prepend-icon="mdi-magnify" clearable="" clear-icon="mdi-close" :rules="[fieldRequired]"></v-autocomplete>
                    </v-col>

                    <v-col cols="12">
                        <v-autocomplete variant="outlined" label="Address" v-model="project.address" :items="addressItems" item-value="value" item-title="title" menu-icon="" prepend-icon="mdi-map-marker-outline" clearable="" clear-icon="mdi-close" no-data-text="Type to search for an address" @update:search="addressFind" @update:modelValue="addressDetails" @click:append="addressReset" :rules="[fieldRequired]">
                            <template v-slot:append-item>
                                <v-list-item><v-img src="images/google_on_white.png" width="42"></v-img></v-list-item>
                            </template>
                        </v-autocomplete>
                    </v-col>

                    <v-col cols="12">
                        <v-alert v-if="alert.show" variant="outlined" :type="alert.type" density="compact">{{ alert.text }}</v-alert>
                    </v-col>
                </v-row>
            </v-form>
        </v-container>

        <v-container v-if="step==2" class="align-self-start">
            <p class="text-h6 font-weight-black text-center my-4">Upload photos</p>

            <foxy-image-upload v-model="project.files" :group_id="project.group_id" @upload-error="showError"></foxy-image-upload>

            <v-alert v-if="alert.show" variant="outlined" :type="alert.type" density="compact">{{ alert.text }}</v-alert>

            <v-form ref="form">
                <v-row>
                    <v-col cols="12">
                        <v-checkbox v-model="project.accept" :rules="[fieldRequired]">
                            <template v-slot:label>
                                I agree to <a href="#" @click.prevent="tosDialog=true" aria-label="Terms of Service" title="Terms of Service" class="ml-1">Terms of Service</a>
                            </template>
                        </v-checkbox>
                    </v-col>
                </v-row>
            </v-form>
        </v-container>

        <v-container v-if="step==3">
            <p class="text-h6 font-weight-black text-center my-4">What's your email?</p>
            <v-form ref="form">
                <v-row>
                    <v-col cols="12">
                        <v-text-field variant="outlined" label="" v-model="project.email" ref="email" clearable="" clear-icon="mdi-close" :rules="[validEmail]">
                            <template v-slot:prepend>
                                <v-btn variant="flat" icon="mdi-email" color="primary"></v-btn>
                            </template>
                        </v-text-field>
                    </v-col>
                </v-row>
            </v-form>
        </v-container>

        <v-container v-if="step==4" class="align-self-start">
            <h1 class="text-h5 text-center font-weight-black py-4">{{ categoryName }}</h1>

            <foxy-estimate :data="estimate"></foxy-estimate>
        </v-container>

        <v-container v-if="step==5" class="align-self-start">
            <h1 class="text-h5 font-weight-black py-4 text-center">Connect with a pro</h1>
            <v-container class="d-flex justify-center">
                <svg class="primary" height="28" width="48" viewBox="0 0 16 16" fill="#009fd9"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0"></path><path fill="#FFF" d="M8.973 10.385a3.71 3.71 0 0 1-.564 1.957L8 13l-.409-.658a3.704 3.704 0 0 1-.564-1.957v-3.14C7.51 6.62 8.231 6.4 8.973 6.4v3.985zM4 5.69V4h8v1.69H4z"></path></svg>
                <p>Powered by Thumbtack</p>
            </v-container>

            <v-container v-if="pros?.length == 0" class="mx-auto">
                <p class="text-center mb-6">No pros found.</p>
                <v-row>
                    <v-col cols="12" class="text-center">
                        <v-btn variant="outlined" class="px-10" :elevation="0" @click="goBack()">Back</v-btn>
                    </v-col>
                </v-row>
            </v-container>

            <thumbtack-pro v-for="(pro, index) in pros" :key="index" :pro="pro"></thumbtack-pro>

            <v-row>
                    <v-col cols="12" class="text-center">
                        <v-btn class="px-6 mt-6" color="primary" :elevation="0" @click="cancel()">Price another project</v-btn>
                    </v-col>
                </v-row>
        </v-container>

        <v-container v-if="step>0 && step<5" class="align-self-end border-t">
            <v-row>
                <v-col cols="5" class="text-left">
                    <v-btn variant="outlined" class="px-10" :elevation="0" @click="goBack()">Back</v-btn>
                </v-col>
                <v-col cols="7" class="text-right">
                    <v-btn color="primary" class="px-10" :elevation="0" @click="goForward()">{{nextBtnText}}</v-btn>
                </v-col>
            </v-row>
        </v-container>
        
        <v-dialog v-model="tosDialog" v-if="tosDialog" fullscreen class="rounded-0">
            <v-card>
                <v-toolbar density="compact" color="primary" :elevation="0" class="rounded-0 pl-2">
                    <span>Terms of Service</span>
                    <v-spacer></v-spacer>
                    <v-btn icon class="rounded-0" size="small" @click="tosDialog=false"><v-icon>mdi-close</v-icon></v-btn>
                </v-toolbar>
                <terms-of-service />
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script setup>
import { computed, watch } from 'vue';
import { categoryItems } from '~/thumbtack';

const app = useNuxtApp()
const route = useRoute()
const step = ref(0)
const loading = ref(false)
const form = ref(null)
const addressItems = ref([])
const project = ref({
    category: null,
    address: null,
    formatted_address: null,
    street: null,
    city: null,
    state: null,
    zip: null,
    files: [],
    group_id: null,
    accept: null,
    email: null,
    //images: []
})
const estimate = ref([{"room": "Bathroom - Master", "name": "Bathroom - Paint walls", "total_cost": "723", "data": [{"name":"Install New Angle Stops","description":"Install new - valve - angle stop - better","quantity":3,"ppu":104.49,"total_cost":313.47}]}])
const pros = ref([])
const tosDialog = ref(false)
const alert = ref({
    show: false,
    text: null,
    type: null
})

const fieldRequired = (val) => {
    return !!val || "This is a required field."
}
const validEmail = (val) => {
    if(/.+@.+\..+/.test(val)) return true;
    return "A valid email address is required."
}

const goForward = async () => {
    alert.value.show = false

    if(step.value <= 3) {
        let { valid } = await form.value.validate()

        if(step.value == 2) {
            if(!(project.value.files.length > 0)) {
                alert.value.text = "You need to upload at least one image."
                alert.value.type = "error"
                alert.value.show = true
                valid = false
            }
            else {
                for(let i=0; i<project.value.files.length; i++) {
                    if(project.value.files[i].tag == null) {
                        valid = false
                    }
                }
            }
        }

        if(valid) {
            if(step.value == 1) {
                if(project.value.group_id == null) {
                    project.value.group_id = await createGroup()
                    if(!project.value.group_id) return
                }
            }
            if(step.value == 3) {
                getEstimate()
            }
            
            step.value++
        }
    }
    else if(step.value == 4) {
        getPros()

        step.value++
    }
    else step.value++
}
const goBack = () => {
    if(step.value > 0) step.value--
}
const cancel = () => {
    step.value = 0
    Object.keys(project.value).forEach(key => {
        if(project.value[key]?.constructor === Array) project.value[key] = []
        else project.value[key] = null
    })
}
const addressFind = (v) => {
    if(v?.length > 6) {
        app.$axios.post(app.$apiEndpoint + 'request.php', {
            api: 'address',
            request: v
        }).then(result => {
            addressItems.value = result.data.items || []
        })
    }
}
const addressDetails = () => {
    if(project.value.address) {
        loading.value = true

        app.$axios.post(app.$apiEndpoint + 'request.php', {
            api: 'address_details',
            pid: project.value.address
        }).then(result => {
            project.value.formatted_address = result.data.formatted_address
            project.value.street = result.data.street
            project.value.city = result.data.city
            project.value.state = result.data.state
            project.value.zip = result.data.zip
            loading.value = false
        }).catch(() => {
            showError("Server connection error. Please retry or contact support.")
            loading.value = false
        })
    }
}
const addressReset = () => {
    project.value.address = null
    project.value.street = null
    project.value.city = null
    project.value.state = null
    project.value.zip = null
    project.value.formatted_address = null
    addressItems.value.length = 0
}
const createGroup = async () => {
    return app.$axios.post(app.$apiEndpoint + "request.php", {
        'api': 'create_group',
        'formatted_address': project.value.formatted_address,
    }).then((res) => {
        return res.data?.group_id
    }).catch(() => {
        showError("Server connection error. Please retry or contact support.")
        return null
    })
}
const showError = (msg) => {
    alert.value.text = msg
    alert.value.type = 'error'
    alert.value.show = true
}
const getPros = () => {
    if(project.value.zip && project.value.category) {
        loading.value = true

        app.$axios.post(app.$apiEndpoint + 'request.php', {
            api: 'pros',
            zip: project.value.zip,
            category: project.value.category,
            min_rating: 4
        }).then(res => {
            pros.value = res.data || []
            loading.value = false
        })
    }
}
const getEstimate = () => {
    loading.value = true

    app.$axios.post(app.$apiEndpoint + 'request.php', {
        api: 'ai',
        group_id: project.value.group_id,
        street: project.value.street,
        city: project.value.city,
        state: project.value.city,
        zip: project.value.zip,
        formatted_address: project.value.formatted_address,
        category: project.value.category,
        email: project.value.email,
        customer: route.params.src,
        tags: tags.value
    }).then(res => {
        estimate.value = res.data?.estimate || []
        //project.value.images = res.data.images || []
        loading.value = false
    }).catch(() => {
        loading.value = false
    })
}
const progress = computed(() => {
    return 100*step.value/4;
})
const nextBtnText = computed(() => {
    if(step.value == 4) return "Connect with pro"
    else return "Next"
})
const categoryName = computed(() => {
    return categoryItems.value.filter((item) => item.value == project.value.category)?.[0]?.title
})
const tags = computed(() => {
    return project.value.files.map((item) => item.tag)
})

watch(() => project, (n) => {
    if(n.value?.files?.length > 0) alert.value.show = false
}, {deep: true})

useHead({
    title: "RECON ai",
    meta: [
        {name: "description", content: "AI powered home renovation, instant quotes, top-rated pros, home improvement, remodeling, construction."},
    ]
})
</script>

<style >
body {
    font-family: 'helvetica', sans-serif;
}
.v-table {
    zoom: 0.85;
}
.v-data-table-header__content > span {
    font-weight: bold !important;
}
</style>