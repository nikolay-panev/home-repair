import vuetify from 'vite-plugin-vuetify'

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  ssr: false,
  telemetry: false,
  future: {
		compatibilityVersion: 4,
	},
  dir: {
    static: 'public',
  },
  compatibilityDate: '2024-04-03',
  devtools: { enabled: true },
  app: {
    baseURL: process.env.BASE_URL || '/',
		head: {
			htmlAttrs: {
				lang: "en-US"
			}
		}
	},
  css: ['vuetify/lib/styles/main.sass', '@mdi/font/css/materialdesignicons.min.css'],
  build: {
		transpile: ['vuetify', 'axios']
	},
  modules: [
	  async (options, nuxt) => {
      nuxt.hooks.hook('vite:extendConfig', (config) => {
        config?.plugins?.push(vuetify())
      })
    },
	],
})
