import { defineStore } from 'pinia'
import { useRouter } from 'vue-router'

export const useMainStore = defineStore('main', () => {
  const app = useNuxtApp()
  const router = useRouter()
  const user = ref({isAuthenticated:false, token:null, payload:null, timeout:0})
  
  const initializeStore = () => {
    return new Promise((resolve, reject) => {
      let token = null
      let exp = null
      if(!process.server) {
        token = localStorage.getItem('reconai-token')
        exp = parseInt(localStorage.getItem('reconai-exp') || 0)

        if(token) {
          let payload = parseJwt(token)
          if(payload instanceof Object) user.value.payload = payload
          user.value.token = token
          user.value.timeout = exp
          if(exp > Date.now()) {
            user.value.isAuthenticated = true
            app.$axios.defaults.headers.common['Authorization'] = `jwt ${ token }`

            setTimeout(() => {
              logout()
            }, exp - Date.now())

            resolve(true)
          }
          else {
            user.value.isAuthenticated = false
            resolve(false)
          }
        }
        else {
          user.value.isAuthenticated = false
          resolve(false)
        }
      }
    })
  }
  const login = (obj) => {
    return new Promise((resolve, reject) => {
      if(obj?.token) {
        let payload = parseJwt(obj.token)
        if(!(payload instanceof Object)) {
          payload = null
        }
        else {
          let exp = new Date(payload.exp*1000).getTime()

          setTimeout(() => {
            logout()
          }, exp - Date.now())
              
          user.value.isAuthenticated = true
          user.value.payload = payload
          user.value.timeout = exp
          user.value.token = obj.token
          app.$axios.defaults.headers.common['Authorization'] = `jwt ${ obj.token }`

          if(!process.server) {
            localStorage.setItem('reconai-token', obj.token)
            localStorage.setItem('reconai-exp', exp)
          }
          resolve(true)
        }
      }
      else reject(false)
    })
  }
  const logout = () => {
    user.value.isAuthenticated = false
    user.value.token = null
    user.value.timeout = 0
    user.value.payload = null

    if(!process.server) {
      localStorage.removeItem('reconai-token')
      localStorage.removeItem('reconai-exp')
    }

    if(router.currentRoute.path !== '/account/login') router.push({path: '/account/login'})
  }

  const parseJwt = (token) => {
    var base64Url = token.split('.')[1]
    var base64 = base64Url.toString().replace(/-/g, '+').replace(/_/g, '/')
    var jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)
    }).join(''))
    
    return JSON.parse(jsonPayload)
  }

  return {user, initializeStore, login, logout}
})
