import { createVuetify } from 'vuetify'
//import * as components from 'vuetify/components'
//import * as directives from 'vuetify/directives'

export default defineNuxtPlugin(nuxtApp => {
  const vuetify = createVuetify({
    ssr: true,
    //components,
    //directives,
    treeShake: true,
    theme: {
      defaultTheme: 'light',
      themes: {
          light: {
              colors: {
                primary: '#0084FF', //#009fd9
                anchor: '#0084FF',
                secondary: '#333333',
                /*
                accent: '#226569',
                anchor: '#57bc8d',
                error: '#ff5252',
                warning: '#fb8c00',
                info: '#2196f3',
                success: '#4caf50',
                */
              },
          },
          dark: {
            colors: {
              primary: '#eba42a',
              anchor: '#eba42a',
              'on-primary': '#ffffff'
            }
          }
      },
  },
  })

  nuxtApp.vueApp.use(vuetify)
})