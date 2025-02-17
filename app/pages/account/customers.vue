<template>
    <v-sheet elevation="0" class="pa-lg-6">
        <CRUD
            label="Customers"
            model="customers"
            :canSearch="true"
            :canAdd="true"
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
        {title: "Customer ID", key: "customer_id", mandatory: true, searchable: true},
        {title: "Customer Name", key: "name", mandatory: true, searchable:true},
        {title: "Customer Eamil", key: "email", mandatory: true, searchable: true},
        {title: "Status", key: "status", mandatory: true, type:"radio", items:["Active", "Inactive"], fieldOptions: {inline: true}},
        {title: "Date", key: "created", editable: false, type: "date"},
    ])

    definePageMeta({
        layout: 'admin'
    })
</script>