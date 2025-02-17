<template>
    <v-navigation-drawer
        app
        rail
        expand-on-hover
        rail-width="70"
        v-model="$vuetify.display.mdAndUp"
    >
        <v-list nav class="bg-primary">
            <v-list-item class="px-0">
                <img src="/images/reconai-logo.png" width="48">
            </v-list-item>
        </v-list>

        <v-divider></v-divider>

        <v-list nav>
            <v-list-item v-for="item in menu" :key="item.title" link :to="item.to" v-show="item.show" :prepend-icon="item.icon" :title="item.title">
            </v-list-item>
        </v-list>

        <v-divider></v-divider>

        <v-list nav>
            <v-list-item v-on:click.stop="logout()" prepend-icon="mdi-power" base-color="error" title="Logout">
            </v-list-item>
        </v-list>
    </v-navigation-drawer>
</template>

<script setup>
import {onMounted} from 'vue'

const app = useNuxtApp()
const menu = ref([])

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
    menu.value = [
        {title: "Dashboard", icon: "mdi-view-dashboard", to: "/account", show: true},
        {title: "Customers", icon: "mdi-account-group", to: "/account/customers", show:  true},
        {title: "Reports", icon: "mdi-file-table", to: "/account/reports", show: true},
        {title: "Emails", icon: "mdi-email", to: "/account/emails", show: true},
        {title: "Users", icon: "mdi-account-tie", to: "/account/users", show:  true},
        {title: "Settings",  icon: "mdi-cogs", to: "/account/settings", show: true}
    ]
})
</script>
<style scoped>
.v-list--nav .v-list-item, .v-list--nav .v-list-item:before {
    border-radius: 0px;
}
.v-icon {
    box-shadow: 0 3px 6px rgba(83,108,167,.16);
}
</style>