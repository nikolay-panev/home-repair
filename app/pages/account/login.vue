<template>
    <v-container class="fill-height">
        <Login @logged-in="redirect" />
    </v-container>
</template>
<script setup>
const router = useRouter()

const redirect = () => {
    router.push("/account")
}

useSeoMeta({
    title: () => "RECONai | Login",
    description: () => "Login page for administrators."
})
definePageMeta({
    layout: 'admin'
})
</script>