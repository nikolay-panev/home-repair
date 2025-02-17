<template>
    <v-card elevation="2" width="100%" max-width="600" class="mx-auto mt-12 rounded-0 pa-lg-12">
        <v-card-text class="py-4 px-4">
            <v-row>
                <v-col cols="12">
                    <h1 class="mt-12 text-primary text-center">Account Login</h1>
                </v-col>
            </v-row>
            <v-form v-model="valid" fluid class="py-12">
                <v-row>
                    <v-col class="py-md-12 px-2 px-md-4 px-lg-8" cols="12" sm="12" v-if="invalidLogins < 5">
                        <v-row>
                            <v-col cols="12">
                                <v-text-field variant="outlined" density="compact" label="E-mail" name="email" v-model="email" autocomplete="email" :disabled="loading" :rules="[required]" aria-label="Email"></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="12">
                                <v-text-field variant="outlined" density="compact" type="password" label="Password" name="password" v-model="password" autocomplete="current-password" :disabled="loading" :rules="[required]" @keydown.enter="login()" aria-label="Password"></v-text-field>
                                <p class="text-right"><a href="/account/lost-password" class="text-decoration-none text-black text-body-2">Lost Your Password?</a></p>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="12">
                                <v-btn variant="outlined" size="large" block @click="login()" v-if="!$store?.user?.isAuthenticated" :disabled="!valid" color="primary">Login</v-btn>
                                <v-btn variant="outlined" size="large" block @click="logout()" v-else color="#a0bff8">Logout</v-btn>
                            </v-col>
                        </v-row>
                        <v-row v-if="error">
                            <v-col cols="12">
                                <v-alert class="mt-3" type="error">{{error}}</v-alert>
                            </v-col>
                        </v-row>
                        <v-row v-if="loading">
                            <v-col cols="12">
                                <v-progress-linear indeterminate></v-progress-linear>
                            </v-col>
                        </v-row>
                    </v-col>
                    <v-col  v-else class="py-12 order-last order-sm-first" cols="12">
                        <v-row class="fill-height">
                            <v-alert class="pa-4 ma-4 text-center" type="error" variant="text">Your IP address was blocked because of too many login attempt failures!</v-alert>
                        </v-row>
                    </v-col>
                </v-row>
            </v-form>
            <v-container fluid class="text-caption text-center">
                <span class="text-grey">Powered by <a class="power" href="https://digitaldevelopments.tech" target="_blank" rel="noopener" title="Visit website" aria-label="Developer Website">Digital Developments Ltd.</a></span>
            </v-container>
        </v-card-text>
    </v-card>
    <!--
    <captcha
        v-if="useCaptcha"
        action="login"
        sitekey="6LdYD3IdAAAAALf0HBh5WrsqH1wxMONZso1IVfZZ"
        @reCAPTCHA3="getToken"
    />
    -->
</template>

<script setup>
//import {onBeforeMount} from 'vue'
//import captcha from "../components/reCAPTCHAv3.vue"
const emit = defineEmits(["logged-in"])
const app = useNuxtApp()
const email = ref(null)
const password = ref(null)
const captchaObj = ref(null)
const error = ref(null)
const valid = ref(false)
const loading = ref(false)
const invalidLogins = ref(0)
const useCaptcha = ref(false)

const required = (val) => {
return !!val || "This is a required field!"
}
const getToken = (t) => {
captchaObj.value = t
}
const login = () => {
error.value = null
loading.value = true
        
app.$axios.post(app.$apiEndpoint+"login.php", {
    'action': 'login',
    'email': email.value,
    'password': password.value,
    'captcha': captchaObj.value
}).then(res => {
    if(res.data.token) {
        app.$store.login(res.data).then(() => emit("logged-in"))
    }
    else if(res.data.error) {
        error.value = res.data.error
    }
    else {
        error.value = "Invalid username or password."
        invalidLogins.value++
    }
}).finally(() => {
    loading.value = false
})
}
const logout = () => {
app.$store.logout()
}
/*
onBeforeMount(() => {
useCaptcha.value = import.meta.env.VITE_APP_GOOGLE_CAPTCHA
})
*/
</script>

<style scoped>
a.power {
color: grey;
text-decoration: none;
}
</style>