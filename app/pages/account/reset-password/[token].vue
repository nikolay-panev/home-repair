<template>
    <v-container class="fill-height">
        <v-card elevation="2" width="100%" max-width="600" class="mx-auto mt-6 rounded-0 pa-lg-4">
            <v-card-text class="py-4 px-4">
                <v-row>
                    <v-col cols="12">
                        <h1 class="mt-12 text-primary text-center">Password Reset</h1>
                    </v-col>
                </v-row>
                <v-form v-model="valid" fluid class="py-12">
                    <v-row>
                        <v-col class="py-md-12 px-2 px-md-4 px-lg-8" cols="12" sm="12">
                            <v-row>
                                <v-col cols="12" class="pa-0">
                                    <v-text-field variant="outlined" density="compact" label="E-mail" name="email" v-model="email" :disabled="true" autocomplete="email" :rules="[fieldRequired, validEmail]" aria-label="Email"></v-text-field>
                                </v-col>
                                <v-col cols="12" class="pa-0">
                                    <v-text-field variant="outlined" density="compact" label="New Password" type="password" name="password1" v-model="password1" :disabled="!tokenIsValid" autocomplete="new-password" :rules="[fieldRequired]" aria-label="New Password"></v-text-field>
                                </v-col>
                                <v-col cols="12" class="pa-0">
                                    <v-text-field variant="outlined" density="compact" label="Confirm Password" type="password" name="password2" v-model="password2" :disabled="!tokenIsValid" autocomplete="new-password" :rules="[fieldRequired, passwordsMatch]" aria-label="Confirm Password"></v-text-field>
                                </v-col>
                            </v-row>
                            <v-row>
                                <v-col cols="12" class="pa-0">
                                    <v-btn variant="outlined" size="large" block @click="resetPassword()" :disabled="!valid || !tokenIsValid" color="primary">Change Password</v-btn>
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
const route = useRoute()
const router = useRouter()
const email = ref(null)
const password1 = ref(null)
const password2 = ref(null)
const valid = ref(false)
const tokenIsValid = ref(false)
const fieldRequired = (val) => {
    return !!val || "This is a required field."
}
const passwordsMatch = (val) => {
    return val == password1.value || "Passowrds do not match."
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

const validateToken = () => {
    if(route.params.token) {
        app.$axios.post(app.$apiEndpoint+"login.php", {
            action: 'token-validate',
            token: route.params.token
        }).then(res => {
            if(res.data?.status == 'fail') {
                alert.value.type = 'error'
                alert.value.text = res.data.message
                alert.value.show = true
                tokenIsValid.value = false
            }
            if(res.data?.status == 'success') {
                tokenIsValid.value = true
                email.value = res.data.email
            }
        })
    }
    else {
        alert.value.type = 'error'
        alert.value.text = "Invalid or missing token."
        alert.value.show = true
        tokenIsValid.value  = false
    }
}

const resetPassword = () => {
    app.$axios.post(app.$apiEndpoint+"login.php", {
        action: 'reset-password',
        email: email.value,
        password: password1.value,
        token: route.params.token
    }).then(res => {
        if(res.data.status == 'success') {
            alert.value.text = 'Your password has been changed successfully. You will be redirected to the login page.'
            alert.value.type = 'success'
            alert.value.show = true
            setTimeout(() => {
                router.push("/account/login")
            }, 3000)
        }
    })
}

definePageMeta({
  layout: 'admin'
})

validateToken()
</script>