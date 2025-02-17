<template>
    <v-sheet elevation="0" class="pa-lg-6">
        <CRUD
            label="Emails"
            model="emails"
            :canSearch="true"
            :canAdd="false"
            :canEdit="true"
            :canDelete="true"
            :headers="headers"
            :conditions="[{deleted:{'is': null}}]"
            :multi-sort="true"
        >

        </CRUD>
    </v-sheet>
</template>

<script setup>
    const headers = ref([
        {title: "ID", key: "id", isId: true, visible: false, editable: false},
        {title: "Recipient", key: "recipient", mandatory: true, searchable: true},
        {title: "Subject", key: "subject", mandatory: true, searchable:true},
        {title: "Message", key: "message", mandatory: true, type: "editor", searchable: true, cols: 12},
        {title: "Attempts", key: "attempts", mandatory: false, searchable: false},
        {title: "Status", key: "status", mandatory: true, type:"radio", items:["OK", "ERROR"], fieldOptions: {inline: true}},
        {title: "Date", key: "created", mandatory: true, type: "date"},
    ])

    definePageMeta({
        layout: 'admin'
    })
</script>