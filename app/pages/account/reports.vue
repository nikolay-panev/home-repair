<template>
    <v-sheet elevation="0" class="pa-lg-6">
        <CRUD
            label="Reports"
            model="reports"
            :canSearch="true"
            :canAdd="false"
            :canEdit="false"
            :canDelete="true"
            :headers="headers"
            :conditions="[{deleted:{'is': null}}]"
            :multi-sort="true"
            :opts="{sortBy: ['created'], sortDesc: [true]}"
        >

        </CRUD>
    </v-sheet>
</template>

<script setup>
    const headers = ref([
        {title:"ID", key: "id", isId: true, visible: false, editable: false},
        {title:"Customer", key: "customer", searchable: true},
        {title:"Category", key: "category", searchable:true},
        {title:"Area", key: "areas", searchable: true},
        {title:"Address", key: "address", mandatory: false, searchable: false},
        {title:"Email", key: "email"},
        {title:"Date", key: "created", mandatory: true, type: "date"},
    ])

    definePageMeta({
        layout: 'admin'
    })
</script>