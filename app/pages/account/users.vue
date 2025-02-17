<template>
    <v-sheet elevation="0" class="pa-lg-6">
        <v-btn color="primary" size="small" variant="text" class="rounded-0" @click="addDialog=true">
            <v-icon>mdi-plus</v-icon>Add new
        </v-btn>
        <CRUD
            label="Users"
            model="users"
            :key="rerender"
            :canSearch="true"
            :canAdd="false"
            :headers="headers"
            :conditions="[{deleted:{'is': null}}]"
            :multi-sort="true"
        >
            <template v-slot:[`item.email`]="obj">
                <a :href="'mailto:'+obj.item.email">{{ obj.item.email }}</a>
            </template>
            <template v-slot:[`item.pwd`]="obj">
                <v-btn icon variant="plain" @click="showDialog(obj.item.id, obj.item.name)"><v-icon>mdi-lock</v-icon></v-btn>
            </template>
        </CRUD>
         <!-- Change Password Dialog -->
        <v-dialog v-model="pwdDialog" max-width="40vw" @keydown.esc.prevent="closeDialog()">
            <v-card>
                <v-toolbar density="compact" color="primary" :elevation="0" class="px-4">
                    <span>Change Password for user {{userName}}</span>
                    <v-spacer></v-spacer>
                    <v-btn icon class="rounded-0" size="small" @click="closeDialog()"><v-icon>mdi-close</v-icon></v-btn>
                </v-toolbar>
                <v-card class="pa-4">
                    <v-form v-model="pwdValid">
                        <v-row>
                             <v-col cols="12">
                                <v-progress-linear indeterminate class="ma-1" :active="!!loading"></v-progress-linear>
                                <v-alert variant="text" :color="alert.color" v-model="alert.show" class="rounded-0" dismissible>{{alert.text}}</v-alert>
                             </v-col>
                        </v-row>
                        <v-row>
                             <v-col cols="12">
                                <v-text-field variant="underlined" label="New Password" v-model="password1" type="password" :rules="[fieldRequired]" autocompelte="new-password"></v-text-field>
                            </v-col>
                        </v-row>
                         <v-row>
                            <v-col cols="12">
                                <v-text-field variant="underlined" label="Confirm Password" v-model="password2" type="password" :rules="[fieldRequired]" autocompelte="new-password"></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row density="compact" class="text-right">
                            <v-col cols="12">
                                <v-btn color="primary" size="small" class="rounded-0" @click="changePassword()" :disabled="!pwdValid">Save</v-btn>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card>
            </v-card>
        </v-dialog>

        <!-- Add User Dialog -->
        <v-dialog v-model="addDialog" max-width="60vw" @keydown.esc.prevent="closeAddDialog()">
            <v-card class="rounded-0">
               <v-card-title>
                    New Record
                </v-card-title>
                <v-card-text>
                    <v-form v-model="addValid">
                        <v-row>
                             <v-col cols="12">
                                <v-progress-linear indeterminate class="ma-1" :active="!!loading"></v-progress-linear>
                                <v-alert variant="text" :color="alert.color" v-model="alert.show" class="rounded-0" dismissible>{{alert.text}}</v-alert>
                             </v-col>
                        </v-row>
                        <v-row>
                             <v-col cols="12" sm="6" md="4" lg="3">
                                <v-text-field variant="underlined" label="Name" v-model="profile.name" :rules="[fieldRequired]"></v-text-field>
                            </v-col>
                             <v-col cols="12" sm="6" md="4" lg="3">
                                <v-text-field variant="underlined" label="Email" v-model="profile.login" :rules="[fieldRequired]"></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6" md="4" lg="3">
                                <v-text-field variant="underlined" label="Password" v-model="profile.password" type="password" :rules="[fieldRequired]" autocomplete="new-password"></v-text-field>
                            </v-col>
                            <v-col cols="12" sm="6" md="4" lg="3">
                                <v-radio-group inline v-model="profile.status" label="Status">
                                    <v-radio label="Active" value="Active"></v-radio>
                                    <v-radio label="Inactive" value="Inactive"></v-radio>
                                </v-radio-group>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn color="blue darken-1" variant="text" class="rounded-0" @click="closeAddDialog()">Cancel</v-btn>
                    <v-btn color="blue darken-1" variant="text" class="rounded-0" @click="addUser()" :disabled="!addValid">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-sheet>
</template>

<script setup>
const app = useNuxtApp()

const headers = ref([
    {title:"ID", key: "id", isId: true, visible: false, editable: false},
    {title:"Name", key: "name", mandatory: true, searchable: true},
    {title:"Email", key: "email", mandatory: true, slot:true, searchable:true},
    {title:"Password", key: "pwd", query: false, slot: true},
    {title:"Status" ,key: "status", mandatory: true, type:"radio", items:["Active", "Inactive"], fieldOptions: {inline: true}},
])
const rerender = ref(0)
const addValid = ref(false)
const pwdValid = ref(false)
const pwdDialog = ref(false)
const addDialog = ref(false)
const password1 = ref(null)
const password2 = ref(null)
const loading =ref(false)
const alert = ref({
    show: false,
    text: null,
    color: null
})
const userId = ref(null)
const userName = ref(null)
const profile = ref({
    name: null,
    login: null,
    password: null,
    status: null
})

const fieldRequired = (val) => {
    return !!val || "This is a required field!"
}
const showDialog = (user_id, name) => {
    pwdDialog.value = true
    userId.value = user_id
    userName.value = name
}
const closeDialog = () => {
    pwdDialog.value = false
    password1.value = null
    password2.value = null
    userId.value = null
    userName.value = null
    pwdValid.value = false
}
const closeAddDialog = () => {
    addDialog.value = false
    addValid.value = false
    profile.value = { name: null, login: null, password: null, role: null}
}
const addUser = () => {
    loading.value = true;
    app.$axios.post(app.$apiEndpoint, {
        table: 'users',
        operation: "insert",
        data: [{"name": profile.value.name, "email": profile.value.login, "password": profile.value.password, "status": profile.value.status}]
    }).then(() => {
        loading.value = false
        rerender.value++
        closeAddDialog()
    })
}
const changePassword = () => {
    if(password1.value === password2.value) {
        loading.value = true
        app.$axios.post(app.$apiEndpoint, {
            table: "users",
            operation: "update",
            data: [{"id": userId.value, "password": password1.value}]
        }).then(() => {
            loading.value = false
            closeDialog()
        })
    }
    else {
        alert.value.show = true
        alert.value.text = "Passwords do not match."
        alert.value.color = 'red'
    }
}
const toggleItem = (id, name, newVal) => {
    app.$axios.post(app.apiEndpoint, {
        operation: 'update',
        table: 'users',
        data: [{'id': id, [name]: newVal}]
    }).then(() => {
        rerender.value++
    })
}

definePageMeta({
  layout: 'admin'
})
</script>