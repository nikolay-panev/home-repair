<template>
    <v-card flat class="rounded-0" height="64px">
        <v-app-bar :elevation="3" app color="transparent">
            <!-- Mobile Menu -->
            <div class="hidden-md-and-up">
                <v-menu class="rounded-0">
                    <template v-slot:activator="{ props }">
                        <v-app-bar-nav-icon v-bind="props"></v-app-bar-nav-icon>
                    </template>
                    <v-list density="compact" nav class="rounded-0">
                        <v-list-item v-for="item in mobileMenu" :key="item.title" link :to="item.to" v-show="item.show" class="rounded-0">
                            <template v-slot:prepend>
                                <v-icon color="#">{{item.icon}}</v-icon>
                            </template>
                            <v-list-item-title class="text-left">{{item.title}}</v-list-item-title>
                        </v-list-item>
                        <v-list-item class="rounded-0" @click="logout()">
                            <template v-slot:prepend>
                                <v-icon>mdi-power</v-icon>
                            </template>
                            <v-list-item-title class="text-left">Logout</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-menu>
            </div>

            <v-spacer></v-spacer>

            <div class="">
                <span class="text-overline">My Account</span>
            </div>

            <v-spacer></v-spacer>

            <div class="mr-4 text-white">
                <v-btn icon class="rounded-0" to="/" target="_blank" title="Visit Site"><v-icon>mdi-exit-to-app</v-icon></v-btn>
            </div>
        </v-app-bar>
    </v-card>
</template>

<script setup>
import { onMounted } from 'vue'

const app = useNuxtApp()

const mobileMenu = ref([])

const logout = () => {
    app.$store.logout()
}
/*
const isAdmin = computed(() => {
    return app.$store.user.payload.role == 'Admin'
})
const isCustomer = computed(() => {
    return app.$store.user.payload.role == 'Customer'
})
const isManager = computed(() => {
    return app.$store.user.payload.role == 'Manager'
})
*/
onMounted(() => {
    mobileMenu.value = [
    {title: "Dashboard", icon: "mdi-view-dashboard", to: "/account", show: true},
        {title: "Customers", icon: "mdi-account-group", to: "/account/customers", show:  true},
        {title: "Reports", icon: "mdi-file-table", to: "/account/reports", show: true},
        {title: "Emails", icon: "mdi-email", to: "/account/emails", show: true},
        {title: "Users", icon: "mdi-account-tie", to: "/account/users", show:  true},
        {title: "Settings",  icon: "mdi-cogs", to: "/account/settings", show: true}
    ]
})
</script>