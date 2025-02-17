<template>
    <v-app>
        <client-only>
            <admin-sidebar v-if="$store?.user?.isAuthenticated" />
            <admin-header v-if="$store?.user?.isAuthenticated" />
            <v-container fluid class="px-lg-16 py-0 ml-lg-5">
                <slot />
            </v-container>
        </client-only>
    </v-app>
</template>

<script setup>
useHead({
    title: "RECONai | Dashboard"
})
</script>

<style scoped>

</style>