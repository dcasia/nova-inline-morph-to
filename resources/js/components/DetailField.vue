<template>

    <div>

        <panel :panel="{ name: label, fields: standardFields }"/>

        <template v-for="({type, fields}) of relationalFields">

            <resource-index v-for="(relationField, index) of fields"
                            :key="index"
                            :field="relationField"
                            :resource-name="relationField.resourceName"
                            :via-resource="relationField.inlineMorphTo.viaResource"
                            :via-resource-id="relationField.inlineMorphTo.viaResourceId"
                            :via-relationship="relationField[`${type}Relationship`]"
                            :relationship-type="type"
                            :load-cards="false"
                            class="mb-6"/>

        </template>

    </div>

</template>

<script>

    import ReplaceValueWithLabel from '../ReplaceValueWithLabel'

    export default {
        props: [ 'resource', 'resourceName', 'resourceId', 'field' ],
        mixins: [ ReplaceValueWithLabel ],
        computed: {
            relationalFields() {

                return [
                    {
                        type: 'hasOne',
                        fields: this.fields.filter(field => field.component === 'has-one-field')
                    },
                    {
                        type: 'hasMany',
                        fields: this.fields.filter(field => field.component === 'has-many-field')
                    },
                    {
                        type: 'belongsToMany',
                        fields: this.fields.filter(field => field.component === 'belongs-to-many-field')
                    }
                ]

            },
            standardFields() {

                return this.fields.filter(field => ![ 'has-one-field', 'has-many-field', 'belongs-to-many-field' ].includes(field.component))

            },
            fields() {

                return this.field.resources.find(resource => resource.className === this.originalValue).fields

            }
        }
    }

</script>
