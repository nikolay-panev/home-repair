<template>
    <v-container class="fill-height">
        <v-card elevation="2" width="100%" max-width="600" class="mx-auto mt-12 rounded-0 pa-lg-12">
            <v-card-text class="py-4 px-4">
                <v-row>
                    <v-col cols="12">
                        <h1 class="mt-12 text-primary text-center">Lost Password</h1>
                    </v-col>
                </v-row>
                <v-form v-model="valid" fluid class="py-12">
                    <v-row>
                        <v-col class="py-md-12 px-2 px-md-4 px-lg-8" cols="12" sm="12">
                            <v-row>
                                <v-col cols="12">
                                    <v-text-field variant="outlined" density="compact" label="E-mail" name="email" v-model="email" autocomplete="email" :rules="[fieldRequired, validEmail]" aria-label="Email"></v-text-field>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="12">
                                    <v-btn variant="outlined" size="large" block @click="sendToken()" :disabled="!valid" color="primary">Submit</v-btn>
                                </v-col>
                            </v-row>
                        </v-col>
                    </v-row>
                    <v-row v-if="alert.show">
                        <v-col class="order-last order-sm-first" cols="12">
                            <v-alert class="pa-4 ma-4 text-center" :type="alert.type" variant="tonal">{{ alert.text }}</v-alert>
                        </v-col>
                    </v-row>
                </v-form>
            </v-card-text>
        </v-card>
    </v-container>
</template>

<script setup>
const app = useNuxtApp()
const email = ref(null)
const valid = ref(false)
const fieldRequired = (val) => {
    return !!val || "This is a required field."
}
const validEmail = (val) => {
    if(/.+@.+\..+/.test(val)) return true;
    return "A valid email address is required."
}
const alert = ref({
    show: false,
    text: null,
    type: null
})

const sendToken = () => {
    app.$axios.post(app.$apiEndpoint+"login.php", {
        "action": "token-generate",
        "email": email.value
    }).then(res => {
        if(res.data?.status == 'fail') {
            alert.value.type = 'error'
            alert.value.text = res.data.message
            alert.value.show = true
        }
        if(res.data?.status == 'success') {
            alert.value.type = 'success'
            alert.value.text = res.data.message
            alert.value.show = true
        }
        email.value = null
    })
}

useSeoMeta({
    title: () => "RECONai | Lost Password",
    description: () => "Password reset page for registered users."
})
definePageMeta({
  layout: 'admin'
})
</script>