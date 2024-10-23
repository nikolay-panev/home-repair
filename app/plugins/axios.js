import axios from "axios"

export default defineNuxtPlugin((nuxtApp) => {
    let a = axios.create({
        baseUrl: null,
        headers: {
            common: {
            }
        }
    })
    /*
    if(!process.server) {
        if(token) a.defaults.headers.common['Authorization'] = `jwt ${ token }`
    }
        */

    return {
        provide: {
            axios: a,
            apiEndpoint: 'https://homerepair.digitaldevelopments.tech/api/',
        },
    }
})