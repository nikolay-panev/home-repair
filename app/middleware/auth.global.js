export default defineNuxtRouteMiddleware((to) => {  
    if(process.server) return

    const app = useNuxtApp()
    const router = useRouter()
    app.$store.initializeStore().then(() => {
        if(to?.fullPath.indexOf('/account') != -1) {
            if(!app.$store.user?.isAuthenticated) {
                if(to?.path != '/account/login' && to?.path != '/account/lost-password' && to?.path?.indexOf('/account/reset-password') == -1) {
                    abortNavigation()
                    return router.push({path: '/account/login', replace: true})
                }
            }
        }
    })
})