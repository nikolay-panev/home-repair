<template>
    <v-sheet elevation="0" class="pa-lg-6">
        <v-tabs v-model="tab" align-tabs="center">
            <v-tab :value="0">Email Templates</v-tab>
            <v-tab :value="1">API Credentials</v-tab>
        </v-tabs>

        <v-tabs-window v-model="tab">
            <v-tabs-window-item :value="0">
                <CRUD
                    label=""
                    model="email_templates"
                    :canSearch="true"
                    :canAdd="true"
                    :canEdit="true"
                    :canDelete="true"
                    :headers="headers2"
                    :conditions="[{deleted:{'is': null}}]"
                    :multi-sort="true"
                >
                </CRUD>
            </v-tabs-window-item>
            
            <v-tabs-window-item :value="1">
                <CRUD
                    label=""
                    model="api_settings"
                    :canSearch="true"
                    :canAdd="true"
                    :canEdit="true"
                    :canDelete="true"
                    :headers="headers1"
                    :conditions="[{deleted:{'is': null}}]"
                    :multi-sort="true"
                >
                </CRUD>
            </v-tabs-window-item>

        </v-tabs-window>
    </v-sheet>
</template>

<script setup>
    const tab = ref(0)
    const headers1 = ref([
        {title: "ID", key: "id", isId: true, visible: false, editable: false},
        {title: "Provider", key: "provider", mandatory: true, searchable: true},
        {title: "Mode", key: "mode", mandatory: true, type:"radio", items:["Sandbox", "Production"], fieldOptions: {inline: true}},
        {title: "API Username", key: "api_username", mandatory: false, searchable: true},
        {title: "API Password", key: "api_password", mandatory: false, searchable: true},
        {title: "API Key", key: "api_key", mandatory: false, searchable: true},
        {title: "API Secret", key: "api_secret", mandatory: false, searchable: true},
        {title: "Log Path", key: "log_path", mandatory: false, searchable: false},
    ])

    const headers2 = ref([
        {title: "ID", key: "id", isId: true, visible: false, editable: false},
        {title: "Template Name", key: "name", mandatory: true, searchable: true},
        {title: "Recipients", key: "recipients", mandatory: false},
        {title: "Subject", key: "subject", mandatory: true, searchable: true},
        {title: "Message", key: "message", mandatory: true, type: 'editor', searchable: true, cols: 12},
    ])

    definePageMeta({
        layout: 'admin'
    })
</script>