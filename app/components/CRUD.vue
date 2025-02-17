<template>
    <v-container fluid class="pa-0">
        <v-data-table
            v-bind="$attrs"
            :headers="visibleFields"
            :items="DataPG.data"
            :options="options"
            :server-items-length="DataPG.totalRecords"
            :loading="loading"
            :show-expand="showExpand"
            :footer-props="footerProps"
            :items-per-page="itemsPerPage"
        >
            <template v-if="showExpand" v-slot:expanded-item="obj">
                <td :colspan="obj.headers.length" class="expander">
                    <slot v-bind="obj" name="expanded-item"></slot>
                </td>
            </template>
            <template v-slot:[`footer.prepend`]>
                <slot name="bottom"></slot>

                <v-btn class="rounded-0" v-if="canExport" size="small" @click="exportXLSX()">
                    <v-icon small>mdi-file-excel</v-icon>Export
                </v-btn>
            </template>
            <template v-slot:top>
                <v-toolbar flat color="white">
                    <v-btn color="primary" v-if="canAdd" size="small" class="rounded-0" @click="editDialog=!editDialog">
                        <v-icon>mdi-plus</v-icon>Add new
                    </v-btn>
                    <v-spacer></v-spacer>
                    <v-toolbar-title>{{label}}</v-toolbar-title>
                    <v-spacer></v-spacer>

                    <div v-if="canSearch" style="width:25%">
                        <v-text-field
                            v-model="searchText"
                            :append-icon="searchCondition ? 'mdi-close': 'mdi-magnify'"
                            label="Search"
                            variant="underlined"
                            density="compact"
                            single-line
                            hide-details
                            @click:append="toggleSearch()"
                            @keyup.enter="doSearch()"
                            @keyup.esc="cancelSearch()"
                        ></v-text-field>
                    </div>
                    <v-btn v-if="canFilter" size="small" icon class="ml-3 rounded-0" @click="filtersBox=!filtersBox">
                        <v-icon>mdi-filter</v-icon>
                    </v-btn>
                </v-toolbar>
                <v-container v-if="canFilter && filtersBox" fluid class="crud-filters">
                    <v-divider horizontal></v-divider>
                    <slot
                        name="filters"
                        v-bind:setConditions="setConditions"
                        v-bind:conditions="gridCondition"
                    ></slot>
                    <!--<v-spacer></v-spacer>
                    SHOULD BE PLACED ELSEWHERE
                    <v-select
                        multiple
                        label="Columns"
                        :items="headers"
                        return-object
                        v-model="adjustVisible"
                    ></v-select>-->
                </v-container>

                <v-divider horizontal></v-divider>
                <v-dialog v-model="editDialog" :retain-focus="false" eager max-width="60vw" class="rounded-0">
                    <v-card class="rounded-0" flat>
                        <v-toolbar color="primary" theme="dark" density="compact" class="rounded-0" elevation="0">
                            <span class="headline px-4">{{ formTitle }}</span>
                            <v-spacer></v-spacer>
                            <v-btn icon class="rounded-0" @click="editDialog=false"><v-icon>mdi-close</v-icon></v-btn>
                        </v-toolbar>

                        <v-card-text>
                            <v-form v-model="editFormValid" ref="crudform" :key="itemBeingEdited?.id">
                                <v-row>
                                    <v-col
                                        v-for="(header) in editableFields"
                                        :key="header.key"
                                        :cols="header.cols ? header.cols : 12"
                                        :sm="header.sm ? header.sm : (header.cols ? null:6)"
                                        :md="header.md ? header.md : (header.cols || header.sm ? null:4)"
                                        :lg="header.lg ? header.lg : (header.cols || header.sm || header.md ? null:3)"
                                        :xl="header.xl ? header.xl : null"
                                    >
                                        <v-autocomplete
                                            v-if="header.type=='select'"
                                            v-bind="header.fieldOptions"
                                            variant="underlined"
                                            v-model="fieldsToMutate[header.modelId ? header.modelId : header.key]"
                                            :label="header.title"
                                            :items="header.items"
                                            :item-value="header.modelFields ? header.modelFields.value : 'value'"
                                            :item-title="header.modelFields ? header.modelFields.text : 'text'"
                                            :rules="header.mandatory ? [ruleMandatory] : []"
                                            hide-details="auto"
                                            :clearable="!header.mandatory"
                                        >
                                            <template v-if="header.mandatory" v-slot:label>
                                                <span style="color:red">*</span>
                                                {{header.title}}
                                            </template>
                                        </v-autocomplete>

                                        <CheckboxList
                                            v-else-if="header.type=='checkboxlist'"
                                            v-bind="header.fieldOptions"
                                            v-model="fieldsToMutate[header.key]"
                                            :label="header.title"
                                            :items="header.items"
                                            :model="header.model"
                                            :item-value="header.modelFields ? header.modelFields.value : 'value'"
                                            :item-title="header.modelFields ? header.modelFields.text : 'text'"
                                            :rules="header.mandatory ? [ruleMandatory] : []"
                                            hide-details="auto"
                                        >
                                            <template v-if="header.mandatory" v-slot:label>
                                                <span style="color:red">*</span>
                                                {{header.title}}
                                            </template>
                                        </CheckboxList>

                                        <DatePickerInput v-else-if="header.type=='date'" v-model="fieldsToMutate[header.key]" :label="header.title" :rules="header.mandatory ? [ruleMandatory] : []">
                                        </DatePickerInput>
                                        <!--
                                        <v-menu 
                                            v-else-if="header.type=='time'" 
                                            ref="menu" 
                                            :close-on-content-click="false" 
                                            `v-model:return-value`="fieldsToMutate[header.key]" 
                                            width="290px"
                                            :nudge-right="40"
                                            v-model="header.menu"
                                            offset-y
                                        >
                                            <template v-slot:activator="{ on }">
                                                <v-text-field 
                                                    v-model="fieldsToMutate[header.key]"
                                                    :label="header.title"
                                                    prepend-icon="mdi-clock-outline" 
                                                    readonly 
                                                    v-on="on"
                                                    hide-details="auto"
                                                    :mandatory="header.mandatory"
                                                    :rules="header.mandatory ? [ruleMandatory] : []"
                                                >
                                                </v-text-field>
                                            </template>

                                            <v-time-picker
                                                v-if="header.menu"
                                                v-bind="header.fieldOptions"
                                                v-model="fieldsToMutate[header.key]"
                                                full-width
                                                :mandatory="header.mandatory"
                                                @click:minute="$refs.menu[0].save(fieldsToMutate[header.key])"
                                            >
                                            </v-time-picker>
                                        </v-menu>
                                        -->

                                        <v-switch
                                            v-else-if="header.type=='switch'"
                                            v-bind="header.fieldOptions"
                                            v-model="fieldsToMutate[header.key]"
                                            :label="header.title"
                                            hide-details="auto"
                                        ></v-switch>

                                        <v-radio-group
                                            v-else-if="header.type=='radio'"
                                            v-bind="header.fieldOptions"
                                            :id="'field_'+header.key"
                                            class="mt-0"
                                            v-model="fieldsToMutate[header.modelId ? header.modelId : header.key]"
                                            :rules="header.mandatory ? [ruleMandatory] : []"
                                        >
                                            <template v-slot:label>
                                                <span v-if="header.mandatory" style="color:red">*</span> {{header.title}}
                                            </template>
                                            <v-radio
                                                v-for="(item,ik) in prepareItems(header.items)"
                                                v-bind="header.subfieldOptions"
                                                :key="ik"
                                                :label="item[header.modelFields ? header.modelFields.text : 'text']"
                                                :value="item[header.modelFields? header.modelFields.value : 'value']"
                                                hide-details="auto"
                                            ></v-radio>
                                        </v-radio-group>
                                        
                                        <v-textarea
                                            v-else-if="header.type=='textarea'"
                                            v-bind="header.fieldOptions"
                                            variant="underlined"
                                            v-model="fieldsToMutate[header.key]"
                                            :label="header.title"
                                            :rules="header.mandatory ? [ruleMandatory] : []"
                                            hide-details="auto"
                                        >
                                            <template v-if="header.mandatory" v-slot:label>
                                                <span style="color:red">*</span>
                                                {{header.title}}
                                            </template>
                                        </v-textarea>

                                        <div v-else-if="header.type=='editor' && editDialog">
                                            <label class="v-label theme--light">{{header.title}}</label>
                                            <Editor
                                                api-key="v66vbmwe7b9izz47wxjrgkp5jr2luh5cr5jpz4abgx1g1dik"
                                                cloud-channel="6"
                                                v-model="fieldsToMutate[header.key]"
                                                model-events= "input change keydown blur focus paste" 
                                                :disabled="false"
                                                :init="{statusbar: false, height: '400', plugins: 'lists link image media paste code wordcount table', toolbar: 'undo redo | blocks | bold italic underline | image fontselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat subscript superscript code'}"
                                            >
                                            </Editor>
                                        </div>
                                         <v-text-field
                                            v-else-if="header.type=='password'"
                                            v-bind="header.fieldOptions"
                                            variant="underlined"
                                            v-model="fieldsToMutate[header.key]"
                                            type="password"
                                            :label="header.title"
                                            :rules="header.mandatory ? [ruleMandatory] : []"
                                            hide-details="auto"
                                        >
                                            <template v-if="header.mandatory" v-slot:label>
                                                <span style="color:red">*</span>
                                                {{header.title}}
                                            </template>
                                        </v-text-field>
                                        
                                        <div v-else-if="header.type=='color'">
                                             <span  v-if="header.mandatory" style="color:red">*</span> {{header.title}}
                                             
                                            <v-color-picker
                                                v-bind="header.fieldOptions"
                                                v-model="fieldsToMutate[header.key]"
                                                :label="header.title"
                                                :rules="header.mandatory ? [ruleMandatory] : []"
                                                hide-details="auto"
                                                mode="rgba"
                                            >
                                            </v-color-picker>
                                        </div>

                                        <v-text-field
                                            v-else
                                            v-bind="header.fieldOptions"
                                            variant="underlined"
                                            v-model="fieldsToMutate[header.key]"
                                            :label="header.title"
                                            :rules="header.mandatory ? [ruleMandatory] : []"
                                            hide-details="auto"
                                        >
                                            <template v-if="header.mandatory" v-slot:label>
                                                <span style="color:red">*</span>
                                                {{header.title}}
                                            </template>
                                        </v-text-field>
                                    </v-col>
                                </v-row>
                            </v-form>
                        </v-card-text>

                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn class="rounded-0" color="primary" @click="save" :disabled="!editFormValid"> Save </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </template>
            <template v-for="fld in slotFields" v-slot:[fld]="obj">
                <slot :name="fld" v-bind="obj"></slot>
            </template>
            <template v-for="(_, name) in $slots" #[name]="slotProps">
                <slot :name="name" v-bind="slotProps || {}"></slot>
            </template>
            <template v-if="canEdit || canDelete" v-slot:[`item.actions`]="{item}">
                <v-progress-circular
                    v-if="loading"
                    color="primary"
                    indeterminate
                >Saving...</v-progress-circular>
                <v-container v-else class="text-no-wrap">
                    <v-icon v-if="canEdit" @click="editItem(item)">mdi-pencil</v-icon>
                    <v-icon v-if="canDelete" class="ml-3" @click="deleteItem(item)">mdi-delete</v-icon>
                    <v-tooltip v-if="errors.indexOf(item)>=0">
                        <template v-slot:activator="{ontt}">
                            <v-icon class="ml-3" color="red" v-on="ontt">mdi-alert</v-icon>
                        </template>
                        <span>Saving Error!</span>
                    </v-tooltip>
                </v-container>
            </template>
        </v-data-table>
        <v-dialog v-model="deleteDialog" persistent max-width="40vw">
            <v-card class="rounded-0">
                <v-card-title>
                    <span class="headline">Delete</span>
                </v-card-title>

                <v-card-text>Are you sure you want to delete this record?</v-card-text>

                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn class="rounded-0" color="blue darken-1" variant="text" @click="deleteDialog=false">Cancel</v-btn>
                    <v-btn class="rounded-0" color="blue darken-1" variant="text" @click="deleteNow()">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <v-snackbar
            v-model="snackBar.show"
            top
            right
            :timeout="2000"
            :color="snackBar.color"
        >{{snackBar.text}}</v-snackbar>
    </v-container>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import Editor from '@tinymce/tinymce-vue';
import xlsx from "xlsx";

const props = defineProps({
    headers: Array,
    model: String,
    label: String,
    canAdd: { type: Boolean, default: true },
    canEdit: { type: Boolean, default: true },
    canDelete: { type: Boolean, default: true },
    canSearch: { type: Boolean, default: true },
    canFilter: { type: Boolean, default: false },
    canExport: { type: Boolean, default: false },
    canEmit: {type: Boolean, default: false},
    conditions: { type: Array, default: null },
    opts: {type: Object, default: function(){return {};}},
    showExpand: { type: Boolean, default: false },
    initialValues: { type: Object, default: function() { return {} }},
    itemsPerPage: {type: Number, default: 25},
    footerProps: {type: Object, default: function() {return {'itemsPerPageOptions': [25, 50, 100, -1]} }}
})

const emit = defineEmits(["data-loaded", "crud-update"])

const app = useNuxtApp()
const crudform = ref(null)
const editDialog = ref(false)
const deleteDialog = ref(false)
const formTitle = ref("New record")
const fieldsToMutate = ref({})
const itemBeingEdited = ref({})
const itemBeingDeleted = ref({})
const DataPG = ref({})
const loading = ref(false)
const errors = ref([])
const snackBar = ref({
    show: false,
    text: "",
    color: "success"
})
const oldHeaders = ref([])  //Used to track changes
const gridCondition = ref([])
const searchText = ref(null)
const searchCondition = ref(null)
const havingCondition = ref(null)
const filtersBox = ref(false)
const adjustVisible = ref([])
const editFormValid = ref(true)
const options = ref(props.opts) // Grid Options - changed when sorted or paginated

const ruleMandatory = (val) => {
    return !!val?.length || "This is a required field!"
}

const merge = (array1, array2, fieldKey) => {
    let map = []
    
    if(array1 instanceof Array) {
        array1.forEach(v => {
            map.push(v[fieldKey]);
        })
    }
    if(array2 instanceof Array) {
        array2.forEach(v => {
            if(map.indexOf(v[fieldKey]) < 0) array1.push(v)
        })
    }
    return array1
}
const prepareItems = (items) => {
    if(!items) return items
            
    let res = []
    items.forEach(v => {
        if(v instanceof Object) {
            res.push(v)
        }
        else res.push({ text: v, value: v })
    })
    
    return res;
}
const setConditions = (c) => {
    gridCondition.value = c
}
const toggleSearch = () => {
    if(searchCondition.value) {
        cancelSearch();
    }
    else {
        doSearch();
    }
}
const doSearch = () => {
    if(searchText.value == null) return
            
    let where = []
    let having = []
    props.headers.forEach(v => {
        if(v.searchable) {
            if(v.key.indexOf(".") == -1) where.push({[v.key]: { like: "%" + searchText.value + "%" }})
            else having.push({[v.key]: { like: "%" + searchText.value + "%" }})
        }
    })
           
    if(where.length > 1) searchCondition.value = [{ OR: where }]
    else searchCondition.value = where
    
    if(having.length > 1) havingCondition.value = [{ OR: having}]
    else havingCondition.value = having
    
    options.value.page = 1

    fetch()
}
const cancelSearch = () => {
    searchText.value = null
    searchCondition.value = null
    options.value.page = 1

    fetch()
}
const loadItems = (field) => {
    field["loading"] = true
    let vars = {}

    if(field?.search) {
        vars["opts"] = { page: 1, itemsPerPage: 20 }
        vars["wh"] = [{[field.modelFields.text]: {like: "%" + field.search + "%"}}]
    }
    else {
        vars["wh"] = []
        vars["opts"] = {}
    }
    app.$axios.post(app.$apiEndpoint, {
        table: field.model,
        operation: "select",
        fields: [field.modelFields.value, field.modelFields.text],
        where: vars["wh"],
        opts: vars["opts"]
    }).then(res => {
        //console.log(res);
        let arr = []
        if(field.items instanceof Array) {
            arr = merge(field.items, res.data.data, field.modelFields.value)
        }
        else arr = _copy(res.data.data)

        field["items"] = arr
    }).finally(() => {
        field["loading"] = false
    })
}
const fetch = () => {
    loading.value = true

    let fetchOptions = JSON.parse(JSON.stringify(options.value))

    // Look for subquery items in options
    if(fetchOptions?.sortBy?.length > 0) {
        fetchOptions.sortBy.forEach((field, index) => {
            if(field.indexOf(".") != -1) {
                // Find the model id in headers
                props.headers.forEach(h => {
                    if(h.key == field && h?.modelId) fetchOptions.sortBy[index] = h.modelId
                })
            }
        })
    }
    
    if(fetchOptions?.groupBy?.length > 0) {
        fetchOptions.groupBy.forEach((field, index) => {
            if(field.indexOf(".") != -1) {
                // Find the model id in headers
                props.headers.forEach(h => {
                    if(h.key == field && h?.modelId) fetchOptions.groupBy[index] = h.modelId
                })
            }
        })
    }
    
    app.$axios.post(app.$apiEndpoint, {
        table: props.model,
        operation: "select",
        fields: gqlFields.value,
        where: totalConditions.value,
        having: havingCondition.value,
        opts: fetchOptions
    }).then(res => {
        DataPG.value = res.data
    }).finally(() => {
        loading.value = false
        emit("data-loaded")
    })
}
const save = () => {
    Object.keys(fieldsToMutate.value).forEach(k => {
        if(fieldsToMutate.value[k] === undefined) fieldsToMutate.value[k] = null
    })

    const theItem = itemBeingEdited.value
    const inserting = Object.keys(theItem).length === 0

    let data = [];
    data.push(JSON.parse(JSON.stringify(fieldsToMutate.value)))

    loading.value = true
    app.$axios.post(app.$apiEndpoint, {
        'table': props.model,
        'operation': inserting ? "insert" : "update",
        'data': data
    }).then(res => {
        if(inserting) {
            if(res.data?.inserted?.length > 0) {
                snackBar.value.color = "success"
                snackBar.value.text = "Record successfully added."
                snackBar.value.show = true
            }
            else {
                snackBar.value.color = "error"
                snackBar.value.text = "Could not add record."
                snackBar.value.show = true
            }
        }
        else {
            _setObject(theItem, res.data?.data?.[0]); /*BUGS WHEN INITIALLY LOADED*/
            snackBar.value.color = "success"
            snackBar.value.text = "Record successfully updated."
            snackBar.value.show = true
        }
    }).catch(() => {
        if(inserting) {
            snackBar.value.color = "error"
            snackBar.value.text = "Error: record could not be added."
            snackBar.value.show = true
        }
        else {
            snackBar.value.color = "error"
            snackBar.value.text = "Error: record could not be updated."
            snackBar.value.show = true
            errors.value.push(theItem)
        }
    }).finally(() => {
        fetch()
        if(props.canEmit) emit("crud-update")
        loading.value = false
    })
    
    editDialog.value = false // ?? Possibly move into finally() ??
}
const deleteNow = () => {
    let theItem = itemBeingDeleted.value
    loading.value = true
    
    app.$axios.post(app.$apiEndpoint, {
        table: props.model,
        operation: "delete",
        fields: [],
        where: [{id: {eq: theItem.id}}]
    }).then(() => {
        fetch()
        snackBar.value.color = "success"
        snackBar.value.text = "Record deleted successfully."
        snackBar.value.show = true
    }).catch(() => {
        errors.value.push(theItem)
        snackBar.value.color = "error"
        snackBar.value.text = "Error: Record could not be deleted."
        snackBar.value.show = true
    }).finally(() => {
        loading.value = false
        if(props.canEmit) emit("crud-update")
    })
    // Possibly move into finally??
    deleteDialog.value = false
}
const editItem = (item) => {
    itemBeingEdited.value = item
    const pos = errors.value.indexOf(item)
    if (pos >= 0) errors.value.splice(pos, 1)
    editDialog.value = true
}
const deleteItem = (item) => {
    itemBeingDeleted.value = item
    deleteDialog.value = true
}
const _setObject = (subject, object) => {
    Object.keys(object).forEach(k => {
        if(k.substring(0, 2) != "__") subject[k] = object[k]
        //this.$set(subject,k,object[k]);
    })
}
const _copy = (varToCopy) => {
    let res
    if(varToCopy instanceof Array) res = _copyArray(varToCopy)
    else if (varToCopy instanceof Object) res = _copyObject(varToCopy)
    else res = varToCopy
    return res
}
const _copyObject = (obj) => {
    let res = {}
    Object.keys(obj).forEach(k => {
        if(obj[k] instanceof Array) res[k] = _copyArray(obj[k])
        else if(obj[k] instanceof Object) res[k] = _copyObject(obj[k])
        else if(k.substring(0, 2) != "__") res[k] = obj[k]
    })
    return res
}
const _copyArray = (arr) => {
    let res = []
    arr.forEach((v, k) => {
        if(v instanceof Array) res[k] = _copyArray(v)
        else if (v instanceof Object) res[k] = _copyObject(v)
        else res[k] = v
    })
    return res
}
const getValue = (obj, index) => {
    if(obj instanceof Object) {
        let parts = index.split(".")
        let res = obj
        parts.forEach(v => {
            if(res instanceof Object) res = res[v]
            else res = null
        })
        return res
    }
    else return null
}
// Possibly needs else? 
const searchVal = (arr, index, value, return_index) => {
    if(arr instanceof Array) {
        let res = {}
        arr.forEach(v => {
            if(v[index] == value) res = v
        })
        return res[return_index]
    }
}
const exportXLSX = () => {
    app.$axios.post(app.$apiEndpoint, {
        table: props.model,
        operation: "select",
        fields: exportableFields.value,
        where: totalConditions.value
    }).then((res) => {
        let h = [];
        props.headers.forEach(v => {
            if(v.query !== false && v.visible !== false && v.title) h.push(v.title)
        })

        let workbook = xlsx.utils.book_new()
        let worksheet = xlsx.utils.aoa_to_sheet([h])
        xlsx.utils.sheet_add_json(worksheet, res.data.data, {skipHeader: true, origin: -1})
        xlsx.utils.book_append_sheet(workbook, worksheet, "data")
        xlsx.writeFile(workbook, "export.xlsx")
    })
}

watch(() => props.conditions, (n) => {
    if(n) gridCondition.value = n
}, {immediate: true, deep: true})
watch(() => gridCondition, () => {
    fetch()
}, {deep: true})
watch(searchText, (n) => {
    if(!n) cancelSearch()
})
watch(options, () => {
    fetch()
}, {deep: true})
watch(adjustVisible, (n) => {
    props.headers.forEach(v => {
        v["visible"] = false
    })
    n.forEach(v => {
        v["visible"] = true
    })
})
watch(editDialog, (n) => {
    if(n) {
        // Edit form opened
        fieldsToMutate.value = {}
        if(Object.keys(itemBeingEdited.value).length === 0) {
            // New Record
            let hasMandatory = false;
            formTitle.value = "New record"
            props.headers.forEach(v => {
                if((v.editable !== false || props.initialValues[v.key] !== undefined) && v.query !== false) {
                    if (v.mandatory) hasMandatory = true
                    if((v.type == "select" || v.type == "radio") && !v.items) {
                        loadItems(v)
                    }
                    if(v.modelId) {
                        fieldsToMutate.value[v.modelId] = props.initialValues[v.key] !== undefined ? props.initialValues[v.key] : undefined
                    }
                    else {
                        fieldsToMutate.value[v.key] = props.initialValues[v.key] !== undefined ? props.initialValues[v.key] : undefined
                    }
                }
            })
            if(hasMandatory) editFormValid.value = false
            //if(crudform)
                crudform.value.resetValidation()
        }
        else {
            // Edit Record
            let hasEmptyMandatory = false
            formTitle.value = "Edit"
            props.headers.forEach(v => {
                if((v.editable !== false || v.isId || props.initialValues[v.key] !== undefined) && v.query !== false) {
                    if(v.mandatory && getValue(itemBeingEdited.value, v.key) == null)
                        hasEmptyMandatory = true
                    if((v.type == "select" || v.type == "radio") && !v.items) {
                        loadItems(v)
                    }
                    if(v.modelId) {
                        if(v.items instanceof Array) {
                            let newItem = [];
                            let vl = getValue(itemBeingEdited.value, v.key)
                            console.log("vl="+vl)
                            if((vl !== undefined) && (vl !== null)) {
                                newItem = [{[v.modelFields.value]: itemBeingEdited.value[v.modelId], [v.modelFields.text]: vl}]
                                console.log(newItem)
                            }
                            let merged = merge(v.items, newItem, v.modelFields.value)

                            v["items"] = merged
                        }
                        else {
                            let newItem = [];
                            let vl = getValue(itemBeingEdited.value, v.key)
                            if((vl !== undefined) && (vl !== null)) {
                                newItem = [{[v.modelFields.value]: itemBeingEdited.value[v.modelId], [v.modelFields.text]: getValue(itemBeingEdited.value, v.key)}]
                            }
                            v["items"] = newItem
                        }
                        fieldsToMutate.value[v.modelId] = itemBeingEdited.value[v.modelId]
                    }
                    else {
                        fieldsToMutate.value[v.key] = itemBeingEdited.value[v.key]
                    }
                }
            })
            if(hasEmptyMandatory) editFormValid.value = false
                //if(crudform)
                    crudform.value.resetValidation()
        }
    }
    else {
        //Edit form closed
        itemBeingEdited.value = {}
        editFormValid.value = true
    }
})
watch(props.headers, (n) => {
    //this.adjustVisible = [];
    n.forEach((v, k) => {
        if(oldHeaders.value[k] && oldHeaders.value[k].search != v.search) {
            if(v.search && v.search.length >= 2 && v.search != getValue(itemBeingEdited.value, v.key) && v.search != searchVal(v.items, v.modelFields.value, fieldsToMutate.value[v.modelId], v.modelFields.text)) {
                loadItems(v)
            }
        }
    })

    oldHeaders.value = _copy(props.headers)
}, {deep: true})

        
const slotFields = computed(() => {
    let res = []
    props.headers.forEach(val => {
        if(val.slot === true) res.push("item." + val.key)
    })
    return res
})
const editableFields = computed(() => {
    return props.headers.filter(val => val.editable !== false && val.query !== false)
})
const gqlFields = computed(() => {
    let flds = [];
            
    props.headers.forEach(v => {
        if(v.query !== false) {
            if(v.key.indexOf(".") >= 0) {
                let parts = v.key.split(".")
                let subq = {'operation': 'select', 'table': parts[0], fields:[parts[1]], where: [{[parts[0]+".id"]:{"eq":v.modelId}}]}
                        
                flds.push(subq)
            }
            else flds.push(v.key)
            if (v.modelId) flds.push(v.modelId);
        }
    })
    return flds
})
const visibleFields = computed(() => {
    let h = [];
    props.headers.forEach(v => {
        if(v.visible !== false) {
            h.push(v)
        }
    })
    if(props.canEdit || props.canDelete)
        h.push({ title: "Actions", key: "actions", editable: false })
    return h
})
const exportableFields = computed(() => {
    let flds = [];
    props.headers.forEach(v => {
        if(v.visible !== false && v.query !== false) {
            if(v.key.indexOf(".") >= 0) {
                let parts = v.key.split(".");
                flds.push(`${parts[0]} { ${parts[1]} }`)
            }
            else flds.push(v.key)
            if(v.modelId) flds.push(v.modelId)
        }
    })
    return flds
})
const totalConditions = computed(() => {
    if(gridCondition.value instanceof Array) {
        if(searchCondition.value instanceof Array) {
            return gridCondition.value.concat(searchCondition.value)
        }
        else return gridCondition.value
    }
    else return searchCondition.value instanceof Array ? searchCondition.value : []
})

onMounted(() => {
    oldHeaders.value = _copy(props.headers)
    props.headers.forEach(v => {
        if(v.visible !== false) adjustVisible.value.push(v)
    })

    fetch()
})

defineExpose({
    DataPG, gridCondition, totalConditions, setConditions
})
/* 
        CRUD PROPERTIES 

            label (mandatory) - Sets GRIDs Label
            model (mandatory) - Defines GraphQL Model's name
            headers (array of objects) Described Below
            conditions (array of objects) "[{ deleted: { is: null } }]" - sets incomming conditions for the CRUD   

            can-add (default true) - CRUD can add record
            can-edit (default true) - CRUD can edit record
            can-delete (default true) - CRUD can delete record
            can-search (default true) - CRUD can search records
            can-filter (default false) - CRUD can filter records
            can-export (default false) - CRUD can export records to Excel

        HEADER PROPERTIES
        
            title (string : mandatory) - GRID Column's Label and Edit Form field's Label
            key (string : mandatory) - GraphQL query field name or object.fieldName as well as slot identifier(item.[value]).
            query (bool : default true) - Determines if that field will be used in GraphQL Query or not. Usefull for slotted columns.        
            slot (bool : default false) - Determines if that siled can be slotted. If true you can use named slot (item.[value])
            
            isId (bool : default false) - used to identify which fields id the ID
            editable (bool : default true) - If the field is editable or not
            visible (bool : default true) - If the field is visible in the GRID
            searchable (bool : default false) - If the field participate in search queries
            mandatory (bool : default false) - if the field is mandatory
            mask (string : default null) - applies mask to the input field (currently used for text fields only):
                '#': {pattern: /\d/},
                'X': {pattern: /[0-9a-zA-Z]/},
                'S': {pattern: /[a-zA-Z]/},
                'A': {pattern: /[a-zA-Z]/, transform: v => v.toLocaleUpperCase()},
                'a': {pattern: /[a-zA-Z]/, transform: v => v.toLocaleLowerCase()},
                '!': {escape: true}

            fieldOptions  (object) - pass option properties to the editable field
            subfieldOptions (object) - pass option properties to the editable subfield. currently used for radios only

            type (enum : default text) - Set the field type. Can be one of [select, checkboxlist, date,  time, switch, radio, text, password, textarea, editor]
            items (array) When type is select you can define the items source for those fields 
            const: 

            model (string) - Sets the name of of the model used for data sorce for that field. Applicable for type select.
            modelId (string : mandatory when model is set) - this is the name of the query field used for reference. Usually an ID of the remote table. 
                When used the value property represents a subquery to a meaningfull column in referenced table. 
            modelFields (object : mandatory when model is set) - this is the model's value and text fields used for field's datasource.
            menu (object) - pass v-model for menu/dialog (used in time picker)

        SLOTS 

            v-slot:filters="{conditions, setConditions}"
            v-slot:item.[value]="{item, header, value}"

        */
</script>
<style scoped>
.primary {
    background: linear-gradient(to right,#32cd32 0,#006c00 100%) !important;
}
</style>